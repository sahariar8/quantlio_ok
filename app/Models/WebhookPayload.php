<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookPayload extends Model
{
    use HasFactory;
    protected $casts = [
        'payload' => 'array',
    ];
    protected $fillable = ['url','payload','order_code','icd_codes','prescribed_meds'];
}
