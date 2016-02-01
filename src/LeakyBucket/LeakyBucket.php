<?php

/*
 * Copyright (c) Jeroen Visser <jeroenvisser101@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeakyBucket;

use LeakyBucket\Exception\StorageException;
use LeakyBucket\Storage\StorageInterface;
use SebastianBergmann\Exporter\Exception;

/**
 * Implements the Leak Bucket algorithm.
 *
 * @author Jeroen Visser <jeroenvisser101@gmail.com>
 */
class LeakyBucket
{
    /**
     * Bucket key's prefix.
     */
    const LEAKY_BUCKET_KEY_PREFIX = 'leakybucket:v1:';

    /**
     * Bucket key's postfix.
     */
    const LEAKY_BUCKET_KEY_POSTFIX = ':bucket';

    /**
     * The key to the bucket.
     *
     * @var string
     */
    private $key;

    /**
     * The current bucket.
     *
     * @var array
     */
    private $bucket;

    /**
     * A StorageInterface where the bucket data will be stored.
     *
     * @var StorageInterface
     */
    private $storage;

    /**
     * Array containing default settings.
     *
     * @var array
     */
    private static $defaults = [
        'capacity' => 10,
        'leak'     => 0.33
    ];

    /**
     * The settings for this bucket.
     *
     * @var array
     */
    private $settings = [];

    /**
     * Class constructor.
     *
     * @param string           $key      The bucket key
     * @param StorageInterface $storage  The storage provider that has to be used
     * @param array            $settings The settings to be set
     */
    public function __construct($key, StorageInterface $storage, array $settings = [])
    {
        $this->key     = $key;
        $this->storage = $storage;

        // Make sure only existing settings can be set
        $settings       = array_intersect_key($settings, self::$defaults);
        $this->settings = array_merge(self::$defaults, $settings);

        $this->bucket = $this->get();
    }

    /**
     * Fills the bucket with a given amount of drops.
     *
     * @param int $drops Amount of drops that have to be added to the bucket
     */
    public function fill($drops = 1)
    {
        if (!$drops > 0) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The parameter "%s" has to be an integer greater than 0.', '$drops'
                )
            );
        }

        // Make sure the key is at least zero
        $this->bucket['drops'] = $this->bucket['drops'] ?: 0;

        // Update the bucket
        $this->bucket['drops'] += $drops;

        // Set the timestamp
        $this->touch();

        $this->overflow();
    }

    /**
     * Spills a few drops from the bucket.
     *
     * @param int $drops Amount of drops to spill from the bucket
     */
    public function spill($drops = 1)
    {
        // Make sure the key is at least zero
        $this->bucket['drops'] = $this->bucket['drops'] ?: 0;

        $this->bucket['drops'] -= $drops;

        // Make sure we don't set it less than zero
        if ($this->bucket['drops'] < 0) {
            $this->bucket['drops'] = 0;
        }
    }

    /**
     * Attach aditional data to the bucket.
     *
     * @param array $data The data to be attached to this bucket
     */
    public function setData(array $data)
    {
        $this->bucket['data'] = (array) $data;
    }

    /**
     * Get additional data from the bucket.
     *
     * @return array
     */
    public function getData()
    {
        return $this->bucket['data'];
    }

    /**
     * Gets the total capacity.
     *
     * @return float
     */
    public function getCapacity()
    {
        return (float) $this->settings['capacity'];
    }

    /**
     * Gets the amount of drops inside the bucket.
     *
     * @return float
     */
    public function getCapacityUsed()
    {
        return (float) $this->bucket['drops'];
    }

    /**
     * Gets the capacity that is still left.
     *
     * @return float
     */
    public function getCapacityLeft()
    {
        return (float) $this->settings['capacity'] - $this->bucket['drops'];
    }

    /**
     * Get the leak setting's value.
     *
     * @return float
     */
    public function getLeak()
    {
        return (float) $this->settings['leak'];
    }

    /**
     * Gets the last timestamp set on the bucket.
     *
     * @return mixed
     */
    public function getLastTimestamp()
    {
        return $this->bucket['time'];
    }

    /**
     * Updates the bucket's timestamp
     */
    public function touch()
    {
        $this->bucket['time'] = microtime(true);
    }

    /**
     * Returns true if the bucket is full.
     *
     * @return bool
     */
    public function isFull()
    {
        // Don't overflow
        $this->overflow();

        // Update the leakage
        $this->leak();

        return (ceil((float) $this->bucket['drops']) == $this->settings['capacity']);
    }

    /**
     * Calculates how much the bucket has leaked.
     */
    public function leak()
    {
        // Calculate the leakage
        $elapsed = microtime(true) - $this->bucket['time'];
        $leakage = $elapsed * $this->settings['leak'];

        // Make sure the key is at least zero
        $this->bucket['drops'] = $this->bucket['drops'] ?: 0;
        $this->bucket['drops'] -= $leakage;

        // Make sure we don't set it less than zero
        if ($this->bucket['drops'] < 0) {
            $this->bucket['drops'] = 0;
        }

        // Set the timestamp
        $this->touch();
    }

    /**
     * Removes the overflow if present.
     */
    public function overflow()
    {
        if ($this->bucket['drops'] > $this->settings['capacity']) {
            $this->bucket['drops'] = $this->settings['capacity'];
        }
    }

    /**
     * Saves the bucket to the StorageInterface used.
     */
    public function save()
    {
        $this->set($this->bucket, $this->settings['capacity'] / $this->settings['leak'] * 1.5);
    }

    /**
     * Resets the bucket.
     *
     * @throws StorageException
     */
    public function reset()
    {
        try {
            $this->storage->purge(static::LEAKY_BUCKET_KEY_PREFIX . $this->key . static::LEAKY_BUCKET_KEY_POSTFIX);
        } catch (Exception $ex) {
            throw new StorageException(sprintf('Could not save "%s" to storage provider.', $this->key));
        }
    }

    /**
     * Sets the active bucket's value
     *
     * @param array $bucket The bucket's contents
     * @param int   $ttl    The time to live for the bucket
     *
     * @throws StorageException
     */
    private function set(array $bucket, $ttl = 0)
    {
        try {
            $this->storage->store(static::LEAKY_BUCKET_KEY_PREFIX . $this->key . static::LEAKY_BUCKET_KEY_POSTFIX, $bucket, $ttl);
        } catch (Exception $ex) {
            throw new StorageException(sprintf('Could not save "%s" to storage provider.', $this->key));
        }
    }

    /**
     * Gets the active bucket's value
     *
     * @return array
     *
     * @throws StorageException
     */
    private function get()
    {
        try {
            return $this->storage->fetch(static::LEAKY_BUCKET_KEY_PREFIX . $this->key . static::LEAKY_BUCKET_KEY_POSTFIX);
        } catch (Exception $ex) {
            throw new StorageException(sprintf('Could not save "%s" to storage provider.', $this->key));
        }
    }
}
