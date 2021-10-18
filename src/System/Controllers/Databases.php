<?php

namespace System;

class Databases {

    private \MongoDB\Client $Client;
    private array $indexes;

    private \System\ObjectsResolver $ObjectsResolver;

    public function __construct(
        private string $SelectedDatabase
    ) {
        $this->indexes = json_decode(file_get_contents(__path__ . "/src/System/Schematics/indexes.json"), true) ?? [];
        $this->Client = new \MongoDB\Client("mongodb://localhost:27017");
        $this->ObjectsResolver = new \System\ObjectsResolver;
    }

    /**
     * Switch database
     *
     * @param string $db
     * @return void
     */
    public function SelectDatabase(string $db): void {
        $this->SelectedDatabase = $db;
    }
    
    /**
     * Count collection documents
     *
     * @param string $collection
     * @param array $filters
     * @return object|null
     */
    public function countDocuments(string $collection, array $filters = []): int {
        return $this->Client->{$this->SelectedDatabase}->{$collection}->countDocuments($filters);
    }

    /**
     * Find a specific data in collection
     *
     * @param string $collection
     * @param string $_id
     * @param array $args
     * @return object|null
     */
    public function findOne(string $collection, string $_id, array $args = []): ?object {
        $args["_id"] = new \MongoDB\BSON\ObjectID($_id);
        return $this->find($collection, $args)[0] ?? null;
    }

    /**
     * Find data in collection
     *
     * @param string $collection
     * @param array $args
     * @return mixed
     */
    public function find(string $collection, array $args = []): mixed {
        $data = [];
        $find = $this->Client->{$this->SelectedDatabase}->{$collection}->find($args);
        $object = $this->FindObjectByCollection($this->SelectedDatabase, $collection);
        foreach ($find as $entry) {
            array_push($data, $this->ObjectsResolver->NewResolve($object, $entry));
        }
        return $data;
    }

    /**
     * Insert single data in collection
     *
     * @param string $collection
     * @param array $args
     * @return mixed
     */
    public function insertOne(string $collection, array $data): \MongoDB\InsertOneResult {
        return $this->Client->{$this->SelectedDatabase}->{$collection}->insertOne($data);
    }

    /**
     * Insert many data in collection
     *
     * @param string $collection
     * @param array $args
     * @return mixed
     */
    public function insertMany(string $collection, array $data): \MongoDB\InsertManyResult {
        return $this->Client->{$this->SelectedDatabase}->{$collection}->insertMany($data);
    }

    /**
     * Update single data in collection
     *
     * @param string $collection
     * @param array $args
     * @return mixed
     */
    public function updateOne(string $collection, string | \MongoDB\BSON\ObjectID $_id, array $data, array $options = []): \MongoDB\UpdateResult {
        $_id = ($_id instanceof \MongoDB\BSON\ObjectID? $_id: new \MongoDB\BSON\ObjectID($_id));
        return $this->updateMany($collection, [ "_id" => $_id ], $data, $options ?? []);
    }

    /**
     * Update many data in collection
     *
     * @param string $collection
     * @param array $args
     * @return mixed
     */
    public function updateMany(string $collection, array $filters, array $data, array $options = []): \MongoDB\UpdateResult {
        return $this->Client->{$this->SelectedDatabase}->{$collection}->updateMany($filters, [ '$set' => $data ], $options);
    }

    /**
     * Update single data in collection
     *
     * @param string $collection
     * @param array $args
     * @return mixed
     */
    public function deleteOne(string $collection, string | \MongoDB\BSON\ObjectID $_id): \MongoDB\DeleteResult {
        $_id = ($_id instanceof \MongoDB\BSON\ObjectID? $_id: new \MongoDB\BSON\ObjectID($_id));
        return $this->deleteMany($collection, [ "_id" => $_id ], 1);
    }

    /**
     * Update many data in collection
     *
     * @param string $collection
     * @param array $args
     * @return mixed
     */
    public function deleteMany(string $collection, array $filters, int $limit = 1): \MongoDB\DeleteResult  {
        return $this->Client->{$this->SelectedDatabase}->{$collection}->deleteMany($filters, [ "limit" => $limit ]);
    }

    /**
     * Interpret the request result
     * 
     * @param \MongoDB\InsertOneResult,\MongoDB\InsertManyResult,\MongoDB\UpdateResult,\MongoDB\DeleteResult $result
     * @return bool
     */
    public function InterpretResult(\MongoDB\InsertOneResult | \MongoDB\InsertManyResult | \MongoDB\UpdateResult | \MongoDB\DeleteResult $result): bool  {
        if ($result instanceof \MongoDB\InsertOneResult | $result instanceof \MongoDB\InsertManyResult) {
            return ($result->getInsertedCount() > 0? true: false);
        } elseif ($result instanceof \MongoDB\UpdateResult) {
            return ($result->getMatchedCount() > 0? true: false);
        } elseif ($result instanceof \MongoDB\DeleteResult) {
            return ($result->getDeletedCount() > 0? true: false);
        } else {
            return false;
        }
    }

    /**
     *  Find object by database and collection name
     *
     * @param string $collection
     * @return object|null
     */
    private function FindObjectByCollection(string $collection): ?object {
        $object = $this->indexes["{$this->SelectedDatabase}.{$collection}"] ?? null;
        return (!empty($object)? new \ReflectionClass($object): null);
    }
    
}