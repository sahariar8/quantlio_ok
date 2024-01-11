<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    protected $table = 'comments';
    protected $fillable = array('test_id', 'comment');

    public function test()
    {
        return $this->belongsTo(TestDetail::class, 'test_id','id');
    }
}
