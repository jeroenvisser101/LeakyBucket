<?php

/*
 * Copyright (c) Jeroen Visser <jeroenvisser101@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeakyBucket\tests;

use LeakyBucket\LeakyBucket;
use LeakyBucket\Storage\RedisStorage;
use LeakyBucket\Storage\StorageInterface;

/**
 * LeakyBucketTest
 *
 * Tests all functionality in the LeakyBucket package.
 *
 * @author Jeroen Visser <jeroenvisser101@gmail.com>
 */
class LeakyBucketTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Class constructor.
     *
     * @param StorageInterface $storage Storage to use. Defaults to RedisStorage
     * @param array            $options The bucket options to use
     *
     * @return \LeakyBucket\LeakyBucket
     */
    protected function getCleanBucket(StorageInterface $storage = null, array $options = [])
    {
        $storage = $storage ?: new RedisStorage();
        $bucket  = new LeakyBucket(
            'leakybucket-test',
            $storage,
            $options
        );

        // Reset it
        $bucket->reset();

        return $bucket;
    }

    /**
     * Tests if the bucket has overflow protection.
     */
    public function testOverflow()
    {
        $bucket = $this->getCleanBucket();

        $bucket->fill($bucket->getCapacity() + 1);
        $this->assertEquals($bucket->getCapacityUsed(), $bucket->getCapacity(), 'Bucket is overflowing.');

        $bucket->reset();
    }

    /**
     * Tests if the bucket can detect a full bucket.
     */
    public function testIsFull()
    {
        $bucket = $this->getCleanBucket();

        $capacity = $bucket->getCapacity();
        for ($i = $capacity; $i > 0; $i--) {
            $bucket->fill();
        }

        $this->assertTrue($bucket->isFull(), 'Bucket did not detect that it was full.');

        $bucket->spill();
        $this->assertFalse($bucket->isFull(), 'Bucket did not detect it wasn\'t full.');

        $bucket->reset();
    }

    /**
     * Tests if the bucket leaks correctly.
     */
    public function testLeak()
    {
        $bucket = $this->getCleanBucket(
            null,
            [
                'capacity' => 10,
                'leak'     => 1,
            ]
        );

        $bucket->fill($bucket->getCapacity());
        $this->assertTrue($bucket->isFull(), 'Bucket was not full when it should be.');

        // Wait one second so we can test the leak/sec
        sleep(1);

        $bucket->leak();
        $this->assertFalse($bucket->isFull(), 'Bucket did not leak correctly.');
        $this->assertEquals(round($bucket->getCapacityLeft()), round($bucket->getLeak()), 'Bucket leak was not equal to the leak/sec that was set.');

        $bucket->reset();
    }

    /**
     * Tests if the bucket fills correctly.
     */
    public function testFill()
    {
        $bucket = $this->getCleanBucket();

        $bucket->fill($rand = rand(1, $bucket->getCapacity()));
        $this->assertEquals($bucket->getCapacityUsed(), $rand, "Bucket was not filled with $rand drops, instead was filled with {$bucket->getCapacityUsed()}.");

        $bucket->reset();
    }

    /**
     * Tests if the bucket isn't going below 0.
     */
    public function testUnderflow()
    {
        $bucket = $this->getCleanBucket();

        $bucket->fill($bucket->getCapacity() / 1.5);

        $bucket->spill($bucket->getCapacity() / 1.2);

        $this->assertEquals($bucket->getCapacityUsed(), 0, 'Bucket underflows.');
    }

    /**
     * Tests if the bucket's timestamp is correctly set.
     */
    public function testLastTimestamp()
    {
        $bucket = $this->getCleanBucket();

        $timestamp = microtime(true);
        $bucket->touch();

        $this->assertEquals(round($timestamp, 3), round($bucket->getLastTimestamp(), 3), 'Timestamp is not updated correctly.');
    }
}
