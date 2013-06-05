function GetXmlHttpObject(handler) {
	var objXMLHttp = null
	if (window.XMLHttpRequest) {
		objXMLHttp = new XMLHttpRequest()
	}
	else if (window.ActiveXObject) {
		objXMLHttp = new ActiveXObject("Microsoft.XMLHTTP")
	}
	return objXMLHttp
}


function search(q, url, id) {
	var xhr = GetXmlHttpObject();

	if (xhr == null) {
		alert("You browser does not support HTTP Request technology (AJAX XHR). Please upgrade");
		return;
	}

	if (!url) url = '/search/db?';
	if (q) url += "q=" + escape(q);
	url += "&ajax";
	xhr.onreadystatechange = function (e) {
		if (xhr.readyState == 4 || xhr.readyState == "complete") {
			// Do we have a custom id where to bind to and display results? If not, use default
			if (!id) {
				document.getElementById("show_results").innerHTML = xhr.responseText;
				document.getElementById(id).scrollIntoView();
			} else document.getElementById(id).innerHTML = xhr.responseText;
		}
	};
	xhr.open("GET", url, true);
	xhr.send(null);
}
