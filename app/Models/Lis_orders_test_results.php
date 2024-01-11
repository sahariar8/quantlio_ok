<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lis_orders_test_results extends Model
{
    use HasFactory;
    protected $table = 'lis_orders_test_results';
    protected $fillable = array('order_code','test_name', 'result_quantitative', 'result_qualitative');
}
