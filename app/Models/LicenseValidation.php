<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseValidation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function certificate()
    {
        return $this->belongsTo(ActivationCertificate::class, 'activation_certificate_id');
    }
}
