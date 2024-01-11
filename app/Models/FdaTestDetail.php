<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FdaTestDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'test_details_from_fda';
}
