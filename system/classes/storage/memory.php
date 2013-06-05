<?php

abstract class memory implements imemory {
	private $max_ttl = 2592000;
	protected $key_lock_time = 30;
	// Set the max lock time - how long to wait before the exclusive lock is cleared
	// Recommended value is 10 microseconds, but on a faster server, this value might go down as low as 0.05
	protected $max_wait_unlock = 10;

	public function acquire_key($key, &$auto_unlocker) {
		$t = microtime(true);
		while (!$this->lock_key($key, $auto_unlocker)) {
			if ((microtime(true) - $t) > $this->max_wait_unlock) {
				return false;
			}
		}
		return true;
	}

	protected function incrementArray($limit_keys_count, $value, $by_value) {
		if ($limit_keys_count > 0 && (count($value) > $limit_keys_count)) {
			$value = array_slice($value, $limit_keys_count * (-1) + 1);
		}

		if (is_array($by_value)) {
			$set_key = key($by_value);
			if (!empty($set_key)) {
				$value[$set_key] = $by_value[$set_key];
			} else {
				$value[] = $by_value[0];
			}
		} else {
			$value[] = $by_value;
		}
		return $value;
	}

	public function set_max_wait_unlock_time($max_wait_unlock = 0.05) {
		$this->max_wait_unlock = $max_wait_unlock;
	}

	public function set_key_lock_time($key_lock_time = 30) {
		$this->key_lock_time = $key_lock_time;
	}

	public function set_max_ttl($ttl){
		if ($ttl) $this->max_ttl = intval($ttl);
	}

	public function get_max_ttl(){
		return $this->max_ttl;
	}
}
