<?php

namespace App\Models;

use App\Http\Filters\ApiFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    protected $fillable = [
        'issue_id',
        'user_id',
        'thread_id',
        'content',
        'solution',
    ];

    protected $casts = [
        'solution' => 'boolean',
    ];

    protected $appends = [
        'likes_count',
        'dislikes_count',
        'reports_count',
    ];

    public function getLikesCountAttribute()
    {
        return $this->contributions()->likes()->count();
    }

    public function getDislikesCountAttribute()
    {
        return $this->contributions()->dislikes()->count();
    }

    public function getReportsCountAttribute()
    {
        return $this->contributions()->reports()->count();
    }

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }

    public function contributions()
    {
        return $this->hasMany(ThreadContribution::class);
    }

    public function solution()
    {
        $this->solution = true;
        $this->save();
    }

    public function unsolution()
    {
        $this->solution = false;
        $this->save();
    }

    public function scopeFilter(Builder $builder, ApiFilter $filters): Builder
    {
        return $filters->apply($builder);
    }

}
