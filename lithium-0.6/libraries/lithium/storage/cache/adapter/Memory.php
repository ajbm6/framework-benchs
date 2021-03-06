<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter;

/**
 * A minimal in-memory cache.
 *
 * This Memory adapter provides basic support for `write`, `read`, `delete`
 * and `clear` cache functionality, as well as allowing the first four
 * methods to be filtered as per the Lithium filtering system.
 *
 * This cache adapter does not implement any expiry-based cache invalidation
 * logic, as the cached data will only persist for the lifetime of the current request.
 *
 * As a result, this cache adapter is best suited for generic memoization of data, and
 * should not be used for for anything that must persist longer than the current
 * request cycle.
 */
class Memory extends \lithium\core\Object {

	/**
	 * Array used to store cached data by this adapter
	 *
	 * @var array
	 */
	protected $_cache = array();

	/**
	 * Magic method to provide an accessor (getter) to protected class variables.
	 *
	 * @param string $variable The variable requested.
	 * @return mixed Variable if it exists, null otherwise.
	 */
	public function __get($variable) {
		if (isset($this->{"_$variable"})) {
			return $this->{"_$variable"};
		}
	}

	/**
	 * Read value(s) from the cache
	 *
	 * @param string $key The key to uniquely identify the cached item.
	 * @return mixed Cached value if successful, false otherwise.
	 * @todo Refactor to use RES_NOTFOUND for return value checks.
	 */
	public function read($key) {
		$cache =& $this->_cache;

		return function($self, $params, $chain) use (&$cache) {
			extract($params);
			return isset($cache[$key]) ? $cache[$key] : null;
		};
	}

	/**
	 * Write value(s) to the cache
	 *
	 * @param string $key The key to uniquely identify the cached item.
	 * @param mixed $data The value to be cached.
	 * @param string $expiry A strtotime() compatible cache time.
	 * @return boolean True on successful write, false otherwise.
	 */
	public function write($key, $data, $expiry) {
		$cache =& $this->_cache;

		return function($self, $params, $chain) use (&$cache) {
			extract($params);
			return (boolean) ($cache[$key] = $data);
		};
	}

	/**
	 * Delete value from the cache
	 *
	 * @param string $key The key to uniquely identify the cached item.
	 * @return mixed True on successful delete, false otherwise.
	 */
	public function delete($key) {
		$cache =& $this->_cache;

		return function($self, $params, $chain) use (&$cache) {
			extract($params);
			if (isset($cache[$key])) {
				unset($cache[$key]);
				return true;
			} else {
				return false;
			}
		};
	}

	/**
	 * Performs a decrement operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to decrement.
	 * @param integer $offset Offset to decrement - defaults to 1.
	 * @return mixed Item's new value on successful decrement, false otherwise.
	 */
	public function decrement($key, $offset = 1) {
		$cache =& $this->_cache;

		return function($self, $params, $chain) use (&$cache, $offset) {
			extract($params);
			return $cache[$key] -= 1;
		};
	}

	/**
	 * Performs an increment operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to increment.
	 * @param integer $offset Offset to increment - defaults to 1.
	 * @return mixed Item's new value on successful increment, false otherwise.
	 */
	public function increment($key, $offset = 1) {
		$cache =& $this->_cache;

		return function($self, $params, $chain) use (&$cache, $offset) {
			extract($params);
			return $cache[$key] += 1;
		};
	}

	/**
	 * Clears user-space cache
	 *
	 * @return mixed True on successful clear, false otherwise.
	 */
	public function clear() {
		foreach ($this->_cache as $key => &$value) {
			unset($this->_cache[$key]);
		}
		return true;
	}

	/**
	 * This adapter is always enabled, as it has no external dependencies.
	 *
	 * @return boolean True
	 */
	public function enabled() {
		return true;
	}

	/**
	 * Garbage collection (GC) is not enabled for this adapter
	 *
	 * @return boolean False
	 */
	public function clean() {
		return false;
	}
}

?>