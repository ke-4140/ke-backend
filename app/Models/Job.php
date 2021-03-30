<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model {
    
    use HasFactory;

    protected $fillable = [
        'owner',
        'src',
        'status',
        'attributes',
    ];

    // ------------------------------------------------

    public static function getJob($uuid, $src) {
        $job = self::where('owner', $uuid)
            ->where('src', $src)
            ->first();

        return $job;
    }

}
