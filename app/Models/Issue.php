<?php

namespace App\Models;

use App\Http\Filters\ApiFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $fillable = [
        'app_id',
        'user_id',
        'title',
        'content',
        'visibility',
        'status',
    ];

    public function app()
    {
        return $this->belongsTo(App::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function threads()
    {
        return $this->hasMany(Thread::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function close()
    {
        $this->status = 'closed';
    }

    public function open()
    {
        $this->status = 'open';
    }

    public function solve()
    {
        $this->status = 'solved';
    }

    public function private()
    {
        $this->visibility = 'private';
    }

    public function public()
    {
        $this->visibility = 'public';
    }

    public function scopeFilter(Builder $builder, ApiFilter $filters): Builder
    {
        return $filters->apply($builder);
    }
}
