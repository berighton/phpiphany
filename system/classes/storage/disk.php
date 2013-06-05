<?php
/**
 * Local file storage class that writes to system hard disk
 * This is the slowest method to use for caching, although it is heavily used for working with system temp data
 *
 * Like other caching classes, the local file storage class extends the similar methods to store and retrieve data
 * save(), add(), read() and del(). But unlike those classes, it stores the filename as a key to access data
 * Also, since it can be used for non-volatile caching, the physical files are not deleted automatically
 * (the class does not have a __destruct) for the purpose of being access later.
 * If this is a temp file on the other hand, one should issue a destroy() method upon exiting a script.
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package system/storage
 * @since 1.0
 *
 */


class pip_disk {

	private $dir;
	public $filename;

	/**
	 * Construct for the file class
	 * Sets the default place for temp files to application_path/tmp
	 *
	 * @param string $dir Custom directory inside the /tmp/ folder
	 * @param bool $in_temp Default place to create a folder is in temp, or a specific location on a server
	 */
	public function __construct($dir = '', $in_temp = true){
		// If a custom directory inside temp was supplied, check if it exists and cd. Otherwise use a temp folder
		$this->dir = $in_temp? 'tmp/' : '';
		if ($dir) $this->dir .= $dir;

		// Create the dir if it does not exists and asign the full path to our private variable
		$this->dir = ($dir = dir_setup($this->dir))? $dir : $this->dir;

		// Generate a unique filename
		$this->filename = md5($_SERVER['REQUEST_TIME'] * rand(1, 100000000));

	}

	/**
	 * Reads the contents of a temp file. Tries to read from a default file if a filename was not supplied.
	 *
	 * @param string $file The filename where to read the data from (optional)
	 * @return string Returns the contents of the file
	 */
	public function read($file = null){
		$file = $file? $this->dir . $file : $this->dir . $this->filename;
		$result = is_file($file)? file_get_contents($file) : false;
		// Unserialize data if it was serialized upon file save
		if (substr($result, -1) == '}' and strpos($result, 'i:0;') !== false) $result = unserialize($result);
		return $result;
	}

	/**
	 * Writes to a temp file. Creates new if no arguments supplied.
	 *
	 * @param string $file The filename where to write temp data (optional)
	 * @param string $data Data to write to a temp file
	 * @return boolean|string Returns number of bytes written or false if the data were not successfully written to a temp file
	 */
	public function save($file = null, $data){
		$file = $file? $this->dir . $file : $this->dir . $this->filename;
		// Serialize data if it is not a string
		if (is_array($data) or is_object($data)) $data = serialize($data);
		$result = file_put_contents($file, $data, LOCK_EX);
		return $result;
	}

	/**
	 * Appends to an existing local temp file
	 *
	 * @param string $file The filename where to write temp data (optional)
	 * @param string $data Data to append to a file
	 * @return boolean|string Returns number of bytes written or false if the data were not successfully written to a temp file
	 */
	public function add($file = null, $data){
		$file = $file? $this->dir . $file : $this->dir . $this->filename;
		$result = file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
		return $result;
	}

	/**
	 * Flushes the contents of a temp file
	 *
	 * @param string $file The filename of a file to empty (optional)
	 * @return boolean Returns true
	 */
	public function del($file = null){
		$file = $file ? $this->dir . $file : $this->dir . $this->filename;
		file_put_contents($file, null, LOCK_EX);
		return true;
	}

	/**
	 * Deletes the file used for local storage
	 *
	 * @param string $file The filename of a file to delete (optional)
	 * @return boolean Returns true
	 */
	public function destroy($file = null){
		$file = $file ? $this->dir . $file : $this->dir . $this->filename;
		if (is_file($file) and unlink($file)) {
			return true;
		} else {
			// If it was not possible to delete the file (permissions issue), simply empty it out
			$this->del();
			return true;
		}
	}
}

