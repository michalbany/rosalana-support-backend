<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Http\Filters\ApiFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'rosalana_account_id',
        'is_admin'
    ];

    protected $casts = [
        'is_admin' => 'boolean'
    ];

    protected $hidden = [
        'rosalana_account_id',
    ];

    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    public function docs()
    {
        return $this->hasMany(Doc::class);
    }

    public function threads()
    {
        return $this->hasMany(Thread::class);
    }

    public function scopeFilter(Builder $builder, ApiFilter $filters): Builder
    {
        return $filters->apply($builder);
    }

}
