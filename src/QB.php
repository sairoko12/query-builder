<?php

namespace Sairoko;

use Exception;
use PDO;
use Sairoko\Connection;

class QB extends Connection
{
    const MODE_OBJECT = 1;
    const MODE_ARRAY = 2;

    private $camps;
    private $from;
    private $where_string;
    private $limit;
    private $orderBy;
    private $sortBy;
    private $groupBy;
    private $joins;
    private $onTransaction = false;
    private $transactionSql = array();
    private $wheres = array(
        'and' => array(),
        'or' => array()
    );

    public function __construct(array $params = array())
    {   
        if (!empty($params)) {
            parent::__construct($params);
        }
    }

    public function select($camps = array())
    {
        if (is_array($camps) && !empty($camps)) {
            $this->camps = implode(',', $camps);
        } elseif (is_array($camps) && empty($camps)) {
            $this->camps = '*';
        } elseif ($camps !== '') {
            $this->camps = $camps;
        }

        return $this;
    }

    public function from($table = array()) 
    {
        if (is_array($table) && !empty($table)) {
            $this->from = $table[key($table)] . " AS " . key($table);
        } else {
            $this->from = (!empty($table)) ? $table : false;
        }

        return $this;
    }

    /**
    * Alias for join methods
    *
    */
    public function join($table = array(), $condition = '', $type = 'inner')
    {
        return call_user_func_array([$this, ($type . 'Join')], [$table, $condition]);
    }

    public function innerJoin($table = array(), $condition = '')
    {
        if (is_array($table)) {
            $this->joins['inner'][] = "INNER JOIN " . $table[key($table)] . " AS " . key($table) . " ON " . $condition;
        } else {
            $this->joins['inner'][] = "INNER JOIN " . $table . " ON " . $condition;
        }

        return $this;
    }

    public function leftJoin($table = array(), $condition = '')
    {
        if (is_array($table)) {
            $this->joins['left'][] = "LEFT JOIN " . $table[key($table)] . " AS " . key($table) . " ON " . $condition;
        } else {
            $this->joins['left'][] = "LEFT JOIN " . $table . " ON " . $condition;
        }

        return $this;
    }

    public function rightJoin($table = array(), $condition = '')
    {
        if (is_array($table)) {
            $this->joins['right'][] = "RIGHT JOIN " . $table[key($table)] . " AS " . key($table) . " ON " . $condition;
        } else {
            $this->joins['right'][] = "RIGHT JOIN " . $table . " ON " . $condition;
        }

        return $this;
    }

    public function fullJoin($table = array(), $condition = '')
    {
        if (is_array($tables)) {
            $this->joins['full'][] = "FULL JOIN " . $table[key($table)] . " AS " . key($table) . " ON " . $condition;
        } else {
            $this->joins['full'][] = "FULL JOIN " . $table . " ON " . $condition;
        }

        return $this;
    }

    public function whereIn($field, $in)
    {
        if (!empty($in)) {
            return $this->where("{$field} IN(?)", $in);
        } else {
            return false;
        }
    }

    public function whereNotIn($field, $notIn)
    {
        if (!empty($notIn)) {
            return $this->where("{$field} NOT IN(?)", $notIn);
        } else {
            return false;
        }
    }

    public function whereNull($field)
    {
        return $this->where("{field} IS NULL");
    }

    public function orWhereNotNull($field)
    {
        return $this->orWhere("{$field} IS NOT NUL");
    }

    public function orWhereBetween($field, $values)
    {
        if (empty($values) || count($values) > 2) {
            return false;
        }

        if (empty($values[0]) || empty($values[1])) {
            return false;
        }

        return $this->orWhere("{$field} BETWEEN " . (is_numeric($values[0]) ? $values[0] : "'{$values[0]}'") . " AND " . (is_numeric($values[1]) ? $values[1] : "'{$values[1]}'"));
    }

    public function whereNotNull($field)
    {
        return $this->where("{$field} IS NOT NUL");
    }

    public function whereBetween($field, $values)
    {
        if (empty($values) || count($values) > 2) {
            return false;
        }

        if (empty($values[0]) || empty($values[1])) {
            return false;
        }

        return $this->where("{$field} BETWEEN " . (is_numeric($values[0]) ? $values[0] : "'{$values[0]}'") . " AND " . (is_numeric($values[1]) ? $values[1] : "'{$values[1]}'"));
    }

