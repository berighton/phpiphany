<?php
/**
 * Download Controller to manage file downloads (by GUID)
 * Pushes the file to the browser (force download)
 *
 * Usage: phpiphany.com/download/8mCwujAs3qzn
 *
 * Upon calling the download, increments the download counter to be used for statistics
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
 
class download extends controller {

	public function __construct(){
		parent::__construct();
	}

	/**
	 * Force file download
	 *
	 */
	public function index(){

/*
		// If you want to display a page before the download (a counter or captcha for example), simply render the page with $view
		// You want also want to force valid tokens for successfull downloads with action_gatekeeper()
		global $view, $router;
		$guid = $router->action;
		$file = orm::get($guid);
		$view->title = 'Download file "' . $file->name . '"';
		$file->download_counter++;
		$file->save();

		$view->content = printr($file, 'File Information', true);
		output_file($file->path, $file->name, $file->mime_type);

		$view->render_page();
		exit();
*/


		// For now, allow file downloads only for the admin. In future, each file will need to have public/private flag set
		admin_only();

		global $router;
		$guid = $router->action;
		$file = orm::get($guid, true);
		// Allow only valid file guids
		if (!$file or $file->subtype != 'file') pip_error("File with GUID \"$guid\" does not exist!");
		// Cleanup: if physical file does not exist, delete this object
		if (!file_exists($file->path)){
			$file->delete();
			pip_error("Invalid file GUID \"$guid\"! Physical file cannot be found because it was either deleted or relocated. Deleting the object...");
			exit();
		}


		// Push the file

		$name = $file->name ? rawurldecode($file->name) : $file->path;
		$size = filesize($file->path);

		ob_end_clean(); //turn off output buffering to decrease cpu usage

		// Required for IE, otherwise Content-Disposition may be ignored
		if (ini_get('zlib.output_compression')) {
			ini_set('zlib.output_compression', 'Off');
		}

		header('Content-Type: ' . $file->mime_type);
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header("Content-Transfer-Encoding: binary");
		header('Accept-Ranges: bytes');

		// The three lines below basically make the download non-cacheable
		header("Cache-control: private");
		header('Pragma: private');
		header("Expires: Oct, 11 Jul 1997 09:00:00 GMT");

		// Multipart-download and download resuming support
		if (isset($_SERVER['HTTP_RANGE'])) {
			list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
			list($range) = explode(",", $range, 2);
			list($range, $range_end) = explode("-", $range);
			$range = intval($range);
			if (!$range_end) {
				$range_end = $size - 1;
			} else {
				$range_end = intval($range_end);
			}

			$new_length = $range_end - $range + 1;
			header("HTTP/1.1 206 Partial Content");
			header("Content-Length: $new_length");
			header("Content-Range: bytes $range-$range_end/$size");
		} else {
			$new_length = $size;
			header("Content-Length: " . $size);
		}

		// Output the file itself
		$chunksize = 1 * (1024 * 1024); //you may want to change this
		$bytes_send = 0;
		if ($path = fopen($file->path, 'r')) {
			if (isset($_SERVER['HTTP_RANGE'])) {
				fseek($path, $range);
			}

			while (!feof($path) && (!connection_aborted()) && ($bytes_send < $new_length)) {
				$buffer = fread($path, $chunksize);
				echo($buffer);
				flush();
				$bytes_send += strlen($buffer);
			}
			fclose($path);
		} else {
			pip_error('Error - can not open download file.');
		}

		// The file was pushed so we can update the download counter
		$file->download_counter++;
		$file->save();

		exit();
	}

	/**
	 * Manage files stored in the system (view description, download counter and an option to delete)
	 *
	 */
	public function manage(){
		// Only admins can manage files
		admin_only();

		$this->set_menu('download manager');

		global $view, $config;
		$view->title = 'All Files';
		$view->content = "<h1>$view->title</h1><br>\n";
		array_push($view->custom_js, $view->assets_dir . 'js/tooltip.js');
		$view->content .= get_entities(array('type' => 'object',
											'subtype' => 'file',
											'joins' => $config->dbprefix . 'files f',
											'wheres' => 'f.guid = e.guid',
											'order_by' => 'f.download_counter DESC, f.size DESC'));

		$view->render_page();
		exit();
	}

	/**
	 * Deletes a selected file (admin only and action token are mandatory)
	 *
	 */
	public function delete(){
		admin_only();
		action_gatekeeper();

		global $config;
		$guid = isset($config->input->parms)? $config->input->parms : '';

		if (!is_array($guid) and $guid){
			$file = orm::get($guid);
			if ($file){
				// Checking for valid path is not necessary here since the file entity exists.
				// unlink() will simply fail, but the pip entity needs to be deleted properly.
				$name = $file->name;
				if ($file->delete()) {
					pip_success("File \"$name\" successfully deleted!", '/download/manage');
				}
				else pip_error("Unable to delete file \"$name\". Please check logs");
				exit();
			} else pip_error("File with GUID \"$guid\" does not exist!");
		} else forward();
	}

}
