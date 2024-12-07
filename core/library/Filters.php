<?php

namespace core\library;


class Filters
{
    private array $filters = [];
    private array $binds = [];

    public function getBind()
    {
        return $this->binds;
    }

    public function where(string $field, string $operator, mixed $value, string $logic = '')
    {
        $formatter = '';
        if (is_array($value)) {
            // $formatter = "('" . implode("','", $value) . "')";
            $formatter = $value;
        } else if (is_string($value)) {
            $formatter = strip_tags("{$value}");
        } else if (is_bool($value)) {
            $formatter = $value ? 1 : 0;
        } else {
            $formatter = $value;
        }

        $value = $formatter;

        $fieldBind = str_contains($field, '.') ? str_replace('.', '', $field) : $field;

        if (is_array($value)) {
            $in = '';
            $i = 0;
            foreach ($value as $item) {
                $key = ':id' . $i++;
                $in .= ($in ? ',' : '') . $key;
                $in_params[$key] = $item;
                $this->binds[$key] = $item;
            }
            $this->filters['where'][] = "{$field} in ({$in}) {$logic}";
        } else {
            if ($operator == 'in') {
                $this->filters['where'][] = "{$field} {$operator} (:{$fieldBind}) {$logic}";
            } else {
                $this->filters['where'][] = "{$field} {$operator} :{$fieldBind} {$logic}";
            }
            $this->binds[$fieldBind] = $value;
        }
    }

    public function join(string $tableJoin, ?string $as, string $fieldTable1, string $operator, string $fieldTable2, string $joinType = 'inner join')
    {
        $as = isset($as) ? ' as ' . $as . ' ' : ' ';
        $this->filters['join'][] = " {$joinType} {$tableJoin}{$as}on {$fieldTable1} {$operator} {$fieldTable2}";
    }

    public function limit(int $limit)
    {
        $this->filters['limit'] = " limit {$limit}";
    }

    public function offset(int $offset)
    {
        $this->filters['offset'] = " offset {$offset}";
    }

    public function orderBy(string $column, string $dir = 'asc')
    {
        $this->filters['orderby'][] = "{$column} {$dir}";
    }

    public function dump()
    {
        $filter = !empty($this->filters['join']) ? implode("", $this->filters['join']) : "";
        $filter .= rtrim(!empty($this->filters['where']) ? ' where ' . implode(" ", $this->filters['where']) : "");
        $filter .= !empty($this->filters['orderby']) ? ' order by ' . implode(", ", $this->filters['orderby']) : "";
        $filter .= $this->filters['limit'] ?? "";
        $filter .= $this->filters['offset'] ?? "";

        return rtrim($filter);
    }
}
