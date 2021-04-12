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
        ]);

        $errors = $validator->errors();
        if (count($errors) > 0) {
            $response['status'] = -80;
            $response['message'] = "Input Validation Failed";
            $response['data'] = $errors;
            return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $name = $request->name;

        $outputRecord = Output::getRecordByName($name);
        if (empty($outputRecord)) {
            $response['status'] = -10;
            $response['message'] = "Unauthroized Access";
            return response()->json($response, Response::UNAUTHORIZED);
        }

        $imageName = $outputRecord->img_addr;
        $s3Path = Storage::disk('s3')->temporaryUrl($imageName, Carbon::now()->addMinutes(5));

        return redirect()->away($s3Path);
    }

    public function getFrame(Request $request) {

        $response = [
            "timestamp" => Date("YmdHis"),
			"api" => __FUNCTION__,
			"status" => -99,
			"message" => "Unexpected error"
        ];

        $validator = Validator::make($request->all(), [
            'src' => 'required|min:3',
            'frame_no' => 'required|integer',
        ]);

        $errors = $validator->errors();
        if (count($errors) > 0) {
            $response['status'] = -80;
            $response['message'] = "Input Validation Failed";
            $response['data'] = $errors;
            return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $src = $request->src;
        $frame_no = $request->frame_no;

        $command = escapeshellcmd("python3 ./python/getFrame.py " .$src ." " .$frame_no);
        $result = null;
        exec("cd ../ && bash -c \"$command\"", $result, $code);

        if ($code != 0) {
            $response['status'] = -50;
            $response['message'] = "Error extracting frame";
            $response['data']['error_code'] = $code;
            return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = base64_decode($result[0]);
        return response($result)->header('Content-type', 'image/jpg');
    }
}
