<?php

class pip_memcached extends pip_memcache {
	protected function init_memcache($host = 'localhost', $port = 11211) {
		try {
			$this->memcache = new Memcached();
			$this->memcache->addServer($host, $port);
			$this->memcache->setOption(Memcached::OPT_COMPRESSION, true);
		} catch (error $e) {
			global $view;
			$view->system_msg = $view->error("Unable to connect to Memcached server. <strong>" . $e->getMessage() . " ('$host' on port $port)</strong>", 'error');
			return false;
		}
	}
}
