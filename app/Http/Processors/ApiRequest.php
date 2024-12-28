<?php

namespace App\Http\Processors\API\V1;

use App\Models\Enums\ApiHistoryAction;
use App\Models\ServiceStatus;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

/**
 * Class pro procesování post requestů pro API
 */
class ApiRequest
{
    /** @var \Illuminate\Database\Eloquent\Model $model */
    protected mixed $model;
    /** @var array<mixed> $data */
    protected array $data;
    /** @var string $requestMethod */
    protected string $requestMethod;
    /** @var array<mixed> $relationships */
    public array $relationships;
    /** @var bool $modelExists */
    protected bool $modelExists = false;
    /** @var array<string, mixed> $creatable */
    protected array $creatable;

    /**
     * @param string $model
     * @param array<mixed> $data
     */
    public function __construct(string $model, array $data, string $requestMethod)
    {
        if (!isset($data['data'])) {
            throw new \InvalidArgumentException('Invalid request format');
        }
        $this->data = $data['data'];

        $this->relationships = $this->data['relationships'] ?? [];

        $this->requestMethod = $requestMethod;

        try {
            $this->model = $this->find($model, $this->data);
            $this->modelExists = true;
        } catch (ModelNotFoundException $e) {
            $this->modelExists = false;
            /** @var \Illuminate\Database\Eloquent\Model $modelInstance */
            $modelInstance = new $model();
            $this->model = $modelInstance;
        }

        // inicializace validačních pravidel pro vytváření nových modelů přes relace
        $this->creatable = [
            'tags' => [
                'name' => 'required|string|max:25|not_in:HA,hasGPU,EFI disk,CloudInit,ISO',
            ],
        ];
    }

    /**
     * Process request
     * @param 'update'|'relationships' $action
     * @return mixed
     */
    public function process(string $action): mixed
    {
        return DB::transaction(function () use ($action) {

            if ($action === 'update') {
                return $this->update();
            }

            if ($action === 'relationships') {
                $relations = $this->findRelationships();
                foreach ($relations as $relationKey => $relation) {
                    if ($relation instanceof BelongsTo) {
                        $relationAction = $this->processBelongsTo($relationKey, $relation);
                    } else if ($relation instanceof BelongsToMany) {
                        $relationAction = $this->processBelongsToMany($relationKey, $relation);
                    } else if ($relation instanceof HasMany) {
                        $relationAction = $this->processHasMany($relationKey, $relation);
                    } else if ($relation instanceof HasOne) {
                        $relationAction = $this->processHasOne($relationKey, $relation);
                    } else {
                        $relationType = class_basename($relation);
                        throw new \InvalidArgumentException("Illegal relationship type for $relationType");
                    }

                    if (method_exists($this->model, 'saveRelationsHistory') && $relationAction !== ApiHistoryAction::NO_CHANGE) {
                        $this->model->saveRelationsHistory($relationKey, $relationAction);
                    }
                }

                return $relations;
            }
        });
    }

    /**
     * Update model
     * #added: History storing
     * @return mixed
     */
    public function update(): mixed
    {
        if (method_exists($this->model, 'updateWithHistory')) {
            $this->model->updateWithHistory($this->data['attributes']);
        } else {
            $this->model->update($this->data['attributes']);
        }
        return $this->model;
    }

    /**
     * Process BelongsTo relationship
     * @param string $name
     * @param BelongsTo $relation
     * @return ApiHistoryAction
     */
    public function processBelongsTo(string $name, BelongsTo $relation): ApiHistoryAction
    {
        $relatedClass = get_class($relation->getRelated());

        // Původní id relace
        $originalKey = $this->model->{$name}?->getKey();

        try {
            $identifier = $this->key($this->relationships[$name], ['id', 'name']);
        } catch (\InvalidArgumentException $e) {
            // neexistuje idenfitikátor takže odpojíme vztah
            $this->model->{$name}()->dissociate();
            $this->model->save();
            return ApiHistoryAction::UNASSIGNED;
        }

        try {
            $relatedModel = $this->find($relatedClass, $this->relationships[$name], ['id', 'name']);
        } catch (ModelNotFoundException $e) {
            // create or exception
            if ($this->isCreatable($name)) {
                $newModel = $this->createRelated($name, $relatedClass, $this->relationships[$name]['attributes'] ?? []);
                if ($newModel) {
                    $relatedModel = $newModel;
                } else {
                    return ApiHistoryAction::ASSIGNED;
                }
            } else {
                throw (new \BadMethodCallException("Model $name not found and creating new models for relation $name is not allowed"));
            }
        }

        // attach related model
        try {
            $this->model->{$name}()->associate($relatedModel);
            $this->model->save();
        } catch (\Exception $e) {
            throw (new \InvalidArgumentException("Wrong data format for relation '$name'"));
        }

        // Zjistit, zda došlo ke změně
        if ($originalKey !== $relatedModel->getKey()) {
            return ApiHistoryAction::ASSIGNED;
        }

        return ApiHistoryAction::NO_CHANGE;
    }

