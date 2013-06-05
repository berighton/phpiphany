<?php
/**
 * Object class extending abstract pip_entity
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


class pip_object extends pip_entity {

	// Redundant definition of these properties ensures their appearance first in the list when viewed the object with print_r
	public $guid, $type, $subtype;

	public $group_guid, $name, $description;

	public function __construct(){
		parent::__construct();
	}

	/**
	 * Saves the current object entity if this object has a guid (if exists)
	 * Else creates a new object
	 *
	 * @return bool Success or failure
	 */
	public function save(){
		global $cache, $db, $config;
		$user = get_loggedin_user();
		// If entity exists, simply update it
		if ($this->guid and (($cache and $cache->read($this->guid)) or $db->fetch_assoc($db->query('SELECT `guid` FROM ' . $config->dbprefix . 'entities WHERE `guid` = ?', array($this->guid))))){
			if (!parent::save()) return false;
			$column_value_pairs = array('name' => $this->name, 'group_guid' => $this->group_guid, 'description' => $this->description);

			$result = $db->update($config->dbprefix . 'objects', $column_value_pairs, 'guid = ?', array($this->guid));
			if ($result){
				// Update relationships and access by first dropping all existing
				$db->query("DELETE FROM `{$config->dbprefix}access` WHERE `user_guid` IS NOT NULL AND `object_guid` = '$this->guid'");

				// Recreate the entries for the owner
				$db->insert($config->dbprefix . 'access', array('user_guid' => $user->guid, 'object_guid' => $this->guid));

				// Update cache
				if ($cache) {
					$object = orm::get($this->guid, true);
					$cache->save($this->guid, $object);

					// Drop other cache keys that rely on fetching the object as well as access
					$cache->save('drop', 'object');
					$cache->del('access');
				}
				return true;
			} else return false;
		// Otherwise create a new object
		} else {
			if (!parent::create()) return false;
			$column_value_pairs = array('guid' => $this->guid, 'group_guid' => $this->group_guid, 'name' => $this->name, 'description' => $this->description);

			$result = $db->insert($config->dbprefix . 'objects', $column_value_pairs);
			if ($result) {
				// Create access entries
				$db->insert($config->dbprefix . 'access', array('user_guid' => $user->guid, 'object_guid' => $this->guid));

				// Cache the new object
				if ($cache) {
					$object = orm::get($this->guid, true);
					$cache->save($this->guid, $object);

					// Drop other cache keys that rely on fetching the object as well as access
					$cache->save('drop', 'object');
					$cache->del('access');
				}
				return true;
			} else {
				// Since the object insert failed, remove new guid record from pip_entities
				parent::delete();
				return false;
			}
		}
	}

	/**
	 * Delete handler
	 * Note that each entity extending pip_entity has to have its own definition of delete() method
	 *
	 * @return bool True on successful delete, false on failure
	 */
	public function delete(){
		global $cache, $config, $db;
		// Proceed only if entity exists
		if ($this->guid and (($cache and $cache->read($this->guid)) or $db->fetch_assoc($db->query('SELECT guid FROM ' . $config->dbprefix . 'entities WHERE guid = ?', array($this->guid))))){
			// Delete the object from objects and entities tables through a transaction
			$db->transaction_start();
			$db->delete($config->dbprefix . 'objects', '`guid` = ?', array($this->guid));
			// Instead of a blunt delete, this query might be substituted to simply update the archived flag and set active to 'no'
			$db->delete($config->dbprefix . 'entities', '`guid` = ?', array($this->guid));
			//$db->update($config->dbprefix . 'entities', array('active' => 'no', 'archived' => 'yes'), '`guid` = ?', array($this->guid));
			// Remove access
			$db->delete($config->dbprefix . 'access', '`object_guid` = ?', array($this->guid));

			if ($db->transaction_complete()){
				// Clear cache for this object
				if ($cache){
					if ($cache->read($this->guid)) $cache->del($this->guid);
					if ($cache->read('access')) $cache->del('access');
				}
				return true;
			} else return false;
		} else return false;
	}

}
