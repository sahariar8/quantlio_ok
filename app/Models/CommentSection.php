<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentSection extends Model
{
    use HasFactory;
    public $timestamps = false; 
    protected $table = 'comments_has_sections';
    protected $fillable = array('comment_id, section_id');
}
