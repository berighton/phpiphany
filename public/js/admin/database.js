/**
 *  Returns an array of children elements of a given parent element based on tag and class name.
 *
 *  @param  parent          The parent element
 *  @param  tag             The HTML tags that we are looking for in the parent element
 *  @param  class_name      Optional. Narrows down the search to only the tags having the indicated class
 *  @param  first_only      Optional. Boolean. If set to TRUE, will return only the first child.
 *
 */
function db_get_elements(parent, tag, class_name, first_only) {

	if (parent){
		// initialize the array to be returned
		var result = new Array();
		// get children elements matching tag_name
		var children = parent.getElementsByTagName(tag);
		// the total number of found children
		var length = children.length;

		// iterate through the found elements
		for (var i = 0; i < length; i++) {
			// current child
			var child = children.item(i);
			// if we need to narrow down the search to elements having a specific class
			if (undefined != class_name) {
				// get the classes of the element
				var classes = child.getAttribute('class');
				// if the element has any classes and the sought class is among them
				if (null != classes && classes.indexOf(class_name) > -1) {
					// if we only need to return the first found element, return it
					if (undefined != first_only && first_only === true) return child;
					// ...otherwise, add it to results
					result.push(child);
				}

				// if no need to narrow down the search to elements having a specific class
			} else {
				// if we only need to return the first found element, return it
				if (undefined != first_only && first_only === true) return child;
				// ...otherwise, add it to results
				result.push(child);
			}
		}

		// if we only need to return the first found element and we are here, it means that there were no elements found
		// and return false
		// otherwise, if there are no elements found, return false or, return the found elements if any
		return (undefined != first_only && first_only === true) ? false : (result.length > 0 ? result : false);
	}

}

/**
 *  Sets the "display" css property for an array of elements.
 *
 *  @param  elements    An array of elements to set the "display" css property for
 *  @param  display     The "display" css property ("block" or "none")
 *
 */
function db_set_display(elements, display) {

	// iterate through the array of elements
	for (index in elements) {
		// set display for each element
		// (exclude the entries in the array that are added by JavaScript)
		if (index.match(/^[0-9]+$/)) elements[index].style.display = display;
	}
}

/**
 *  Closes all tabs shown by the debug console
 *
 *  @param  ignore  Tab to ignore and leave as it is
 *
 */
function db_minimize(skip) {
	// if tab is not one of the following
	if (!(
			skip.indexOf('db-records') > -1 ||
					skip.indexOf('db-explain') > -1 ||
					skip.indexOf('db-backtrace') > -1

			)) {

		// these are some of the main tabs
		var tabs = ['db-errors', 'db-successful-queries', 'db-unsuccessful-queries', 'db-warnings'];

		// iterate through the tabs
		for (index in tabs) {
			var tab = tabs[index];
			// if we should not skip this tab
			if (tab != skip) {
				// get the tab element from the DOM
				var tab = document.getElementById(tab);
				// if tabs exists
				if (null != tab) {
					// get children <table>s having the 'db-entry' class
					var children = db_get_elements(tab, 'table', 'db-entry');
					// hide them
					db_set_display(children, 'none');
					// and also hide the tab
					tab.style.display = 'none';
				}
			}
		}

		// if the "globals" tab is not to be skipped
		if (null == skip.match(/^db\-globals/)) {
			// hide the globals submenu
			document.getElementById('db-globals-submenu').style.display = 'none';
			// the parent element
			var parent = document.getElementById('db-globals');
			// hide the parent element
			parent.style.display = 'none';
			// the sub-tabs of the "global" main tab
			var tabs = ['post', 'get', 'session', 'cookie', 'files', 'server'];
			// iterate through the tabs
			for (index in tabs) {
				// the actual name of the sub-tab element
				var el = 'db-globals-' + tabs[index];
				// if element exists, hide it
				if (null != document.getElementById(el)) document.getElementById(el).style.display = 'none';
			}

			// if a sub-tab of the "globals" main tab is to be skipped
		} else {

			// the sub-tabs of the "global" main tab
			var tabs = ['post', 'get', 'session', 'cookie', 'files', 'server'];
			// iterate through the tabs
			for (index in tabs) {
				// the actual name of the sub-tab element
				el = 'db-globals-' + tabs[index];
				// if element is not to be skipped and it exists, hide it
				if (el != skip && null != document.getElementById(el)) document.getElementById(el).style.display = 'none';
			}
		}
	}
}


