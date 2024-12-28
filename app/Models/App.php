<?php

namespace App\Models;

use App\Http\Filters\ApiFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class App extends Model
{
    protected $fillable = [
        'rosalana_account_id',
        'name',
        'description',
        'icon',
        'created_at',
        'updated_at',
    ];

    protected static array $rosalanaData = [];

    public static function sync(Collection $app): App
    {
            $model = static::updateOrCreate([
                'rosalana_account_id' => $app['id'],
                'name' => $app['name'],
            ], [
                'name' => $app['name'],
                'updated_at' => $app['updated_at'],
                'created_at' => $app['created_at'],
            ]);

            self::$rosalanaData[$app['id']]['url'] = $app['url'] ?? '';
            self::$rosalanaData[$app['id']]['master'] = $app['master'] ?? false;
            return $model;
    }

    public function applyRosalanaData(): self
    {
        $this->setAttribute('active', isset(self::$rosalanaData[$this->rosalana_account_id]));
        $this->setAttribute('url', self::$rosalanaData[$this->rosalana_account_id]['url'] ?? null);
        $this->setAttribute('master', self::$rosalanaData[$this->rosalana_account_id]['master'] ?? null);

        return $this;
    }

    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    public function docs()
    {
        return $this->hasMany(Doc::class);
    }

    public function scopeFilter(Builder $builder, ApiFilter $filters): Builder
    {
        return $filters->apply($builder);
    }
}
