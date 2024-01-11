<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lis_orders_details extends Model
{
    use HasFactory;
    protected $table = 'lis_orders_details';
    protected $fillable = array('order_code','in_house_lab_locations','patient_name','patient_dob','patient_gender','patient_phone_number','account_name','provider_first_name','provider_last_name','accession_number','clia_sample_type','sample_collection_date','received_date','icd_codes','medication_uuids','test_panel_type','provider_npi','prescribed_medications','drug_drug_interactions','contraindicated_conditions','boxed_warnings','account_location','state','reported_date');
    protected $casts =  [
    	'icd_codes' => 'array',
    	'medication_uuids' => 'array',
    	'test_panel_type' => 'array'
	];
}
