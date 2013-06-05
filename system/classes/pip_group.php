<?php
/**
 * Group class extending abstract pip_entity
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


class pip_group extends pip_entity {

	// Redundant definition of these properties ensures their appearance first in the list when viewed the object with print_r
	public $guid, $type, $subtype;

	public $parent_guid, $name, $description, $access, $members, $objects;

	public function __construct(){
		parent::__construct();

		$this->type = 'group';
		$this->subtype = 'general';
	}

	/**
	 * Saves the current group entity if this object has a guid (if exists)
	 * Else creates a new group
	 *
	 * @return bool Success or failure
	 */
	public function save(){
		global $cache, $db, $config;
		$user = get_loggedin_user();
		// If entity exists, simply update it
		if ($this->guid and (($cache and $cache->read($this->guid)) or $db->fetch_assoc($db->query('SELECT `guid` FROM ' . $config->dbprefix . 'entities WHERE `guid` = ?', array($this->guid))))){
			if (!parent::save()) return false;
			$pg = (isset($this->parent_guid) and $this->parent_guid)? $this->parent_guid : 'NULL';
			$column_value_pairs = array('parent_guid' => $pg, 'name' => $this->name, 'description' => $this->description, 'access' => $this->access);

			$result = $db->update($config->dbprefix . 'groups', $column_value_pairs, 'guid = ?', array($this->guid));
			if ($result){
				// Update relationships and access by first dropping all existing
				$db->query("DELETE FROM `{$config->dbprefix}access` WHERE `user_guid` IS NOT NULL AND `group_guid` = '$this->guid'");
				$db->query("DELETE FROM `{$config->dbprefix}memberships` WHERE `group_guid` = '$this->guid'");
				if ($this->members){
					foreach ($this->members as $guid){
						$db->insert($config->dbprefix . 'memberships', array('user_guid' => $guid, 'group_guid' => $this->guid));
						$db->insert($config->dbprefix . 'access', array('user_guid' => $guid, 'group_guid' => $this->guid));
					}
				}
				// Recreate the entries for the owner too
				$db->insert($config->dbprefix . 'memberships', array('user_guid' => $user->guid, 'group_guid' => $this->guid));
				$db->insert($config->dbprefix . 'access', array('user_guid' => $user->guid, 'group_guid' => $this->guid));

				// Handle file uploads (if any)
				$this->handle_uploads();

				// Update cache
				if ($cache) {
					$group = orm::get($this->guid, true);
					$cache->save($this->guid, $group);

					// Drop other cache keys that rely on fetching the group as well as access
					$cache->save('drop', 'group');
					$cache->del('access');
				}
				return true;
			} else return false;
		// Otherwise create a new group
		} else {
			if (!parent::create()) return false;
			$column_value_pairs = array('guid' => $this->guid, 'parent_guid' => $this->parent_guid, 'name' => $this->name, 'description' => $this->description, 'access' => $this->access);

			$result = $db->insert($config->dbprefix . 'groups', $column_value_pairs);
			if ($result) {
				// Create relationships and access entries if members were specified
				if ($this->members){
					foreach ($this->members as $guid){
						$db->insert($config->dbprefix . 'memberships', array('user_guid' => $guid, 'group_guid' => $this->guid));
						$db->insert($config->dbprefix . 'access', array('user_guid' => $guid, 'group_guid' => $this->guid));
					}
				}
				// Do the same for the creator
				$db->insert($config->dbprefix . 'memberships', array('user_guid' => $user->guid, 'group_guid' => $this->guid));
				$db->insert($config->dbprefix . 'access', array('user_guid' => $user->guid, 'group_guid' => $this->guid));

				// Handle file uploads (if any)
				$this->handle_uploads();

				// Cache the new group
				if ($cache) {
					$group = orm::get($this->guid, true);
					$cache->save($this->guid, $group);

					// Drop other cache keys that rely on fetching the group as well as access
					$cache->save('drop', 'group');
					$cache->del('access');
				}
				return true;
			} else {
				// Since the group insert failed, remove new guid record from pip_entities
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
			// Delete the group from groups and entities tables through a transaction
			$db->transaction_start();
			$db->delete($config->dbprefix . 'groups', '`guid` = ?', array($this->guid));
			// Instead of a blunt delete, this query might be substituted to simply update the archived flag and set active to 'no'
			$db->delete($config->dbprefix . 'entities', '`guid` = ?', array($this->guid));
			//$db->update($config->dbprefix . 'entities', array('active' => 'no', 'archived' => 'yes'), '`guid` = ?', array($this->guid));
			// Remove relationships
			$db->delete($config->dbprefix . 'memberships', '`group_guid` = ?', array($this->guid));
			// Remove access
			$db->delete($config->dbprefix . 'access', '`group_guid` = ?', array($this->guid));

			if ($db->transaction_complete()){
				// Clear cache for this group
				if ($cache){
					if ($cache->read($this->guid)) $cache->del($this->guid);
					if ($cache->read('access')) $cache->del('access');
				}
				return true;
			} else return false;
		} else return false;
	}

}
