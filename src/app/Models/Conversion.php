<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversion extends Model
{
    protected $fillable = [
        'input_file',
        'output_file',
        'status',
        'error'
    ];
}
