<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditLogs;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasAuditLogs;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        if (in_array($this->role, [ROLES['super_admin'], ROLES['admin']])) {
            return [
                'name' => $this->name,
                'role' => $this->role,
                'branch_id' => null
            ];
        }
        if ($this->role == 'branch') {
            return [
                'name' => $this->name,
                'role' => $this->role,
                'branch_id' => $this->branch_id
            ];
        }

        return [
            'name' => $this->name,
            'role' => $this->role,
            'branch_id' => $this->branch_id ?? null
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected $fillable = [
        'username',
        'name',
        'mobile',
        'email',
        'password',
        'role',
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