    public function where($where, $wildcard = null)
    {
        if (is_callable($where)) {
            $currentWheres['and'] = $this->wheres['and'];
            $currentWheres['or'] = $this->wheres['or'];

            $this->clearWhere();

            $where($this);
            $this->whereAssemble();
            $this->wheres['and'] = array_merge($currentWheres['and'], ["(" . str_replace(" WHERE ", "", $this->where_string) . ")"]);
            $this->wheres['or'] = $currentWheres['or'];
            unset($currentWheres);

            return $this;
        }

        if (is_null($wildcard)) {
            $this->wheres['and'][] = $where;
        } elseif(!is_array($wildcard)) {
            if (FALSE !== strpos($where, '?')) {
                $this->wheres['and'][] = (is_numeric($wildcard)) ? str_replace("?", "{$wildcard}", $where) : str_replace("?", "'{$wildcard}'", $where);
            } else {
                if (is_numeric($wildcard)) {
                    $this->wheres['and'][] = $where . " = " . $wildcard;
                } else {
                    $this->wheres['and'][] = $where . " = " . "'{$wildcard}'";
                }
            }
        } elseif (is_array($wildcard)) {
            $clean = implode(',', $this->array_map_assoc(function($k, $v) {
                            return (is_numeric($v)) ? "{$v}" : "'{$v}'";
                        }, $wildcard));
            $this->wheres['and'][] = str_replace("?", $clean, $where);
        }

        return $this;
    }

