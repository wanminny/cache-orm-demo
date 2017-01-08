<?php
/**
 * Created by PhpStorm.
 * User: Zip
 * Date: 14/12/7
 * Time: 上午12:11
 */

namespace Orm\MySQL;

use Orm\MySQL\DebugException;
//use Hood\Debug\DebugException;

class Server extends Root
{
    /**
     * 服务器随机选择模式
     * @var Integer
     */
    const SERVER_SELECT_MODEL_RAND = 1;

    /**
     * 服务器选择静态
     * @var Integer
     */
    const SERVER_SELECT_MODEL_STATIC = 2;

    /**
     * @var string
     */
    protected $_sectionSeparator = ':';

    /**
     * 字符分割
     * @var string
     */
    protected $_nestSeparator = '.';

    /**
     * 服务器选择模式
     * @var int
     */
    private $serverSelectModel = 1;

    /**
     * ini 数组
     * @var array
     */
    private $iniArray = array();

    /**
     * 检查服务状态
     * @var bool
     */
    private $checkServerStatus = true;


    public function __construct($filename)
    {
        $this->iniArray = $this->_loadIniFile($filename);
    }

    /**
     * 设置服务器选择模式
     * @param $model
     * @return $this
     */
    public function setSelectModel($model)
    {
        $this->serverSelectModel = $model;
        return $this;
    }

    /**
     * 获取服务器地址
     * @param $section
     * @param $node
     * @param int $model
     * @return array|mixed
     * @throws DebugException
     */
    public function getServerConfig($section, $node = null)
    {
        $sectionArray = $this->_processSection($section);
        if ($node == null) {
            return $sectionArray;
        } elseif (isset($sectionArray[$node])) {
            return $sectionArray[$node];
        }
        return array();

    }

    /**
     * 选择服务器
     * @param $servers
     * @param $model
     * @return array|mixed
     * @throws DebugException
     */
    public function getServer($servers, $model = 1)
    {
        $serverArray = $this->_parseServer($servers);
        switch ($model) {
            case 1:
                if (count($serverArray) <= 1) {
                    $servers = $serverArray;
                } else {
                    $servers = $this->_randServer($serverArray);
                }
                break;
            case 2:
                $servers = $serverArray;
                break;
            default:
                throw new DebugException('Server select model not ' . $model);
        }
        return $servers;
    }

    /**
     * 获取服务器Map
     * @param $section
     * @param $node
     * @param int $model
     * @return array
     * @throws DebugException
     */
    public function getServerMap($servers, $model = 1)
    {
        $_servers = $this->getServer($servers, $model);
        if (empty($_servers)) {
            return array();
        }
        $_serversMap = array();
        if ($model == 1 && count($_servers) == 1) {
            $_serversMap = $this->_processHost($_servers[0]);
        } elseif ($model == 2) {
            foreach ($_servers as $key => $host) {
                $_serversMap[] = $this->_processHost($host);
            }
        }
        return $_serversMap;
    }

    /**
     * 获取所有配置Map
     * @param $section
     * @param $node
     * @return array
     */
    public function getSectionConfig($section, $node)
    {
        $servers = $this->getServerConfig($section, $node);
        $_serversMap = array();
        foreach ($servers as $key => $host) {
            $_serversMap[] = $this->_processHost($host);
        }
        return $_serversMap;
    }

    /**
     * 随机&权重随机
     * @param array $server
     * @return mixed
     */
    private function _randServer(array $server)
    {
        if (substr_count($server[0], ':') == 2) {
            $_domain = array();
            foreach ($server as $key => $domain) {
                $_domain[] = $this->_processHost($domain);
            }
            $_server = $this->_countWeight($_domain);
            $_server = $_server['host'] . ':' . $_server['port'] . ':' . $_server['weight'];
        } else {
            $_server = $server[array_rand($server)];
        }
        return $_server;
    }

    /**
     * 权重计算
     * @param array $data
     * @return mixed
     */
    private function _countWeight(array $data)
    {
        $weight = 0;
        $tempArray = array();
        foreach ($data as $v) {
            $weight += (int)$v['weight'];
            for ($i = 0; $i < $v['weight']; $i++) {
                $tempArray[] = $v;//放大数组
            }
        }
        $int = mt_rand(0, $weight - 1);//获取一个随机数
        return $tempArray[$int];
    }

