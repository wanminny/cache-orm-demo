<?php
/**
 * Created by PhpStorm.
 * User: Zip
 * Date: 14/11/23
 * Time: 上午1:39
 */

namespace Orm\Cache;

//use Hood\Core\Root;
use Orm\MySQL\Root;
//use Hood\Debug\DebugException;

class Memcached extends Root implements CacheInterface
{
    private $mcInstances = array();

    private $persistentIDs = array();

    private $timeout = 150;

    private $section = 'memcached';

    private $node = 'servers';

    private $tagName = '';

    private $prefix = '';

    private $persistentID = 'hood.cache';

    private $childNodes = 'hosts';

    public function __construct($prefix = '', $persistentID = 'hood.cache')
    {
        parent::__construct();
        $this->prefix = $prefix;
        $this->persistentIDs[] = $this->persistentID = $persistentID;
    }

    /**
     * 设置子节点
     * @param $childNode
     * @return $this
     */
    public function setChildNodes($childNode)
    {
        $this->childNodes = $childNode;
        return $this;
    }

    /**
     * 设置前缀
     * @param $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 设置共享连接ID
     * @param $persistentID
     * @return $this
     */
    public function setPersistentID($persistentID)
    {
        $this->persistentID = $persistentID;
        return $this;
    }

    /**
     * @param $persistentID
     * @return \Memcached
     * @throws \Hood\Debug\DebugException
     */
    private function init()
    {
        if (isset($this->mcInstances[$this->persistentID])) {
            $mc = $this->mcInstances[$this->persistentID];
        } else {
            $instance = new \Memcached($this->persistentID);
            $instance->setOption(\Memcached::OPT_PREFIX_KEY, $this->prefix);
            $instance->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);//设置为md5并且分布算法将会 采用带有权重的一致性hash分布
            $instance->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $this->timeout);
            if (count($instance->getServerList()) < 1) {
                $server = $this->getServerHost('cache');
                $_serverHosts = $server->getServerConfig($this->section, $this->node);
                if (empty($_serverHosts[$this->childNodes])) {
                    throw new DebugException('Memcache Host Config is Null.');
                }
                $mcServers = $this->_makeHosts($server->getServer($_serverHosts[$this->childNodes], 2));
                $instance->addServers($mcServers);
                unset($mcServers);
            }
            $this->mcInstances[$this->persistentID] = $mc = $instance;
        }
        return $mc;
    }


    /**
     * 设置mc配置的块
     * @param $section
     * @return $this
     */
    public function setSection($section)
    {
        $this->section = $section;
        return $this;
    }

    /**
     * 设置mc配置的块节点
     * @param $node
     * @return $this
     */
    public function setNode($node = null)
    {
        if ($node != null) $this->node = $node;
        return $this;
    }

    /**
     * 组织host
     * @param array $hosts
     * @return array
     */
    private function _makeHosts(array $hosts)
    {
        $_server = array();
        foreach ($hosts as $key => $val) {
            $_server[] = explode(':', $val);
        }
        return $_server;
    }

    /**
     * 构建tag
     * @param bool $mode
     * @return string
     */
    private function _makeTag($mode = false)
    {
        if (empty($this->tagName)) return '';
        $_tagVal = $this->init()->get($this->tagName);
        if (empty($_tagVal) && $mode == true) {
            $_tagVal = md5(microtime() . mt_rand() . uniqid());
            $this->init()->set($this->tagName, $_tagVal, 0);
        }
        unset($this->tagName);
        return empty($_tagVal) ? '' : $_tagVal . '.';
    }

    /**
     * 检索一个元素
     * @param $key
     * @param callable $cache_cb
     * @param float $cas_token
     * @return mixed
     */
    public function get($key, $cacheCb = null, &$casToken = null)
    {
        return $this->init()->get($this->_makeTag() . $key, $cacheCb, $casToken);
    }

    /**
     *  向一个新的key下面增加一个元素
     * @param $key
     * @param $value
     * @param $expiration
     * @return bool
     */
    public function add($key, $value, $expiration = 0)
    {
        return $this->init()->add($this->_makeTag(true) . $key, $value, $expiration);
    }

    /**
     * 向已存在元素后追加数据
     * @param $key
     * @param $value
     * @return bool
     */
    public function append($key, $value)
    {
        return $this->init()->append($this->_makeTag(true) . $key, $value);
    }

    /**
     * 比较并交换值
     * @param $casToken
     * @param $key
     * @param $value
     * @param int $expiration
     * @return bool
     */
    public function cas($casToken, $key, $value, $expiration = 0)
    {
        return $this->init()->cas($casToken, $this->_makeTag(true) . $key, $value, $expiration);
    }

    /**
     * 减小数值元素的值
     * @param $key
     * @param int $offset
     * @return int
     */
    public function decrement($key, $offset = 1)
    {
        return $this->init()->decrement($this->_makeTag() . $key, $offset);
    }

    /**
     * @param $key
     * @param int $time
     * @return bool
     */
    public function delete($key, $time = 0)
    {
        return $this->init()->delete($this->_makeTag() . $key, $time);
    }

    /**
     * 删除多个数据
     * @param array $keys
     * @param int $time
     * @return bool
     */
    public function deleteMulti(array $keys, $time = 0)
    {
        return $this->init()->deleteMulti($this->_makeMultiKey($keys), $time);
    }

    /**
     * 组合多key 数据
     * @param $keys
     * @return array
     */
    private function _makeMultiKey($keys, $mode = false)
    {
        $_keys = array();
        $tag = $this->_makeTag($mode);
        foreach ($keys as $key) {
            $_keys[] = $tag . $key;
        }
        return $_keys;
    }

    /**
     * 请求多个元素
     * @param array $keys
     * @param null $withCas
     * @param callable $valueCb
     * @return bool
     */
    public function getDelayed(array $keys, $withCas = null, callable $valueCb = null)
    {
        return $this->init()->getDelayed($this->_makeMultiKey($keys), $withCas, $valueCb);
    }

    /**
     * 抓取所有剩余的结果
     * @return array
     */
    public function fetchAll()
    {
        return $this->init()->fetchAll();
    }

    /**
     * 检索多个元素
     * @param array $keys
     * @param array $cas_tokens
     * @param null $flags
     * @return mixed
     */
    public function getMulti(array $keys, array &$casTokens = null, $flags = null)
    {
        return $this->init()->getMulti($this->_makeMultiKey($keys), $casTokens, $flags);
    }

    /**
     * 增加数值元素的值
     * @param $key
     * @param int $offset
     * @param int $initialValue
     * @param int $expiry
     * @return int
     */
    public function increment($key, $offset = 1, $initialValue = 0, $expiry = 0)
    {
        return $this->init()->increment($this->_makeTag() . $key, $offset, $initialValue, $expiry);
    }

    /**
     * 检查memcache是否长连接
     * @return bool
     */
    public function isPersistent()
    {
        return $this->init()->isPersistent();
    }

    /**
     * 设置
     * @param $key
     * @param $value
     * @param int $expiration
     * @return bool
     */
    public function set($key, $value, $expiration = 0)
    {
        return $this->init()->set($this->_makeTag(true) . $key, $value, $expiration);
    }

    /**
     * 设置多个数据
     * @param array $items
     * @param int $expiration
     * @return bool
     */
    public function setMulti(array $items, $expiration = 0)
    {
        $_items = array();
        $tag = $this->_makeTag(true);
        foreach ($items as $key => $val) {
            $_items[$tag . $key] = $val;
        }
        return $this->init()->setMulti($_items, $expiration);
    }

    /**
     * 设置tag
     * @param $tagName
     * @return $this
     */
    public function tag($tagName)
    {
        $this->tagName = $tagName;
        return $this;
    }

    /**
     * 清除服务列表
     * @return $this
     */
    public function resetServerList()
    {
        $this->init()->resetServerList();
        return $this;
    }
}