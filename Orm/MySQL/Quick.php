<?php

namespace Orm\MySQL;

class Quick extends Connection
{
    /**
     *
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @param $fun
     * @return Result
     */
    private function _execute($sql, $parameterMap = array(), $replaceMap = array())
    {
        if ($this->_cacheStatus === true) {
            if (!empty($this->_cacheTagName)) {
                $this->dbCache()->tag($this->_cacheTagName)->delete($this->_cacheKey);
            } else {
                $this->dbCache()->delete($this->_cacheKey);
            }
            $this->_resetParameter();
        }
        $this->_statement($sql, $parameterMap, $replaceMap);
        return new Result($this->_PDOConn, $this->_PDOStatement);
    }

    /**
     * 快速取数据
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @param $fun
     * @return mixed
     */
    private function quicken($sql, $parameterMap = array(), $replaceMap = array(), $fun)
    {

        if ($this->_cacheStatus === true) {
            if (empty($this->_cacheKey)) {
                $this->_cacheKey = md5($this->getSQL($sql, $parameterMap, $replaceMap));
            }
            if (!empty($this->_cacheTagName)) {
                $cacheData = $this->dbCache()->tag($this->_cacheTagName)->get($this->_cacheKey);
            } else {
                $cacheData = $this->dbCache()->get($this->_cacheKey);
            }
            if (!empty($cacheData)) {
                $this->_resetParameter();
                return $cacheData;
            }
        }
        $cacheData = parent::$fun($sql, $parameterMap, $replaceMap);
//        var_dump($cacheData);
        if ($this->_cacheStatus === true) {
            if (!empty($this->_cacheTagName)) {
                $this->dbCache()->tag($this->_cacheTagName)->set($this->_cacheKey, $cacheData, $this->_cacheExpire);
            } else {
                $this->dbCache()->set($this->_cacheKey, $cacheData, $this->_cacheExpire);
            }
        }
        $this->_resetParameter();
        return $cacheData;
    }

    /**
     * 取回结果集中所有字段的值,作为关联数组返回  第一个字段作为码
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return array
     */
    public function fetchAssoc($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->quicken($sql, $parameterMap, $replaceMap, __FUNCTION__);
    }


    /**
     * 取回结果集中所有字段的值以对象形式输出,按自定义class
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return array
     */
    public function fetchClass($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->quicken($sql, $parameterMap, $replaceMap, __FUNCTION__);
    }

    /**
     * 获取所有数据集合
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return array
     */
    public function fetchAll($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->quicken($sql, $parameterMap, $replaceMap, __FUNCTION__);
    }

    /**
     * 获取一条数据集合
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return mixed
     */
    public function fetchRow($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->quicken($sql, $parameterMap, $replaceMap, __FUNCTION__);
    }

    /**
     * 取回一个相关数组
     * 第一个字段值为key
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return array
     */
    public function fetchPairs($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->quicken($sql, $parameterMap, $replaceMap, __FUNCTION__);
    }

    /**
     * 取回第一个字段值
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return string
     */
    public function fetchOne($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->quicken($sql, $parameterMap, $replaceMap, __FUNCTION__);
    }

    /**
     * 获取指定字段值
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return string
     */
    public function fetchColumn($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->quicken($sql, $parameterMap, $replaceMap, __FUNCTION__);
    }

    /**
     * 取回结果集中所有字段的值 以对象形式输出
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return mixed
     */
    public function fetchObject($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->quicken($sql, $parameterMap, $replaceMap, __FUNCTION__);
    }
    
    /**
     * 取回所有结果行的第一个字段名
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return array
     */
    public function fetchCol($sql, $parameterMap = array(), $replaceMap = array())
    {
    	return $this->quicken($sql, $parameterMap, $replaceMap, __FUNCTION__);
    }
    
    /**
     * 插入数据
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return Result
     */
    public function insert($sql, array $parameterMap = array(), array $replaceMap = array())
    {
        return $this->_execute($sql, $parameterMap, $replaceMap);
    }

    /**
     * 更新数据
     * @param string $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return Result
     */
    public function update($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->_execute($sql, $parameterMap, $replaceMap);
    }

    /**
     * 删除数据
     * @param string $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return Result
     */
    public function delete($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->_execute($sql, $parameterMap, $replaceMap);
    }

    /**
     * 取回结果集中所有字段的值以对象形式输出,按自定义class
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return array
     */
    public function fetchClassRow($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->quicken($sql, $parameterMap, $replaceMap, __FUNCTION__);
    }

    /**
     * db cache 对象
     * @return \Orm\Cache\DbCacheRedis
     */
    private function dbCache()
    {
        $cacheDriveName = $this->_cacheDrive['drive'];
        $cls = "\\Orm\\Cache\\".$cacheDriveName;
        return new $cls($this->_cacheDrive['node'], $this->_cacheDrive['child_node']);
    }

    private function _resetParameter()
    {
        $this->_cacheTagName = '';
        $this->_cacheKey = '';
        $this->_className = '';
        $this->_cacheStatus = true;
    }
} 