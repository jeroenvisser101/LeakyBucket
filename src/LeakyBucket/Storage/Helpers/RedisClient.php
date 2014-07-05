<?php

/*
 * Copyright (c) Jeroen Visser <jeroenvisser101@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeakyBucket\Storage\Helpers;

use Predis\Client;

/**
 * RedisClient
 *
 * @method get($key)
 * @method exists($key)
 * @method set(string $key, string $value, string $param = null, int $ttl = null)
 * @method del(string $key)
 */
class RedisClient extends Client
{
}
