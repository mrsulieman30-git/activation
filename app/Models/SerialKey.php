<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerialKey extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($serialKey) {
            if (empty($serialKey->key_value)) {
                $serialKey->key_value = collect(range(1, 4))
                    ->map(fn() => strtoupper(\Illuminate\Support\Str::random(4)))
                    ->join('-');
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function activationRequests()
    {
        return $this->hasMany(ActivationRequest::class);
    }
}
