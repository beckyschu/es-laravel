<?php

namespace App\Http\Controllers\Api;

use App;
use Response;
use App\Models\Submission;
use App\Mail\SubmissionNotification;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;

class SubmissionsController extends Controller
{
    public function create()
    {
        // Create the submission
        $submission = new Submission(request()->all());

        // Save the submission
        $submission->save();

        // Send email with submission contents
        Mail::to('operations@ipshark.com')->send(new SubmissionNotification($submission));

        // Return an empty 201 response
        return Response::make(null, 201);
    }
}
