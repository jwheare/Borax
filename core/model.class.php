<?php

namespace Core;
use DateTime;
use Exception;
use Core\Url;

class Model extends RelationshipCache {
    
    var $db = 'db';
    var $table = null;
    protected $columns = array();
    protected $getByColumns = null;
    var $id = null;
    var $creation_date = null;
    var $data = array();
    public function db () {
        return service($this->db);
    }
    public function __construct($data = array(), $keyPrefix = null) {
        $this->loadData($data, $keyPrefix);
    }
    public function __call($method, $args) {
        // loadBy* magic method handler
        if (preg_match('/^((?:load|getAll)By)(.+)/', $method, $matches)) {
            // Check key is a valid column
            $method = $matches[1];
            $key = strtolower($matches[2]);
            if ($key == 'id' || in_array($key, $this->columns)) {
                array_unshift($args, $key);
                return call_user_func_array(array($this, $method), $args);
            }
        }
        return parent::__call($method, $args);
    }
    
    // Overload getter and setter to store data
    public function __get($name) {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
    }
    public function __set($name, $value) {
        if (in_array($name, $this->columns)) {
            $this->data[$name] = $value;
        }
    }
    
    protected function loadData($data, $keyPrefix = '') {
        if (!$data) {
            return false;
        }
        if ($keyPrefix) {
            $keyPrefix = "{$keyPrefix}_";
        }
        // Load data into the object
        $this->beforeLoad($data, $keyPrefix);
        if (array_key_exists("{$keyPrefix}id", $data)) {
            $this->id = $data["{$keyPrefix}id"];
            unset($data["{$keyPrefix}id"]);
        }
        if (array_key_exists("{$keyPrefix}creation_date", $data)) {
            $this->creation_date = new DateTime($data["{$keyPrefix}creation_date"]);
            unset($data["{$keyPrefix}creation_date"]);
        }
        foreach ($this->columns as $column) {
            $this->data[$column] = isset($data[$keyPrefix . $column]) ? $data[$keyPrefix . $column] : null;
        }
        return true;
    }
    protected function getModels ($rows) {
        $models = array();
        foreach ($rows as $modelData) {
            $class = get_called_class();
            $models[] = new $class($modelData);
        }
        return $models;
    }
    
