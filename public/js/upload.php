<?php
/**
 * HTML5 File Drag & Drop upload JS library using XHR2 XMLHttpRequest
 * Original code is featured as a demo at sitepoint by Craig Buckler
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


// Capture GET variables
if (isset($_GET['m']) and $_GET['m']){
	$multi = 'for (var i = 0, f; f = files[i]; i++) {
			UploadFile(f);
		}';
} else $multi = "UploadFile(files[0]);";

if (isset($_GET['t']) and $t = $_GET['t']){
	// Map file type to file MIME
	if ($t == 'audio' or $t == 'video' or $t == 'image' or $t == 'text'){
		$n = ($t == 'audio' or $t == 'image')? 'n' : '';
		$type = "if (file.type.indexOf('$t') != 0) Output('<span class=\"red\"><strong>The file you are trying to upload is NOT a{$n} $t file!</strong></span>', true);\n			else ";
	} elseif ($t == 'document'){
		$type = "if (file.type.indexOf(\"text\") != 0 && file.type.indexOf(\"word\") < 0 && file.type.indexOf(\"excel\") < 0 " .
				"&& file.type.indexOf(\"powerpoint\") < 0 && file.type.indexOf(\"document\") < 0 && file.type.indexOf(\"pdf\") < 0) " .
				"Output('<span class=\"red\"><strong>The file you are trying to upload is NOT a document file!</strong></span>', true);\n			else ";
	} elseif ($t == 'archive'){
		$type = "if (file.type.indexOf(\"zip\") < 0 && file.type.indexOf(\"rar\") < 0 && file.type.indexOf(\"tar\") < 0 " .
				"&& file.type.indexOf(\"gz\") < 0 && file.type.indexOf(\"7z\") < 0 && file.type.indexOf(\"iso\") < 0) " .
				"Output('<span class=\"red\"><strong>The file you are trying to upload is NOT an archive file!</strong></span>', true);\n			else ";
	// If the type specified was invalid, discard this check
	} else $type = '';
} elseif (isset($_GET['e']) and $e = $_GET['e']){
	// Check if this is an array of allowed extensions
	$e = explode(',', $e);
	$type = "var ext = file.name.split('.').pop();\n			if (";
	foreach ($e as $ext){
		if ($ext){
			$type .= "ext != '$ext' && ";
		}
	}
	$type = substr($type, 0, -4) . ") Output('<span class=\"red\"><strong>The file you are trying to upload should be of \"" . $_GET['e'] . "\" extension(s)</strong></span>', true);\n			else ";
} else $type = '';

$size = (isset($_GET['s']) and $_GET['s'])? $_GET['s'] : '2097152';

if (isset($_GET['p']) and $_GET['p']){
	$path = mysql_real_escape_string($_GET['p']);
} else $path = '/users/upload';

echo <<<JS

(function () {

	// getElementById
	function id(id) {
		return document.getElementById(id);
	}

	// Output information
	function Output(msg, error) {
		var m = id("messages");
		m.innerHTML = (!error)? msg + m.innerHTML : msg;
	}

	// File drag hover
	function FileDragHover(e) {
		e.stopPropagation();
		e.preventDefault();
		e.target.className = (e.type == "dragover" ? "hover" : "");
	}


	// File selection
	function FileSelectHandler(e) {
		// cancel event and hover styling
		FileDragHover(e);

		// fetch FileList object
		var files = e.target.files || e.dataTransfer.files;

		// process File object(s)
		$multi
	}

	// Display file size in a human readable format
	function readable_size(bytes) {
		var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
		if (bytes == 0) return 'n/a';
		var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
		return Math.round(bytes / Math.pow(1024, i), 2) + sizes[i];
	};

	// Output file information
	function ParseFile(file) {
		Output("<p>Type: <strong>" + file.type + "</strong> Size: <strong>" + readable_size(file.size) + "</strong></p>", true);

		// Display an image
		if (file.type.indexOf("image") == 0) {
			var reader = new FileReader();
			reader.onload = function (e) {
				Output('<p><img src="' + e.target.result + '" alt="' + file.name + '" style="max-width: 510px;"></p>');
			}
			reader.readAsDataURL(file);
		}

		// Display text
		if (file.type.indexOf("text") == 0) {
			var reader = new FileReader();
			reader.onload = function (e) {
				// Escape HTML entities
				var el = document.createElement("div");
				var e2 = e.target.result;
				el.innerText = el.textContent = e2;
				e2 = el.innerHTML;
				if (e2.length > 1000) e2 = e2.substr(0,1000) + ' <strong>...</strong>';
				// Print the result
				Output('<pre style="max-width: 510px;">' + e2 + '</pre>');
			}
			reader.readAsText(file);
		}
	}

	// Upload the file(s)
	function UploadFile(file) {
		var xhr = new XMLHttpRequest();
		var success = false;
		if (xhr.upload) {
			{$type}if (file.size > id("MAX_FILE_SIZE").value) Output('<strong class="red">File size can not exceed ' + readable_size("$size") + '!</strong>', true);
			else {

				/*
				var progressBar = document.querySelector('progress');
				xhr.upload.onprogress = function (e) {
					if (e.lengthComputable) {
						progressBar.value = (e.loaded / e.total) * 100;
						progressBar.textContent = progressBar.value; // Fallback for unsupported browsers.
					}
				};
				*/

				// Open connection to the upload controller
				xhr.open("POST", '$path', true);

				// Create progress bar and listen to the upload progress.
				// Remove the hidden class, thus showing the progress bar
				id('progress').className = "progress";
				var progress = id('bar');
				xhr.upload.onprogress = function (e) {
					if (e.lengthComputable) {
						var value = (e.loaded / e.total) * 100;
						progress.style.width = value + "%";
						progress.textContent = Math.round(value) + "%"; // Fallback for unsupported browsers.
					}
				};

				// File received/failed
				xhr.onreadystatechange = function (e) {
					progress.style.width = "100%";
					if (xhr.readyState == 4 && xhr.status == 200) {
						progress.textContent = "100%";
						progress.style.fontWeight = "bold";
						// Uploader should only bring good news
						if (xhr.responseText && xhr.responseText.indexOf("ERROR") != 0) {
							progress.textContent = xhr.responseText;
							success = true;
							// Process the file on the client side and display the results on the screen if it is an image or a text
							if (success) {
								ParseFile(file);
								// Reset the error status message
								//id('ajax_status').className = "hidden";
							}
						} else {
							id('progress').className = "progress progress-danger";
							progress.textContent = (xhr.responseText && xhr.responseText.indexOf("ERROR") == 0)? xhr.responseText : "Upload Failed";
						}
					}
					id('fileselect').style.display = "none";
					id('filedrag').style.display = "none";
				};

				// Send the proper header information along with the request
				xhr.setRequestHeader("X_FILENAME", file.name);
				xhr.setRequestHeader("X_FILETYPE", file.type);
				xhr.setRequestHeader("X_FILESIZE", file.size);
				xhr.setRequestHeader("Cache-Control", "no-cache");

				// Start upload
				xhr.send(file);
			}
		}
	}


	// Initialize
	function Init() {
		var fileselect = id("fileselect");
		var filedrag = id("filedrag");

		// file select
		fileselect.addEventListener("change", FileSelectHandler, false);

		// is XHR2 available?
		var xhr = new XMLHttpRequest();
		if (xhr.upload) {
			// file drop
			filedrag.addEventListener("dragover", FileDragHover, false);
			filedrag.addEventListener("dragleave", FileDragHover, false);
			filedrag.addEventListener("drop", FileSelectHandler, false);
			filedrag.style.display = "block";
		}
	}

	// Call initialization file
	if (window.File && window.FileList && window.FileReader) {
		Init();
	}

})();

JS;
