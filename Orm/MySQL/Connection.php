<?php


namespace Orm\MySQL;

use \Closure;

/**
 * 适配器
 * Class Connection
 */
class Connection extends Root
{
    /**
     * 数据库连接池
     * @var array
     */
    protected $_instances = array();

    /**
     * 缓存驱动
     * @var string
     */
    protected $_cacheDrive = array(
        'drive' => 'DbCacheRedis',//'Memcached',
//        'drive' => 'Memcached',//'Memcached',
        'node' => null,
        'child_node' => 'hosts'
    );

    /**
     * 属性
     * @var array
     */
    private $_attribute = array(
        \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
        \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION
    );


    /**
     * 设置指定集合字段
     * @var int
     */
    private $_columnNumber = 0;

    /**
     * 数据库读取模式
     * @var array
     */
    private $_modality = null;

    /**
     * FETCH_CLASS 专用 class name
     * @var null
     */
    protected $_className = null;

    /**
     *
     * @var \PDOStatement
     */
    protected $_PDOStatement;


    /**
     *
     * @var \PDO
     */
    protected $_PDOConn;


    /**
     * 数据类型
     * @var array
     */
    protected $paramType = array(
        'bool' => \PDO::PARAM_BOOL,
        'boolean' => \PDO::PARAM_BOOL,
        'null' => \PDO::PARAM_NULL,
        'integer' => \PDO::PARAM_INT,
        'int' => \PDO::PARAM_INT,
        'string' => \PDO::PARAM_STR,
        'stmt' => \PDO::PARAM_STMT,
        'object' => \PDO::PARAM_LOB,
        'float' => \PDO::PARAM_STR,
        'double' => \PDO::PARAM_STR,
        'output' => \PDO::PARAM_INPUT_OUTPUT
    );

    protected $_database;

    /**
     * 写模式
     */
    const MODALITY_WRITE = 'write';

    /**
     * 读取模式
     */
    const MODALITY_READ = 'read';


    public function __construct($database)
    {
        parent::__construct();
        $this->_database = $database;
    }

    /**
     * 强制设置数据库连接方式
     * @param $modality
     * @return $this
     */
    public function setModality($modality)
    {
        $this->_modality = $modality;
        return $this;
    }

