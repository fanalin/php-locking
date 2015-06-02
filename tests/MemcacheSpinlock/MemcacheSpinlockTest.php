<?php

namespace fanalin\Spinlock\MemcacheSpinlock;

class MemcacheSpinlockTest extends \PHPUnit_Framework_TestCase
{
    private $memcache;

    public function setUp()
    {
        $this->memcache = new \Memcached();
        $this->memcache->addServer('localhost', 11211);
        $this->memcache->flush();
    }

    public function testLock()
    {
        $resource = 'MY_RESOURCE';

        $spinlock = new MemcacheSpinlock($resource, $this->memcache, 10, 10, 10);

        $spinlock->acquire();
        $this->assertEquals(MemcacheSpinlock::SPINLOCK_VAL, $this->memcache->get($resource));

        $spinlock->release();
        $this->assertFalse($this->memcache->get($resource));
    }

    /**
     * @expectedException fanalin\Spinlock\SpinlockException
     */
    public function testNotLockable()
    {
        $resource = 'MY_RESOURCE';

        // fake spinlock from another process by setting key manually
        $this->memcache->set($resource, 1);

        // we have a maximum number of tries => acquiring should fail.
        $spinlock = new MemcacheSpinlock($resource, $this->memcache, 10, 10, 1);
        $spinlock->acquire();
    }
}
