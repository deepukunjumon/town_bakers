<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Branch extends Authenticatable implements JWTSubject
{
    use HasFactory;

    public function users()
    {
        return $this->hasMany(User::class);
    }

    protected $fillable = [
        'id',
        'code',
        'name',
        'address',
        'mobile',
        'email',
        'phone',
        'status'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'branch_id' => $this->id,
            'code' => $this->code
        ];
    }
}
