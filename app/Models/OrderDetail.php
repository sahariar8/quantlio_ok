<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
protected $fillable = ['order_code','icd_codes','account_name','account_location','provider_name','provider_npi','in_house_lab_location','patient_DOB','prescribed_medications','drug_drug_interactions','contraindicated_conditions','boxed_warnings'];
}
