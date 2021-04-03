<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use App\Models\Job;
use App\Models\Output;

use Carbon\Carbon;

class ImageController extends Controller {
    
    public function getImage(Request $request) {

        $response = [
            "timestamp" => Date("YmdHis"),
			"api" => __FUNCTION__,
			"status" => -99,
			"message" => "Unexpected error"
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'uuid' => 'required|min:3',
            'src' => 'required|min:3',
        ]);

        $errors = $validator->errors();
        if (count($errors) > 0) {
            $response['status'] = -80;
            $response['message'] = "Input Validation Failed";
            $response['data'] = $errors;
            return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $uuid = $request->uuid;
        $name = $request->name;
        $src = $request->src;

        $job = Job::getJob($uuid, $src);
        if (empty($job)) {
            $response['status'] = -10;
            $response['message'] = "Job not found";
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $jobID = $job->id;
        $outputRecord = Output::getRecord($jobID, $name);
        if (empty($outputRecord)) {
            $response['status'] = -10;
            $response['message'] = "Unauthroized Access";
            return response()->json($response, Response::UNAUTHORIZED);
        }

        $imageName = $outputRecord->img_addr;
        $s3Path = Storage::disk('s3')->temporaryUrl($imageName, Carbon::now()->addMinutes(5));

        return redirect()->away($s3Path);
    }
}
