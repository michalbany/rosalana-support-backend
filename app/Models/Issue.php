<?php

namespace App\Models;

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
        $this->save();
    }

    public function open()
    {
        $this->status = 'open';
        $this->save();
    }

    public function solve()
    {
        $this->status = 'solved';
        $this->save();
    }

    public function private()
    {
        $this->visibility = 'private';
        $this->save();
    }

    public function public()
    {
        $this->visibility = 'public';
        $this->save();
    }
}
