<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugInteraction extends Model
{
    use HasFactory;
    protected $table = 'drug_interactions';
    public $timestamps = false;
}
