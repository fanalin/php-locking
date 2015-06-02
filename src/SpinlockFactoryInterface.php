<?php

namespace fanalin\Spinlock;

/**
 * Interface for a factory for Spinlocks.
 */
interface SpinlockFactoryInterface
{
    /**
     * create spinlock.
     *
     * @param string $resource a string identifying the resource to lock
     *
     * @return SpinlockInterface
     */
    public function get($resource);
}
