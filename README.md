# Spinlocks for PHP

Avoid race conditions with PHP? Want to use spinlocks in PHP to protect critical code paths
from parallel execution by multiple processes? Use this library!

## Why?

A common race condition that I fairly often see is the [TOCTTOU](http://en.wikipedia.org/wiki/Time_of_check_to_time_of_use)
problem.
It is created when the check and the use of a resource is done in separate steps and another
process may alter the resource between the check and the use.

For example:
You have a basket system where only one basket may exist for a customer.
To get a basket (including a "lazy initialization") you could do something like this:

```php
class BasketManager {
	public function getBasketForCustomer(Customer $customer)
	{
	    $currentBasket = $this->basketRepository->getBasketForCustomer($customer);
		if ($currentBasket) {
			return $currentBasket;
		}

		$basket = new Basket($customer);
		// A
		return $basket;
	}
}
```

Let's further assume that at step A some complex computation must be done.
The customer, as impatient as he is, does not wait until your page is reloaded and clicks
"Add to basket" a second time, all while the computation in A runs. While the second process
arrives at the "exists a basket currently?" check, no basket exists and it also starts the
computation and later creates a new basket. So you have two different baskets for the same
customer.

This scenario is unlikely if the timespan between the check and the use (here: the creation
of the new basket) is small, but it is still possible.

Sometimes, you can circumvent these problems by expressing this constraint in other systems,
e.g. by a constraint in the database. But this is not always possible.

One possible solution for this problem is a lock. Usually, you need some sort of shared
memory to implement a lock, and handling of shared memory may be cumbersome in PHP.

This library implements Spinlocks, which allow synchronizing parallel processes.
The shared memory for these spinlocks can be in several other systems. Currently, only
a implementation for memcache is done, but implementations for usage of filesystems (with
flock), MySQL (with GET_LOCK, although it won't be a true spinlock then :)), APC or Redis
should be easily implementable.

## Installation

TODO

## Usage

With this library, the code from above may look like this:

```php
class BasketManager {
	public function getBasketForCustomer(Customer $customer)
	{
		$lock = $this->spinlockFactory->get('basket-' . $customer->getId());
		$lock->acquire();
	    $currentBasket = $this->basketRepository->getBasketForCustomer($customer);
		if ($currentBasket) {
			return $currentBasket;
		}

		$basket = new Basket($customer);
		// A

		$lock->release();
		return $basket;
	}
}
```

The code between $lock->acquire() and $lock->release() can only be run by one parallel
process for the same resource. Here, the resource we want to lock is the basket for a
specific customer. This spinlock library always uses a string representation of the lock-object
(here: "basket-1" for customer 1). Therefore, two separate customers may create baskets
in parallel, but one customer can not.

This library provides two interfaces:

### SpinlockFactoryInterface

The SpinlockFactoryInterface provides a convenience factory method for creating Spinlocks.
It has a single method "get(string)" which creates a spinlock for the resource, which is
identified by the given string.

### SpinlockInterface

The SpinlockInterface is the base interface for all Spinlock implementations in this library.
It defines two methods:

#### acquire()
When you call this method, the spinlock tries to acquire a lock for the resource.
If the lock is not acquirable (e.g.: the spinlock implementation has a maximum tries count
and the number of tries exceeds that counter), than a SpinlockException will be thrown.
In all other cases, the spinlock is acquired.

#### release()
Releases a spinlock. If the spinlock is not releasable, a SpinlockException will be thrown.
Important: the resource does not need to be locked by this spinlock instance. release() also
releases spinlocks from other spinlock object instances (for the same resource), in the same
PHP process or even in other processes.

With this way, you can lock resources over process boundaries. E.g.: you can lock an article
in your CMS when a user begins editing it. The process which begins the editing of the article
is long gone when the user releases the article for other authors.

## Contributing

See the [CONTRIBUTING](CONTRIBUTING.md) file.

## License

This library is licensed under MIT.
