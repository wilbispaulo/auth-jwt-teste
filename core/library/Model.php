<?php

namespace core\library;

use PDO;
use PDOException;
use PDOStatement;
use core\library\Filters;
use core\library\Connection;

abstract class Model
{
    private mixed $fields = '*';
    private ?Filters $filters = null;
    protected string $table;
    private array $dbArgs = [];

    public function __construct()
    {
        $this->setDBAttributes([
            'host' => $_ENV['DB_HOST'],
            'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
        ]);
    }

    public function getTable()
    {
        return $this->table;
    }

    public function setFields(mixed $fields)
    {
        $this->fields = $fields;
    }

    public function setFilters(Filters $filters)
    {
        $this->filters = $filters;
    }

    public function setDBAttributes(array $dbArgs)
    {
        $this->dbArgs = $dbArgs;
    }

    public function create(array $valuesAssoc): bool
    {
        try {
            $fields = array_keys($valuesAssoc);
            $sql = "insert into {$this->table} (" . implode(', ', $fields) . ") values (:" . implode(', :', $fields) . ")";
            if (!$prepare = self::connect($sql, $this->dbArgs)) {
                return false;
            };
            return $prepare->execute($valuesAssoc);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function fetchAllObj(): array | false
    {
        try {
            if (is_array($this->fields)) {
                $fields = implode(', ', $this->fields);
            } else {
                $fields = $this->fields;
            }
            $sql = "select {$fields} from {$this->table}{$this->filters?->dump()}";

            if (!$prepare = self::connect($sql, $this->dbArgs)) {
                return false;
            };
            $prepare->execute(empty($this->filters) ? [] : $this->filters->getBind());
            return $prepare->fetchAll(PDO::FETCH_CLASS, get_called_class());
        } catch (PDOException $e) {
            return false;
        }
    }

    public function fetchAll(): array | false
    {
        try {
            if (is_array($this->fields)) {
                $fields = implode(', ', $this->fields);
            } else {
                $fields = $this->fields;
            }
            $sql = "select {$fields} from {$this->table}{$this->filters?->dump()}";

            if (!$prepare = self::connect($sql, $this->dbArgs)) {
                return false;
            };
            $prepare->execute(empty($this->filters) ? [] : $this->filters->getBind());
            return $prepare->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function findBy(string $field = '', mixed $value = ''): array | false
    {
        try {
            if (is_array($this->fields)) {
                $fields = implode(', ', $this->fields);
            } else {
                $fields = $this->fields;
            }

            $sql = (empty($this->filters)) ?
                "select {$fields} from {$this->table} where {$field} = :{$field}" :
                "select {$fields} from {$this->table} {$this->filters?->dump()}";
            if (!$prepare = self::connect($sql, $this->dbArgs)) {
                return false;
            };

            $prepare->execute(empty($this->filters) ? [$field => $value] : $this->filters->getBind());
            return $prepare->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function findByObj(string $field = '', mixed $value = ''): object | false
    {
        try {
            $sql = (empty($this->filters)) ?
                "select {$this->fields} from {$this->table} where {$field} = :{$field}" :
                "select {$this->fields} from {$this->table} {$this->filters?->dump()}";

            if (!$prepare = self::connect($sql, $this->dbArgs)) {
                return false;
            };
            $prepare->execute(empty($this->filters) ? [$field => $value] : $this->filters->getBind());
            return $prepare->fetchObject(get_called_class());
        } catch (PDOException $e) {
            return false;
        }
    }

    // update users set firstName = :firstName, lastName = 'Prado', password = '8888' where id = 5
    public function update(array $fieldsValuesAssoc, string $fieldFilter = '', mixed $valueFilter = ''): bool
    {
        try {
            $sql = "update {$this->table} set";
            foreach ($fieldsValuesAssoc as $key => $valueAssoc) {
                $sql .= " {$key} = :{$key},";
                $valuesAssoc[":{$key}"] = $valueAssoc;
            }
            $sql = rtrim($sql, ",");
            if (empty($this->filters)) {
                $sql .= " where {$fieldFilter} = :{$fieldFilter}";
                $valuesAssoc[":{$fieldFilter}"] = $valueFilter;
            } else {
                $sql .= "{$this->filters?->dump()}";
                $valuesAssoc = array_merge($valuesAssoc, $this->filters->getBind());
            }
            if (!$prepare = self::connect($sql, $this->dbArgs)) {
                return false;
            };
            return $prepare->execute($valuesAssoc);
        } catch (PDOException $e) {
            return false;
        }
    }

    // delete from users where id = 12
    public function delete(string $field = '', string|int $value = ''): bool
    {
        try {
            $sql = (empty($this->filters)) ?
                "delete from {$this->table} where {$field} = :{$field}" :
                "delete from {$this->table} {$this->filters?->dump()}";
            if (!$prepare = self::connect($sql, $this->dbArgs)) {
                return false;
            };
            return $prepare->execute(empty($this->filters) ? [$field => $value] : $this->filters->getBind());
        } catch (PDOException $e) {
            return false;
        }
    }

    public function first(string $field, string $dir = 'asc'): object | false
    {
        try {
            $sql = "select {$this->fields} from {$this->table} order by {$field} {$dir} limit 1";
            if (!$prepare = self::connect($sql, $this->dbArgs)) {
                return false;
            };
            $prepare->execute();
            return $prepare->fetchObject(get_called_class());
        } catch (PDOException $e) {
            return false;
        }
    }

    public function count(): int | false
    {
        try {
            $sql = "select count({$this->fields}) from {$this->table}{$this->filters?->dump()}";
            if (!$prepare = self::connect($sql, $this->dbArgs)) {
                return false;
            };
            $prepare->execute(empty($this->filters) ? [] : $this->filters->getBind());
            return $prepare->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function connect(string $sql, array $dbArgs): PDOStatement | false
    {
        try {
            $keysPattern = ['host', 'port', 'dbname', 'username', 'password'];
            $keysDB = array_keys($dbArgs);
            if (count(array_diff_key($keysDB, $keysPattern)) > 0) {
                return false;
            }
            $connection = new Connection($dbArgs['host'], $dbArgs['port'], $dbArgs['dbname'], $dbArgs['username'], $dbArgs['password']);
            return $connection->connect()->prepare($sql);
        } catch (PDOException $e) {
            return false;
        }
    }
}