    /**
     * Process BelongsToMany relationship
     * @param string $name
     * @param BelongsToMany $relation
     * @return ApiHistoryAction
     */
    public function processBelongsToMany(string $name, BelongsToMany $relation): ApiHistoryAction
    {
        $relatedClass = get_class($relation->getRelated());

        $relatedModels = [];

        $originalKeys = $this->model->{$name}->pluck('id')->toArray();

        foreach ($this->relationships[$name] as $relatedData) {
            try {
                $relatedModels[] = $this->find($relatedClass, $relatedData, ['id', 'name'])->getKey();
            } catch (ModelNotFoundException $e) {
                // create or exception
                if ($this->isCreatable($name)) {
                    $newModel = $this->createRelated($name, $relatedClass, $relatedData['attributes'] ?? []);
                    if ($newModel) {
                        $relatedModels[] = $newModel->getKey();
                    } else {
                        continue;
                    }
                } else {
                    throw (new \BadMethodCallException("Model $name not found and creating new models for relation $name is not allowed"));
                }
            }
        }

        // attach all related models
        try {
            $this->model->{$name}()->sync($relatedModels);
        } catch (\Exception $e) {
            throw (new \InvalidArgumentException("Wrong data format for relation $name"));
        }

        // Zjistit, zda došlo ke změně
        if (array_diff($originalKeys, $relatedModels) || array_diff($relatedModels, $originalKeys)) {
            return ApiHistoryAction::UPDATED;
        }
        return ApiHistoryAction::NO_CHANGE;
    }

    /**
     * Process hasMany relationship
     * @param string $name
     * @param HasMany $relation
     * @return ApiHistoryAction
     */
    protected function processHasMany(string $name, HasMany $relation): ApiHistoryAction
    {
        $relatedClass = get_class($relation->getRelated());

        $relationData = $this->relationships[$name];

        $originalKeys = $this->model->{$name}->pluck('id')->toArray();

        // IDs souvisejících modelů z požadavku
        $relatedIds = [];

        foreach ($relationData as $itemData) {
            try {
                $relatedModel = $this->find($relatedClass, $itemData, ['id', 'name']);
            } catch (ModelNotFoundException | \InvalidArgumentException $e) {
                if ($this->isCreatable($name)) {

                    // add foreign key to related model
                    $itemAttributes = $itemData['attributes'] ?? [];
                    $itemAttributes[$relation->getForeignKeyName()] = $this->model->getKey();

                    $relatedModel = $this->createRelated($name, $relatedClass, $itemAttributes);
                } else {
                    throw new \BadMethodCallException("Model $name not found and creating new models for relation $name is not allowed");
                }
            }

            // Aktualizujeme atributy a nastavíme cizí klíč
            try {
                $relatedModel->update(array_merge($itemData['attributes'] ?? [], [
                    $relation->getForeignKeyName() => $this->model->getKey(),
                ]));
            } catch (\Throwable $e) {
                throw new \InvalidArgumentException("Invalid attributes for relation $name. " . $e->getMessage());
            }

            $relatedModel->save();

            $relatedIds[] = $relatedModel->getKey();
        }

        // Odstraníme související modely, které nejsou v požadavku
        $relation->whereNotIn($relation->getRelated()->getKeyName(), $relatedIds)->delete();

        // Zjistíme, zda došlo ke změně
        if (array_diff($originalKeys, $relatedIds) || array_diff($relatedIds, $originalKeys)) {
            return ApiHistoryAction::UPDATED;
        }

        return ApiHistoryAction::NO_CHANGE;
    }

