<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTestClassSection extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['order_id','section_id','test','test_class'];
}
