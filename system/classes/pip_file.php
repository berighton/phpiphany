<?php
/**
 * File class extending pip_object
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


class pip_file extends pip_object {

	// Redundant definition of these properties ensures their appearance first in the list when viewed the object with print_r
	public $guid, $type, $subtype, $description;

	public $name, $original_name, $mime_type, $extension, $size, $path, $owner, $download_counter;

	public function __construct(){
		parent::__construct();

		$this->type = 'object';
		$this->subtype = 'file';
		$this->owner = 'user';
	}

	/**
	 * Saves the current file entity if this object has a guid (exists)
	 * Otherwise creates a new file
	 *
	 * @return bool Success or failure
	 */
	public function save(){
		global $cache, $db, $config;
		// If entity exists, simply update it
		if ($this->guid and (($cache and $cache->read($this->guid)) or $db->fetch_assoc($db->query('SELECT `guid` FROM ' . $config->dbprefix . 'entities WHERE `guid` = ?', array($this->guid))))){
			if (!parent::save()) return false;
			$column_value_pairs = array(
								'filename' => $this->name,
								'original_name' => $this->original_name,
								'mime_type' => $this->mime_type,
								'extension' => $this->extension,
								'size' => $this->size,
								'path' => $this->path,
								'download_counter' => (int)$this->download_counter);

			$result = $db->update($config->dbprefix . 'files', $column_value_pairs, 'guid = ?', array($this->guid));
			if ($result){

				// Recreate the entries for the owner (if group)
				if ($this->owner == 'group') $db->insert($config->dbprefix . 'access', array('group_guid' => $this->owner_guid, 'object_guid' => $this->guid));

				// Update cache
				if ($cache) {
					$file = orm::get($this->guid, true);
					$cache->save($this->guid, $file);

					// Drop other cache keys that rely on fetching the object as well as access
					$cache->save('drop', 'file');
					$cache->del('access');
				}
				return true;
			} else return false;
		// Otherwise create a new file object
		} else {
			if (!parent::save()) return false;
			$column_value_pairs = array(
								'guid' => $this->guid,
								'filename' => $this->name,
								'original_name' => $this->original_name,
								'mime_type' => $this->mime_type,
								'extension' => $this->extension,
								'size' => $this->size,
								'path' => $this->path);

			$result = $db->insert($config->dbprefix . 'files', $column_value_pairs);
			if ($result) {

				// Cache the new object
				if ($cache) {
					$file = orm::get($this->guid, true);
					$cache->save($this->guid, $file);

					// Drop other cache keys that rely on fetching the object as well as access
					$cache->save('drop', 'file');
					$cache->del('access');
				}
				return true;
			} else {
				// Since the file insert failed, remove new guid record from pip_objects
				parent::delete();
				return false;
			}
		}
	}

	/**
	 * Delete handler. Since invokation of parent method would delete object and entity, all we have to do is delete 'file' entry
	 * Note that each entity extending pip_entity has to have its own definition of delete() method
	 *
	 * @return bool True on successful delete, false on failure
	 */
	public function delete(){
		global $config, $db;
		$path = $this->path;
		// Delete the file from files table
		if ($db->delete($config->dbprefix . 'files', '`guid` = ?', array($this->guid))) {
			// Green light only if the object was successfully deleted through a parent method
			if (parent::delete()) {
				global $cache;
				if ($cache) $cache->save('drop', 'file');
				if (file_exists($path)) unlink($path);
				return true;
			}
			else return false;
		} else return false;
	}

	/**
	 * Gets the common MIME types and tries to lookup the supplied file extension
	 * It is possible to provide an alternative libs/mime.types file,
	 * but Apache does a good job keeping up with the latest types
	 *
	 * @param string $extension File extension
	 * @return array returns the matches extension MIME type or a full array
	 */
	public function get_mime_type($extension = '') {
		global $config;
		$apache_types = explode("\n", file_get_contents($config->env->root . '/libs/mime.types'));
		$mime_types = array();
		foreach ($apache_types as $type) {
			if (isset($type[0]) && $type[0] !== '#' && preg_match_all('#([^\s]+)#', $type, $out) && isset($out[1]) && ($count = count($out[1])) > 1) {
				for ($i = 1; $i < $count; $i++) {
					$mime_types[$out[1][$i]] = $out[1][0];
				}
			}
		}
		return $extension? $mime_types[$extension] : $mime_types;
	}

	/**
	 * Gets the corresponding icon for the file depending on the MIME type
	 *
	 * @return bool|string Returns the icon path if match to extension was done successfully, false otherwise
	 */
	public function get_icon(){
		if ($ext = $this->extension){
			global $config;
			// The URL relative icon path
			$path = $config->site_url . $config->mime_icons . '/';
			// The true full icon file path
			$dir = scandir($config->env->root . 'public/' . $config->mime_icons);
			foreach ($dir as $icon) {
				// Ideally we should have a match for most of the commonly known extension
				if ($ext == pathinfo($icon, PATHINFO_FILENAME)) return $path . $icon;
			}
			// Since we're still here, it means the icon was not found
			// We will have to map to the closest one we have

			// Archive
			if ($ext == 'tar' or $ext == 'tgz' or $ext == 'tbz' or $ext == 'gz' or $ext == 'bz2') return $path . 'zip.png';
			// Video
			elseif (strpos($this->mime_type, 'video') !== false) return $path . 'mov.png';
			// Audio
			elseif (strpos($this->mime_type, 'audio') !== false) return $path . 'sound.png';
			// Image
			elseif (strpos($this->mime_type, 'image') !== false) return $path . 'jpg.png';
			// Text
			elseif (strpos($this->mime_type, 'text') !== false or $ext == 'sql' or $ext == 'xml') return $path . 'notepad.png';
			// LibreOffice documents
			elseif ($ext == 'odp' or $ext == 'fodp') return $path . 'ppt.png';
			elseif ($ext == 'ods' or $ext == 'fods') return $path . 'xls.png';
			elseif ($ext == 'odg' or $ext == 'fodg' or $ext == 'fodt') return $path . 'odt.png';
			// Else load default
			else return $path . 'unknown.png';
		}
		return false;
	}

}
