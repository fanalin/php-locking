<?php

namespace fanalin\Spinlock;

/**
 * Interface for a Spinlock.
 */
interface SpinlockInterface
{
    /**
     * acquire spinlock.
     *
     * If method returns successfully, the spinlock is acquired.
     * If any error occurs while waiting for the spinlock (e.g.: maximum waiting
     *  time exceeded) a SpinlockException is thrown
     *
     * @throws SpinlockException if spinlock could not be acquired
     */
    public function acquire();

    /**
     * release spinlock
     *
     * @throws SpinlockException if spinlock could not be released
     */
    public function release();
}
