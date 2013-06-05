<?php
/**
 * load() function - fetch URL content with the ability to submit custom PSOT headers
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package libs
 * @since 1.0
 *
 */

/**
 * Link: http://www.bin-co.com/php/scripts/load/
 * Version : 3.00.A
 *
 *
Features

    Easy to use.
    Supports Get and Post methods.
    Supports HTTP Basic Authentication - this will work - http://binny:password@example.com/
    Supports both Curl and Fsockopen. Tries to use curl - if it is not available, users fsockopen.
    Secure URL(https) supported with Curl

Options

The first argument of this function is the URL to be fetched. The second argument is an associative array. This is an optional argument. The following values are supported in this array.

return_info
    Possible values - true/false
    If this is true, the function will return an associative array rather than just a string. The array will contain 3 elements...

    headers
        An associative array containing all the headers returned by the server.
    body
        A string - the contents of the URL.
    info
        Some information about the fetch. This is the result returned by the 'curl_getinfo()' function. Supported only with Curl.

method
    Possible Values - post/get
    Specifies the method to be used.
modified_since
    If this option is set, the 'If-Modified-Since' header will be used. This will make sure that the URL will be fetched only it was modified.

Examples

The code to fetch the contents of an URL will look like this...

$contents = load('http://example.com/rss.xml');

Simple, no? This will just return the contents of the URL. If you need to do more complex stuff, just use the second argument to pass more options...

$options = array(
	'return_info'	=> true,
	'method'		=> 'post'
);
$result = load('http://www.bin-co.com/rss.xml.php?section=2',$options);
print_r($result);

The output will be like this...

Array
(
    [headers] => Array
        (
            [Date] => Mon, 18 Jun 2007 13:56:22 GMT
            [Server] => Apache/2.0.54 (Unix) PHP/4.4.7 mod_ssl/2.0.54 OpenSSL/0.9.7e mod_fastcgi/2.4.2 DAV/2 SVN/1.4.2
            [X-Powered-By] => PHP/5.2.2
            [Expires] => Thu, 19 Nov 1981 08:52:00 GMT
            [Cache-Control] => no-store, no-cache, must-revalidate, post-check=0, pre-check=0
            [Pragma] => no-cache
            [Set-Cookie] => PHPSESSID=85g9n1i320ao08kp5tmmneohm1; path=/
            [Last-Modified] => Tue, 30 Nov 1999 00:00:00 GMT
            [Vary] => Accept-Encoding
            [Transfer-Encoding] => chunked
            [Content-Type] => text/xml
        )
	[body] => ... Contents of the Page ...
	[info] => Array
        (
            [url] => http://www.bin-co.com/rss.xml.php?section=2
            [content_type] => text/xml
            [http_code] => 200
            [header_size] => 501
            [request_size] => 146
            [filetime] => -1
            [ssl_verify_result] => 0
            [redirect_count] => 0
            [total_time] => 1.113792
            [namelookup_time] => 0.180019
            [connect_time] => 0.467973
            [pretransfer_time] => 0.468035
            [size_upload] => 0
            [size_download] => 2274
            [speed_download] => 2041
            [speed_upload] => 0
            [download_content_length] => 0
            [upload_content_length] => 0
            [starttransfer_time] => 0.826031
            [redirect_time] => 0
        )
)
 *
 *
 */
