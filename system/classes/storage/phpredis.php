<?php

class phpredis extends pip_redis {
	protected function init_redis_server($host = 'localhost', $port = '6379') {
		try {
			$this->redis = new Redis();
			$this->redis->connect($host, $port);
			if ($this->redis->PING()) return true;
		} catch (error $e) {
			throw new error("Unable to connect to phpredis server. <strong>" . $e->getMessage() . " ('$host' on port $port)</strong>");
		}
	}
}
