<?php

namespace fanalin\Spinlock\MemcacheSpinlock;

use fanalin\Spinlock\SpinlockFactoryInterface;
use fanalin\Spinlock\SpinlockInterface;

/**
 * MemcacheSpinlockFactory is a factory for creating MemcacheSpinlock's.
 */
class MemcacheSpinlockFactory implements SpinlockFactoryInterface
{
    /**
     * memcache connection.
     *
     * @var \Memcached
     */
    private $memcached;

    /**
     * Maximum Time-To-Life of a spinlock.
     * The spinlock will be auto-released by memcached if this time exceeds.
     * 0 means, that the spinlock will live forever if not manually released.
     *
     * @var int
     */
    private $maximumTtl;

    /**
     * Time in milliseconds between to "spins" while trying to acquire the spinlock.
     *
     * @var int
     */
    private $retryInterval;

    /**
     * Maximum number of tries before giving up when acquiring the spinlock.
     * 0 means: try forever.
     *
     * @var int
     */
    private $maximumTries;

    /**
     * constructor.
     *
     * @param \Memcached $memcached     memcache connection to use
     * @param int        $maximumTtl    maximum time-to-life of a spinlock. 0 means forever
     * @param int        $retryInterval time (in ms) after which a retry to set the spinlock will be started
     * @param int        $maximumTries  maximum number of tries to acquire spinlock. 0 means infinity
     */
    public function __construct(\Memcached $memcached, $maximumTtl, $retryInterval, $maximumTries)
    {
        $this->memcached = $memcached;
        $this->maximumTtl = $maximumTtl;
        $this->retryInterval = $retryInterval;
        $this->maximumTries = $maximumTries;
    }

    /**
     * create spinlock.
     *
     * @param string $resource a string identifying the resource to lock
     *
     * @return SpinlockInterface
     */
    public function get($resource)
    {
        return new MemcacheSpinlock(
            $resource,
            $this->memcache,
            $this->maximumTtl,
            $this->retryInterval,
            $this->maximumTries
        );
    }
}
