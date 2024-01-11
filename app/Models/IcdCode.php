<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IcdCode extends Model
{
    use HasFactory;
    protected $fillable = ['icd','description','mesh'];
}
