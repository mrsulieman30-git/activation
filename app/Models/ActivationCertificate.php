<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivationCertificate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'certificate_data' => 'array',
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function activationRequest()
    {
        return $this->belongsTo(ActivationRequest::class);
    }

    public function serialKey()
    {
        return $this->belongsTo(SerialKey::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function validations()
    {
        return $this->hasMany(LicenseValidation::class);
    }
}
