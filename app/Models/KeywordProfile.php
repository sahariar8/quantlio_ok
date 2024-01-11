<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeywordProfile extends Model
{
    use HasFactory;
    public $timestamps = false; //by default timestamp true
    // public $incrementing = false;
    
    protected $table = 'keyword_profiles';
    protected $fillable = array('keyword_id, profile_id');
}
