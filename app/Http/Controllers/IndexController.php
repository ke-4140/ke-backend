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
        if (config('app.debug') == true) {
            $response['command'] = $command;
        }
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

    // -------------------------------------------------------------------------------

    public function getTranscript(Request $request) {

        $response = [
            "timestamp" => Date("YmdHis"),
			"api" => __FUNCTION__,
			"status" => -99,
			"message" => "Unexpected error"
        ];

        $validator = Validator::make($request->all(), [
            'src' => 'required|min:11',
            'timestamps' => 'json',
        ]);

        $errors = $validator->errors();
        if (count($errors) > 0) {
            $response['status'] = -80;
            $response['message'] = "Input Validation Failed";
            $response['data'] = $errors;
            return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $src = $request->src;

        $command = escapeshellcmd("python3 ./python/getTranscript.py " .$src);
        $result = null;
        exec("cd ../ && bash -c \"$command\"", $result, $code);

        $transcriptArray = json_decode($result[0], true);

        if ($request->has('timestamps')) {

            $timestamps_json = $request->timestamps;
            $timestamps = json_decode($timestamps_json, true);
            $timestamps[] = PHP_INT_MAX;

            $newTranscript = array();
            $item = [];
            $item['start'] = $timestamps[0];
            $item['text'] = '';
            $item['duration'] = 0;
            $timestampIndex = 0;

            // group transcripts together
            foreach ($transcriptArray as $rawItem) {

                // repeat until item start is in range
                while (!($rawItem['start'] >= $timestamps[$timestampIndex]
                 && $rawItem['start'] <= $timestamps[$timestampIndex + 1])) {

                    $newTranscript[] = $item;
                    $timestampIndex++;
                    $item['start'] = $timestamps[$timestampIndex];
                    $item['text'] = '';
                    $item['duration'] = 0;
                    if ($timestampIndex >= count($timestamps)) { break; }
                }
                
                $item['text'] .= $rawItem['text'] ." ";
                $item['duration'] += $rawItem['duration'];
                if ($timestampIndex >= count($timestamps)) { break; }
            }
            // push the last one as well
            $newTranscript[] = $item;
            // replace the output
            $transcriptArray = $newTranscript;
        }

        $response['status'] = 0;
        $response['message'] = "OK";
        $response['data'] = $transcriptArray;
        return response()->json($response);
    }
}
