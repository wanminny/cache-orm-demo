<?php

namespace Orm\MySQL;

/**
 * 结果
 * Class Result
 */
class Result
{
    /**
     *
     * @var \PDO
     */
    private $_PDOConn;

    /**
     * @var \PDOStatement
     */
    private $_PDOStatement;

    /**
     * @param $PDOConn
     * @param $PDOStatement
     */
    public function __construct($PDOConn, $PDOStatement)
    {
        $this->_PDOConn = $PDOConn;
        $this->_PDOStatement = $PDOStatement;
    }

    /**
     * 获取插入的ID
     * @param null $name
     * @return int
     */
    public function lastInsertId($name = null)
    {
        return $this->_PDOConn->lastInsertId($name);
    }

    /**
     * 在一个多行集语句句柄中推进到下一个行集
     * @return bool
     */
    public function nextRowset()
    {
        return $this->_PDOStatement->nextRowset();
    }

    /**
     * 获取更新数量
     * @return int
     */
    public function rowCount()
    {
        return $this->_PDOStatement->rowCount();
    }

    /**
     * 获取statement Error Code
     * @return string
     */
    public function statementErrorCode()
    {
        return $this->_PDOStatement->errorCode();
    }

    /**
     * 获取statement Error Info
     * @return array
     */
    public function statementErrorInfo()
    {
        return $this->_PDOStatement->errorInfo();
    }

    /**
     * 获取pdo error code
     * @return mixed
     */
    public function pdoErrorCode()
    {
        return $this->_PDOConn->errorCode();
    }

    /**
     * 获取PDO Error Info
     * @return array
     */
    public function pdoErrorInfo()
    {
        return $this->_PDOConn->errorInfo();
    }
} 