/**
 *  Toggles the "display" css property of an element or an array of elements.
 *
 *  @param  element     An element or an array of elements.
 *
 */
function db_toggle(element) {

	// close all tabs, except the one given as argument to this function
	db_minimize(element);

	// if element is the actual console
	if (element == 'console') {
		// get the element from the DOM
		var el = document.getElementById('db');
		// toggle its display property
		el.style.display = (el.style.display != 'block' ? 'block' : 'none');
		// if not the console element
	} else {

		// let's see what the element is
		switch (element) {
			case 'db-errors':
			case 'db-successful-queries':
			case 'db-unsuccessful-queries':
			case 'db-warnings':
				// get the element from the DOM
				var el = document.getElementById(element);
				// if element exists
				if (null != el) {
					// get the children <table> elements having the 'db-entry' class
					var children = db_get_elements(el, 'table', 'db-entry');
					// get the negated value of the display property of the element
					var status = (el.style.display != 'block' ? 'block' : 'none');
					// update the display property for all the element's children
					db_set_display(children, status);
					// update the display property of the element itself
					el.style.display = status;
				}
				break;

			case 'db-globals-submenu':
				// get the element from the DOM
				var el = document.getElementById(element);
				// toggle display property of the element
				el.style.display = (el.style.display != 'block' ? 'block' : 'none');
				// this is the parent of the element
				var parent = document.getElementById('db-globals');
				// toggle display property of the parent
				parent.style.display = (parent.style.display != 'block' ? 'block' : 'none');
				break;

			case 'db-globals-post':
			case 'db-globals-get':
			case 'db-globals-session':
			case 'db-globals-cookie':
			case 'db-globals-files':
			case 'db-globals-server':
				// get the element from the DOM
				var el = document.getElementById(element);
				// toggle display property of the element
				el.style.display = (el.style.display != 'block' ? 'block' : 'none');
				break;
			default:

				// get the element from the DOM
				el = document.getElementById(element);
				// se if the element is the "show records", "explain" or "backtrace" tab
				var matches = element.match(/\-([a-z]+)([0-9]+)$/);
				// if the element is the "show records", "explain" or "backtrace" tab
				if (null != matches) {
					// when we open the "show records", "explain" or the "backtrace" tab we need to
					// hide the other two
					// therefore, get all three tabs
					var elem1 = document.getElementById('db-records-' + matches[1] + matches[2]);
					var elem2 = document.getElementById('db-explain-' + matches[1] + matches[2]);
					var elem3 = document.getElementById('db-backtrace-' + matches[1] + matches[2]);
					// if tab exists and is not the one being opened, close it
					if (null != elem1 && elem1 != el) elem1.style.display = 'none';
					if (null != elem2 && elem2 != el) elem2.style.display = 'none';
					if (null != elem3 && elem3 != el) elem3.style.display = 'none';
				}
				// toggle display property of the element
				if (null != el) el.style.display = (el.style.display != 'block' ? 'block' : 'none');
		}
	}

}

startStack = function() {
};  // A stack of functions to run onload/domready

registerOnLoad = function(func) {
	var orgOnLoad = startStack;
	startStack = function () {
		orgOnLoad();
		func();
		return;
	}
}

var ranOnload = false; // Flag to determine if we've ran the starting stack already.

if (document.addEventListener) {

	// Mozilla actually has a DOM READY event.
	document.addEventListener("DOMContentLoaded", function() {
		if (!ranOnload) {
			ranOnload = true;
			startStack();
		}
	}, false);
} else if (document.all && !window.opera) {
	// This is the IE style which exploits a property of the (standards defined) defer attribute
	document.write("<scr" + "ipt id='DOMReady' defer=true " + "src=//:><\/scr" + "ipt>");
	document.getElementById("DOMReady").onreadystatechange = function() {
		if (this.readyState == "complete" && (!ranOnload)) {
			ranOnload = true;
			startStack();
		}
	}
}

var orgOnLoad = window.onload;
window.onload = function() {
	if (typeof(orgOnLoad) == 'function') {
		orgOnLoad();
	}
	if (!ranOnload) {
		ranOnload = true;
		startStack();
	}
}

