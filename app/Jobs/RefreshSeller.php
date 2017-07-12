<?php

namespace App\Jobs;

use App\Models;
use App\Contracts;
use App\Repositories;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefreshSeller extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $seller;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Models\Seller $seller)
    {
        $this->seller = $seller;
        $this->onQueue('submissions');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Repositories\Eloquent\SellerRepository $sellerRepo)
    {
        $sellerRepo->refresh($this->seller);
    }
}
