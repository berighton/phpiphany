<?php
/**
 * Object Relational Mapper (ORM) is a factory class that handles creation of a corresponding pip entity
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


class orm {

	/**
	 * Gets a proper pip entity class from a given guid
	 * Returns an instantiated object populated with information from database
	 *
	 * @static
	 * @param string $guid Entity guid to lookup
	 * @param bool $invalidate_cache If set to true, the information would not be read from cache but instead force a DB read (optional)
	 * @return object pip_entity
	 * @throws error
	 */
	static function get($guid, $invalidate_cache = false){
		global $db, $cache, $config;
		if (!$db) throw new error('Database server is not running or not configured properly in the settings file!');

		// Check if the object matching this guid is stored in cache.
		// If so, retrieve it from there rather than making an expensive database request
		if (!$invalidate_cache and $cache and $cached = $cache->read($guid)){
			return $cached;
		// Otherwise ask the database
		} else {
			$db->query("SELECT s.class, e.* FROM {$config->dbprefix}entities e, {$config->dbprefix}entity_subtypes s WHERE guid = ? AND e.subtype = s.subtype", array($guid));
			if ($row = $db->fetch_assoc()){
				// We do not need the 'archived' column in our results. It is used only for internal DB maintenance
				unset($row['archived']);
				// If subtype class was defined and is callable, make an object of that class
				$class = array_shift($row);
				if ($class and class_exists($class, false)) $entity = new $class;
				// First check by type and inside for any specific subtypes
				if ($row['type'] == 'user') {
					$db->query("SELECT `fname`, `lname`, `username`, `password`, `salt`, `email`, `language`, `admin`, `code`, `last_login`, `prev_last_login` FROM {$config->dbprefix}users WHERE guid = ?", array($guid));
					$row2 = $db->fetch_obj();
					$entity = (isset($entity) and $entity)? $entity : new pip_user();
					$entity->name = $row2->fname . ' ' . $row2->lname;
				} elseif ($row['type'] == 'group'){
					$db->query("SELECT `name`, `description`, `access` FROM {$config->dbprefix}groups WHERE guid = ?", array($guid));
					$row2 = $db->fetch_obj();
					$entity = (isset($entity) and $entity)? $entity : new pip_group();
				} else {
					$db->query("SELECT `name`, `description` FROM {$config->dbprefix}objects WHERE guid = ?", array($guid));
					$row2 = $db->fetch_obj();
					// Files
					if ($row['subtype'] == 'file'){
						$db->query("SELECT f.* FROM {$config->dbprefix}files f, {$config->dbprefix}objects o WHERE f.guid = o.guid and f.guid = ?", array($guid));
						$row3 = $db->fetch_obj();
						$entity = (isset($entity) and $entity)? $entity : new pip_file();
					} else $entity = (isset($entity) and $entity)? $entity : new pip_object();
				}

				// Populate the entity object with fields from the database
				foreach ($row as $k => $v) $entity->$k = $v;
				if (isset($row2) and $row2) foreach ($row2 as $k => $v) $entity->$k = $v;
				if (isset($row3) and $row3) foreach ($row3 as $k => $v) $entity->$k = $v;

				// Get entity relationship if it's not a group
				if ($row['type'] != 'group'){
					$db->query("SELECT `group_guid` FROM {$config->dbprefix}memberships WHERE user_guid = ?", array($guid));
					$row2 = $db->fetch_assoc_all();
					if ($row2) foreach ($row2 as $v) $entity->membership[] = $v['group_guid'];
				} else {
					// Otherwise get all group members and objects
					$db->query("SELECT `user_guid` as 'entity_guid' FROM {$config->dbprefix}memberships WHERE group_guid = ?", array($guid));
					$row2 = $db->fetch_assoc_all();
					if ($row2) {
						foreach ($row2 as $v) {
							$related = self::get($v['entity_guid']);
							if ($related->type == 'user') $entity->members[] = $v['entity_guid'];
							else $entity->objects[] = $v['entity_guid'];
						}
					}
				}

				// Store the result in cache
				if ($cache){
					$entity->cached = new dater() . ': expires in 3 days from this date';
					$cache->save($guid, $entity);
				}
				// Return the resulted entity object
				return $entity;
			} else {
				trigger_error('<strong class="red">Invalid GUID!</strong> The supplied GUID "<u>' . $guid . '</u>" was not found in the database');
				return false;
			}
		}
	}

	/**
	 * Extend the scope to any entity By populating a given class with data retrieved from running the query
	 *
	 * Usage:
	 * $blogger_object = orm::build('SELECT * FROM `blog` WHERE id = 416', 'Blogger');
	 *
	 * @param string $query SQL query that returns one row
	 * @param string $class The class name to use that needs to be populated with data
	 * @return mixed Returns either the instantiated $class or false on any error
	 */
	static function build($query, $class){
		global $view;
		if ($class and class_exists($class)) {
			global $db;
			$db->query($query);
			if ($entity = $db->fetch_assoc()) {
				$obj = new $class;
				foreach ($entity as $k => $v){
					$obj->$k = $v;
				}
				return $obj;
			} else {
				$msg = 'Invalid query passed into the ORM class. No results found!';
				echo $view->error($msg);
				trigger_error('<strong class="red">' . $msg . '</strong>');
				return false;
			}
		} else {
			$msg = 'Invalid class name passed into the ORM class. Cannot continue!';
			echo $view->error($msg);
			trigger_error('<strong class="red">' . $msg . '</strong>');
			return false;
		}
	}

}

