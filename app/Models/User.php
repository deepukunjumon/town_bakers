<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Ramsey\Uuid\Guid\Guid;
use Illuminate\Support\Str;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        $this->is_admin === 1 ? $role = 'admin' : $role = 'branch';
        if ($role == 'admin') {
            return [
                'role' => $role,
                'branch_id' => null
            ];
        }
        if ($role == 'branch') {
            return [
                'role' => $role,
                'branch_id' => $this->branch_id
            ];
        }
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected $fillable = [
        'username',
        'password',
        'is_admin',
        'branch_id',
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getIdAttribute($value)
    {
        return (string) $value;
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            if (!$user->getKey()) {
                $user->id = (string) Str::uuid();
            }
        });
    }
}