    /**
     * 创建PDO数据库对象
     * @param string $modality (write/read)
     * @return \PDO
     * @throws DebugException
     */
    public function connect($modality = 'write')
    {
        if ($this->_modality != null) $modality = $this->_modality;
        $connectKey = $this->_database . '_' . $modality;
        if (!isset($this->instances[$connectKey])) {
            $server = $this->getServerHost('db');
            $_serverConfig = $server->getServerConfig('database', $this->_database);
            $_dbConfig = $server->getServerConfig('mysql');
            if (!isset($_serverConfig[$modality]) || !in_array($modality, array('write', 'read'))) {
                throw new DebugException('MySQL Not Modality ' . $modality);
            }
            $_server = $server->getServerMap($_serverConfig[$modality], Server::SERVER_SELECT_MODEL_RAND);
            if (empty($_server)) {
                throw new DebugException('Db Server is null.');
            }
            $charset = isset($_dbConfig['charset']) ? $_dbConfig['charset'] : 'UTF8';
            $dsn = 'mysql:host=' . $_server['host'] . ';port=' . $_server['port'] . ';dbname=' . $this->_database;

            $options = array(
                \PDO::ATTR_PERSISTENT => isset($_dbConfig['persistent']) ? $_dbConfig['persistent'] : true,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $charset
            );
            try {
                $_connection = new \PDO($dsn, $_serverConfig['username'], $_serverConfig['passwd'], $options);
                foreach ($this->_attribute as $key => $val) {
                    $_connection->setAttribute($key, $val);
                }
                $this->instances[$connectKey] = $_connection;
            } catch (\PDOException $e) {
                throw new DebugException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }
        $this->_modality = null;
        return $this->instances[$connectKey];
    }

    /**
     * 设置属性
     * @param $attribute
     * @param $value
     * @return $this
     */
    public function setAttribute($attribute, $value)
    {
        $this->_attribute[$attribute] = $value;
        return $this;
    }

    /**
     * 单例事务处理
     * @param callable $callback
     * @return mixed
     * @throws DebugException
     * @throws \Exception
     */
    public function transaction(Closure $callback)
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
        } catch (DebugException $e) {
            $this->rollBack();
        }
        return $result;
    }

    /**
     * 开启事务
     * @throws DebugException
     */
    public function beginTransaction()
    {
        $this->connect()->beginTransaction();
    }

    /**
     * 提交事务
     * @throws DebugException
     */
    public function commit()
    {
        $this->connect()->commit();
    }

    /**
     * 判断是否有事务开启
     * @return bool
     * @throws DebugException
     */
    public function inTransaction()
    {
        return $this->connect()->inTransaction();
    }

    /**
     * 回滚
     * @throws DebugException
     */
    public function rollBack()
    {
        $this->connect()->rollBack();
        
    }

    /**
     * 禁用的命令
     * @var array
     */
    private $disableCommands = array('select', 'insert', 'delete', 'update');
    
    /**
     * 检查禁用参数
     * @param $str
     * @return bool
     */
    protected function checkDisableCommands($str)
    {
        foreach ($this->disableCommands as $command) {
            if (preg_match('/\b'.$command.'\b/i', $str) > 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * 替换SQL的替换参数
     * @param $sql
     * @param array $replaceMap
     * @return string
     * @throws DebugException
     */
    protected function makeReplaceMapToSql($sql, array $replaceMap)
    {
        $_replaceMap = array();
        foreach ($replaceMap as $key => $value) {
            if (!is_string($value)) {
                throw new DebugException('替换参数值不能为数组.');
            }
            if ($this->checkDisableCommands($value) == false) {
                throw new DebugException('替换参数包括了禁用sql方法.');
            }
            $_replaceMap["#" . $key . "#"] = $value;
        }
        return strtr($sql, $_replaceMap);
    }

    /**
     * @param $sql
     * @param $parameterMap
     * @param $replaceMap
     * @return \PDOStatement
     * @throws DebugException
     */
    protected function _statement($sql, $parameterMap, $replaceMap)
    {
        $sql = $this->makeReplaceMapToSql($sql, $replaceMap);
//        var_dump($sql);
        $this->_PDOConn = $conn = $this->connect();
        $this->_PDOStatement = $stmt = $conn->prepare($sql);
        $this->bindValues($parameterMap, $stmt);
        $stmt->execute();
        return $stmt;
    }

    /**
     * 绑定一组数据
     * @param array $params
     * @param \PDOStatement $stmt
     * @throws DebugException
     */
    private function bindValues(array $params, \PDOStatement $stmt)
    {
        foreach ($params as $parameter => $value) {
            if (is_array($value) || is_object($value)) {
                throw new DebugException('Sql绑定参数不能为数组或对象.');
            }
            $dataType = $this->paramType [strtolower(gettype($value))];
            $stmt->bindValue($parameter, $value, $dataType);
        }
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
        $this->_statement($sql, $parameterMap, $replaceMap);
        return new Result($this->_PDOConn, $this->_PDOStatement);
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
        $this->_statement($sql, $parameterMap, $replaceMap);
        return new Result($this->_PDOConn, $this->_PDOStatement);
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
        $this->_statement($sql, $parameterMap, $replaceMap);
        return new Result($this->_PDOConn, $this->_PDOStatement);
    }

    /**
     * 原生Query
     * @param $sql
     * @return \PDOStatement
     * @throws DebugException
     */
    public function query($sql)
    {
        return $this->connect('read')->query($sql);
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
        $stmt = $this->_statement($sql, $parameterMap, $replaceMap);
        $data = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tmp = array_values(array_slice($row, 0, 1));
            $data[$tmp[0]] = $row;
        }
        return $data;
    }

    /**
     * 设置结果集的Class
     * @param null $className
     * @return $this
     */
    public function setFetchClass($className = null)
    {
        $this->_className = (string)$className;
        return $this;
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
        return $this->_statement($sql, $parameterMap, $replaceMap)->fetchAll(\PDO::FETCH_CLASS, $this->_className);
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
        $stmt = $this->_statement($sql, $parameterMap, $replaceMap);
        $stmt->setFetchMode(\PDO::FETCH_CLASS, $this->_className);
        return $stmt->fetch();
    }

    /**
     * 关闭游标，使语句能再次被执行
     * @return $this
     */
    public function closeCursor()
    {
        $this->_PDOStatement->closeCursor();
        return $this;
    }

    /**
     * 返回结果集中的列数
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return int
     */
    public function columnCount($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->_statement($sql, $parameterMap, $replaceMap)->columnCount();
    }

    /**
     * 打印一条 SQL 预处理命令
     * @param $sql
     * @param $parameterMap
     * @param $replaceMap
     * @return bool
     */
    public function debugDumpParams($sql, $parameterMap = array(), $replaceMap = array())
    {
        return $this->_statement($sql, $parameterMap, $replaceMap)->debugDumpParams();
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
        /// connect 非缓存方式入口

        echo "eeeeentry";
        return $this->_statement($sql, $parameterMap, $replaceMap)->fetchAll(\PDO::FETCH_ASSOC);
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
        return $this->_statement($sql, $parameterMap, $replaceMap)->fetch(\PDO::FETCH_ASSOC);
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
        $stmt = $this->_statement($sql, $parameterMap, $replaceMap);;
        $data = array();
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $data[$row[0]] = $row[1];
        }
        return $data;
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
        return $this->_statement($sql, $parameterMap, $replaceMap)->fetchColumn(0);
    }

    /**
     * 字符转义
     * @param $value
     * @return string
     */
    public function quoteStr($value)
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }

    /**
     * 字符转换
     * @param $string
     * @param \PDO $parameterType
     * @return string
     * @throws DebugException
     */
    public function quote($string, $parameterType = \PDO::PARAM_STR)
    {
        return $this->connect('read')->quote($string, $parameterType);
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
        return $this->_statement($sql, $parameterMap, $replaceMap)->fetchColumn($this->_columnNumber);
    }

    /**
     * 取回所有结果行的第一个字段名
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return string
     */
    public function fetchCol($sql, $parameterMap = array(), $replaceMap = array())
    {
    	return $this->_statement($sql, $parameterMap, $replaceMap)->fetchAll(\PDO::FETCH_COLUMN, 0);
    }
    
    /**
     * 设置指定集合数据字段
     * @param $columnNumber
     * @return $this
     */
    public function setFetchColumn($columnNumber)
    {
        $this->_columnNumber = (int)$columnNumber;
        return $this;
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
        return $this->_statement($sql, $parameterMap, $replaceMap)->fetchObject($this->_className);
    }

    /**
     * 获取SQL
     * @param $sql
     * @param array $parameterMap
     * @param array $replaceMap
     * @return mixed|string
     * @throws DebugException
     */
    public function getSQL($sql, $parameterMap = array(), $replaceMap = array())
    {
        $sql = $this->makeReplaceMapToSql($sql, $replaceMap);
        if (strstr($sql, ':')) {
            $matches_s = array();
            foreach ($parameterMap as $key => $val) {
                if (is_string($val)) {
                    $val = "'{$val}'";
                }
                $matches_s[':' . $key] = $val;
            }
            $asSql = strtr($sql, $matches_s);
        } else {
            $asSql = $sql;
            foreach ($parameterMap as $val) {
                $strPos = strpos($asSql, '?');
                if (is_string($val)) {
                    $val = "'{$val}'";
                }
                $asSql = substr_replace($asSql, $val, $strPos, 1);
            }
        }
        return $asSql;
    }

    /**
     * 缓存tag
     * @param $tagName
     * @return $this
     */
    public function tag($tagName)
    {
        $this->_cacheTagName = $tagName;
        return $this;
    }

    /**
     * 缓存key
     * @param string $key
     * @param null $prefix
     * @return $this
     */
    public function key($key)
    {
        $this->_cacheKey = (string)$key;
        return $this;
    }

    /**
     * 缓存时间
     * @param $expire
     * @return $this
     */
    public function expire($expire)
    {
        $this->_cacheExpire = (int)$expire;
        return $this;
    }

    /**
     * 设置缓存
     * @param bool $status
     * @param string $drive
     * @return $this
     */
    public function cache($status = true, array $drive = array())
    {
        if (isset($drive['drive'])) {
            $this->_cacheDrive['drive'] = $drive['drive'];
        }
        if (isset($drive['drive'])) {
            $this->_cacheDrive['node'] = $drive['node'];
        }
        if (isset($drive['drive'])) {
            $this->_cacheDrive['child_node'] = $drive['child_node'];
        }
        $this->_cacheStatus = (bool)$status;
        return $this;
    }

    /**
     * 重置参数
     */
    private function _resetParameter()
    {

    }
} 
