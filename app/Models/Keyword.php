<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    use HasFactory;
    protected $table = 'keywords';
    protected $fillable = array('profile_id', 'primary_keyword','secondary_keyword','resultant_keyword');
    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }
}
