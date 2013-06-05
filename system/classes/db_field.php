<?php
/**
 * Class for creating database fields in the new table
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

class db_field {
	public $name, $type, $size, $null, $ai, $index, $fk, $fk_ondelete, $fk_onupdate, $default, $collation, $attribute, $comment;

	public function __construct(){
		$this->type = 'varchar';
		$this->size = '250';
		$this->null = true;
		$this->ai = $this->index = $this->fk = $this->fk_ondelete = $this->fk_onupdate = $this->default = $this->attribute = $this->comment = false;
		$this->collation = 'utf8_unicode_ci';
	}

	/**
	 * Formats the foreign key actions properly
	 * Acceptable actions: 'restrict', 'cascade', 'null' and 'none'
	 * It will also set the field index property to true as this field MUST be indexes in roder to create a relationship
	 *
	 * @return void
	 */
	public function format_fk(){
		if ($this->fk){
			$this->index = $this->index? $this->index : true;
			$this->fk_ondelete = $this->fk_ondelete ? $this->fk_ondelete : 'restrict'; // 'cascade', 'null', 'none'
			if (!preg_match('/(restrict|cascade|null|none)/i', $this->fk_ondelete)) {
				trigger_error("Database field '$this->name' does not have a valid foreign key ON DELETE action!", E_USER_WARNING);
			} else {
				$this->fk_ondelete = strtoupper(strtr($this->fk_ondelete, array('null' => 'set null', 'none' => 'no action')));
			}
			$this->fk_onupdate = $this->fk_onupdate? $this->fk_onupdate : 'restrict'; // 'cascade', null, 'none'
			if (!preg_match('/(restrict|cascade|null|none)/i', $this->fk_onupdate)) {
				trigger_error("Database field '$this->name' does not have a valid foreign key ON UPDATE action!", E_USER_WARNING);
			} else {
				$this->fk_onupdate = strtoupper(strtr($this->fk_onupdate, array('null' => 'set null', 'none' => 'no action')));
			}
		}
	}
}
