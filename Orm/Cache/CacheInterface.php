<?php


//namespace Orm\Cache;

namespace Orm\Cache;

interface CacheInterface
{
    public function get($key);

    public function add($key, $value, $minutes);

    public function set($key, $value, $minutes);

    public function increment($key, $value = 1);

    public function decrement($key, $value = 1);

    public function delete($key);

    public function tag($tagName);
} 