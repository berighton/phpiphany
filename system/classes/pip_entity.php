<?php
/**
 * The abstract class to define any entity inside phpiphany
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


abstract class pip_entity {

	public $guid, $url, $name, $type, $subtype, $owner_guid, $active, $updated, $created;

	public function __construct(){
		//$this->time_created = $_SERVER['REQUEST_TIME'];
	}

	/**
	 * Update an existing entity
	 *
	 * @return bool
	 */
	public function save(){
		if (!$this->guid) return false;
		global $db, $config;
		return $db->update($config->dbprefix . 'entities', array(
							'subtype' => $this->subtype,
							'owner_guid' => $this->owner_guid,
							'active' => $this->active,
							'updated' => 'NOW()'
							), 'guid = ?', array($this->guid));
	}

	/**
	 * Creates new entity by first checking that this guid does not exist and then inserting a new row in pip_entities table
	 * Since this is an abstract class, the following method should be called from child classes parent::create();
	 *
	 * @return bool True on success, false on error
	 */
	public function create(){
		global $cache, $db, $config;
		// Make sure entity does not exist
		if ($this->guid and (($cache and $cache->read($this->guid)) or $db->fetch_assoc($db->query('SELECT `guid` FROM ' . $config->dbprefix . 'entities WHERE `guid` = ?', array($this->guid))))){
			return false;
		} else {
			// Generate a new GUID. But first, check if this guid is not in the pool of already issued guids
			do $this->guid = $this->generate_guid();
			while ($db->fetch_assoc($db->query("SELECT `guid` FROM {$config->dbprefix}entities WHERE `guid` = '$this->guid'")));

			$this->owner_guid = $this->owner_guid? $this->owner_guid : get_loggedin_user()->guid;
			$this->active = 'yes';
			return $db->insert($config->dbprefix . 'entities', array(
							'guid' => $this->guid,
							'type' => $this->type,
							'subtype' => $this->subtype,
							'owner_guid' => $this->owner_guid,
							'active' => $this->active
							))? true : false;
		}
	}

	/**
	 * Generates a 12 character random GUID (Globally Unique ID)
	 * Twelve characters are comprised of 26 upper and lower case letters and 10 digits
	 * This results in C(62,12) = 62! / 12! (62 - 12)! = 2,160,153,123,141 unique entities
	 *
	 * @return string
	 */
	private function generate_guid() {
		// Possible seeds (26 upper and lower case characters plus 10 digits)
		$seeds = 'abcdefghijklmnopqrstuvwqyzABCDEFGHIJKLMNOPQRSTUVWQYZ0123456789';
		// Randomize the seeds
		$seeds = str_shuffle($seeds);

		// Define some variables
		$guid = '';
		$seeds_count = strlen($seeds) - 1;

		// Generate
		for ($i = 0; $i < 12; ++$i) {
			$guid .= $seeds{mt_rand(0, $seeds_count)};
		}

		/*
		 * For 64bit operating system, you might use a significantly faster code
		 * A random number/string will be generated between: 100000000000 to zzzzzzzzzzzz that is always 12 characters.
		 * Note that this will produce less unique ids as upper case characters are omitted in this combination
		 *
		 * $guid = base_convert(mt_rand(0x1D39D3E06400000, 0x41C21CB8E0FFFFFF), 10, 36);
		 */
		return $guid;
	}

	/**
	 * Delete entity action
	 * Should be extended by every child class
	 *
	 * @return bool T/F
	 */
	public function delete(){
		// Remove a record from entities table only if this object has GUID
		if ($this->guid){
			global $cache, $db, $config;
			if ($db->delete($config->dbprefix . 'entities', 'guid = "' . $this->guid . '"')) {
				// If cache has any information about this GUID, drop it
				if ($cache){
					if ($cache->read($this->guid)) $cache->del($this->guid);
				}
				return true;
			} else return false;
		}
		return false;
	}

	/**
	 * Upload handler
	 * Renames and moves the physical file from the temporary location to a defined path
	 *
	 * @return bool T/F
	 */
	public function handle_uploads(){
		// For now we will handle only one file (single upload)
		if (isset($_SESSION['pip_file_guid']) and $_SESSION['pip_file_guid']){
			$file = orm::get($_SESSION['pip_file_guid']);
			if ($file){
				// Place the file in a user or group directory
				if (isset($_SESSION['pip_file_owner_type']) and $_SESSION['pip_file_owner_type']){
					dir_setup('/uploads/' . $_SESSION['pip_file_owner_type']);
					global $config;
					//$file->name = $this->guid . '_' . $_SERVER['REQUEST_TIME'] . substr($file->name, strrpos($file->name, '.'));
					$file->name = $this->guid;
					// Overwrite the avatar filename. Otherwise, make it unique by appending a timestamp
					$file->name .= (stripos($_SESSION['pip_file_owner_type'], 'avatar') !== false)? '' : '_' . $_SERVER['REQUEST_TIME'];
					// Append file extension
					$file->name .= substr($file->filename, strrpos($file->filename, '.'));
					$file->filename = $this->name;
					$file->owner_guid = $this->guid;
					$file->owner = (stripos($_SESSION['pip_file_owner_type'], 'group') !== false)? 'group' : 'user';
					$path = $config->env->root . '/uploads/' . $_SESSION['pip_file_owner_type'] . '/' . $file->name;
					// Move the physical file
					rename($file->path, $path);
					// Set the new path
					$file->path = $path;
					// Remove old session keys
					unset($_SESSION['pip_file_owner_type']);
					unset($_SESSION['pip_file_guid']);
					// Save
					return $file->save()? true : false;
				} else return false;
			}
		}
		return false;
	}

	// Cleanup traces of bad GUIDS
	static function cleanup($guid){
		global $config, $db;
		if ($entity = $db->fetch_assoc($db->select('guid', $config->dbprefix . 'entities', 'guid = ?', array($guid)))){
			if ($result = $db->fetch_assoc($db->select('guid', $config->dbprefix . 'entities', 'guid = ?', array($guid)))){
				//
			}
		}
	}
}
