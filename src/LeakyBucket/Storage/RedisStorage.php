<?php

/*
 * Copyright (c) Jeroen Visser <jeroenvisser101@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeakyBucket\Storage;

use LeakyBucket\Storage\Helpers\RedisClient;

/**
 * Storage for Redis servers.
 *
 * @author Jeroen Visser <jeroenvisser101@gmail.com>
 */
class RedisStorage implements StorageInterface
{
    /**
     * An instance of the RedisClient
     *
     * @var RedisClient
     */
    private static $redis;

    /**
     * Class constructor.
     *
     * @param RedisClient $redis An instance of the RedisClient
     */
    public function __construct(RedisClient $redis = null)
    {
        if ($redis) {
            self::$redis = $redis;
        } elseif (!self::$redis) {
            self::$redis = new RedisClient();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function store($key, $value, $ttl = 0)
    {
        if (isset($ttl) && $ttl > 0) {
            self::$redis->set($key, serialize($value), 'EX', $ttl);
        } else {
            self::$redis->set($key, serialize($value));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($key)
    {
        return unserialize(self::$redis->get($key));
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        return self::$redis->exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function purge($key)
    {
        self::$redis->del($key);
    }
}
