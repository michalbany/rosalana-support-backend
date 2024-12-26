<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $fillable = [
        'name',
        'description',
        'url',
        'rosalana_account_id',
    ];
}
