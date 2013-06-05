<?php
/**
 * User class extending abstract pip_entity
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


class pip_user extends pip_entity {

	// Redundant definition of these properties ensures their appearance first in the list when viewed the object with print_r
	public $guid, $type, $subtype;

	public $fname, $lname, $username, $password, $salt, $email, $language, $admin, $last_login, $prev_last_login, $membership = array();

	public function __construct(){
		parent::__construct();

		$this->type = 'user';
		$this->subtype = 'admin';
	}

	/**
	 * Saves the current user entity if this object has a guid (if exists)
	 * Else creates a new user entity
	 *
	 * @return bool Success or failure
	 */
	public function save(){
		global $cache, $db, $config;
		// If entity exists, simply update it
		if ($this->guid and (($cache and $cache->read($this->guid)) or $db->fetch_assoc($db->query('SELECT `guid` FROM ' . $config->dbprefix . 'entities WHERE `guid` = ?', array($this->guid))))){
			$this->owner_guid = $this->subtype == 'admin'? 'sysadmin' : get_loggedin_userid();
			if (!parent::save()) return false;
			$column_value_pairs = array(
								'fname' => $this->fname,
								'lname' => $this->lname,
								'username' => $this->username,
								'password' => $this->password,
								'salt' => $this->salt,
								'email' => $this->email,
								'language' => $this->language,
								'admin' => ((isset($this->admin) and ($this->admin == 'yes' or $this->admin === true))? 'yes' : 'no'));

			// To handle NULL dates
			if ($this->last_login) $column_value_pairs['last_login'] = $this->last_login;
			if ($this->prev_last_login) $column_value_pairs['prev_last_login'] = $this->prev_last_login;

			$result = $db->update($config->dbprefix . 'users', $column_value_pairs, 'guid = ?', array($this->guid));
			if ($result){
				if ($cache) {
					// To properly handle login dates we need to re-read this information from database
					$user = orm::get($this->guid, true);
					$this->last_login = $user->last_login;
					$this->prev_last_login = $user->prev_last_login;
					$this->admin = $this->admin == 'yes'? true : false;
					$cache->save($this->guid, $this);
					// Drop other cache keys that rely on fetching the users
					$cache->save('drop', 'user');
				}
				return true;
			} else return false;
		// Otherwise create a new user
		} else {
			$this->owner_guid = $this->owner_guid? $this->owner_guid : ($this->subtype == 'admin'? 'sysadmin' : get_loggedin_userid());
			if (!parent::create()) return false;
			$column_value_pairs = array(
								'guid' => $this->guid,
								'fname' => $this->fname,
								'lname' => $this->lname,
								'username' => $this->username,
								'password' => $this->password,
								'salt' => $this->salt,
								'email' => $this->email,
								'language' => $this->language,
								'admin' => $this->admin);

			$result = $db->insert($config->dbprefix . 'users', $column_value_pairs);
			if ($result) {
				// Cache the new user
				if ($cache) {
					$user = orm::get($this->guid, true);
					$cache->save($this->guid, $user);
					// Drop other cache keys that rely on fetching the users
					$cache->save('drop', 'user');
				}
				return true;
			} else {
				// Since the user insert failed, remove new guid record from pip_entities
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
			// Delete the user from users and entities tables through a transaction
			$db->transaction_start();
			$db->delete($config->dbprefix . 'users', '`guid` = ?', array($this->guid));
			// Instead of a ruthless delete, this query might be substituted to simply update the archived flag and set active to 'no'
			$db->delete($config->dbprefix . 'entities', '`guid` = ?', array($this->guid));
			//$db->update($config->dbprefix . 'entities', array('active' => 'no', 'archived' => 'yes'), '`guid` = ?', array($this->guid));
			// Remove relationships
			$db->delete($config->dbprefix . 'memberships', '`user_guid` = ?', array($this->guid));
			// Remove access
			$db->delete($config->dbprefix . 'access', '`user_guid` = ?', array($this->guid));

			if ($db->transaction_complete()){
				// Clear cache for this user
				if ($cache){
					if ($cache->read($this->guid)) $cache->del($this->guid);
					if ($cache->read('access')) $cache->del('access');
				}
				return true;
			} else return false;
		} else return false;
	}

	/**
	 * Generates a secure user password using CRYPT_SHA256 as well as sets a sixteen character random salt
	 *
	 * @param string $password Password in clear text
	 * @param bool $new Is this a new password generation call or we're trying to match a password (user login action)
	 * @return string
	 */
	function encrypt_password($password, $new = true) {
		if ($new) $this->salt = '$5$' . substr(md5(microtime() . rand()), rand(0, 18), 13);
		return crypt($password, $this->salt);
	}

	/**
	 * Checks if the supplied password is the same as the one stored in the database for this user
	 *
	 * @param string $password Password to check
	 * @return bool True on success, false on failure
	 */
	public function check_password($password){
		if (!$this->username or !$this->password) return false;
		return $this->encrypt_password($password, false) == $this->password? true : false;
	}

}
