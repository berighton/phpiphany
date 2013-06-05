/**
 * phpiphany helper functions
 * Non dependant on any JS framework
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package js
 * @since 1.0
 *
 */



/**
 * AJAX handler
 * Based on Andreas Lagerkvist's code, modified for phpiphany
 *
 *
 * USAGE:
 *
 * // GET article with ID 12 and alert its contents
 * ajax({
 *   url: 'get-article.php',
 *   data: 'id=12',
 *   callback: function (data) {
 *       alert(data);
 *   }
 * });
 *
 * // POST a comment to post-comment.php
 * ajax({
 *   method: 'POST',
 *   url: 'post-comment.php',
 *   data: 'author=John Doe&email=johndoe@johndoe.com&content=Hi, my name is John Doe',
 *   callback: function (data) {
 *       alert('Thanks for your comment!');
 *   }
 * });
 *
 * @param conf
 * @param update_elem_id
 */
function ajax(conf, update_elem_id) {
	// Create config
	var config = {
		method:		conf.method || 'get',
		url:		conf.url,
		data:		conf.data || '',
		callback:	conf.callback || function (data) {
			if (update_elem_id) {
				document.getElementById(update_elem_id).innerHTML = data;
			}
		}
	};

	// Create ajax request object
	var request;

	try {
		request = new XMLHttpRequest();
	}catch (e) {
		request = new ActiveXObject('Microsoft.XMLHTTP');
	}

	// This runs when request is complete
	var on_success = function () {
		if (request.readyState == 4) {
			config.callback(request.responseText);
		}
	};

	// Send the request
	if (config.method.toUpperCase() == 'POST') {
		request.open('POST', config.url, true);
		request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		request.onreadystatechange = on_success;
		request.send(config.data);
	} else {
		request.open('GET', config.url + '?' + config.data, true);
		request.onreadystatechange = on_success;
		request.send(null);
	}
}