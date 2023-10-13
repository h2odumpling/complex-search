<?php

namespace H2o\ComplexSearch;

class RelationNode
{
    public $table;

    public $primaryKey;

    /**
     * @var self
     */
    public $parentNode;

    public $childNodes = array();

    public $fields = array();

    public $joinName;

    public $joinTable;

    public $localKey;

    public $otherKey;

    /**
     * RelationNode constructor.
     * @param $model \Illuminate\Database\Eloquent\Model | string
     */
    public function __construct($model)
    {
        if (is_string($model)) {
            $this->table = $model;
        } else {
            $this->table = $model->getTable();
            $this->primaryKey = $model->getKeyName();
        }
    }

    public function setJoin($other_key, $local_key, $name)
    {
        $this->joinName = $name;
        $this->otherKey = $this->getName($other_key);
        $this->localKey = $this->getName($local_key);
    }

    public function getQualifiedLocalKeyName()
    {
        return $this->table . '.' . $this->localKey;
    }

    public function getQualifiedOtherKeyName()
    {
        return $this->parentNode->table . '.' . $this->otherKey;
    }

    /**
     * @return array
     */
    public function path()
    {
        $path = $this->parentNode ? $this->parentNode->path() : [];

        if ($this->joinName) $path[] = $this->joinName;

        return $path;
    }

    /**
     * @return array
     */
    public function join()
    {
        if ($this->parentNode) {
            return [$this->table, $this->getQualifiedLocalKeyName(), $this->getQualifiedOtherKeyName()];
        }
        return [];
    }

    /**
     * @return array
     */
    public function joins()
    {
        $joins = $this->parentNode ? [$this->joinName => $this->join()] : [];
        if ($this->parentNode) {
            return array_merge($this->parentNode->joins(), $joins);
        }
        return $joins;
    }

    /**
     * @param $node self
     * @return $this
     */
    public function addChild($node)
    {
        $node->parentNode = $this;

        $this->childNodes[$node->joinName] = $node;

        return $this;
    }

    /**
     * @param $node self
     * @return $this
     */
    public function appendTo($node)
    {
        $this->parentNode = $node;

        $node->childNodes[$this->joinName] = $this;

        return $this;
    }

    /**
     * @param $path array
     * @return bool
     */
    public function hasPath($path)
    {
        $curr = $this->path();

        if (count($path) > count($curr)) return false;

        for ($i = count($path) - 1; $i >= 0; $i--) {
            $j = count($curr) - count($path) + $i;
            if ($curr[$j] !== $path[$i]) return false;
        }

        return true;
    }

    /**
     * @param $field string
     * @return RelationNode | bool
     */
    public function hasJoinField($field)
    {
        if ($this->localKey !== $field) {
            return false;
        }

        return $this->parentNode->hasJoinField($this->otherKey) ?: $this;
    }

    /**
     * @param $field string
     * @return bool
     */
    public function hasField($field)
    {
        return array_key_exists($field, $this->fields);
    }

    /**'
     * @param $key_name string
     * @return string
     */
    public function getName($key_name)
    {
        $argv = explode('.', $key_name);

        return end($argv);
    }
}
