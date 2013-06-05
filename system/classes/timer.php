<?php
/**
 * Script execution timer
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package system/classes
 * @since 1.0
 *
 */


/**
 * It is possible to instantiate this class anywhere in the code and as many times as needed
 *
 * Usage:
 * $runtime = new timer;
 * {script}
 * echo 'Total running time of the script: ' . $runtime->stop();
 */
class timer {

	public $time_start, $time_end;

	/**
	 * Class construct, starts the timer
	 *
	 */
	public function __construct() {
		$this->time_start = microtime(true);
	}

	/**
	 * Stops the execution timer and returns the total time
	 *
	 * @return string Returns the time in the format d:hh:mm:ss
	 */
	public function stop() {
		$this->time_end = microtime(true);
		$s = $this->time_end - $this->time_start;
		$m = $h = $d = 0;
		if ($s / 3600 > 1) {
			$h = intval($s / 3600);
			$d = $h > 24 ? intval($h / 24) : 0;
			$m = ($s / 60) - ($h * 60);
			$s %= 60;
		} elseif ($s / 60 > 1) {
			$m = $s / 60;
			$s %= 60;
		}
		return sprintf("%02d:%02d:%02d:%02.4f (d:h:m:s.ms)", $d, $h, $m, $s);
	}
}
