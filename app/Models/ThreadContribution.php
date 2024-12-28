<?php

namespace App\Models;

use App\Http\Filters\ApiFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ThreadContribution extends Model
{
    protected $fillable = [
        'thread_id',
        'user_id',
        'type', // 'like' | 'dislike' | 'report'
    ];

    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function like()
    {
        $this->type = 'like';
        $this->save();
    }

    public function dislike()
    {
        $this->type = 'dislike';
        $this->save();
    }

    public function report()
    {
        $this->type = 'report';
        $this->save();
    }

    public function scopeLikes($query)
    {
        return $query->where('type', 'like');
    }

    public function scopeDislikes($query)
    {
        return $query->where('type', 'dislike');
    }

    public function scopeReports($query)
    {
        return $query->where('type', 'report');
    }

    public function scopeSolutions($query)
    {
        return $query->where('type', 'solution');
    }

    public function scopeFilter(Builder $builder, ApiFilter $filters): Builder
    {
        return $filters->apply($builder);
    }
}
