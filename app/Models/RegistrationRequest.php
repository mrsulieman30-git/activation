<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationRequest extends Model
{
    protected $fillable = [
        'clinic_name',
        'contact_name',
        'email',
        'phone',
        'device_fingerprint',
        'device_name',
        'status',
        'serial_key_id',
        'reviewed_by',
        'reviewed_at'
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function serialKey()
    {
        return $this->belongsTo(SerialKey::class);
    }
    
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
