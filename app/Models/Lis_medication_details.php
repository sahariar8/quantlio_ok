<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lis_medication_details extends Model
{
    use HasFactory;
    protected $table = 'lis_medication_details';
    protected $fillable = array('order_code','medication_uuids','medication_name','metabolite_id','metabolite_name');
}
