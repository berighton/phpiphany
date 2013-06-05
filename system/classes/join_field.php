<?php
/**
 * Class for creating fields that are used for multitable method
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

class join_field {
	public $table, $op, $sql, $value;
	// We do not explicitly  define name and value variables otherwise the setter not going to work
	//public $name, $value;

	/**
	 * Class construct sets the default operator to 'AND'
	 */
	public function __construct(){
		$this->op = 'AND';
	}

	/**
	 * Class setter. If the field name was specified, break it down to define the table name as well
	 *
	 * @param  $name Object property name
	 * @param  $value Object property value
	 * @return void
	 */
	public function __set($name, $value){
		if ($name == 'name' and $value and $link = explode('.', $value) and count($link) != 2){
			global $view;
			$view->alert('Error setting a field name for "join_field" object! The format should be <em>tablename.fieldname</em>', 'error');
			$this->name = 'ERROR';
		} elseif ($name == 'name' and isset($link) and count($link) == 2) {
			$this->table = $link[0];
			$this->name = $value;
		} else {
			$this->$name = $value;
		}
	}

}
