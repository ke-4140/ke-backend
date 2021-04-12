<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Output extends Model {

    use HasFactory;

    protected $fillable = [
        'job_id',
        'vid_time',
        'frame_no',
        'ssim',
        'img_addr',
        'read_at'
    ];

    // -------------------------------------------------------------------------------

    public static function getUnreadOutput($jobID) {

        $outputs = self::where('job_id', $jobID)
            ->whereNull('read_at')
            ->get();

        foreach($outputs as $output) {
            $output['img_addr'] = config('app.url') ."api/image?name=" .$output['img_addr'];
        }

        self::where('job_id', $jobID)
            ->whereNull('read_at')
            ->update([
                'read_at' => DB::raw('NOW()'),
            ]);

        return $outputs;
    }

    // -------------------------------------------------------------------------------

    public static function getRecord($jobID, $image_name){

        $output = self::where('job_id', $jobID)
            ->where('img_addr', $image_name)
            ->first();

        return $output;
    }

    // -------------------------------------------------------------------------------

    public static function getRecordByName($image_name){

        $output = self::where('img_addr', $image_name)
            ->first();

        return $output;
    }
}
