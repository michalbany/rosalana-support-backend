<?php

namespace App\Models\Traits;

use App\Models\Enums\ApiHistoryAction;

trait HasHistory
{

    /**
     * Update the model with history
     * #note - Not implemented
     */
    public function updateWithHistory(mixed $data): bool
    {
        $this->fill($data);

        if ($this->isDirty()) {
            // ModelHistory::updated($this);
        }

        return $this->save();
    }

    public function saveRelationsHistory(string $key, ApiHistoryAction $action): bool
    {
        $relation = $this->$key;

        if ($relation instanceof \Illuminate\Database\Eloquent\Collection) {
            $relation->each(function ($item) use ($key, $action) {
                try {
                    // ModelHistory::relationUpdated($this, $key, $item, $action);
                } catch (\Throwable $e) {
                    return false;
                }
            });
        } else {
            try {
                // ModelHistory::relationUpdated($this, $key, $relation, $action);
            } catch (\Throwable $e) {
                return false;
            }
        }

        return true;
    }
}
