<?php
/**
 * Date formatter
 * Extends the PHP5 DateTime class and adds several custom methods
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package classes
 * @since 1.0
 *
 */

class dater extends DateTime {

	/**
	 * Returns Date in ISO8601 format
	 *
	 * @param bool|int $ts An optional timestamp can be specified to be formatted in phpiphany date style
	 * @return \dater formatted date
	 */
	public function __construct($ts = false) {
		parent::__construct();
		if ($ts) $this->setTimestamp($ts);
		return $this->format('Y-m-d [H:i:s]');
	}

	/**
	 * Returns Date in ISO8601 format
	 *
	 * @return String
	 */
	public function __toString() {
		return $this->format('Y-m-d [H:i:s]');
	}

	/**
	 * Returns difference between $this and $now
	 *
	 * @param Datetime|String $now
	 * @param null $absolute
	 * @return \DateInterval
	 */
	public function diff($now = 'NOW', $absolute = NULL) {
		if (!($now instanceOf DateTime)) {
			$now = new DateTime($now);
		}
		return parent::diff($now);
	}

	/**
	 * Returns Age in Years
	 *
	 * @param Datetime|String $now
	 * @return Integer
	 */
	public function getAge($now = 'NOW') {
		return $this->diff($now)->format('%y');
	}

	/**
	 * Returns only the date in the YYYY-MM-DD format
	 *
	 * @return Datetime|String $now
	 */
	public function date() {
		return $this->format('Y-m-d');
	}

	/**
	 * Returns only the time in the HH:MM:SS format
	 *
	 * @param bool $ampm If set to true, the time returned would include AM/PM. Otherwise it is a 24h clock
	 * @return Datetime|String $now
	 */
	public function time($ampm = false) {
		if ($ampm) return $this->format('g:i:s a');
		else return $this->format('H:i:s');
	}

}

