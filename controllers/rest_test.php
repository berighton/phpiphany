<?php
/**
 * Testing REST API controller
 *
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
 
class rest_test extends rest {

	// Enable authentication
	protected $rest_auth = 'basic';
	// Define output format (json, xml, html, csv, php, serialized)
	protected $rest_format = 'json';

	public function __construct(){
		parent::__construct();
	}

	// Default fallback if no action is specified
	public function index(){
		$this->response(array('msg' => 'THIS... IS... INDEX!!!!!'));
	}

	// Using GET, all parameters are captured
	public function getter_get(){
		global $config;
		$this->response(array('msg' => 'Yey! Getter method worked....', 'config parms' => $config->input->parms, 'input get' => $this->get_args));
	}

	// Testing POST input
	public function setter_post(){
		// Require valid token
		action_gatekeeper($this, true);
		$this->response(array('msg' => 'Yey! Setter method worked....', 'input post' => $this->post_args, 'input body' => $this->request->body, 'all input' => $this->input()));
	}
}