    /**
     * 解析host
     * @param $domain
     * @return array
     */
    private function _processHost($domain)
    {
        $_hostMap = array();
        $domainArray = explode(':', $domain);
        switch (count($domainArray)) {
            case 2 :
                list($host, $port) = $domainArray;
                $_hostMap = array(
                    'host' => (string)$host,
                    'port' => (int)intval($port)
                );
                break;
            case 3 :
                list($host, $port, $weight) = $domainArray;
                $_hostMap = array(
                    'host' => (string)$host,
                    'port' => (int)intval($port),
                    'weight' => (int)intval($weight)
                );
                break;
        }
        return $_hostMap;
    }

    /**
     * 设置检测服务状态
     * @param $check
     * @return $this
     */
    public function setCheckServer($check)
    {
        $this->checkServerStatus = $check;
        return $this;
    }

    /**
     * 检查服务
     * @param array $servers
     * @return array
     */
    public function checkServer(array $servers)
    {
        $_servers = array();
        foreach ($servers as $key => $val) {
            $status = $this->ping($val['host'], $val['port']);
            if ($status > 0) {
                $_servers[] = $val;
            }
        }
        return $_servers;
    }

    /**
     * 解析Server
     * @param $ips
     * @return array|mixed
     */
    protected function _parseServer($serverString)
    {
        return explode(',', str_replace(array(
            ' ',
            "\n"
        ), '', $serverString));
    }

    public function getSection($section)
    {
        return $this->_processSection($section);
    }

    public function get($name, $section = null)
    {
        return $this->_processSection($section);
    }

    /**
     * 加载ini
     * @param $filename
     * @return array
     * @throws DebugException
     */
    private function _loadIniFile($filename)
    {
        $loaded = $this->_parseIniFile($filename);
        $iniArray = array();
        foreach ($loaded as $key => $data) {
            $pieces = explode($this->_sectionSeparator, $key);
            $thisSection = trim($pieces[0]);
            switch (count($pieces)) {
                case 1:
                    $iniArray[$thisSection] = $data;
                    break;
                case 2:
                    $extendedSection = trim($pieces[1]);
                    $iniArray[$thisSection] = array_merge(array(';extends' => $extendedSection), $data);
                    break;
                default:
                    throw new DebugException("Section '$thisSection' may not extend multiple sections in $filename");
            }
        }
        return $iniArray;
    }

    /**
     * 解析ini
     * @param $filename
     * @return array
     */
    private function _parseIniFile($filename)
    {
//        $this->debugError()->setUserDebug();
        $iniArray = parse_ini_file($filename, true);
        if ($iniArray == false) {
            throw new DebugException(' Ini file ' . $filename . ' not find. ');
        }
//        $this->debugError()->restoreUserDebug();
        return $iniArray;
    }

    /**
     * 解析节点
     * @param $section
     * @return array
     * @throws DebugException
     */
    protected function _processSection($section)
    {
        $config = array();
        $thisSection = array();
        if (isset($this->iniArray[$section])) {
            $thisSection = $this->iniArray[$section];
        }
        foreach ($thisSection as $key => $value) {
            if (strtolower($key) == ';extends') {
                if (isset($this->iniArray[$value])) {
                    $config = $this->iniArray[$value] + $config;
                } else {
                    throw new DebugException("Parent section '$section' cannot be found");
                }
            } else {
                $config = $this->_processKey($config, $key, $value);
            }
        }
        return $config;
    }

    /**
     * 解析键值
     * @param array $config
     * @param $key
     * @param string $val
     * @return array
     */
    protected function _processKey(array $config, $key, $val = '')
    {
        if (strpos($key, $this->_nestSeparator) !== false) {
            $pieces = explode($this->_nestSeparator, $key, 2);
            if (strlen($pieces[0]) && strlen($pieces[1])) {
                if (!isset($config[$pieces[0]])) {
                    if ($pieces[0] === '0' && !empty($config)) {
                        $config = array($pieces[0] => $config);
                    } else {
                        $config[$pieces[0]] = array();
                    }
                }
                $config[$pieces[0]] = $this->_processKey($config[$pieces[0]], $pieces[1], $val);
            }
        } else {
            $config[$key] = $val;
        }
        return $config;
    }

    /**
     * ping
     * @param $domain
     * @param $port
     * @param int $timeout
     * @return float|int|mixed
     */
    public function ping($domain, $port, $timeout = 3)
    {
        $starttime = microtime(true);
        $file = @fsockopen($domain, $port, $errno, $errstr, $timeout);
        $stoptime = microtime(true);
        $status = -1;
        if ($file) {
            stream_set_blocking($file, false);//非堵塞
            stream_set_timeout($file, 3);
            fclose($file);
            $status = ($stoptime - $starttime) * 1000;
            $status = floor($status);
        }
        return $status;
    }
}