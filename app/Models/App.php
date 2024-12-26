<?php

namespace App\Models;

use App\Services\RosalanaApps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Void_;

class App extends Model
{
    protected $fillable = [
        'rosalana_account_id',
        'name',
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

            self::$rosalanaData[$app['id']] = $app['url'] ?? '';
            return $model;
    }

    public function applyRosalanaData(): self
    {
        $this->setAttribute('active', isset(self::$rosalanaData[$this->rosalana_account_id]));
        $this->setAttribute('url', self::$rosalanaData[$this->rosalana_account_id] ?? null);

        return $this;
    }


    // public function docs(): HasMany

    // public function supportAsks... hasmamy
}
