<?php

namespace Core;
use App\Model;

abstract class RelationshipCache {
    private $cache = array();
    protected $relations = array();
    public function __call($method, $args) {
        // Look up relationships in the cache
        if (preg_match('/^get(.+)/', $method, $matches)) {
            $key = strtolower($matches[1]);
            if (isset($this->relations[$key])) {
                return $this->getRelationship($key);
            } else {
                return $this->$key;
            }
        }
    }
    public function primeCache($key, $value) {
        $this->cache[$key] = $value;
    }
    protected function getRelationship($key) {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        } else {
            $relationVars = $this->relations[$key];
            $relatedClass = $relationVars[0];
            $relatedColumn = $relationVars[1];
            $column = $key;
            if (count($relationVars) === 3) {
                $column = $relationVars[2];
            }
            $className = "App\\Model\\$relatedClass";
            $relationship = new $className();
            $loadBy = "loadBy" . $relatedColumn;
            $relationship->$loadBy($this->$column);
            if ($relationship->id) {
                $this->primeCache($column, $relationship);
                return $relationship;
            }
            return false;
        }
    }
}