function load($url, $options = array()) {
	$default_options = array('method' => 'get', 'post_data' => false, 'return_info' => false, 'return_body' => true, 'cache' => false, 'referer' => '', 'headers' => array(), 'session' => false, 'session_close' => false,);
	// Sets the default options.
	foreach ($default_options as $opt => $value) {
		if (!isset($options[$opt])) {
			$options[$opt] = $value;
		}
	}

	$url_parts = parse_url($url);
	$ch = false;
	$info = array( //Currently only supported by curl.
		'http_code' => 200);
	$response = '';

	$send_header = array('Accept' => 'text/*', 'User-Agent' => 'phpiphany/1.00.A (http://www.phpiphany.com)') + $options['headers']; // Add custom headers provided by the user.

	if ($options['cache']) {
		$cache_folder = path(sys_get_temp_dir(), 'php-load-function');
		if (isset($options['cache_folder'])) {
			$cache_folder = $options['cache_folder'];
		}
		if (!file_exists($cache_folder)) {
			$old_umask = umask(0); // Or the folder will not get write permission for everybody.
			mkdir($cache_folder, 0777);
			umask($old_umask);
		}

		$cache_file_name = md5($url) . '.cache';
		$cache_file = path($cache_folder, $cache_file_name); //Don't change the variable name - used at the end of the function.

		if (file_exists($cache_file)) { // Cached file exists - return that.
			$response = file_get_contents($cache_file);

			//Seperate header and content
			$separator_position = strpos($response, "\r\n\r\n");
			$header_text = substr($response, 0, $separator_position);
			$body = substr($response, $separator_position + 4);

			foreach (explode("\n", $header_text) as $line) {
				$parts = explode(": ", $line);
				if (count($parts) == 2) {
					$headers[$parts[0]] = chop($parts[1]);
				}
			}
			$headers['cached'] = true;

			if (!$options['return_info']) {
				return $body;
			} else {
				return array('headers' => $headers, 'body' => $body, 'info' => array('cached' => true));
			}
		}
	}

	if (isset($options['post_data'])) { //There is an option to specify some data to be posted.
		$options['method'] = 'post';

		if (is_array($options['post_data'])) { //The data is in array format.
			$post_data = array();
			foreach ($options['post_data'] as $key => $value) {
				$post_data[] = "$key=" . urlencode($value);
			}
			$url_parts['query'] = implode('&', $post_data);
		} else { //Its a string
			$url_parts['query'] = $options['post_data'];
		}
	} elseif (isset($options['multipart_data'])) { //There is an option to specify some data to be posted.
		$options['method'] = 'post';
		$url_parts['query'] = $options['multipart_data'];
		/*
					This array consists of a name-indexed set of options.
					For example,
					'name' => array('option' => value)
					Available options are:
					filename: the name to report when uploading a file.
					type: the mime type of the file being uploaded (not used with curl).
					binary: a flag to tell the other end that the file is being uploaded in binary mode (not used with curl).
					contents: the file contents. More efficient for fsockopen if you already have the file contents.
					fromfile: the file to upload. More efficient for curl if you don't have the file contents.

					Note the name of the file specified with fromfile overrides filename when using curl.
				 */
	}

	///////////////////////////// Curl /////////////////////////////////////
	//If curl is available, use curl to get the data.
	if (function_exists("curl_init") and (!(isset($options['use']) and $options['use'] == 'fsocketopen'))) { //Don't use curl if it is specifically stated to use fsocketopen in the options

		if (isset($options['post_data'])) { //There is an option to specify some data to be posted.
			$page = $url;
			$options['method'] = 'post';

			if (is_array($options['post_data'])) { //The data is in array format.
				$post_data = array();
				foreach ($options['post_data'] as $key => $value) {
					$post_data[] = "$key=" . urlencode($value);
				}
				$url_parts['query'] = implode('&', $post_data);

			} else { //Its a string
				$url_parts['query'] = $options['post_data'];
			}
		} else {
			if (isset($options['method']) and $options['method'] == 'post') {
				$page = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
			} else {
				$page = $url;
			}
		}

		if ($options['session'] and isset($GLOBALS['_binget_curl_session'])) {
			$ch = $GLOBALS['_binget_curl_session'];
		} //Session is stored in a global variable
		else {
			$ch = curl_init($url_parts['host']);
		}

		curl_setopt($ch, CURLOPT_URL, $page) or die("Invalid cURL Handle Resouce");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Just return the data - not print the whole thing.
		curl_setopt($ch, CURLOPT_HEADER, true); //We need the headers
		curl_setopt($ch, CURLOPT_NOBODY, !($options['return_body'])); //The content - if true, will not download the contents. There is a ! operation - don't remove it.
		//curl_setopt($ch, CURLOPT_PROXY, $proxy); //Optional: use proxy
		$tmpdir = NULL; //This acts as a flag for us to clean up temp files
		$mode = '0700'; //Folder permissions
		if (isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
			curl_setopt($ch, CURLOPT_POST, true);
			if (is_array($url_parts['query'])) {
				//multipart form data (eg. file upload)
				$postdata = array();
				foreach ($url_parts['query'] as $name => $data) {
					if (isset($data['contents']) && isset($data['filename'])) {
						if (!isset($tmpdir)) { //If the temporary folder is not specifed - and we want to upload a file, create a temp folder.
							//  :TODO:
							$dir = sys_get_temp_dir();
							$prefix = 'load';

							if (substr($dir, -1) != '/') {
								$dir .= '/';
							}
							do {
								$path = $dir . $prefix . mt_rand(0, 9999999);
							} while (!mkdir($path, $mode));

							$tmpdir = $path;
						}
						$tmpfile = $tmpdir . '/' . $data['filename'];
						file_put_contents($tmpfile, $data['contents']);
						$data['fromfile'] = $tmpfile;
					}
					if (isset($data['fromfile'])) {
						// Not sure how to pass mime type and/or the 'use binary' flag
						$postdata[$name] = '@' . $data['fromfile'];
					} elseif (isset($data['contents'])) {
						$postdata[$name] = $data['contents'];
					} else {
						$postdata[$name] = '';
					}
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $url_parts['query']);
			}
		}

		//Set the headers our spiders sends
		curl_setopt($ch, CURLOPT_USERAGENT, $send_header['User-Agent']); //The Name of the UserAgent we will be using ;)
		$custom_headers = array("Accept: " . $send_header['Accept']);
		if (isset($options['modified_since'])) {
			array_push($custom_headers, "If-Modified-Since: " . gmdate('D, d M Y H:i:s \G\M\T', strtotime($options['modified_since'])));
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
		if ($options['referer']) {
			curl_setopt($ch, CURLOPT_REFERER, $options['referer']);
		}

		curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/binget-cookie.txt"); //If ever needed...
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$custom_headers = array();
		unset($send_header['User-Agent']); // Already done (above)
		foreach ($send_header as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $item) {
					$custom_headers[] = "$name: $item";
				}
			} else {
				$custom_headers[] = "$name: $value";
			}
		}
		if (isset($url_parts['user']) and isset($url_parts['pass'])) {
			$custom_headers[] = "Authorization: Basic " . base64_encode($url_parts['user'] . ':' . $url_parts['pass']);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);

		$response = curl_exec($ch);

		if (isset($tmpdir)) {
			//rmdirr($tmpdir); //Cleanup any temporary files :TODO:
		}

		$info = curl_getinfo($ch); //Some information on the fetch

		if ($options['session'] and !$options['session_close']) {
			$GLOBALS['_binget_curl_session'] = $ch;
		} //Dont close the curl session. We may need it later - save it to a global variable
		else {
			curl_close($ch);
		} //If the session option is not set, close the session.

		//////////////////////////////////////////// FSockOpen //////////////////////////////
	} else { //If there is no curl, use fsocketopen - but keep in mind that most advanced features will be lost with this approch.

		if (!isset($url_parts['query']) || (isset($options['method']) and $options['method'] == 'post')) {
			$page = $url_parts['path'];
		} else {
			$page = $url_parts['path'] . '?' . $url_parts['query'];
		}

		if (!isset($url_parts['port'])) {
			$url_parts['port'] = ($url_parts['scheme'] == 'https' ? 443 : 80);
		}
		$host = ($url_parts['scheme'] == 'https' ? 'ssl://' : '') . $url_parts['host'];
		$fp = fsockopen($host, $url_parts['port'], $errno, $errstr, 30);
		if ($fp) {
			$out = '';
			if (isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
				$out .= "POST $page HTTP/1.1\r\n";
			} else {
				$out .= "GET $page HTTP/1.0\r\n"; //HTTP/1.0 is much easier to handle than HTTP/1.1
			}
			$out .= "Host: $url_parts[host]\r\n";
			foreach ($send_header as $name => $value) {
				if (is_array($value)) {
					foreach ($value as $item) {
						$out .= "$name: $item\r\n";
					}
				} else {
					$out .= "$name: $value\r\n";
				}
			}
			$out .= "Connection: Close\r\n";

			//HTTP Basic Authorization support
			if (isset($url_parts['user']) and isset($url_parts['pass'])) {
				$out .= "Authorization: Basic " . base64_encode($url_parts['user'] . ':' . $url_parts['pass']) . "\r\n";
			}

			//If the request is post - pass the data in a special way.
			if (isset($options['method']) and $options['method'] == 'post') {
				if (is_array($url_parts['query'])) {
					//multipart form data (eg. file upload)

					// Make a random (hopefully unique) identifier for the boundary
					srand((double)microtime() * 1000000);
					$boundary = "---------------------------" . substr(md5(rand(0, 32000)), 0, 10);

					$postdata = array();
					$postdata[] = '--' . $boundary;
					foreach ($url_parts['query'] as $name => $data) {
						$disposition = 'Content-Disposition: form-data; name="' . $name . '"';
						if (isset($data['filename'])) {
							$disposition .= '; filename="' . $data['filename'] . '"';
						}
						$postdata[] = $disposition;
						if (isset($data['type'])) {
							$postdata[] = 'Content-Type: ' . $data['type'];
						}
						if (isset($data['binary']) && $data['binary']) {
							$postdata[] = 'Content-Transfer-Encoding: binary';
						} else {
							$postdata[] = '';
						}
						if (isset($data['fromfile'])) {
							$data['contents'] = file_get_contents($data['fromfile']);
						}
						if (isset($data['contents'])) {
							$postdata[] = $data['contents'];
						} else {
							$postdata[] = '';
						}
						$postdata[] = '--' . $boundary;
					}
					$postdata = implode("\r\n", $postdata) . "\r\n";
					$length = strlen($postdata);
					$postdata = 'Content-Type: multipart/form-data; boundary=' . $boundary . "\r\n" . 'Content-Length: ' . $length . "\r\n" . "\r\n" . $postdata;

					$out .= $postdata;
				} else {
					$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
					$out .= 'Content-Length: ' . strlen($url_parts['query']) . "\r\n";
					$out .= "\r\n" . $url_parts['query'];
				}
			}
			$out .= "\r\n";

			fwrite($fp, $out);
			while (!feof($fp)) {
				$response .= fgets($fp, 128);
			}
			fclose($fp);
		}
	}

	//Get the headers in an associative array
	$headers = array();

	if ($info['http_code'] == 404) {
		$body = "";
		$headers['Status'] = 404;
	} else {
		//Seperate header and content
		$header_text = substr($response, 0, $info['header_size']);
		$body = substr($response, $info['header_size']);

		foreach (explode("\n", $header_text) as $line) {
			$parts = explode(": ", $line);
			if (count($parts) == 2) {
				if (isset($headers[$parts[0]])) {
					if (is_array($headers[$parts[0]])) {
						$headers[$parts[0]][] = chop($parts[1]);
					} else {
						$headers[$parts[0]] = array($headers[$parts[0]], chop($parts[1]));
					}
				} else {
					$headers[$parts[0]] = chop($parts[1]);
				}
			}
		}

	}

	if (isset($cache_file)) { //Should we cache the URL?
		file_put_contents($cache_file, $response);
	}

	if ($options['return_info']) {
		return array('headers' => $headers, 'body' => $body, 'info' => $info, 'curl_handle' => $ch);
	}
	return $body;
}


/**
 * Takes one or more file names and combines them, using the correct path separator for the current platform and then return the result.
 * Example: path('/var','www/html/','try.php'); // returns '/var/www/html/try.php'
 *
 * @return string Proper path
 **/
function path() {
	$path = '';
	$arguments = func_get_args();
	$args = array();
	foreach ($arguments as $a) {
		if ($a !== '') {
			$args[] = $a; //Removes the empty elements
		}
	}

	$arg_count = count($args);
	for ($i = 0; $i < $arg_count; $i++) {
		$folder = $args[$i];

		if ($i != 0 and $folder[0] == DIRECTORY_SEPARATOR) {
			$folder = substr($folder, 1);
		} //Remove the first char if it is a '/' - and its not in the first argument
		if ($i != $arg_count - 1 and substr($folder, -1) == DIRECTORY_SEPARATOR) {
			$folder = substr($folder, 0, -1);
		} //Remove the last char - if its not in the last argument

		$path .= $folder;
		if ($i != $arg_count - 1) {
			$path .= DIRECTORY_SEPARATOR;
		} //Add the '/' if its not the last element.
	}
	return $path;
}
