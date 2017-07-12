<?php

namespace App\Http\Controllers\Api;

use Response;
use Transaction;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use League\Fractal\Resource\Collection;
use App\Transformers\TransactionTransformer;
use League\Fractal\Serializer\JsonApiSerializer;
use App\Contracts\TransactionRepositoryInterface;

class TransactionsController extends Controller
{
    protected $request;

    protected $fractal;

    protected $events;

    public function __construct(
        Request $request,
        Manager $fractal,
        TransactionRepositoryInterface $transactions
    ) {
        $this->request      = $request;
        $this->fractal      = $fractal;
        $this->transactions = $transactions;

        $this->fractal->setSerializer(new JsonApiSerializer);
    }

    public function listing()
    {
        //Grab requested page
        $page = (int) $this->request->get('page') ?: 1;

        //Fetch filters from request
        $filters = $this->request->get('filter', []);

        //Fetch paginated data
        $transactions = app(TransactionRepositoryInterface::class)->paginate($page, 20, $filters);

        //Enable includes
        $this->fractal->parseIncludes('user,account');

        //Build fractal item
        $collection = new Collection($transactions, new TransactionTransformer, 'transactions');

        //Build response
        $response = $this->fractal->createData($collection)->toArray();

        //Add meta data
        $response['meta'] = [
            'total' => $transactions->total(),
            'page'  => $page
        ];

        //Grab current URL
        $url = urldecode($this->request->fullUrl());

        //Generate next page link
        $nextPageLink = null;
        if ($transactions->hasMorePages()) {
            if (str_contains($url, 'page=')) {
                $nextPageLink = str_replace('page='.$page, 'page='.($page + 1), $url);
            } elseif (str_contains($url, '?')) {
                $nextPageLink = $url.'&page='.($page + 1);
            } else {
                $nextPageLink = $url.'?page='.($page + 1);
            }
        }

        //Add links
        $response['links'] = [
            'prev' => (1 < $page) ? str_replace('page='.$page, 'page='.($page - 1), $url) : null,
            'next' => $nextPageLink,
        ];

        //Return success response
        return Response::json($response);
    }

    public function undo($id)
    {
        //Open a transaction for this undo
        Transaction::open('undo', 'generic.undo');

        //Undo the transaction
        $transaction = $this->transactions->undo($id);

        //Build fractal item
        $item = new Item($transaction, new TransactionTransformer, 'transactions');

        //Build response
        $response = $this->fractal->createData($item)->toArray();

        //Return success response
        return Response::json($response);
    }
}
