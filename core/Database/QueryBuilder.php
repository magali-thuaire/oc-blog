<?php

namespace Core\Database;

class QueryBuilder
{
    private array $select = [];
    private array $where = [];
    private string $from = '';
    private array $innerJoin = [];
    private array $leftJoin = [];
    private array $orderBy = [];
    private array $groupBy = [];
    private string $insert = '';
    private string $delete = '';
    private string $deleteTarget = '';
    private array $columns = [];
    private string $update = '';
    private array $set = [];

    public function select(): static
    {
        $this->select = func_get_args();
        return $this;
    }

    public function addSelect(): static
    {
        $this->select = array_merge($this->select, func_get_args());
        return $this;
    }

    public function where(): static
    {
        $this->where = func_get_args();
        return $this;
    }

    public function andWhere(): static
    {
        $this->where = array_merge($this->where, func_get_args());
        return $this;
    }

    public function orderBy($prop, $direction): static
    {
        $this->orderBy[] = $prop . ' ' . $direction;
        return $this;
    }

    public function from($table, $alias): static
    {

        $this->from = $table . ' ' . $alias;

        return $this;
    }

    public function innerJoin($table, $alias, $join): static
    {

        $this->innerJoin[] = $table . ' ' . $alias . ' ON ' . $join;

        return $this;
    }

    public function leftJoin($table, $alias, $join, $where = null): static
    {
        if ($where) {
            $where = str_replace($alias . '.', '', $where);
            $this->leftJoin[] = '(SELECT * FROM ' . $table .  ' WHERE ' . $where . ') ' . $alias . ' ON ' . $join;
        } else {
            $this->leftJoin[] = $table . ' ' . $alias . ' ON ' . $join;
        }
        return $this;
    }

    public function addCount($count, $alias, $groupBy): static
    {
        $this->select[] = 'count(' . $count . ') as ' . $alias;
        $this->groupBy[] = $groupBy;
        return $this;
    }

    public function groupBy(): static
    {
        $this->groupBy = func_get_args();
        return $this;
    }

    public function insert($table): static
    {
        $this->insert = $table;
        return $this;
    }

    public function delete($table, $alias = '', $target = ''): static
    {
        $this->deleteTarget = $target;
        $this->delete = $table . ' ' . $alias;
        return $this;
    }

    public function columns(): static
    {
        $this->columns = func_get_args();
        return $this;
    }

    public function addColumns(): static
    {
        $this->columns = array_merge($this->columns, func_get_args());
        return $this;
    }

    public function update($table, $alias): static
    {

        $this->update = $table . ' ' . $alias;

        return $this;
    }

    public function set(): static
    {
        $this->set = func_get_args();
        return $this;
    }

    public function addSet(): static
    {
        $this->set = array_merge($this->set, func_get_args());
        return $this;
    }

    public function getQuery(): string
    {

        // select
        $statement =  'SELECT ' . implode(', ', $this->select) . ' FROM ' . $this->from;

        // insert
        if (!empty($this->insert)) {
            $statement = 'INSERT INTO ' . $this->insert;
        }

        // columns
        if (!empty($this->columns)) {
            $statement .= ' ('
                          . implode(', ', $this->columns) . ')'
                          . ' VALUES (' . ':'
                          . implode(', :', $this->columns) . ')';
        }

        // update
        if (!empty($this->update)) {
            $statement =  'UPDATE ' . $this->update;
        }

        // set
        if (!empty($this->set)) {
            $statement .=  ' SET ' . implode(', ', $this->set);
        }

        // delete
        if (!empty($this->delete)) {
            $statement =  'DELETE ' . $this->deleteTarget . ' FROM ' . $this->delete;
        }

        // inner join
        if (!empty($this->innerJoin)) {
            foreach ($this->innerJoin as $innerJoin) {
                $statement .= ' INNER JOIN ' . $innerJoin;
            }
        }

        // left join
        if (!empty($this->leftJoin)) {
            foreach ($this->leftJoin as $leftJoin) {
                $statement .= ' LEFT JOIN ' . $leftJoin;
            }
        }

        // where
        if (!empty($this->where)) {
            $statement .= ' WHERE ' . implode(' AND ', $this->where);
        }

        // groupBy
        if (!empty($this->groupBy)) {
            $statement .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        // orderBy
        if (!empty($this->orderBy)) {
            $statement .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        return $statement;
    }
}
