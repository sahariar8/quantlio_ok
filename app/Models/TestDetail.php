<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestDetail extends Model
{
    use HasFactory;
    protected $table = 'test_details';
    protected $fillable = array('dendi_test_name', 'class','description','LLOQ','ULOQ');

}
