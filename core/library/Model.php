<?php

namespace core\library;

use PDO;
use PDOException;
use core\library\Filters;
use core\library\Connection;
use core\library\Pagination;

abstract class Model
{
    private mixed $fields = '*';
    private ?Filters $filters = null;
    private string $pagination = '';
    protected string $table;

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

    public function setPagination(Pagination $pagination)
    {
        $pagination->setTotalItens($this->count());
        $this->pagination = $pagination->dump();
    }

    public function create(array $valuesAssoc): bool
    {
        try {
            $fields = array_keys($valuesAssoc);
            $sql = "insert into {$this->table} (" . implode(', ', $fields) . ") values (:" . implode(', :', $fields) . ")";
            $prepare = self::connect($sql);
            return $prepare->execute($valuesAssoc);
        } catch (PDOException $e) {
            var_dump($e->getMessage());
        }
    }

    public function fetchAllObj(): array
    {
        try {
            if (is_array($this->fields)) {
                $fields = implode(', ', $this->fields);
            } else {
                $fields = $this->fields;
            }
            $sql = "select {$fields} from {$this->table}{$this->filters?->dump()}{$this->pagination}";

            $prepare = self::connect($sql);
            $prepare->execute(empty($this->filters) ? [] : $this->filters->getBind());
            return $prepare->fetchAll(PDO::FETCH_CLASS, get_called_class());
        } catch (PDOException $e) {
            var_dump($e->getMessage());
        }
    }

    public function fetchAll(): array
    {
        try {
            if (is_array($this->fields)) {
                $fields = implode(', ', $this->fields);
            } else {
                $fields = $this->fields;
            }
            $sql = "select {$fields} from {$this->table}{$this->filters?->dump()}{$this->pagination}";

            $prepare = self::connect($sql);
            $prepare->execute(empty($this->filters) ? [] : $this->filters->getBind());
            return $prepare->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            var_dump($e->getMessage());
        }
    }

    public function findBy(string $field = '', mixed $value = ''): array
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
            $prepare = self::connect($sql);
            $prepare->execute(empty($this->filters) ? [$field => $value] : $this->filters->getBind());
            return $prepare->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function findByObj(string $field = '', mixed $value = ''): object | false
    {
        try {
            $sql = (empty($this->filters)) ?
                "select {$this->fields} from {$this->table} where {$field} = :{$field}" :
                "select {$this->fields} from {$this->table} {$this->filters?->dump()}";

            $prepare = self::connect($sql);
            $prepare->execute(empty($this->filters) ? [$field => $value] : $this->filters->getBind());
            return $prepare->fetchObject(get_called_class());
        } catch (PDOException $e) {
            var_dump($e->getMessage());
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
            $prepare = self::connect($sql);
            return $prepare->execute($valuesAssoc);
        } catch (PDOException $e) {
            var_dump($e->getMessage());
        }
    }

    // delete from users where id = 12
    public function delete(string $field = '', string|int $value = ''): bool
    {
        try {
            $sql = (empty($this->filters)) ?
                "delete from {$this->table} where {$field} = :{$field}" :
                "delete from {$this->table} {$this->filters?->dump()}";
            $prepare = self::connect($sql);
            return $prepare->execute(empty($this->filters) ? [$field => $value] : $this->filters->getBind());
        } catch (PDOException $e) {
            return false;
        }
    }

    public function first(string $field, string $dir = 'asc'): object | false
    {
        try {
            $sql = "select {$this->fields} from {$this->table} order by {$field} {$dir} limit 1";
            $prepare = self::connect($sql);
            $prepare->execute();
            return $prepare->fetchObject(get_called_class());
        } catch (PDOException $e) {
            var_dump($e->getMessage());
        }
    }

    public function count(): mixed
    {
        try {
            $sql = "select count({$this->fields}) from {$this->table}{$this->filters?->dump()}";
            $prepare = self::connect($sql);
            $prepare->execute(empty($this->filters) ? [] : $this->filters->getBind());
            return $prepare->fetchColumn();
        } catch (PDOException $e) {
            var_dump($e->getMessage());
        }
    }

    public static function connect($sql)
    {
        $connection = new Connection($_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_NAME'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
        return $connection->connect()->prepare($sql);
    }
}
