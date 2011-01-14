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
            list($class, $column) = $this->relations[$key];
            $className = "App\\Model\\$class";
            $relationship = new $className();
            $loadBy = "loadBy" . $column;
            $relationship->$loadBy($this->$key);
            if ($relationship->id) {
                $this->primeCache($key, $relationship);
                return $relationship;
            }
            return false;
        }
    }
}