    public function orWhere($orWhere, $wildcard = null)
    {
        if (is_callable($orWhere)) {
            $currentWheres['and'] = $this->wheres['and'];
            $currentWheres['or'] = $this->wheres['or'];

            $this->clearWhere();

            $orWhere($this);
            $this->whereAssemble();
            $this->wheres['or'] = array_merge($currentWheres['or'], ["(" . str_replace(" WHERE ", "", $this->where_string) . ")"]);
            $this->wheres['and'] = $currentWheres['and'];
            unset($currentWheres);

            return $this;
        }

        if (is_null($wildcard)) {
            $this->wheres['or'][] = $orWhere;
        } elseif(!is_array($wildcard)) {
            if (FALSE !== strpos($orWhere, '?')) {
                $this->wheres['or'][] = (is_numeric($wildcard)) ? str_replace("?", "{$wildcard}", $orWhere) : str_replace("?", "'{$wildcard}'", $orWhere);
            } else {
                if (is_numeric($wildcard)) {
                    $this->wheres['or'][] = $orWhere . " = " . $wildcard;
                } else {
                    $this->wheres['or'][] = $orWhere . " = " . "'{$wildcard}'";
                }
            }
        } elseif (is_array($wildcard)) {
            $clean = implode(',', $this->array_map_assoc(function($k, $v) {
                            return (is_numeric($v)) ? "{$v}" : "'{$v}'";
                        }, $wildcard));
            $this->wheres['or'][] = str_replace("?", $clean, $orWhere);
        }
        
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function orderBy($camp, $order)
    {
        $this->orderBy = $camp;
        $this->sortBy = $order;
        return $this;
    }

    public function groupBy($camps = array())
    {
        if (is_array($camps)) {
            $this->groupBy = implode(',', $camps);
        } else {
            $this->groupBy = $camps;
        }

        return $this;
    }

    public function insert($table, $data = array())
    {
        $query = $this->insertQuery($table, $data);

        if ($this->onTransaction) {
            return $this->transactionSql[] = $query;
        }

        $result = $this->db()->prepare($query);
        $result->execute();
        return $this->db()->lastInsertId();
    }

    public function insertQuery($table, $data = array())
    {
        return "INSERT INTO {$table}(" . implode(',', array_keys($data)) . ") VALUES(" . implode(',', array_map(function($value) {
                                return (is_numeric($value)) ? "{$value}" : "'{$value}'";
                        }, $data)) . ");";
    }

    public function insertBatch($table, $data = array(), $onDuplicateKey = '')
    {
        $query = $this->insertBatchQuery($table, $data);

        if ($this->onTransaction) {
            return $this->transactionSql[] = $query;
        }

        if (empty($query)) {
            return false;
        }

        $result = $this->db()->prepare($query);

        try {
            $result->execute();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function insertBatchQuery($table, $data = array(), $onDuplicateKey = '')
    {
        $insert_query = "INSERT INTO {$table}";
        
        if (count($data) < 0 || !is_array($data)) {
            return false;
        }

        if (empty($data[0])) {
            return false;
        }

        $camps = implode(",", array_keys($data[0]));
        $insert_query .= "(" . $camps . ") VALUES ";

        $values = $this->array_map_assoc(function($k, $v) {
            $out = "(";
            $ln = count($v);
            $count = 0;

            foreach ($v AS $key => $value) {
                $count++;

                if ($count === $ln) {
                    if (empty($value)) {
                        $out .= "NULL";
                    } else {
                        $out .= (is_numeric($value)) ? $value : "'{$value}'";
                    }
                } else {
                    if (empty($value)) {
                        $out .= "NULL,";
                    } else {
                        $out .= (is_numeric($value)) ? $value . "," : "'{$value}',";
                    }
                }
            }

            $out .= ")";

            return $out;
        }, $data);

        $insert_query .= implode(",", $values) . ((!empty($onDuplicateKey)) ? " ON DUPLICATE KEY UPDATE {$onDuplicateKey}" : "") . ";";
        return $insert_query;
    }

    private function array_map_assoc($callback, $array)
    {
        $r = array();

        foreach ($array as $key => $value) {
            $r[$key] = $callback($key, $value);
        }

        return $r;
    }

    public function update($table, $data, $condition = '')
    {
        $query = $this->updateQuery($table, $data, $condition = '');

        if ($this->onTransaction) {
            return $this->transactionSql[] = $query;
        }

        $result = $this->db()->prepare($query);

        try {
            return $result->execute();
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }


    public function updateQuery($table, $data, $condition = '')
    {
        $query = "UPDATE {$table} SET " . implode(',', $this->array_map_assoc(function($k, $v) {
                            return (is_numeric($v)) ? "{$k} = {$v}" : "{$k} = '{$v}'";
                        }, $data)) . " ";

        if ($condition !== '') {
            if (is_string($condition)) {
                $query .= "WHERE " . $condition;
            } elseif(is_array($condition)) {
                $query .= "WHERE " . implode(' AND ', $this->array_map_assoc(function($k, $v){
                    return (is_numeric($v)) ? "{$k} = {$v}" : "{$k} = '{$v}'";
                }, $condition));
            }
        } elseif ($condition === '') {
            $this->whereAssemble();
            if (!empty($this->where_string)) {
                $query .= $this->where_string;
                $this->clearWhere();
            }
        }

        $query .= ";";

        return $query;
    }

    public function delete($table, $condition = false)
    {
        $query = $this->deleteQuery($table, $condition);

        if ($this->onTransaction) {
            return $this->transactionSql[] = $query;
        }

        try {
            return $this->db()->exec($query);
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function deleteQuery($table, $condition = false)
    {
        $query = "DELETE FROM {$table}";

        if (false !== $condition) {
            if(is_array($condition)) {
                $query .= " WHERE " . implode(' AND ', $this->array_map_assoc(function($k, $v) {
                            return (is_numeric($v)) ? "{$k} = {$v}" : "{$k} = '{$v}'";
                        }, $condition)) . ";";
            } elseif(is_string($condition)) {
                $query .= " WHERE {$condition}";
            }
        } else {
            $this->whereAssemble();
            if (!empty($this->where_string)) {
                $query .= " " . $this->where_string;
                $this->clearWhere();
            } else {
                $query .= ";";
            }
        }

        return $query;
    }

    private function whereAssemble()
    {
        if (!empty($this->wheres['and']) || !empty($this->wheres['or'])) {
            $where = ' WHERE ';

            $ands = 0;
            $ors = 0;

            foreach ($this->wheres AS $key => $value) {
                if (count($value)) {
                    switch ($key) {
                        case 'and':
                            $ands ++;

                            if ($ors > 0) {
                                $where .= ' AND ';
                            }

                            $where .= implode(' AND ', $value);
                            break;
                        case 'or':
                            $ors ++;

                            if ($ands > 0) {
                                $where .= ' OR ';
                            }

                            $where .= implode(' OR ', $value);
                            break;
                    }
                }
            }

            $this->where_string = $where;
            return $this;
        } else {
            return false;
        }
    }

    public function assemble()
    {
        if (empty($this->camps)) {
            $this->camps = "*";
        }

        if ($this->from === false) {
            return false;
        }

        $query = "SELECT " . $this->camps . " FROM " . $this->from;

        //Juntamos JOINS
        if ($this->validJoins()) {
            foreach ($this->joins AS $key => $value) {
                if (count($value)) {
                    for ($i = 0; $i < count($value); $i++) {
                        $query .= ' ' . $value[$i];
                    }
                }
            }
        }

        $this->whereAssemble();
        $query .= $this->where_string;

        //Agregamos groupBy
        if (strlen($this->groupBy) > 0) {
            $query .= " GROUP BY {$this->groupBy}";
        }

        //Agregamos orderBy
        if (!empty($this->orderBy) && !empty($this->sortBy)) {
            $query .= " ORDER BY {$this->orderBy} {$this->sortBy}";
        }

        //Agregamos limit
        if (!empty($this->limit) && is_numeric($this->limit) && intval($this->limit) > 0) {
            $query .= " LIMIT {$this->limit}";
        }

        $query .= ";";        

        return $query;
    }

    public function db()
    {
        return parent::getAdapter();
    }

    private function clearWhere()
    {
        $this->wheres = array(
            'and' => array(),
            'or' => array()
        );

        $this->where_string = "";

        return $this;
    }

    private function clearSql()
    {
        $this->camps = null;
        $this->from = null;
        $this->where_string = null;
        $this->limit = null;
        $this->orderBy = null;
        $this->sortBy = null;
        $this->groupBy = null;
        $this->joins = null;
        
        return $this->clearWhere();
    }

    public function quickQuery($from, $camps = array(), $where = array())
    {
        $setup = $this->select($camps)->from($from);
        
        if (is_array($where) && !empty($where)) {
            foreach ($where as $k => $w) {
                if (is_array($w)) {
                    switch ($k) {
                        case 'and':
                            foreach ($w as $n => $sw) {
                                if (!is_numeric($n) && is_string($n)) {
                                    $setup->where($n, $sw);
                                } else {
                                    $setup->where($sw);
                                }
                            }
                            break;
                        case 'or':
                            foreach ($w as $n => $sw) {
                                if (!is_numeric($n) && is_string($n)) {
                                    $setup->orWhere($n, $sw);
                                } else {
                                    $setup->orWhere($sw);
                                }
                            }
                            break;
                        default:
                            return false;
                            break;
                    }
                } else {
                    if (!is_numeric($k) && !is_int($k)) {
                        $setup->where($k . ' = ?', $w);
                    } else {
                        $setup->where($w);
                    }
                }
            }
        } elseif ($where !== '' && !is_array($where)) {
            $setup->where($where);
        }

        return $this;
    }

    // TODO - Support autocommit
    public function transaction($process)
    {
        if (!is_callable($process)) {
            return false;
        }

        $this->onTransaction = true;

        try {
            $this->db()->beginTransaction();

            $process($this);

            if (empty($this->transactionSql)) {
                return false;
            }

            foreach ($this->transactionSql as $statement) {
                $this->db()->exec($statement);
            }

            if ($this->onTransaction) {
                return $this->commit();
            }

            return $this;
        } catch (\Exception $e) {
            if ($this->onTransaction) {
                $this->rollback();
            }

            return false;
        }
    }

    public function commit()
    {
        if (!$this->onTransaction) {
            return false;
        }

        $this->db()->commit();
        $this->onTransaction = false;

        return $this;
    }

    public function rollback()
    {
        if (!$this->onTransaction) {
            return false;
        }

        $this->db()->rollback();
        $this->onTransaction = false;

        return $this;
    }

    public function call($procedure, $params = array(), $fetch = false)
    {
        $wildcards = substr(str_repeat("?,", count($params)), 0, -1);
        $stmt = $this->db()->prepare("CALL {$procedure}({$wildcards});");

        for ($i = 0; $i < count($params); $i++) {
            $type = (is_numeric($params[$i])) ? (is_float($params[$i]) ? PDO::PARAM : PDO::PARAM_INT) : PDO::PARAM_STR;
            $stmt->bindParam(($i+1), $params[$i], $type);
        }

        try {
            if ($fetch) {
                return ($stmt->execute()) ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
            }

            return $stmt->execute();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function validJoins()
    {
        if (count($this->joins) >= 1) {
            $counter = 0;
            foreach ($this->joins AS $type => $join) {
                if (count($join) >= 1) {
                    $counter ++;
                }
            }

            return ($counter <= 0) ? false : true;
        }

        return false;
    }

    public function table($name = array())
    {
        return call_user_func_array([$this, 'from'], func_get_args());
    }

    //Fetch methods
    public function all($fields = array(), $to = self::MODE_OBJECT)
    {
        if (!empty($fields)) {
            $this->select($fields);
        }

        $sql = (string) $this->assemble();

        if (empty($sql)) {
            return false;
        }

        $obj = $this->db()->query($sql);

        if (is_object($obj)) {
            if (1 === $to) {
                $result = $obj->fetchAll(PDO::FETCH_OBJ);
            } else {
                // You ca add more case's ;)
                switch ($to) {
                    case 2:
                        $result = $obj->fetchAll(PDO::FETCH_ASSOC);
                        break;
                    default:
                        $result = $obj->fetchAll($to);
                        break;
                }
            }

            if (is_array($result)) {
                $this->clearSql();

                return $result;
            } else {
                return false;
            }
        }

        return false;
    }

    public function row($fields = array(), $to = self::MODE_OBJECT)
    {
        if (!empty($fields)) {
            $this->select($fields);
        }

        $sql = (string) $this->assemble();

        if (empty($sql)) {
            return false;
        }

        $obj = $this->db()->query($sql);

        if (is_object($obj)) {
            if (1 === $to) {
                $result = $obj->fetch(PDO::FETCH_OBJ);
            } else {
                // You can add more case's ;)
                switch ($to) {
                    case 2:
                        $result = $obj->fetch(PDO::FETCH_ASSOC);
                        break;
                    default: 
                        $result = $obj->fetch($to);
                        break;
                }
                
                $this->clearSql();

                return $result;
            }

            if (is_object($result) || is_array($result)) {
                $this->clearSql();

                return $result;
            } else {
                return false;
            }
        }

        return false;
    }
}