    private function loadBy($keys, $values) {
        // Prepare the query
        $keys = (array) $keys;
        $values = (array) $values;
        $wheres = array();
        foreach ($keys as $key) {
            $wheres[] = "`$key` = ?";
        };
        $query = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $wheres);
        // Execute
        $row = $this->db()->fetch($query, $values);
        // Load data, returns false on non-existence
        return $this->loadData($row);
    }
    private function getAllBy($keys = null, $values = null, $limit = null, $page = 1, $ordering = "creation_date DESC") {
        $query = "SELECT %s FROM $this->table ";
        if ($keys) {
            $keys = (array) $keys;
            $values = (array) $values;
            $wheres = array();
            foreach ($keys as $key) {
                $wheres[] = "`$key` = ?";
            };
            $query .= "WHERE " . implode(' AND ', $wheres) . " ";
        }
        // Get the total
        $total = $this->db()->fetchColumn(sprintf($query, "COUNT(id)"), $values);
        // Run the paginated query
        $query .= "ORDER BY $ordering ";
        if ($limit) {
            $query .= "LIMIT $limit ";
        }
        $offset = ($page - 1) * $limit;
        if ($offset) {
            $query .= "OFFSET $offset ";
        }
        $rows = $this->db()->fetchAll(sprintf($query, "*"), $values);
        // Create an array of models
        $models = $this->getModels($rows);
        return array($models, $total);
    }
    
    public function getAll($limit = null) {
        return $this->getAllBy(null, null, $limit);
    }
    
    protected function beforeLoad(&$data, $keyPrefix = '') {
        // override in subclass
    }
    protected function beforeMutate() {
        // override in subclass
    }
    protected function afterMutate() {
        // override in subclass
    }
    protected function beforeInsert() {
        // override in subclass
    }
    protected function afterInsert() {
        // override in subclass
    }
    protected function beforeUpdate() {
        // override in subclass
    }
    protected function afterUpdate() {
        // override in subclass
    }
    protected function beforeDelete() {
        // override in subclass
    }
    protected function afterDelete() {
        // override in subclass
    }
    
    public function insert() {
        // Callback
        $this->beforeMutate();
        $this->beforeInsert();
        // Prepare the query
        $values = array();
        $data = $this->data;
        foreach ($this->columns as $k) {
            if (array_key_exists($k, $data)) {
                $keys[] = "`$k`";
                $values[] = ":$k";
                if ($data[$k] instanceof DateTime) {
                    $data[$k] = $this->$k->format('Y-m-d H:i:s');
                }
            }
        }
        if ($this->creation_date) {
            $keys[] = '`creation_date`';
            $values[] = ':creation_date';
            $data['creation_date'] = $this->getCreationDateData();
        }
        if ($this->id) {
            $keys[] = '`id`';
            $values[] = ':id';
            $data['id'] = $this->id;
        }
        $query = "INSERT INTO {$this->table} (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ")";
        // Execute in a transaction
        $db = $this->db();
        $db->beginTransaction();
        $db->execute($query, $data);
        // Load data
        $lastId = $db->lastInsertId();
        $this->loadById($lastId);
        $db->commit();
        // Callback
        $this->afterMutate();
        $this->afterInsert();
    }
    
    public function update() {
        // Callback
        $this->beforeMutate();
        $this->beforeUpdate();
        // Prepare the query
        $sets = array();
        foreach ($this->columns as $col) {
            $sets[] = "`$col` = :$col";
        }
        $query = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE `id` = :id";
        $params = $this->data;
        $params['id'] = $this->id;
        // Execute
        $this->db()->execute($query, $params);
        // Callback
        $this->afterMutate();
        $this->afterUpdate();
    }
    
    public function delete() {
        if (!$this->load()) {
            return false;
        }
        // Callback
        $this->beforeMutate();
        $this->beforeDelete();
        // Prepare query
        $query = "DELETE FROM {$this->table} WHERE `id` = :id";
        $params = array('id' => $this->id);
        // Execute
        $this->db()->execute($query, $params);
        // Callback
        $this->afterMutate();
        $this->afterDelete();
        return true;
    }
    public function save() {
        // Can't save without data
        if (empty($this->data)) {
            return false;
        }
        // Update or insert
        if ($this->id) {
            $this->update();
        } else {
            $this->insert();
        }
        return true;
    }
    public function load() {
        if ($this->id) {
            return $this->loadById($this->id);
        }
        if (!$this->getByColumns) {
            throw new ModelException('This model has no getByColumns');
        }
        $loadByValues = array();
        $loadByColumns = array();
        foreach ($this->getByColumns as $column) {
            if ($this->{$column} !== null) {
                $loadByColumns[] = $column;
                $loadByValues[] = $this->{$column};
            }
        }
        if (empty($loadByColumns)) {
            throw new ModelException('No loadable columns specified');
        }
        return $this->loadBy($loadByColumns, $loadByValues);
    }
    // returns created (bool)
    public function loadOrCreate() {
        $loaded = $this->load();
        if ($loaded) {
            return false;
        } else {
            $this->insert();
            return true;
        }
    }
    public function getCreationDate() {
        return $this->creation_date ? $this->creation_date->format('D j M Y g:ia') : null;
    }
    public function getCreationDateData() {
        return $this->creation_date ? $this->creation_date->format('Y-m-d H:i:s') : null;
    }
    public function getEncodedId() {
        return encodeNumber($this->id);
    }
    
    public function equals($model) {
        return $model && ($this->table === $model->table) && ($this->id === $model->id);
    }
    
    public function getColumnsSql($table = null, $qualified = true) {
        if (!$table) {
            $table = $this->table;
        }
        $selects = array(
            "{$table}.id" . ($qualified ? " AS {$table}_id" : ""),
            "{$table}.creation_date" . ($qualified ? " AS {$table}_creation_date" : ""),
        );
        foreach ($this->columns as $col) {
            $selects[] = "{$table}.{$col}" . ($qualified ? " AS {$table}_{$col}" : "");
        }
        return implode(', ', $selects);
    }
    public function total() {
        return $this->totalBy();
    }
    public function totalBy($keys = array(), $values = array()) {
        // Prepare the query
        $keys = (array) $keys;
        $values = (array) $values;
        $wheres = array();
        foreach ($keys as $key) {
            $wheres[] = "`$key` = ?";
        };
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' AND ', $wheres);
        // Execute
        $total = $this->db()->fetchColumn($query, $values);
        return $total;
    }
    public function fuckBitly() {
        $shorter = Url::addHost($this->getShortUrl(), shortHost());
        header("Link: <$shorter>; rev=canonical");
        return $shorter;
    }
}

class ModelException extends Exception {
    var $callingClass;
    public function __construct($message, $code = 0, Exception $previous = null) {
        $trace = $this->getTrace();
        $this->callingClass = strtolower($trace[0]['class']);
        parent::__construct($message, $code, $previous);
    }
    public function __toString() {
        return $this->callingClass . " {$this->getStatusLine()}";
    }
    public function getStatusLine() {
        return "{$this->getCode()} {$this->getMessage()}";
    }
    // TODO fix for new framework
    public function handle() {
        $status = $this->getStatusLine();
        if (acceptJson()) {
            $errorData = array(
                'error' => $this->getMessage(),
                'code' => $this->getCode(),
            );
            if ($previous = $this->getPrevious()) {
                $errorData['previous'] = (string) $previous;
            }
            errorJson($status, $errorData);
        } else {
            // User friendly info
            $errorHeading = "Model error";
            $errorMessage = "<p>{$this->getMessage()}</p>";
            // Debug info
            $errorMessage .= '<div class="debug">';
            $errorMessage .= '<h2>Debug info</h2>';
            $errorMessage .= "<p>({$this->getCode()})</p>";
            
            $errorMessage .= '</div>';
            error($status, $errorHeading, $errorMessage);
        }
    }
}
