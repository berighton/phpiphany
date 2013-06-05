<?php
/**
 * Caching class
 *
 * Caching is achieved by several means in phpiphany:
 * APC, Memcache, Redis, PHPRedis, Shared Memory or Local Disk Storage
 * This is configured in the settings file, along with the server/port information
 *
 *
 * Usage: Either call the static cache::init() method to get the object or simply read the global $cache
 *
 *
 * This wrapper class invokes modules from Memory library written by Eugene (jamm)
 * https://github.com/jamm/Memory
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package system
 * @since 1.0
 *
 */

$cache = cache::init();

class cache {

	// Store the instantiated object of cache depending on the cache type chosen in settings file
	public static function init($name = '') {
		global $config;

		if ($config->cache){
			if (!$config->cache_server[0]['host']) throw new error('Caching has not been configured properly. Please set caching server in the settings file!');
			require dirname(__FILE__) . '/classes/storage/imemory.php';
			require dirname(__FILE__) . '/classes/storage/memory.php';
			require dirname(__FILE__) . '/classes/storage/locker.php';
			switch ($config->cache_type) {
				case 'phpredis':
					require dirname(__FILE__) . '/classes/storage/iredis_server.php';
					require dirname(__FILE__) . '/classes/storage/redis_server.php';
					require dirname(__FILE__) . '/classes/storage/redis.php';
					require dirname(__FILE__) . '/classes/storage/phpredis.php';
					$name = $name? $name : 'phprediska';
					$cache_instance = new phpredis($name);
					break;
				case 'redis':
					require dirname(__FILE__) . '/classes/storage/iredis_server.php';
					require dirname(__FILE__) . '/classes/storage/redis_server.php';
					require dirname(__FILE__) . '/classes/storage/redis.php';
					$name = $name? $name : 'rediska';
					$cache_instance = new pip_redis($name);
					break;
				case 'memcache':
					require dirname(__FILE__) . '/classes/storage/memcache.php';
					$name = $name? $name : 'memcache';
					$cache_instance = new pip_memcache($name);
					break;
				case 'memcached':
					require dirname(__FILE__) . '/classes/storage/memcache.php';
					require dirname(__FILE__) . '/classes/storage/memcached.php';
					$name = $name? $name : 'memcached';
					$cache_instance = new pip_memcached($name);
					break;
				case 'apc':
					require dirname(__FILE__) . '/classes/storage/apc.php';
					$name = $name? $name : 'apc';
					$cache_instance = new pip_apc($name);
					break;
				// Default fallback to local file storage
				default:
					$name = $name? $name : 'filecache';
					$cache_instance = new pip_disk($name);
					break;
			}
			return $cache_instance;
		} else {
			trigger_error('<strong class="red">Caching is disabled!</strong> System is reverted back to the <u>old and slow</u> way of doing things:
							sessions and temp files are handled by a very expensive disk writes - local file storage<br>');
			return false;
		}
	}

	// This class can not be instantiated explicitly
	private function __construct() {
		throw new error('The class "' . __CLASS__ . '" cannot be instantiated explicitly!');
	}

}
