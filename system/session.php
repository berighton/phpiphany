<?php
/**
 * Sessions class that acts as a wrapper for PHP's default session handling functions
 *
 * If caching was enabled by the application, storing session data is done via a caching mechanism defined in settings file.
 * This provides better security, performance and most importantly scalability!
 *
 * The fastest and most reliable way to use custom session handler is to use a cache C extension in php.ini file.
 * This class checks if the extension has been loaded and tries to set the session.save_handler value in the settings
 * If it fails to find an extension, it falls back on creating a custom handler utilizing the caching class methods.
 *
 * The skeleton for this class is loosely based on Zebra-Session class by Stefan Gabos
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

$session = new session();

class session {

	/* Local instance of a cache object */
	private $cache;

	/* Session expiry time in seconds. Default is 30 minutes (1800 seconds) */
	private $expiry = 43200; // for now it is set to 12hours

	/* Session key lock to avoid race conditions (cache miss storm) */
	private $key_lock = '';

	/* Session monitor will be copied from the config file to determine how strict the session timeouts have to be */
	private $session_mode;

	/**
	 *  Constructor of class. Initializes the class and automatically calls
	 *  {@link http://php.net/manual/en/function.session-start.php}.
	 *
	 *   NOTE:
	 *   Expired sessions are cleaned up from the database whenever the <i>garbage collection routine</i> is run.
	 *   Default is the value of <i>session.gc_maxlifetime</i> as set in php.ini
	 *   Read more at {@link http://www.php.net/manual/en/session.configuration.php}
	 *
	 *   To clear any confusions that may arise: in reality, <i>session.gc_maxlifetime</i>
	 *   does not represent a session's lifetime but the number of seconds after which a
	 *   session is seen as <i>garbage</i> and is deleted by the <i>garbage collection routine</i>.
	 *   The PHP setting that sets a session's lifetime is <i>session.cookie_lifetime</i>
	 *   and is usually set to "0" - indicating that a session is active until
	 *   the browser/browser tab is closed. When this class is used, a session is active
	 *   until the browser/browser tab is closed and/or a session has been inactive
	 *   for more than the number of seconds specified by <i>session.gc_maxlifetime</i>.
	 *
	 *   To see the actual value of <i>session.gc_maxlifetime</i> for your environment, use the {@link get_settings()} method
	 *   Pass an empty string to keep default value.
	 *
	 */
	public function __construct() {

		global $config, $cache;

		// If caching was enabled, use the caching mechanism to store the sessions. Otherwise use standard PHP $_SESSION
		if ($config->cache === true and $cache) {

			// Clone the cache instance in our class variable for easy access and a separate scope
			$this->cache = clone($cache);

			if ($this->cache) {
				// Make sure session cookies never expire so that session lifetime will depend only on the value of $session_lifetime
				ini_set('session.cookie_lifetime', 0);

				// Set the expiry of this session to number of seconds defined in this->expiry
				ini_set('session.gc_maxlifetime', $this->expiry);

				// Set the cache TTL to the max life time of this session
				$this->cache->set_max_ttl($this->expiry);

				// Assign the session monitor mode from the config object to be stored in a local variable
				$this->session_mode = $config->session_mode;

				$extension = false;

				// First check to see if the cache extension is loaded and if so, simply enable it through the session.save_handler
				switch ($config->cache_type) {
					case 'redis':
					case 'phpredis':
						if (extension_loaded('redis')) {
							$session_save_path = '';
							// Get the server settings and build the session save path
							foreach ($config->cache_server as $server){
								$session_save_path .= 'tcp://';
								$parms = '';
								foreach ($server as $key => $value){
									if ($key == 'host') $session_save_path .= $value? "$value:" : 'localhost:';
									elseif ($key == 'port') $session_save_path .= $value? $value : '6379';
									else $parms .= $value? "&$key=$value" : '';
								}
								if ($parms) $session_save_path .= '?' . substr($parms, 1);
								$session_save_path .= ', ';
							}
							$session_save_path = substr($session_save_path, 0, -2);
							//$session_save_path = "tcp://$host:$port?weight=1&timeout=1&retry_interval=15"; //, tcp://<redis_server2>:6379?weight=3&timeout=2.5";
							ini_set('session.save_handler', 'redis');
							ini_set('session.save_path', $session_save_path);
							$extension = true;
						}
						break;
					case 'memcache':
					case 'memcached':
						if (extension_loaded('memcached') or extension_loaded('memcache')) {
							$session_save_path = '';
							// Get the server settings and build the session save path
							foreach ($config->cache_server as $server){
								$session_save_path .= 'tcp://';
								$parms = '';
								foreach ($server as $key => $value){
									if ($key == 'host') $session_save_path .= $value? "$value:" : 'localhost:';
									elseif ($key == 'port') $session_save_path .= $value? $value : '11211';
									else $parms .= $value? "&$key=$value" : '';
								}
								if ($parms) $session_save_path .= '?' . substr($parms, 1);
								$session_save_path .= ', ';
							}
							$session_save_path = substr($session_save_path, 0, -2);
							if ($config->cache_type == 'memcached') ini_set('session.save_handler', 'memcached');
							elseif ($config->cache_type == 'memcache') ini_set('session.save_handler', 'memcache');
							ini_set('session.save_path', $session_save_path);
							$extension = true;
						}
						// Note that when using memcache for sessions not as an extension, the maximum value size is 1MB
						// And since session.write serializes ENTIRE sessions global variable, space limitation might occur
						break;
				}

				// Otherwise, register the new handler
				if (!$extension) {
					session_set_save_handler(array(&$this, 'open'), array(&$this, 'close'), array(&$this, 'read'), array(&$this, 'write'), array(&$this, 'destroy'), array(&$this, 'gc'));
					// The following prevents unexpected effects when using objects as save handlers
					register_shutdown_function('session_write_close');
				}
			}
		}

		// Start the session
		session_start();

		// Store the creation time
		$_SESSION['created'] = $_SERVER['REQUEST_TIME'];
	}

	/**
	 * Custom open() function
	 *
	 * @param $save_path
	 * @param $session_name
	 * @return bool
	 */
	function open($save_path, $session_name) {
		return true;
	}

	/**
	 * Custom close() function
	 *
	 * @return bool
	 */
	function close() {
		return true;
	}

	/**
	 * Custom read() function
	 *
	 * @param $session_id
	 * @return string
	 */
	function read($session_id) {
		// Try to get a lock for the key. Exit if we can't acquire it
		if (!$this->key_lock and !$this->cache->acquire_key($session_id, $this->key_lock)) {
			trigger_error('<strong class="red">Reading $_SESSION[] FAILED!</strong> Cannot obtain an exclusive lock for this session. Either the session server is busy or it went down.<br>');
			return '';
		}

		// By now we should have the key lock stored in $this->key_lock, so we can continue reading
		$i = intval('-1');
		$result = $this->cache->read($session_id, $i, false);
		// If something was found
		if ($result and $result !== false) {
			return $result;
		}
		// On error return an empty string - this HAS to be an empty string
		return '';
	}

	/**
	 * Custom write() function
	 * Saves the session with session_id being the key and serialized session data as the value
	 *
	 * @param $session_id
	 * @param $session_data
	 * @return bool Returns true on success, false on error
	 */
	function write($session_id, $session_data) {
		// Save session only if there is data
		if (isset($session_data) and $session_data) {
			// See if we have the proper lock for this session
			if ($this->key_lock) {
				$result = $this->cache->save($session_id, $session_data, $this->expiry, null, false);
				// Unlock the key
				$this->cache->unlock_key($this->key_lock);
				$this->key_lock = '';

				// Upon successful save
				if ($result and $result === true) {
					return true;
				} else {
					trigger_error('<strong class="red">Writing to $_SESSION[] FAILED!</strong> Please make sure that your caching server is running and is properly configured<br>');
					return false;
				}
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Custom destroy() function
	 *
	 * @param $session_id
	 * @return bool
	 */
	function destroy($session_id) {
		$result = $this->cache->del($session_id);
		// Returned result should be number of keys deleted. Return true if it deleted any
		if (isset($result) and is_numeric($result) and $result > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Custom gc() function (garbage collector)
	 *
	 * @param $maxlifetime
	 */
	function gc($maxlifetime) {
		$this->cache->del_old();
	}


	/**
	 *  Queries the system for the values of <i>session.gc_maxlifetime</i>, <i>session.gc_probability</i> and <i>session.gc_divisor</i>
	 *  and returns them as an associative array.
	 *
	 *  To view the result in a human-readable format use:
	 *  <code>
	 *
	 *  // Instantiate the class
	 *  $session = new session();
	 *
	 *  // Get the default settings
	 *  print '<pre>' . print_r($session->get_settings(), true) . '</pre>';
	 *
	 *  //  would output something similar to (depending on your actual settings)
	 *  //  Array
	 *  //  (
	 *  //      [session.gc_maxlifetime] => 1440 seconds (24 minutes)
	 *  //      [session.gc_probability] => 1
	 *  //      [session.gc_divisor] => 1000
	 *  //      [probability] => 0.1%
	 *  //  )
	 *  </code>
	 *
	 *
	 * @return array   Returns the values of <i>session.gc_maxlifetime</i>, <i>session.gc_probability</i> and <i>session.gc_divisor</i>
	 *                  as an associative array.
	 */
	public function get_settings() {
		// get the settings
		$gc_maxlifetime = ini_get('session.gc_maxlifetime');
		$gc_probability = ini_get('session.gc_probability');
		$gc_divisor = ini_get('session.gc_divisor');

		// return them as an array
		return array('session.gc_maxlifetime' => $gc_maxlifetime . ' seconds (' . round($gc_maxlifetime / 60) . ' minutes)',
			'session.gc_probability' => $gc_probability, 'session.gc_divisor' => $gc_divisor, 'probability' => $gc_probability / $gc_divisor * 100 . '%',);
	}

	/**
	 * Monitors the session expiration.
	 * When $session_mode is set to 'loose', the session will remain open for as long as the user is online
	 * (increasing the TTL with $expiry increments). If it is set to strict, the session will time out
	 * exactly after the specified amount of time elapsed from the session creation
	 *
	 * Loose method also rectifies the problem of session fixation by regenerating a new session_id
	 *
	 * This method is already called from the landing page (index.php) so there is no need to call again from elsewhere
	 * It does not however, issue any error messages upon session exipration, so it has to be done manually on pages
	 * you want to check the validity of the session (for example pages that require authentication)
	 *
	 * @return void
	 */
	public function expiration_monitor() {
		if ($this->session_mode == 'strict'){
			if (($_SERVER['REQUEST_TIME'] - $_SESSION['created']) > $this->expiry) {
				$this->stop();
			}
		} else {
			// Get the lowest of the two values
			$time = (ini_get('session.gc_maxlifetime') < $this->expiry)? ini_get('session.gc_maxlifetime') : $this->expiry;
			if (($_SERVER['REQUEST_TIME'] - $_SESSION['created']) > $time) {
				session_regenerate_id(true);
				$_SESSION['created'] = $_SERVER['REQUEST_TIME'];
			}
		}
	}

	/**
	 *  Deletes all data related to the session
	 *
	 *  <code>
	 *  // Start the session
	 *  $session = new session();
	 *
	 *  // End current session
	 *  $session->stop();
	 *  </code>
	 *
	 * @return void
	 */
	public function stop() {
		session_regenerate_id(true);
		session_unset();
		session_destroy();
	}


}

