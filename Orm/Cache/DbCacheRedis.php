<?php


namespace Orm\Cache;
use Orm\MySQL\Root;
use Orm\MySQL\DebugException;
use \Redis;

class DbCacheRedis extends Root implements CacheInterface
{
	private $instances = array();
	
	private $servers = array();
	
	private $hashServers = array();
	//算法
	private $algorithm = 'consistent';
	
	private $timeout = 1.5;
	
	private $section = 'redis';
	
	private $node = 'servers';
	
	private $tagName = '_tag_';
	
	private $prefix = '_redis_';
	
	private $persistentID = 'hood.cache';
	
	private $childNodes = 'hosts';
	
	public function __construct($prefix = '_redis_', $persistentID = 'hood.cache')
	{
		parent::__construct();
		$this->prefix = $prefix;
		$this->persistentID = $persistentID;
	}
    
    public function __call($name, $arguments) {
        return call_user_func_array(array($this->init(),$name), $arguments);
    }
	
	/**
	 * 设置前缀
	 * 
	 * @param $prefix
	 * @return $this
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
		return $this;
	}
	
	/**
	 * 设置redis配置的块节点
	 * 
	 * @param $node
	 * @return $this
	 */
	public function setNode($node = null)
	{
		if ($node != null) $this->node = $node;
		return $this;
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
	
	public function init($key = '')
	{
		$key = empty($key) ? $this->_makeTag() : $key;
		if (isset($this->instances[$this->persistentID])) 
		{
			$serverHost = $this->_getChoiceServer($this->servers, $key);
		} 
		else 
		{
			$server = $this->getServerHost('cache');
			$_serverHosts = $server->getServerConfig($this->section, $this->node);
			if (empty($_serverHosts[$this->childNodes]))  
			{
				throw new DebugException('redis Host Config is Null.');
			}
			$this->servers = $server->getServer($_serverHosts[$this->childNodes], 2);
			$serverHost = $this->_getChoiceServer($this->servers, $key);
		}
		if(empty($this->instances[$this->persistentID][$serverHost]))
		{
			$serverMap = array_combine(array('host','port'), explode(':', $serverHost));
			$redis = new Redis();
			$redis->connect($serverMap['host'], $serverMap['port'], $this->timeout);
			$this->instances[$this->persistentID][$serverHost] = $redis;
		}
		if($this->instances[$this->persistentID][$serverHost]->ping() == '+PONG')
		{
			return $this->instances[$this->persistentID][$serverHost];
		}
	}
	
	/**
	 * 获取数据
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		try
		{

			$val = $this->init()->get($this->_makeTag($key));
			return is_numeric($val) ? $val : unserialize($val);
		} catch (\Exception $e)
		{
			return false;
		}
	}

	/**
	 * 添加数据
	 * 
	 * @param string $key
	 * @param mixed $value
   	 * @param int $expire
	 */
    public function add($key, $value, $expire = 86400)
    {
    	try 
    	{
	    	$instance = $this->init();
	    	$newKey = $this->_makeTag($key);
	    	if($instance->exists($newKey))
	    	{
	    		return true;
	    	}
	    	$instance->sAdd($this->_makeTag(), $newKey);
	    	return $instance->setex($newKey, $expire, serialize($value));
    	} catch (\Exception $e)
    	{
    		return false;
    	}
    }
    
    /**
     * 设置数据
     * 
     * @param string $key
     * @param mixed $value
     * @param int $expire
     */
    public function set($key, $value, $expire = 86400)
    {
    	try 
    	{
	    	$instance = $this->init();
	    	$newKey = $this->_makeTag($key);
	    	$instance->sAdd($this->_makeTag(), $newKey);
	    	return $instance->setex($newKey, $expire, serialize($value));
    	} catch (\Exception $e)
    	{
    		return false;
    	}
    }
    
    /**
     * 累计
     * 
     * @param string $key
     * @param integer $value
     */
    public function increment($key, $value = 1)
    {
    	try
    	{
    		return $this->init()->incrBy($this->_makeTag($key), $value);
    	}catch(\Exception $e)
    	{
    		return false;
    	}
    }
    
    /**
     * 递减
     * 
     * @param string $key
     * @param integer $value
     */
    public function decrement($key, $value = 1)
    {
    	try
    	{
    		return $this->init()->decrBy($this->_makeTag($key), $value);
    	} catch(\Exception $e)
    	{
    		return false;
    	}
    }
    
    /**
     * 删除数据
     * 
     * @param string $key
     */
    public function delete($key)
    {
    	try
    	{
	    	$instance = $this->init();
	    	if(!empty($key))
	    	{
	    		return $instance->del($this->_makeTag($key));
	    	}
	    	else 
	    	{
	    		foreach($instance->sMembers($this->_makeTag()) as $key)
	    		{
	    			$instance->del($key);
	    		}
	    		return true;
	    	}
    	} catch(\Exception $e)
    	{
    		return false;
    	}
    }
    
    
    /**
     * 设置tag
     * 
     * @param string $tagName
     */
    public function tag($tagName)
    {
    	$this->tagName = $tagName;
    	return $this;
    }
    
    /**
     * 构建tag
     * 
     * @param string $key
     * @return string
     */
    private function _makeTag($key = '')
    {
    	return $this->prefix.$this->tagName.$key;
    }
    
    /**
     * 获取服务器
     * 
     * @param array $servers
     * @param string $key
     * @return array
     */
    private function _getChoiceServer($servers, $key)
    {
    	$server = array();
    	switch ($this->algorithm)
    	{
    		case 'mod':
    			$server = $this->_getModServer($servers, $key);
    		break;
    		case 'consistent':
    			$server = $this->_getConsistentHashServer($servers, $key);	
    		break;
    	}
    	return $server;
    }
    
    /**
     * 获取一致性hash服务列表
     * 
     * @param array $servers
     * @param array $key
     * @return int
     */
    private function _getConsistentHashServer($servers, $key)
    {
    	$hash = sprintf('%u', crc32($key));
    	$len = count($servers);
    	if ($len == 0) 
    	{
    		return false;
    	}
    	$hashServers = $this->_getMakeHashServer($servers);
    	$keys  = array_keys($hashServers);
    	$values = array_values($hashServers);
    	// 如果不在区间内，则返回最后一个server
    	if ($hash <= $keys[0] || $hash >= $keys[$len - 1]) 
    	{
    		return $values[$len - 1];
    	}
    	foreach ($keys as $key => $pos) 
    	{
    		$next_pos = null;
    		if (isset($keys[$key + 1])) 
    		{
    			$next_pos = $keys[$key + 1];
    		}
    		if (is_null($next_pos))  
    		{
    			return $values[$key];
    		}
    		// 区间判断
    		if ($hash >= $pos && $hash <= $next_pos) 
    		{
    			return $values[$key];
    		}
    	}
    }
    
    /**
     * 生成hash列表
     * 
     * @param array $servers
     * @return array
     */
    private function _getMakeHashServer($servers) 
    {
    	if(empty($this->hashServers))
    	{
    		foreach($servers as $server) 
    		{
    			$hash = sprintf('%u', crc32($server));
    			$this->hashServers[$hash] = $server;
    		}
    		ksort($this->hashServers);
    	}
    	return $this->hashServers;
    }
    
    /**
     * 获取
     * 
     * @param array $servers
     * @param string $key
     * @return array
     */
    private function _getModServer($servers, $key)
    {
    	$val = sprintf('%u', crc32($key)) % count($servers);
    	return $servers[$val];
    }
}