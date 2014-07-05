<?php

/*
 * Copyright (c) Jeroen Visser <jeroenvisser101@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeakyBucket\Storage;

/**
 * StorageInterface
 *
 * The interface for a datastorage used by LeakyBucket.
 */
interface StorageInterface
{
    /**
     * Stores a given $value in a container referenced by $key.
     *
     * @param string $key   The key that references the value
     * @param mixed  $value The value that has to be stored inside of $key
     * @param int    $ttl   The time to live for the value (in seconds)
     *
     * @return void
     */
    public function store($key, $value, $ttl = 0);

    /**
     * Retrieves a value that belongs to a given $key.
     *
     * @param string $key The key that references the value that has to be retrieved
     *
     * @return mixed
     */
    public function fetch($key);

    /**
     * Checks if $key exists.
     *
     * @param string $key The key that has to be checked
     *
     * @return bool
     */
    public function exists($key);

    /**
     * Removes an entry from the dataset.
     *
     * @param string $key The key from which the value has to be removed
     *
     * @return void
     */
    public function purge($key);
}
