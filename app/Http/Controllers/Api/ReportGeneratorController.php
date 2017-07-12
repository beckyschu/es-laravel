<?php

namespace App\Http\Controllers\Api;

use Auth;
use Ramsey\Uuid\Uuid;
use App\Models\Report;
use Illuminate\Http\Request;
use JonnyW\PhantomJs\Client;
use App\Http\Controllers\Controller;

class ReportGeneratorController extends Controller
{
    public function saveReport($id = null)
    {
        // Store report
        $report = $this->storeReport($id ? Report::findOrFail($id) : null, request());

        // Return report data
        return $this->getReport($report->id);
    }

    public function generatePdf($id = null)
    {
        // Store report
        $report = $this->storeReport($id ? Report::findOrFail($id) : null, request());

        // Already has a PDF, return that
        if (app('filesystem')->disk('public')->exists('reports/'.$report->id.'.pdf')) {
            return response()->json([
                'data' => [
                    'id'  => $report->id,
                    'pdf' => url('storage/reports/'.$report->id.'.pdf')
                ]
            ]);
        }

        // @todo Move this out into a service

        // Initiate Guzzle client
        $client = new \GuzzleHttp\Client;

        // Send request to PDF Layer
        $response = $client->get('http://api.pdflayer.com/api/convert', [
            'query' => [
                'access_key'    => config('services.pdflayer.key'),
//                'document_url'  => 'http://67635437.ngrok.io/api/reports/'.$report->id.'/render',
                'document_url'  => url('api/reports/'.$report->id.'/render'),
                'document_name' => $report->id.'.pdf',
                'margin_top'    => 0,
                'margin_bottom' => 0,
                'margin_left'   => 0,
                'margin_right'  => 0,
                'creator'       => 'IP Shark',
                'force'         => 1,
                'test'          => 'production' == config('app.env') ? 0 : 1
            ]
        ]);

        // Store PDF
        app('filesystem')->disk('public')->put(
            'reports/'.$report->id.'.pdf',
            (string) $response->getBody()
        );

        // Return response
        return response()->json([
            'data' => [
                'id'  => $report->id,
                'pdf' => url('storage/reports/'.$report->id.'.pdf')
            ]
        ]);
    }

    public function renderPdf($id)
    {
        // Fetch report by ID
        $report = Report::find($id);

        // Return view
        return view('reports/pdf', [
            'report'     => $report,
            'reportData' => json_decode($report->report)
        ]);
    }

    public function getReport($id)
    {
        // Return response
        return response()->json([
            'data' => Report::find($id)->toArray(),
        ]);
    }

    public function getReports()
    {
        // Begin reports query
        $query = Report::query()->orderBy('created_at', 'DESC');

        // Only fetch this account if selected
        if ($account = Auth::getAccount()) {
            $query = $query->where('account_id', $account->id);
        }

        // Return reports collection
        return response()->json([
            'data' => $query->get()
        ]);
    }

    protected function storeReport(Report $report = null, Request $request)
    {
        // No report yet
        if (! $report) {
            $currentSequence = Report::where('account_id', Auth::getAccountId())->max('sequence');

            $report = new Report([
                'id'         => Uuid::uuid4(),
                'account_id' => Auth::getAccountId(),
                'sequence'   => $currentSequence ? $currentSequence + 1 : 1
            ]);
        }

        // Fill report entity
        $report->fill([
            'report'              => json_encode($request->input('report')),
            'date_range'          => (string) $request->input('report')['details']['range'],
            'discovered_listings' => (int) $request->input('report')['summary']['discovered'],
            'pending_listings'    => (int) $request->input('report')['summary']['pending'],
            'closed_listings'     => (int) $request->input('report')['summary']['closed'],
        ]);

        // Report contents have changed, clear cached PDF
        if ($report->isDirty('report')) {
            app('filesystem')->disk('public')->delete('reports/'.$report->id.'.pdf');
        }

        // Save report updates
        $report->save();

        // Return report entity
        return $report;
    }

    public function uploadLogo()
    {
        // Store uploaded file
        $path = request()->file('file')->store('logos', 'public');

        // Return response with path
        return response()->json([
            'data' => [
                'path' => '/storage/'.$path
            ]
        ]);
    }

    public function deleteReports()
    {
        // Fetch report ID's from request
        $reportIds = request()->get('reports');

        // No report ID's provided, bail out
        if (! $reportIds) return response('');

        // Loop reports and delete them
        foreach ($reportIds as $id) {

            // Fetch and delete report
            if ($report = Report::find($id)) {
                $report->delete();
            }

        }

        // Return empty success response
        return response('');
    }

    public function downloadReports()
    {
        // Fetch report ID's from request
        $reportIds = request()->get('reports');

        // No report ID's provided, bail out
        if (! $reportIds) return response()->json([ 'data' => null ]);

        // Get reports collection
        $reports = Report::whereIn('id', $reportIds)->get();

        // Map only PDF attribute
        $reports = $reports->map(function ($report) {
            return [
                'name' => 'Report '.$report->sequence.' for '.$report->account->name,
                'path' => $report->pdf
            ];
        });

        // Return empty success response
        return response()->json([
            'data' => $reports
        ]);
    }
}