registerOnLoad(function () {

	// are there any error messages?
	var errors = document.getElementById('db-errors');
	// are there any unsuccessful queries
	var unsuccessful = document.getElementById('db-unsuccessful-queries');
	// if there are error messages
	if (null != errors) {
		// get all the "error messages" tab's children <table>s having the 'db-entry' class
		var children = db_get_elements(errors, 'table', 'db-entry');
		// set the found tables' display property to "block"
		db_set_display(children, 'block');
		// set the "error messages" tab's display property to "block"
		errors.style.display = 'block';
		// if there are unsuccessful queries
	} else if (null != unsuccessful) {
		// get all the "error messages" tab's children <table>s having the 'db-entry' class
		var children = db_get_elements(unsuccessful, 'table', 'db-entry');
		// set the found tables' display property to "block"
		db_set_display(children, 'block');
		// set the "unsuccessful queries" tab's display property to "block"
		unsuccessful.style.display = 'block';
	} else {
		// if there are successful queries
		var successful = document.getElementById('db-successful-queries');
		if (successful){
			// are there any queries that need to be highlighted?
			// get all the "successful queries" tab's children <table>s having the 'db-highlight' class
			var highlight = db_get_elements(successful, 'table', 'db-highlight');
			// set the found tables' display property to "block"
			db_set_display(highlight, 'block');
			// set the "successful queries" tab's display property to "block"
			successful.style.display = 'block';
		}
	}

	adjust_table_width();

});


/**
 * Static way of adjusting the table size to the current browser size (not resolution)
 * @TODO: Make it dynamic to autoadjust with the browser size
 *
 */
function adjust_table_width(){
	var table = document.getElementById('db-records-sq1');
	var w = window, d = document, e = d.documentElement, g = d.getElementsByTagName('body')[0];
	var x = w.innerWidth || e.clientWidth || g.clientWidth;
	var y = w.innerHeight || e.clientHeight || g.clientHeight;
	var width = 0, height = 0;

	if (x < 1024) width = '700px';
	if (x >= 1024 && x < 1280) width = (950*(x/1024)) + 'px';
	if (x >= 1280 && x < 1366) width = (1210*(x/1280)) + 'px';
	if (x >= 1366 && x < 1440) width = (1300*(x/1366)) + 'px';
	if (x >= 1440 && x < 1680) width = (1370*(x/1440)) + 'px';
	if (x >= 1680 && x < 1920) width = (1600*(x/1680)) + 'px';
	if (x >= 1920 && x < 2560) width = '86.5%';
	if (x >= 2560) width = '2490px';

	if (table) table.style.maxWidth = width;
}

function getElementsByClassName(className, tag, parent) {
	var
		// initialize the array to be returned
			result = [],
			elements, i,
		// if native support is available
			nativeSupport = parent.getElementsByClassName,
		// the regular expression for matching class names
			regexp = new RegExp('\\b' + className + '\\b', 'i');
	// if parent is undefined, the parent is the document object
	parent || (parent = document);
	// if tag is undefined, match all tags
	tag || (tag = '*');

	// if native implementation is available
	// get elements having the sought class
	if (nativeSupport) elements = parent.getElementsByClassName(className);
	// get children elements matching the given tag
	else elements = parent.getElementsByTagName(tag);

	// the total number of found elements
	i = elements.length;

	// iterate through the found elements
	// decreasing while loop is the fastest way to iterate in JavaScript
	// http://blogs.oracle.com/greimer/entry/best_way_to_code_a
	while (i--)
		// if getElementsByClassName is available natively
		if ((nativeSupport &&
			// and we need specific tags and current element's tag is what we're looking for
				(tag != '*' && elements[i].nodeName.toLowerCase() == tag) ||
			// or we don't need specific tags
				tag == '*'
			// or if getElementsByClassName is not available natively
				) || (!nativeSupport &&
			// first, test if the class name *contains* what we're searching for
			// because indexOf is much faster than a regular expression
				elements[i].className.indexOf(className) > -1 &&
			// if class name contains what we're searching for
			// use a regular expression to test if there's an exact match
				regexp.test(elements[i].className))
				)
		// add it to results
			result.push(elements[i]);

	// if there are no elements found, return false or, return the found elements if any
	return result.length ? results : false;

}