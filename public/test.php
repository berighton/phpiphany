<?php
/**
 * Test file used for development and testing
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package main
 * @since 1.0
 *
 */

//require_once(dirname(dirname(__FILE__)) . '/init.php');


/* Working PHP loading bar *
$x = 0;
echo 'loading: ';
while ($x <= 10) {
	echo '.';
	sleep(1);
	ob_flush();
	flush();
	$x++;
}
*/

//stream_wrapper_restore('http');
global $config, $view, $db, $cache, $session;

//fatal
//$conf = new config();

//warning
$a = explode('.', $config);

//foreach ($config as $c){
	//do stuff
//}

$pg = '';

if (count($_GET) > 1){
	//$pg .= '<br /><strong>Paramaters: </strong><br /><pre>' . print_r($_GET, true) . '</pre>';
}

if (isset($_GET['page']) and $_GET['page'] == 'accordion'){
	$pg .=<<<HTML


	<dl>
		<dt><a href="#Section1">Section 1</a></dt>
		<dd id="Section1">
			<p>
				Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin consectetur, ante non iaculis suscipit, massa tortor dictum massa, mattis iaculis massa odio sit amet ipsum. Integer posuere enim ac felis feugiat auctor. Ut urna dui, mollis hendrerit fermentum non, lacinia non enim. Vestibulum lacus risus, tempor vel egestas at, laoreet id tortor. Cras augue sapien, cursus in facilisis id, bibendum a enim. Curabitur semper ligula et ligula aliquet scelerisque. Nunc quis aliquet sem. Duis a rhoncus enim. Integer lacinia, mi.
			</p>
		</dd>
		<dt><a href="#Section2">Section 2</a></dt>

		<dd id="Section2">
			<p>
				Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin consectetur, ante non iaculis suscipit, massa tortor dictum massa, mattis iaculis massa odio sit amet ipsum. Integer posuere enim ac felis feugiat auctor. Ut urna dui, mollis hendrerit fermentum non, lacinia non enim. Vestibulum lacus risus, tempor vel egestas at, laoreet id tortor. Cras augue sapien, cursus in facilisis id, bibendum a enim. Curabitur semper ligula et ligula aliquet scelerisque. Nunc quis aliquet sem. Duis a rhoncus enim. Integer lacinia, mi.
			</p>
		</dd>
		<dt><a href="#Section3">Section 3</a></dt>
		<dd id="Section3">
			<p>
				Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin consectetur, ante non iaculis suscipit, massa tortor dictum massa, mattis iaculis massa odio sit amet ipsum. Integer posuere enim ac felis feugiat auctor. Ut urna dui, mollis hendrerit fermentum non, lacinia non enim. Vestibulum lacus risus, tempor vel egestas at, laoreet id tortor. Cras augue sapien, cursus in facilisis id, bibendum a enim. Curabitur semper ligula et ligula aliquet scelerisque. Nunc quis aliquet sem. Duis a rhoncus enim. Integer lacinia, mi.
			</p>

		</dd>
		<dt><a href="#Section4">Section 4</a></dt>
		<dd id="Section4">
			<p>
				Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin consectetur, ante non iaculis suscipit, massa tortor dictum massa, mattis iaculis massa odio sit amet ipsum. Integer posuere enim ac felis feugiat auctor. Ut urna dui, mollis hendrerit fermentum non, lacinia non enim. Vestibulum lacus risus, tempor vel egestas at, laoreet id tortor. Cras augue sapien, cursus in facilisis id, bibendum a enim. Curabitur semper ligula et ligula aliquet scelerisque. Nunc quis aliquet sem. Duis a rhoncus enim. Integer lacinia, mi.
			</p>
		</dd>
	</dl>


HTML;

} else {

	$pg .= printr($config, 'config object', true);
	$pg .= printr($view, 'view object', true);


	//throw new Exception('System file iosys.dll has stopped responding. Restarting the application might help.');
	//throw new error('Simple error. Blame the thrower.');

	//try {
	//	throw new error('IO error. Try-and-catch block is acting wierd. Panic?!');
	//} catch (error $e){
	//	echo $e;
	//}

	$view->alert('This is the critical error alert popup. It is issued when something went wrong, entity does not exist or user invoked it manually', 'error');
	$view->alert('This is the warning alert popup. It is issued when something works, but not the way it was intended, or user invoked it manually', 'warning');
	$view->alert('This is the success alert popup. It is issued when user did something right or on a successful system action', 'success');


}

$view->title = 'My first test page!';
$view->render_page($pg);

