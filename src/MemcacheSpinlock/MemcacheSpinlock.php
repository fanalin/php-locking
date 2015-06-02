<?php

namespace fanalin\Spinlock\MemcacheSpinlock;

use fanalin\Spinlock\SpinlockInterface;
use fanalin\Spinlock\SpinlockException;

/**
 * A spinlock implementation for PHP using memcache.
 *
 * The lock is identified by a key in the memcache-server
 */
class MemcacheSpinlock implements SpinlockInterface
{
  /**
   * the "value" of the lock in memcached. Not used anywhere, therefore this
   * is just a random number
   */
  const SPINLOCK_VAL = 1;

  /**
   * flags for memcached when setting the key.
   * See http://php.net/manual/de/memcache.add.php for possible flags
   */
  const MEMCACHED_FLAGS = 0;

  /**
   * a identifier over the resource to lock
   * @var string
   */
  private $resource;

  /**
   * memcache connection
   * @var \Memcached
   */
  private $memcached;

  /**
   * Maximum Time-To-Life of a spinlock.
   * The spinlock will be auto-released by memcached if this time exceeds.
   * 0 means, that the spinlock will live forever if not manually released.
   * @var integer
   */
  private $maximumTtl;

  /**
   * Time in milliseconds between to "spins" while trying to acquire the spinlock
   * @var integer
   */
  private $retryInterval;

  /**
   * Maximum number of tries before giving up when acquiring the spinlock.
   * 0 means: try forever
   * @var integer
   */
  private $maximumTries;

  /**
   * constructor
   * @param string   $resource      a string identifying the resource to lock.
   * This string must not be used in memcached in any other context than the spinlock
   * @param \Memcache $memcached     memcache connection to use
   * @param integer  $maximumTtl    maximum time-to-life of a spinlock. 0 means forever
   * @param integer  $retryInterval time (in ms) after which a retry to set the spinlock will be started
   * @param integer  $maximumTries  maximum number of tries to acquire spinlock. 0 means infinity
   */
  public function __construct($resource, \Memcached $memcached, $maximumTtl, $retryInterval, $maximumTries)
  {
      $this->resource = $resource;
      $this->memcached = $memcached;
      $this->maximumTtl = $maximumTtl;
      $this->retryInterval = $retryInterval;
      $this->maximumTries = $maximumTries;
  }

  /**
   * acquire spinlock.
   *
   * If method returns successfully, the spinlock is acquired.
   * If any error occurs while waiting for the spinlock (e.g.: maximum waiting
   *  time exceeded) a SpinlockException is thrown
   *
   * @throws SpinlockException if spinlock could not be acquired
   */
  public function acquire()
  {
      $tryCount = 0;
      // the spinlock implementation: try to set key. If not possible, try again
      // until maximum number of tries reached or the lock is acquired
      while (! $this->addKey()) {
          ++$tryCount;
          if ($this->maximumTries > 0 && $tryCount >= $this->maximumTries) {
              throw new SpinlockException('Spinlock not acquired: maximum number of tries reached');
          }
          usleep($this->retryInterval);
      }
  }

  /**
   * try to acquire lock
   * @return boolean true if lock was acquired, false otherwise
   */
  private function addKey()
  {
      // Memcache::add will return true if key could be set and false if it
      // was already set. We use this behavior for our lock. But we must
      // make sure that the key (the resource identifier, $this->resource) is
      // not used in memcache in any other context than the spinlock.
      return $this->memcached->add(
          $this->resource,
          self::SPINLOCK_VAL,
          self::MEMCACHED_FLAGS,
          $this->maximumTtl
      );
  }

  /**
   * release spinlock
   *
   * @throws SpinlockException if spinlock could not be released
   */
  public function release()
  {
      if (! $this->memcached->delete($this->resource)) {
        throw new SpinlockException('Spinlock not released');
      }
  }
}
