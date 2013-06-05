<?php
/**
 * Controller abstract class
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package controllers
 * @since 1.0
 *
 */


abstract class controller {

	private $ts;

	public function __construct(){
		$this->ts = time();
	}

	public function index(){
		// Abstract class cannot be instantiated
		throw new error('Calling an index page of the abstract controller class is not allowed!');
	}

	public function ajax() {
		action_gatekeeper();
		global $config;
		// Make sure we do not render the full page
		$config->direct_output = true;
		// Send out the result JSON encoded
		echo json_encode('AJAX is ready for action');
	}

	public function scaffolding(){
		echo 'scaffolding will be loaded here';
	}

	/**
	 * Activates a certain navbar menu item
	 *
	 * @param string $name The name of the menu item to activate
	 * @param string $menu The submenu array if this method is called recursively (optional)
	 * @return array|bool|string
	 */
	public function set_menu($name, $menu = ''){
		$root = $found = false;
		if (!$menu) {
			$menu = $_SESSION['pip_navbar_menu'];
			$root = true;
		}
		if (isset($menu) and is_array($menu) and count($menu) > 0){
			foreach ($menu as $key => $value){
				if (isset($value['submenu']) and is_array($value['submenu'])) {
					$submenu = $this->set_menu($name, $value['submenu']);
					$menu[$key]['submenu'] = $submenu['submenu'];
					$menu[$key]['active'] = $submenu['found'];
				} elseif (strcasecmp($value['name'], $name) == 0) {
					$menu[$key]['active'] = $found = true;
				} else $menu[$key]['active'] = false;
			}
			if ($root) {
				global $view;
				$view->navbar_menu = $_SESSION['pip_navbar_menu'] = $menu;
			} else return array('submenu' => $menu, 'found' => $found);
		}
		return true;
	}

