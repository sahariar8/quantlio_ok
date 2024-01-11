<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabLocation extends Model
{
    use HasFactory;
    protected $table = 'lab_locations';
    protected $fillable = array('location','address','director','CLIA','phone','fax','website');
}