    /**
     * Process HasOne relationship
     * @param string $name
     * #important: Not Tested!
     * @param HasOne $relation
     * @return ApiHistoryAction
     */
    public function processHasOne(string $name, HasOne $relation): ApiHistoryAction
    {
        $relatedClass = get_class($relation->getRelated());

        $relationData = $this->relationships[$name];

        $originalKey = $this->model->{$name}?->getKey();

        // Pokud je relace prázdná, odstraníme existující vztah
        if (empty($relationData)) {
            $existingRelatedModel = $this->model->{$name};
            if ($existingRelatedModel) {
                $existingRelatedModel->delete();
                return ApiHistoryAction::UNASSIGNED;
            }
            return ApiHistoryAction::NO_CHANGE;
        }

        try {
            $relatedModel = $this->find($relatedClass, $relationData, ['id', 'name']);
            $relatedModel->update($relationData['attributes'] ?? []);
        } catch (ModelNotFoundException $e) {
            if ($this->isCreatable($name)) {
                $relatedModel = $this->createRelated($name, $relatedClass, $relationData['attributes'] ?? []);
            } else {
                throw new \BadMethodCallException("Model $name not found and creating new models for relation $name is not allowed");
            }
        }

        // Nastavíme cizí klíč na hlavní model
        $relatedModel->{$relation->getForeignKeyName()} = $this->model->getKey();
        $relatedModel->save();

        // Načteme relaci znovu, aby byla aktuální
        $this->model->setRelation($name, $relatedModel);

        // Zjistit, zda došlo ke změně
        if ($originalKey) {
            if ($originalKey !== $relatedModel->id) {
                return ApiHistoryAction::UPDATED;
            }
            // Pokud jsou ID stejná, nedošlo ke změně
            return ApiHistoryAction::NO_CHANGE;
        } else {
            // Pokud původní model nebyl přítomen a nyní je přiřazen, došlo ke změně
            return ApiHistoryAction::ASSIGNED;
        }
    }
    /**
     * Create related model
     * @param string $name
     * @param string $relatedClass
     * @param array<mixed> $attributes
     * @return mixed (Model)
     */
    protected function createRelated(string $name, string $relatedClass, array $attributes): mixed
    {
        $validationRules = $this->creatable[$name];

        if (empty($attributes)) {
            throw new \InvalidArgumentException("Attempt to create related model $name without attributes");
        }

        $validator = \Illuminate\Support\Facades\Validator::make($attributes, $validationRules);
        if ($validator->fails()) {
            $failedRules = $validator->failed();

            // Failed on specific rule -> skip creating
            if (isset($failedRules['name']['NotIn'])) {
                return null;
            }

            throw new \InvalidArgumentException("Invalid attributes for relation $name. " . implode(', ', $validator->errors()->all()));
        }

        $relatedModel = $relatedClass::create($attributes);

        return $relatedModel;
    }

    /**
     * Find relationships in model
     * @return array<Relation>
     */
    protected function findRelationships()
    {
        $relationships = [];
        foreach ($this->relationships as $relationName => $relationData) {
            if (!method_exists($this->model, $relationName)) {
                throw new \InvalidArgumentException("Relation $relationName not found");
            }

            $relation = $this->model->{$relationName}();

            if (!$relation instanceof Relation) {
                throw new \InvalidArgumentException("Relation $relationName is not a valid relation");
            }

            $relationships[$relationName] = $relation;
        }

        return $relationships;
    }

    /**
     * Find model by passed identifier in data
     * @param string $modelClass
     * @param array<mixed> $data
     * @param array<string>|string $key
     * @return mixed
     */
    protected function find(string $modelClass, array $data, array|string $key = 'id')
    {
        // check if class exists
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException("Model $modelClass not found");
        }
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException("Model $modelClass is not an instance of Eloquent Model");
        }

        // find some key from $keys in data
        $key = $this->key($data, $key, $modelClass);

        // find model by key
        $model = $modelClass::where($key)->first();


        if (!$model) {
            throw (new ModelNotFoundException())->setModel($modelClass, $key);
        }

        return $model;
    }


    /**
     * Find passed key in data
     * @param array<mixed> $data
     * @param array<string>|string $key
     * @return array<mixed>
     */
    protected function key(array $data, array|string $key, string $modelClass = 'unknown')
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                if ($k == 'id' && isset($data[$k])) {
                    return [$k => $data[$k]];
                } else if (isset($data['attributes'][$k])) {
                    return [$k => $data['attributes'][$k]];
                }
            }
        } else {
            if ($key == 'id' && isset($data[$key])) {
                return [$key => $data[$key]];
            } else if (isset($data['attributes'][$key])) {
                return [$key => $data['attributes'][$key]];
            }
        }

        throw new \InvalidArgumentException("Identifier key not found for $modelClass");
    }

    public function type(): string
    {
        /** Cases:
         * 1. Create: !id && attributes
         * 2. Update: id && attributes
         * 3. Delete: id && !attributes
         */

        $requestType = $this->requestMethod;

        $id = isset($this->data['id']) ? $this->data['id'] : null;
        $attributes = isset($this->data['attributes']) ? $this->data['attributes'] : null;

        if (!$id && $attributes && $requestType === 'POST' && !$this->modelExists) {
            return 'CREATE';
        }

        if ($id && $attributes && $requestType === 'PATCH' && $this->modelExists) {
            return 'UPDATE';
        }

        if ($id && !$attributes && $requestType === 'DELETE' && $this->modelExists) {
            return 'DELETE';
        } else {
            // This is because ID is required as key for update and delete
            throw new \InvalidArgumentException('Invalid data format or request type');
        }
    }


    /**
     * Check if relation is creatable
     * @param string $relationName
     * @return bool
     */
    protected function isCreatable(string $relationName): bool
    {
        return array_key_exists($relationName, $this->creatable) ? true : false;
    }
}
