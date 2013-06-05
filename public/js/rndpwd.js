function genpwd() {

	var str = new String();

	var le = 12;
	var up = true;
	var lo = true;
	var dg = true;
	var pn = true;
	var ct = "";
	var rm = "`'\"\\~";


	str = "";
	ch = new Array();

	for (i = 0; i < 33; i++)
		ch[ ch.length ] = 0;
	for (; i < 48; i++)
		ch[ ch.length ] = ( pn == true ) ? 1 : 0;
	for (; i < 58; i++)
		ch[ ch.length ] = ( dg == true ) ? 1 : 0;
	for (; i < 65; i++)
		ch[ ch.length ] = ( pn == true ) ? 1 : 0;
	for (; i < 91; i++)
		ch[ ch.length ] = ( up == true ) ? 1 : 0;
	for (; i < 97; i++)
		ch[ ch.length ] = ( pn == true ) ? 1 : 0;
	for (; i < 123; i++)
		ch[ ch.length ] = ( lo == true ) ? 1 : 0;
	for (; i < 127; i++)
		ch[ ch.length ] = ( pn == true ) ? 1 : 0;
	ch[ ch.length ] = 0;

	for (i = 0; i < ct.length; i++)
		ch[ ct.charCodeAt(i) ] = 1;
	for (i = 0; i < rm.length; i++)
		ch[ rm.charCodeAt(i) ] = 0;

	if (le.value < 1)
		le.value = 1;
	if (le.value > 128)
		le.value = 128;

	count = 0;
	do {
		x = Math.floor(Math.random() * 128);
		if (ch[ x ] == 1)
			str += String.fromCharCode(x);
		count++;
	} while (str.length < le && count < 1024);
	if (count == 1024)
		alert("Random selections exceeded safety limit.\n Try again or use larger selection set.");
	return str;

}
