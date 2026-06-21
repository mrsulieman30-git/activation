<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivationRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function serialKey()
    {
        return $this->belongsTo(SerialKey::class);
    }

    public function certificate()
    {
        return $this->hasOne(ActivationCertificate::class);
    }
}