	/**
	 * File upload handler. Handles AJAX uploads as well as regular form POST
	 * Extending classes will automatically have this method available
	 */
	public function upload(){
		global $config;
		$config->direct_output = true;

		// Some security checking
		if (!is_loggedin()) die('You must be logged in to upload');
		action_gatekeeper(true);

		// Get the upload folder full path. Initially the file is uploaded to the temp folder.
		// This is done to account for scenarios where the file/avatar is uploaded before the entity is created.
		// Use $entity->handle_uploads() to properly map the uploaded file to the new entity
		$dir = 'uploads/tmp/';
		$path = $config->env->root . $dir;
		// Setup directories if they don't exist
		dir_setup($dir);

		// This works for a single file upload only, by overwriting any other streams into one filename
		// @TODO: make file naming accept multi file uploads
		$system_filename = get_loggedin_user()->guid;
		$path .= $system_filename;

		$original_filename = isset($_SERVER['HTTP_X_FILENAME'])? $_SERVER['HTTP_X_FILENAME'] : false;
		$original_filesize = isset($_SERVER['HTTP_X_FILESIZE'])? $_SERVER['HTTP_X_FILESIZE'] : $_FILES['fileselect']['size']; //$_SERVER['CONTENT_LENGTH'];
		$original_filetype = isset($_SERVER['HTTP_X_FILETYPE'])? $_SERVER['HTTP_X_FILETYPE'] : $_FILES['fileselect']['type'];

		// Additional level of error checking (although it is also done on client side via JS checks)
		if (isset($_SESSION['pip_file_size']) and $original_filesize > $_SESSION['pip_file_size']) {
			echo 'ERROR: File size can not exceed ' . readable_size($_SESSION['pip_file_size']);
			exit();
		} elseif (isset($_SESSION['pip_file_type']) and $_SESSION['pip_file_type']){
			$t = $_SESSION['pip_file_type'];
			// Map file type to file MIME
			if ($t == 'audio' or $t == 'video' or $t == 'image' or $t == 'text'){
				$n = ($t == 'audio' or $t == 'image')? 'n' : '';
				if (strpos($original_filetype, $t) === false) {
					echo "ERROR: The file you are trying to upload is NOT a{$n} $t file!";
					exit();
				}
			} elseif ($t == 'document'){
				if (strpos($original_filetype, 'text') === false and strpos($original_filetype, 'word') === false and
					strpos($original_filetype, 'excel') === false and strpos($original_filetype, 'powerpoint') === false and
					strpos($original_filetype, 'document') === false and strpos($original_filetype, 'pdf') === false) {
						echo "ERROR: The file you are trying to upload is NOT a document file!";
						exit();
				}
			} elseif ($t == 'archive'){
				if (strpos($original_filetype, 'zip') === false and strpos($original_filetype, 'rar') === false and
					strpos($original_filetype, 'tar') === false and strpos($original_filetype, 'gz') === false and
					strpos($original_filetype, 'iso') === false and strpos($original_filetype, '7z') === false) {
						echo "ERROR: The file you are trying to upload is NOT an archive file!";
						exit();
				}
			}
		}

		$exists = false;
		if ($original_filename) {
			$ext = substr($original_filename, strrpos($original_filename, '.'));
			// If check on file extension was put in place, do not proceed with the upload and throw an error
			if (isset($_SESSION['pip_file_ext']) and $allowed_ext = $_SESSION['pip_file_ext'] and strpos($allowed_ext, substr($ext, 1)) === false) {
				echo "ERROR: The file you are trying to upload is NOT of '{$_SESSION['pip_file_ext']}' extension(s)!";
				exit();
			}
			$fullpath = $path . $ext;
			if (file_exists($fullpath)) $exists = true;
			// AJAX call
			file_put_contents($fullpath, file_get_contents('php://input'));
			echo 'File ' . truncate($original_filename, 45) . ' successfully uploaded!';
		} else {
			// Form submit
			$files = $_FILES['fileselect'];
			foreach ($files['error'] as $id => $err) {
				if ($err == UPLOAD_ERR_OK) {
					$original_filename = $files['name'][$id];
					$ext = substr($original_filename, strrpos($original_filename, '.'));
					// If check on file extension was put in place, do not proceed with the upload and throw an error
					if (isset($_SESSION['pip_file_ext']) and $allowed_ext = $_SESSION['pip_file_ext'] and strpos($allowed_ext, $ext) === false) {
						echo "ERROR: The file you are trying to upload is NOT of '{$_SESSION['pip_file_ext']}' extension(s)!";
						exit();
					}
					$fullpath = $path . $ext;
					if (file_exists($fullpath)) $exists = true;
					move_uploaded_file($files['tmp_name'][$id], $fullpath);
					echo 'File ' . truncate($original_filename, 45) . ' successfully uploaded!';
				}
			}
		}

		$system_filename .= $ext;
		$ext = substr($ext, 1);
		// Create a new entity only if this is a fresh file.
		// Else, the entity would stay the same as well as the link to a file; only the contents of a file would change
		if (!$exists){
			$file = new pip_file;
			$file->name = $system_filename;
			$file->path = $fullpath;
		} else {
			$file = get_file($system_filename);
			if (!$file) exit();
		}

		$file->description = 'File uploaded by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
		$file->original_name = $original_filename;
		$file->mime_type = isset($_SERVER['HTTP_X_FILETYPE'])? $_SERVER['HTTP_X_FILETYPE'] : $file->get_mime_type($ext);
		$file->extension = strtolower($ext);
		$file->size = isset($_SERVER['HTTP_X_FILESIZE'])? $_SERVER['HTTP_X_FILESIZE'] : filesize($fullpath);
		$file->save();
		$_SESSION['pip_file_guid'] = $file->guid;

		unset($_SESSION['pip_file_size']);
		unset($_SESSION['pip_file_type']);
		exit();
	}
	
	/**
	 * Printer method to display a given information in a printer-friendly fashion
	 * Since this is defined in a main controller, it will be available to all controller extending it.
	 */
	public function printer(){
		global $config;

		action_gatekeeper();

		if (!$config->input->parms) {
			pip_error('No parameters given to the printer');
			exit;
		} else {
			$guid = $config->input->parms;
			$guid = is_array($guid)? $guid[0] : $guid;
		}

		if (!$guid) {
			pip_error('Entity unique identifier MUST be supplied in order to print it');
		}
		$entity = orm::get($guid);
		if (!$entity) {
			pip_error('Entity with given identifier DOES NOT exist in our database');
		}

		// Make sure we do not render the page
		$config->direct_output = true;
		printr($entity, 'Printing Entity', false, false);
		exit();
	}
	
}

