<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use App\Models\Job;
use App\Models\Output;

class IndexController extends Controller {
    
    public function newJob(Request $request) {

        $response = [
            "timestamp" => Date("YmdHis"),
			"api" => __FUNCTION__,
			"status" => -99,
			"message" => "Unexpected error"
        ];

        $validator = Validator::make($request->all(), [
            'src' => 'required|min:3',
        ]);

        $errors = $validator->errors();
        if (count($errors) > 0) {
            $response['status'] = -80;
            $response['message'] = "Input Validation Failed";
            $response['data'] = $errors;
            return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $uuid = Str::uuid();

        $job = Job::create([
            'owner' => $uuid,
            'src' => $request->src,
            'status' => 'queued',
            'attributes' => '{}',
        ]);

        // call python script here
        $command = "python3 ./python/main.py ".$uuid ." " .$request->src ." > /dev/null 2>&1 &";
        exec("cd ../ && bash -c \"$command\"");
        
        $response['status'] = 0;
        $response['message'] = "Job Created";
        $response['data'] = $job;
        return response()->json($response, Response::HTTP_CREATED);
    }

    // -------------------------------------------------------------------------------

    public function getJob(Request $request) {

        $response = [
            "timestamp" => Date("YmdHis"),
			"api" => __FUNCTION__,
			"status" => -99,
			"message" => "Unexpected error"
        ];

        $validator = Validator::make($request->all(), [
            'src' => 'required|min:3',
            'uuid' => 'required|min:3'
        ]);

        $errors = $validator->errors();
        if (count($errors) > 0) {
            $response['status'] = -80;
            $response['message'] = "Input Validation Failed";
            $response['data'] = $errors;
            return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $uuid = $request->uuid;
        $src = $request->src;

        $job = Job::getJob($uuid, $src);
        if (empty($job)) {
            $response['status'] = -10;
            $response['message'] = "Job not found";
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $jobID = $job->id;
        $outputs = Output::getUnreadOutput($jobID);

        $response['status'] = 0;
        $response['message'] = "OK";
        $response['data']['job'] = $job;
        $response['data']['outputs'] = $outputs;
        return response()->json($response);
    }
}
