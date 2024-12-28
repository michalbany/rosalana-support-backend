<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doc extends Model
{
    protected $fillable = [
        'app_id',
        'user_id',
        'title',
        'content',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function app()
    {
        return $this->belongsTo(App::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUnpublished($query)
    {
        return $query->where('status', 'draft');
    }

    public function publish()
    {
        $this->status = 'published';
        $this->published_at = now();
        $this->save();
    }

    public function draft()
    {
        $this->status = 'draft';
        $this->published_at = null;
        $this->save();
    }
}
