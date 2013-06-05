<?php
/**
 * Database model - a wrapper for multiple databases
 *
 * Original design taken from the Zebra Database Class version 2.7.1
 * http://stefangabos.ro/php-libraries/zebra-database
 * Heavily modified to suit phpiphany needs
 *
 * This is an advanced, compact, lightweight, object-oriented MySQL database wrapper, built on PHP's MySQL extension.
 * Provides methods for interacting with MySQL databases that are more powerful and intuitive than PHP's defaults.
 * It supports transactions and provides ways for caching query results using the default phpiphany caching class.
 * This wrapper also comes with a sophisticated debugging interface that includes detailed information about
 * the executed queries: execution time, returned/affected rows, excerpts of the found rows, error messages, etc.
 * It also automatically EXPLAIN's each SELECT query (so you don't miss those keys again!).
 *
 * An extra security layer is achieved with the use of prepared statements, where arguments are escaped automatically
 * ********************************************************************************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package models
 * @since 1.0
 *
 *
 * Usage: $db = database::init();
 * or simply make it global like so: global $db
 * Changing scope would work on every page because we instantiate and store the $db object below
 *
 */

$db = database::init();

class database {

	/**
	 * After an INSERT, UPDATE or DELETE query, read this property to get the number of affected rows.
	 *
	 * <i>This is a read-only property!</i>
	 *
	 * <code>
	 * // after an "action" query...
	 * echo $db->affected_rows;
	 * </code>
	 *
	 * @var integer
	 */
	public $affected_rows;

	/**
	 * Array with cached results.
	 * We will use this for fetching and seek
	 *
	 */
	private $cached_results;

	/**
	 * Sets how many of the records returned by a SELECT query should be shown in the debug console.
	 *
	 * <code>
	 * // show more records
	 * $db->console_show_records(50);
	 * </code>
	 *
	 * <i>Be aware that having this property set to a high number (thousands or more) and having a query that returns
	 * that many rows, can cause your script to crash due to memory limitations. In this case you should either lower
	 * the value of this property or try and set PHP's memory limit higher using:</i>
	 *
	 * <code>
	 * // set PHP's memory limit to 20 MB
	 * ini_set('memory_limit','20M');
	 * </code>
	 *
	 * Default is 20.
	 *
	 * @var integer
	 */
	public $console_show_records;

	/**
	 * Currently selected MySQL database
	 *
	 */
	private $database;

	/**
	 * Setting this property to TRUE, will instruct the class to generate debug information for each query it executes.
	 *
	 * Debug information can later be reviewed by calling the {@link show_debug_console()} method.
	 *
	 * <b>Don't forget to set this to FALSE when going live. Generating the debug information consumes a lot of
	 * resources and is meant to be used in the development process only!</b>.
	 *
	 * Note that not calling <i>show_debug_console()</i> when {@link debug} is set to TRUE, will not disable debug
	 * information: debug information will still be generated only it will not be shown!
	 *
	 * The propper solution is to always use show_debug_console() at the end of your scripts and simply change the state
	 * of <i>$debug</i> as <i>show_debug_console()</i> will not display anything if <i>$debug</i> is set to FALSE.
	 *
	 * <code>
	 * $db->debug = false;
	 * </code>
	 *
	 * Default is TRUE.
	 *
	 * @var boolean
	 */
	public $debug;

	/**
	 * All debug information is stored in this array.
	 *
	 */
	private $debug_info;

	/**
	 * An array of IP addresses for which to show the debug console (when calling {@link show_debug_console()} and
	 * {@link debug} is TRUE).
	 *
	 * Leaving this an empty array, will display the debug console for everybody.
	 *
	 * <code>
	 * // show the debug console only to specific IPs
	 * $db->debugger_ip = array('192.168.0.12', '192.168.0.13');
	 * </code>
	 *
	 * Default is an empty array.
	 *
	 * @var array
	 */
	public $debugger_ip;

	/**
	 * By default, if {@link set_charset()} is not called, a warning message will be displayed in the debug console.
	 *
	 * The ensure that data is both properly saved and retrieved from the database you should call this method, first
	 * thing after connecting to the database.
	 *
	 * If you don't want to call the method and don't want to see the warning either, set this property to FALSE.
	 *
	 * Default is TRUE.
	 *
	 * @var boolean
	 */
	public $disable_warnings;

	/**
	 * After a SELECT query done through either {@link select()} or {@link query()} methods, and having set the
	 * <i>$calc_rows</i> argument to TRUE, this property would contain the number of records that would have been
	 * returned if there was no LIMIT applied to the query.
	 *
	 * If <i>$calc_rows</i> is FALSE, or is TRUE but there is no LIMIT applied to the query, this property's value will
	 * be equal to {@link returned_rows}.
	 *
	 * <i>This is a read-only property!</i>
	 *
	 * @var integer
	 */
	public $found_rows;

	/**
	 * The language to be used in the debug console.
	 *
	 * The name of the PHP file to use from the <i>/languages</i> folder, without extension (i.e. "german" for the
	 * german language not "german.php").
	 *
	 * <i>Language file must exist in the "languages" folder!</i>
	 *
	 * <code>
	 * // set a different language for the debug console
	 * $db->language = 'french';
	 * </code>
	 *
	 * Default is "english".
	 *
	 * @var string
	 */
	public $language;

	/**
	 * MySQL link identifier.
	 *
	 */
	private $link_identifier;

	/**
	 * Path (with trailing slash) where to store the log file.
	 *
	 * Data is written to the log file when calling the {@link write_log()} method.
	 *
	 * <i>At the given path the script will attempt to create a file named "db.log". Remember to grant the appropriate
	 * right to the script!</i>
	 *
	 * <b>IF YOU'RE LOGGING, MAKE SURE YOU HAVE A CRON JOB OR ANYTHING THAT DELETES THE LOG FILE FROM TIME TO TIME!</b>
	 *
	 * @var string
	 */
	public $log_path;

	/**
	 * Time (in seconds) after which a query will be considered as running for too long.
	 *
	 * If a query's execution time exceeds this number, a notification email will be automatically sent to the address
	 * defined by {@link notification_address}, having {@link notifier_domain} in subject.
	 *
	 * <code>
	 * // consider queries running for more than 5 seconds as slow and send email
	 * $db->max_query_time = 5;
	 * </code>
	 *
	 * Default is 10.
	 *
	 * @var integer
	 */
	public $max_query_time;

	/**
	 * By setting this property to TRUE, a minimized version of the debug console will be shown by default.
	 *
	 * Clicking on it, will show the full debug console.
	 *
	 * Default is TRUE
	 *
	 * @var boolean
	 */
	public $minimize_console;

	/**
	 * Email address to which notification emails to be sent when a query's execution time exceeds the number of
	 * seconds set by {@link max_query_time}.
	 *
	 * If a query's execution time exceeds the number of seconds set by {@link max_query_time}, a notification email
	 * will be automatically sent to the address defined by {@link notification_address}, having {@link notifier_domain}
	 * in subject.
	 *
	 * <code>
	 * // the email address where to send an email when there are slow queries
	 * $db->notifier_address = 'youremail@yourdomain.com';
	 * </code>
	 *
	 * @var string
	 */
	public $notification_address;

	/**
	 * Domain name to be used in the subject of notification emails sent when a query's execution time exceeds the number
	 * of seconds set by {@link max_query_time}.
	 *
	 * If a query's execution time exceeds the number of seconds set by {@link max_query_time}, a notification email
	 * will be automatically sent to the address defined by {@link notification_address}, having {@link notifier_domain}
	 * in subject.
	 *
	 * <code>
	 * // set a domain name so that you'll know where the email comes from
	 * $db->notifier_domain = 'yourdomain.com';
	 * </code>
	 *
	 * @var string
	 */
	public $notifier_domain;

	/**
	 * After a SELECT query, read this property to get the number of returned rows.
	 *
	 * <i>This is a read-only property!</i>
	 *
	 * See {@link found_rows} also.
	 *
	 * <code>
	 * // after a select query...
	 * echo $db->returned_rows;
	 * </code>
	 *
	 * @var integer
	 */
	public $returned_rows;

	/**
	 * Tells whether a transaction is in progress or not.
	 *
	 * Possible values are
	 * -   0, no transaction is in progress
	 * -   1, a transaction is in progress
	 * -   2, a transaction is in progress but an error occurred with one of the queries
	 * -   3, transaction is run in test mode and it will be rolled back upon completion
	 *
	 */
	private $transaction_status;

	/**
	 * Array of warnings, generated by the script, to be shown to the user in the debug console
	 *
	 */
	private $warnings;

	/**
	 * Rendered flag. Applicable to the debug console - monitors only one page render of this div
	 *
	 */
	private $rendered;

	/**
	 * Local instance of a cache object
	 *
	 */
	private $cache;

	/**
	 * Store the single instance of the database
	 *
	 */
	private static $pip_instance = null;

	/**
	 * Stores SQL fatal error (if any)
	 */
	public $fatal_error = '';

	/**
	 * Singleton instance preparation
	 *
	 * @static
	 * @return object Database object
	 */
	public static function init() {
		// Re-instantiate self if it was not done so previously
		if (null === self::$pip_instance) {
			self::$pip_instance = new self();
		}

		return self::$pip_instance;
	}


	/**
	 * Constructor of the class
	 * Initializes the class' properties
	 *
	 */
	public function __construct() {
		global $config, $cache;
		$config->db_check();

		$this->path = dirname(dirname(__FILE__));
		// sets default values for the class' properties
		$this->console_show_records = 20;
		$this->minimize_console = true;
		$this->debug = $config->debug? true : false;
		$this->language('en');
		$this->max_query_time = 10;
		$this->log_path = $this->path . '/tmp/';
		$this->notification_address = $this->notifier_domain = '';
		$this->total_execution_time = $this->transaction_status = 0;
		$this->cached_results = $this->debug_info = $this->debugger_ip = array();

		// set the current ip as the first entry in the white list
		$this->debugger_ip[] = $config->env->ip;

		$this->database = $this->link_identifier = false;
		// set default warnings:
		$this->warnings = array('charset' => true);
		// connect if auto-connect was set in the settings. Otherwise a $db->connect() will have to be invoked manually
		if ($config->autoconnect) $this->connect();

		// define the charset
		//$this->set_charset();

		// If caching was enabled, use the caching mechanism to store the db result.
		if ($config->cache === true and $cache) {
			// Store the cache instance in our class variable for easy access
			$this->cache = &$cache;
		} else {
			// If cache is disabled or unavailable
			$this->cache = false;
		}

		// Finally return the object
		return $this;
	}

	/**
	 * Closes the MySQL connection
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function close() {
		// close the last one open
		mysql_close($this->link_identifier);
	}

	/**
	 * Opens a connection to a MySQL Server and selects a database.
	 *
	 * <i>Since the library is using "lazy connection" (it is not actually connecting to the database until the first
	 * query is executed) there's no link identifier available when calling this method!</i>
	 *
	 * If no information was supplied about the DB, the function will try to take it from the config object
	 *
	 * <i>If you need the link identifier use the {@link get_link()} method!</i>
	 * <i>If you need the connection to the database to be made right-away, set the "connect" argument to TRUE.</i>
	 *
	 * <code>
	 * // create the database object
	 * $db = new database();
	 *
	 * // notice that we're not doing any error checking. errors will be shown in the debug console
	 * $db->connect('host', 'username', 'password', 'database');
	 *
	 * </code>
	 *
	 * @param  mixed  $host       The address of the MySQL server to connect to (i.e. localhost).
	 * @param  mixed  $user       The user name used for authentication when connecting to the MySQL server.
	 * @param  mixed  $password   The password used for authentication when connecting to the MySQL server.
	 * @param  mixed  $database   The database to be selected after the connection is established.
	 * @param  boolean $is_new     (Optional) By default, if a second call is made to mysql_connect() with the same
	 *                             host, user and password, no new link will be established, but instead, the link
	 *                             identifier of the already opened link will be returned.
	 *
	 *                             Therefore, you <b>MUST</b> set this to TRUE whenever you instantiate this class
	 *                             other than for the first time, inside the same script, for accessing a different
	 *                             database than the previous one, but on the same host and with the same user name and
	 *                             password.
	 *
	 *                             Default is FALSE.
	 *
	 * @param  boolean $connect    (Optional) Setting this argument to TRUE will force the library to connect to the
	 *                             database right away.
	 *
	 *                             Default is FALSE.
	 * @return void
	 */
	public function connect($host = false, $user = false, $password = false, $database = false, $is_new = false, $connect = false) {
		// we are using lazy-connection
		// that is, we are not going to actually connect to the database until we execute the first query
		// the actual connection is done by the connected method

		if (!$host and !$user and !$password and !$database){
			global $config;
			$this->credentials = array('host' => $config->dbhost, 'user' => $config->dbuser, 'password' => $config->dbpass, 'database' => $config->dbname, 'is_new' => $is_new);
		} else {
			$this->credentials = array('host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'is_new' => $is_new);
		}
		// conenct now, if we need to connect right away
		if ($connect) {
			$this->connected();
		}
	}

	/**
	 * Returns a normalized array describing the SQL data type
	 *
	 * $db->datatype('char');
	 *
	 * @TODO Only MySQL is support at this time
	 * 
	 * @param   string $type MySQL data type
	 * @param   bool $html_columns If true, will return the data types prepared for the HTML select box generation
	 * @return  array If $type was supplied, return the data type, otherwise return the array of types
	 */
	public function datatype($type = '', $html_columns = false){
		$types = array(
			// SQL-92
			'bit'                           => array('type' => 'string', 'exact' => true),
			'bit varying'                   => array('type' => 'string'),
			'char'                          => array('type' => 'string', 'exact' => true),
			'char varying'                  => array('type' => 'string'),
			'character'                     => array('type' => 'string', 'exact' => true),
			'character varying'             => array('type' => 'string'),
			'date'                          => array('type' => 'string'),
			'dec'                           => array('type' => 'float', 'exact' => true),
			'decimal'                       => array('type' => 'float', 'exact' => true),
			'double precision'              => array('type' => 'float'),
			'float'                         => array('type' => 'float'),
			'int'                           => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
			'integer'                       => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
			'interval'                      => array('type' => 'string'),
			'national char'                 => array('type' => 'string', 'exact' => true),
			'national char varying'         => array('type' => 'string'),
			'national character'            => array('type' => 'string', 'exact' => true),
			'national character varying'    => array('type' => 'string'),
			'nchar'                         => array('type' => 'string', 'exact' => true),
			'nchar varying'                 => array('type' => 'string'),
			'numeric'                       => array('type' => 'float', 'exact' => true),
			'real'                          => array('type' => 'float'),
			'smallint'                      => array('type' => 'int', 'min' => '-32768', 'max' => '32767'),
			'time'                          => array('type' => 'string'),
			'time with time zone'           => array('type' => 'string'),
			'timestamp'                     => array('type' => 'string'),
			'timestamp with time zone'      => array('type' => 'string'),
			'varchar'                       => array('type' => 'string'),

			// SQL:1999
			'binary large object'               => array('type' => 'string', 'binary' => true),
			'blob'                              => array('type' => 'string', 'binary' => true),
			'boolean'                           => array('type' => 'bool'),
			'char large object'                 => array('type' => 'string'),
			'character large object'            => array('type' => 'string'),
			'clob'                              => array('type' => 'string'),
			'national character large object'   => array('type' => 'string'),
			'nchar large object'                => array('type' => 'string'),
			'nclob'                             => array('type' => 'string'),
			'time without time zone'            => array('type' => 'string'),
			'timestamp without time zone'       => array('type' => 'string'),

			// SQL:2003
			'bigint'    => array('type' => 'int', 'min' => '-9223372036854775808', 'max' => '9223372036854775807'),

			// SQL:2008
			'binary'            => array('type' => 'string', 'binary' => true, 'exact' => true),
			'binary varying'    => array('type' => 'string', 'binary' => true),
			'varbinary'         => array('type' => 'string', 'binary' => true),

			// MySQL
			'blob'                      => array('type' => 'string', 'binary' => true, 'character_maximum_length' => '65535'),
			'bool'                      => array('type' => 'bool'),
			'bigint unsigned'           => array('type' => 'int', 'min' => '0', 'max' => '18446744073709551615'),
			'datetime'                  => array('type' => 'string'),
			'decimal unsigned'          => array('type' => 'float', 'exact' => true, 'min' => '0'),
			'double'                    => array('type' => 'float'),
			'double precision unsigned' => array('type' => 'float', 'min' => '0'),
			'double unsigned'           => array('type' => 'float', 'min' => '0'),
			'enum'                      => array('type' => 'string'),
			'fixed'                     => array('type' => 'float', 'exact' => true),
			'fixed unsigned'            => array('type' => 'float', 'exact' => true, 'min' => '0'),
			'float unsigned'            => array('type' => 'float', 'min' => '0'),
			'int unsigned'              => array('type' => 'int', 'min' => '0', 'max' => '4294967295'),
			'integer unsigned'          => array('type' => 'int', 'min' => '0', 'max' => '4294967295'),
			'longblob'                  => array('type' => 'string', 'binary' => true, 'character_maximum_length' => '4294967295'),
			'longtext'                  => array('type' => 'string', 'character_maximum_length' => '4294967295'),
			'mediumblob'                => array('type' => 'string', 'binary' => true, 'character_maximum_length' => '16777215'),
			'mediumint'                 => array('type' => 'int', 'min' => '-8388608', 'max' => '8388607'),
			'mediumint unsigned'        => array('type' => 'int', 'min' => '0', 'max' => '16777215'),
			'mediumtext'                => array('type' => 'string', 'character_maximum_length' => '16777215'),
			'national varchar'          => array('type' => 'string'),
			'numeric unsigned'          => array('type' => 'float', 'exact' => true, 'min' => '0'),
			'nvarchar'                  => array('type' => 'string'),
			'point'                     => array('type' => 'string', 'binary' => true),
			'real unsigned'             => array('type' => 'float', 'min' => '0'),
			'set'                       => array('type' => 'string'),
			'smallint unsigned'         => array('type' => 'int', 'min' => '0', 'max' => '65535'),
			'text'                      => array('type' => 'string', 'character_maximum_length' => '65535'),
			'tinyblob'                  => array('type' => 'string', 'binary' => true, 'character_maximum_length' => '255'),
			'tinyint'                   => array('type' => 'int', 'min' => '-128', 'max' => '127'),
			'tinyint unsigned'          => array('type' => 'int', 'min' => '0', 'max' => '255'),
			'tinytext'                  => array('type' => 'string', 'character_maximum_length' => '255'),
			'year'                      => array('type' => 'string'),
		);

		if ($html_columns){
			return array(
				// most used
				'INT',
				'VARCHAR',
				'TEXT',
				'DATE',

				// numeric
				'NUMERIC' => array(
					'TINYINT',
					'SMALLINT',
					'MEDIUMINT',
					'INT',
					'BIGINT',
					'-',
					'DECIMAL',
					'FLOAT',
					'DOUBLE',
					'REAL',
					'-',
					'BIT',
					'BOOLEAN',
					'SERIAL',),

				// Date/Time
				'DATE and TIME' => array(
					'DATE',
					'DATETIME',
					'TIMESTAMP',
					'TIME',
					'YEAR',),

				// Text
				'STRING' => array(
					'CHAR',
					'VARCHAR',
					'-',
					'TINYTEXT',
					'TEXT',
					'MEDIUMTEXT',
					'LONGTEXT',
					'-',
					'BINARY',
					'VARBINARY',
					'-',
					'TINYBLOB',
					'MEDIUMBLOB',
					'BLOB',
					'LONGBLOB',
					'-',
					'ENUM',
					'SET',),

				'SPATIAL' => array(
					'GEOMETRY',
					'POINT',
					'LINESTRING',
					'POLYGON',
					'MULTIPOINT',
					'MULTILINESTRING',
					'MULTIPOLYGON',
					'GEOMETRYCOLLECTION',),
			);
		}

		if ($type){
			if (isset($types[$type])) return $types[$type];
			else return array('type' => 'Undefined');
		}

		return $types;
	}

	/**
	 * Counts the values in a column of a table.
	 *
	 * <code>
	 * // count male users
	 * $male = $db->count('id', 'users', 'gender = "M"');
	 *
	 * // when working with variables you should use the following syntax
	 * // this way variables will be mysql_real_escape_string-ed first
	 * $users = $db->count('id', 'users', 'gender = ?', array($gender));
	 * </code>
	 *
	 * @param  string  $column         Name of the column in which to do the counting.
	 * @param  string  $table          Name of the table containing the column.
	 * @param  string  $where          (Optional) A MySQL WHERE clause (without the WHERE keyword).
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $replacements   (Optional) An array with as many items as the total parameter markers ("?", question
	 *                                 marks) in <i>$column</i>, <i>$table</i> and <i>$where</i>. Each item will be
	 *                                 automatically {@link escape()}-ed and will replace the corresponding "?".
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $cache          (Optional) Instructs the script on whether it should cache the query's results
	 *                                 or not. Can be either FALSE - meaning no caching - or an integer representing the
	 *                                 number of seconds after which the cached results are considered to be expired
	 *                                 and the query will be executed again.
	 *
	 *                                 The caching method is specified by the value of the {@link caching_method} property.
	 *
	 *                                 Default is FALSE.
	 *
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *
	 *                                 Default is FALSE.
	 *
	 * @return mixed                   Returns the number of counted records, or FALSE if no records matching the given
	 *                                 criteria (if any) were found. It also returns FALSE if there are no records in
	 *                                 the table or on error.
	 *
	 *                                 <i>This method may return boolean FALSE, but may also return a non-Boolean value
	 *                                 which evaluates to FALSE, such as 0. Use the === operator for testing the return
	 *                                 value of this method.</i>
	 */
	public function count($column, $table, $where = '', $replacements = '', $cache = false, $highlight = false) {
		// run the query
		$this->query('SELECT COUNT(' . $column . ') AS counted FROM `' . $table . '`' . ($where != '' ? ' WHERE ' . $where : ''), $replacements, $cache, $highlight);

		// if query was executed successfully and one or more records were returned
		if ($this->last_result && $this->returned_rows > 0) {
			// fetch the result
			$row = $this->fetch_assoc();
			// return the result
			return $row['counted'];
		}

		// if error or no records
		return false;
	}

	/**
	 * Deletes rows from a table.
	 *
	 * <code>
	 * // delete male users
	 * $db->delete('users', 'gender = "M"');
	 *
	 * // when working with variables you should use the following syntax
	 * // this way variables will be mysql_real_escape_string-ed first
	 * $db->delete('users', 'gender = ?', array($gender));
	 * </code>
	 *
	 * @param  string  $table          Table from which to delete.
	 * @param  string  $where          (Optional) A MySQL WHERE clause (without the WHERE keyword).
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $replacements   (Optional) An array with as many items as the total parameter markers ("?", question
	 *                                 marks) in <i>$table</i> and <i>$where</i>. Each item will be automatically
	 *                                 {@link escape()}-ed and will replace the corresponding "?".
	 *                                 Default is "" (an empty string).
	 *
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *                                 Default is FALSE.
	 *
	 * @return boolean                 Returns TRUE on success, or FALSE on error
	 */
	public function delete($table, $where = '', $replacements = '', $highlight = false) {
		// run the query
		$this->query('DELETE FROM `' . $table . '`' . ($where != '' ? ' WHERE ' . $where : ''), $replacements, false, $highlight);

		// if query was successful
		if ($this->last_result) {
			return true;
		}

		// if query was unsuccessful
		return false;
	}

	/**
	 * Returns one or more columns from ONE row of a table.
	 *
	 * <code>
	 * // get name, surname and age of all male users
	 * $result = $db->lookup('name, surname, age', 'users', 'gender = "M"');
	 *
	 * // when working with variables you should use the following syntax
	 * // this way variables will be mysql_real_escape_string-ed first
	 * $result = $db->lookup('name, surname, age', 'users', 'gender = ?', array($gender));
	 * </code>
	 *
	 * @param  string  $column         One or more columns to return data from.
	 *
	 *                                 <i>If only one column is specified, the returned result will be the specified
	 *                                 column's value, whereas if more columns are specified, the returned result will
	 *                                 be an associative array!</i>
	 *
	 *                                 <i>You may use "*" (without the quotes) to return all the columns from the
	 *                                 row.</i>
	 *
	 * @param  string  $table          Name of the in which to search.
	 * @param  string  $where          (Optional) A MySQL WHERE clause (without the WHERE keyword).
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @param array|string $replacements (Optional) An array with as many items as the total parameter markers ("?", question
	 *                                 marks) in <i>$column</i>, <i>$table</i> and <i>$where</i>. Each item will be
	 *                                 automatically {
	 * @param  mixed   $cache          (Optional) Instructs the script on whether it should cache the query's results
	 *                                 or not. Can be either FALSE - meaning no caching - or an integer representing the
	 *                                 number of seconds after which the cached results are considered to be expired
	 *                                 and the query will be executed again.
	 *
	 *                                 The caching method is specified by the value of the {
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *
	 *                                 Default is FALSE.
	 *
	 * @link escape()}-ed and will replace the corresponding "?".
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @link caching_method} property.
	 *
	 *                                 Default is FALSE.
	 *
	 * @return mixed                   Found value/values, or FALSE if no records matching the given criteria (if any)
	 *                                 were found. It also returns FALSE if there are no records in the table or on error.
	 */
	public function lookup($column, $table, $where = '', $replacements = '', $cache = false, $highlight = false) {
		// run the query
		$this->query('SELECT ' . $column . ' FROM `' . $table . '`' . ($where != '' ? ' WHERE ' . $where : '') . ' LIMIT 1', $replacements, $cache, $highlight);

		// if query was executed successfully and one or more records were returned
		if ($this->last_result && $this->returned_rows > 0) {

			// fetch the result
			$row = $this->fetch_assoc();

			// if there is only one column in the returned set
			// return as a single value
			if (count($row) == 1) {
				return array_pop($row);
			} else {
				// if more than one columns, return as an array
				return $row;
			}

		}

		// if error or no records
		return false;
	}

	/**
	 * Looks up the maximum value in a column of a table.
	 *
	 * <code>
	 * // get the maximum age of male users
	 * $result = $db->max('age', 'users', 'gender = "M"');
	 *
	 * // when working with variables you should use the following syntax
	 * // this way variables will be mysql_real_escape_string-ed first
	 * $result = $db->max('age', 'users', 'gender = ?', array($gender));
	 * </code>
	 *
	 * @param  string  $column         Name of the column in which to search.
	 * @param  string  $table          Name of table in which to search.
	 * @param  string  $where          (Optional) A MySQL WHERE clause (without the WHERE keyword).
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $replacements   (Optional) An array with as many items as the total parameter markers ("?", question
	 *                                 marks) in <i>$column</i>, <i>$table</i> and <i>$where</i>. Each item will be
	 *                                 automatically {@link escape()}-ed and will replace the corresponding "?".
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $cache          (Optional) Instructs the script on whether it should cache the query's results
	 *                                 or not. Can be either FALSE - meaning no caching - or an integer representing the
	 *                                 number of seconds after which the cached results are considered to be expired
	 *                                 and the query will be executed again.
	 *
	 *                                 The caching method is specified by the value of the {@link caching_method} property.
	 *
	 *                                 Default is FALSE.
	 *
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *
	 *                                 Default is FALSE.
	 *
	 * @return mixed                   The maximum value in the column, or FALSE if no records matching the given criteria
	 *                                 (if any) were found. It also returns FALSE if there are no records in the table
	 *                                 or on error.
	 *
	 *                                 <i>This method may return boolean FALSE, but may also return a non-Boolean value
	 *                                 which evaluates to FALSE, such as 0. Use the === operator for testing the return
	 *                                 value of this method.</i>
	 */
	public function max($column, $table, $where = '', $replacements = '', $cache = false, $highlight = false) {
		// run the query
		$this->query('SELECT MAX(' . $column . ') AS maximum FROM `' . $table . '`' . ($where != '' ? ' WHERE ' . $where : ''), $replacements, $cache, $highlight);

		// if query was executed successfully and one or more records were returned
		if ($this->last_result && $this->returned_rows > 0) {
			// fetch the result
			$row = $this->fetch_assoc();
			// return the result
			return $row['maximum'];
		}

		// if error or no records
		return false;
	}

	/**
	 * Sums the values in a column of a table.
	 *
	 * Example:
	 *
	 * <code>
	 * // get the total logins of all male users
	 * $result = $db->sum('login_count', 'users', 'gender = "M"');
	 *
	 * // when working with variables you should use the following syntax
	 * // this way variables will be mysql_real_escape_string-ed first
	 * $result = $db->sum('login_count', 'users', 'gender = ?', array($gender));
	 * </code>
	 *
	 * @param  string  $column         Name of the column in which to sum values.
	 * @param  string  $table          Name of the table in which to search.
	 * @param  string  $where          (Optional) A MySQL WHERE clause (without the WHERE keyword).
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @param  array|string   $replacements   (Optional) An array with as many items as the total parameter markers ("?", question
	 *                                 marks) in <i>$column</i>, <i>$table</i> and <i>$where</i>. Each item will be
	 *                                 automatically {@link escape()}-ed and will replace the corresponding "?".
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $cache          (Optional) Instructs the script on whether it should cache the query's results
	 *                                 or not. Can be either FALSE - meaning no caching - or an integer representing the
	 *                                 number of seconds after which the cached results are considered to be expired
	 *                                 and the query will be executed again.
	 *
	 *                                 The caching method is specified by the value of the {@link caching_method} property.
	 *
	 *                                 Default is FALSE.
	 *
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *
	 *                                 Default is FALSE.
	 *
	 * @return mixed                   Returns the sum, or FALSE if no records matching the given criteria (if any) were
	 *                                 found. It also returns FALSE if there are no records in the table or on error.
	 *
	 *                                 <i>This method may return boolean FALSE, but may also return a non-Boolean value
	 *                                 which evaluates to FALSE, such as 0. Use the === operator for testing the return
	 *                                 value of this method.</i>
	 */
	public function sum($column, $table, $where = '', $replacements = '', $cache = false, $highlight = false) {
		// run the query
		$this->query('SELECT SUM(' . $column . ') AS total FROM `' . $table . '`' . ($where != '' ? ' WHERE ' . $where : ''), $replacements, $cache, $highlight);

		// if query was executed successfully and one or more records were returned
		if ($this->last_result && $this->found_rows > 0) {
			// fetch the result
			$row = $this->fetch_assoc();
			// return the result
			return $row['total'];
		}

		// if error or no records
		return false;
	}

	/**
	 * Escapes special characters in a string for use in an SQL statement.
	 *
	 * <i>This method also encapsulates given string in single quotes!</i>
	 * <i>Works even if gpc magic quotes are ON.</i>
	 *
	 * <code>
	 * // use the method in a query
	 * // THIS IS NOT THE RECOMMENDED METHOD!
	 * $db->query('SELECT * FROM users WHERE gender = "' . $db->escape($gender) . '"');
	 *
	 * // the recommended method
	 * // (variable are automatically escaped this way)
	 * $db->query('SELECT * FROM users WHERE gender = ?', array($gender));
	 * </code>
	 *
	 * @param  mixed  $data        String or array that needs to be escaped.
	 *
	 * @return mixed               Returns the escaped string.
	 */
	public function escape($data) {
		// if an active connection exists
		if ($this->connected()) {
			if (is_array($data)){
				foreach ($data as $elem => $datum){
					if (is_object($datum)){
						foreach ($datum as $key => $val){
							// if "magic quotes" are on, strip slashes
							if (get_magic_quotes_gpc()) {
								$datum->$key = stripslashes($val);
							}
							// escape and save the field
							$datum->$key = mysql_real_escape_string($val, $this->link_identifier);
						}
					} else {
						// if "magic quotes" are on, strip slashes
						if (get_magic_quotes_gpc()) {
							$data[$elem] = stripslashes($datum);
						}
						// escape and save the field
						$data[$elem] = mysql_real_escape_string($datum, $this->link_identifier);
					}
				}
				return $data;
			} elseif (is_object($data)){
				foreach ($data as $key => $val){
					// if "magic quotes" are on, strip slashes
					if (get_magic_quotes_gpc()) {
						$data->{$key} = stripslashes($val);
					}
					// escape and save the field
					$data->{$key} = mysql_real_escape_string($val, $this->link_identifier);
				}
				return $data;
			} else {
				// if "magic quotes" are on, strip slashes
				if (get_magic_quotes_gpc()) {
					$data = stripslashes($data);
				}
				// escape and return the string
				return mysql_real_escape_string($data, $this->link_identifier);
			}
		}

		// upon error, we don't have to report anything as connected() method already did
		// just return FALSE
		return false;
	}

	/**
	 * Creates a new table
	 * Two main parameters that it expects are the table name and an array of column descriptions specified as a db_field object
	 * Each user-inputted string is escaped via $this->escape()
	 *
	 * Usage:
	 *
	 * $db = new database();
	 *
	 * $f1 = new db_field();
	 * $f1->name = 'id';
	 * $f1->type = 'int';
	 * $f1->size = 32;
	 * $f1->null = false;
	 * $f1->ai = true;
	 * $f1->index = 'primary';
	 *
	 * $f2 = new db_field();
	 * $f2->name = 'name';
	 * $f2->type = 'varchar';
	 * $f2->size = 50;
	 * $f2->null = false;
	 * $f2->default = 'phpiphany';
	 * $f2->index = 'unique';
	 *
	 * $f3 = new db_field();
	 * $f3->name = 'description';
	 * $f3->null = true;
	 * $f3->index = true;
	 * $f3->comment = 'Full product description would go here';
	 *
	 * $f4 = new db_field();
	 * $f4->name = 'fk_id';
	 * $f4->type = 'int';
	 * $f4->size = 9;
	 * $f4->null = false;
	 * $f4->fk = 'othertable.id';
	 * $f4->fk_onupdate = 'none';
	 *
	 * $table_name = 'automagic';
	 * echo $db->create_table($table_name, array($f1, $f2, $f3))?
	 * 		$view->error('Database table named "' . $table_name . '" was inserted successfully!', 'success') :
	 * 		$view->error('Database table insert failed!', 'error');
	 *
	 * @param string $name The table name
	 * @param array $fields Array of db_field objects describing the fields in this table
	 * @param string $engine MySQL engine (default InnoDB)
	 * @param string $comment Table comment (optional)
	 * @param string $charset Table character set (default utf8)
	 * @param string $collate Table collation (default utf8_unicode_ci)
	 * @return bool Returns true on successfull table creation, false on error
	 *
	 */
	public function create_table($name, array $fields, $engine = 'InnoDB', $comment = '', $charset = 'utf8', $collate = 'utf8_unicode_ci'){
		// Escape the values
		$name = $this->escape($name);
		$fields = $this->escape($fields);
		$charset = $this->escape($charset);
		$collate = $this->escape($collate);
		$comment = $comment? "COMMENT='" . $this->escape($comment) . "'" : '';

		// Does this table have an autoincrement?
		$ai = $index = false;

		$sql = "CREATE TABLE IF NOT EXISTS `$name` ( \n";
		foreach ($fields as $field){
			//check if type is scalar
			// if ($this->is_field_scalar($field->type))
			$null = $field->null? 'NULL' : 'NOT NULL';
			$f_collation = $field->collation? 'COLLATE ' . $field->collation : preg_match('/(char|text)/i', $field->type)? 'COLLATE utf8_unicode_ci' : '';
			if ($field->ai){
				$field->ai = 'AUTO_INCREMENT';
				$ai = true;
			} else {
				$field->ai = '';
			}
			$type = preg_match('/(DATE|TEXT|TINYTEXT|MEDIUMTEXT|LONGTEXT|BLOB|TINYBLOB|MEDIUMBLOB|LONGBLOB)/i', $field->type)? $field->type : "$field->type($field->size)";
			if ($field->default) {
				$df_val = $field->default == 'ts'? 'CURRENT_TIMESTAMP' : "'$field->default'";
				$default = "DEFAULT $df_val";
			} else {
				$default = '';
			}
			$f_comment = $field->comment? "COMMENT '$field->comment'" : '';
			$sql .= '	' . trim("`{$field->name}` $type $null $default $field->ai $f_collation $field->attribute $f_comment") . ", \n";

			// Build the index string that would be inserted in the end of this query that is specific to the table
			if ($field->index){
				if ($field->index === 'primary') $index .= "	PRIMARY KEY (`$field->name`), \n";
				if ($field->index === 'unique') $index .= "	UNIQUE `$field->name` (`$field->name`), \n";
				if ($field->index === 'text') $index .= "	FULLTEXT `$field->name` (`$field->name`), \n";
				if ($field->index === true or $field->index == 1) $index .= "	KEY `$field->name` (`$field->name`), \n";
			}

			// Get any foreign key constraints (if any)
			if ($field->fk and $tmp = $this->relate($field, $name, true, false)) $index .= $tmp;
		}

		$ai = $ai? 'AUTO_INCREMENT=1' : '';
		if (trim($index)){
			// Remove last comma (3 chars) if no constraints were supplied, otherwise only 1 char
			$index = (!isset($tmp) or !$tmp)? substr($index, 0, strlen($index) - 3) : substr($index, 0, strlen($index) - 1);
			$sql .= $index;
		} else {
			// Remove the last comma
			$sql = substr($sql, 0, strlen($sql) - 3);
		}

		$sql .= trim(") ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collate $comment $ai");

		return $this->query($sql)? true : false;
	}

	/**
	 * Drops a specific table from the database
	 *
	 * CAUTION: Be very careful with this function as all the data inside that table will also be deleted!
	 *
	 *
	 * @param string $name Table name
	 * @param bool $truncate If set to true, a truncate command will be issued only removing the rows in the table. (default false)
	 *                       Otherwise the entire table will be deleted from the database including all of its rows, indexes and privileges
	 * @return bool Returns true on successful table delete, false on error
	 */
	public function drop_table($name, $truncate = false){
		$name = $this->escape($name);
		$sql = $truncate? "TRUNCATE TABLE `$name`" : "DROP TABLE `$name`";
		return $this->query($sql)? true : false;
	}

	/**
	 * Creates a relationship between table fields
	 * Note that in order for a field to become a foreign key to another table, it MUST be indexed
	 * either with with 'ALTER TABLE `tablename` ADD INDEX ( `fk_id` ) '; OR use create_table() method with does it automatically
	 * Also note, $field->fk is expected to be in the format 'tablename.fieldname'
	 *
	 * This function works 'per one table' only, meaning for multiple tables you will have to invoke this function several times
	 * The fields are passed as a reference in order to update the original object setting index to true
	 *
	 *
	 * @param mixed $data The fields to be processed. Can be a single object (of db_field class!) or an array of these objects
	 * @param string $table Table name to which add these relationships
	 * @param bool $new_table Specify whether or not to generate in-table query using CONSTRAINT or an ALTER table command (usually needed only for the create_table calls)
	 * @param bool $execute Specify whether or not to execute the generated sql or simply return the query (usually needed only for the create_table calls)
	 * @return mixed Returns true/resource/sql on success, false on error
	 */
	public function relate(&$data, $table, $new_table = false, $execute = true){
		$sql = '';
		if ($data) {
			$table = $this->escape($table);
			// If $data is a single object (not array)
			if (!is_array($data)) {
				if ($data instanceof db_field) {
					if ($data->fk) {
						$fk = explode('.', $data->fk);
						if (count($fk) == 2) {
							$name = $this->escape($data->name);
							$fk_table = $this->escape($fk[0]);
							$fk_field = $this->escape($fk[1]);
							$data->format_fk();
							$ondelete = $this->escape($data->fk_ondelete);
							$onupdate = $this->escape($data->fk_onupdate);
							// If this is a new table, generate a CONSTRAINT query, otherwise ALTER
							if ($new_table) {
								$const = substr(md5($table . $name . time()), 5, 10);
								$sql = "CONSTRAINT `{$table}_{$const}` FOREIGN KEY (`$name`) REFERENCES `$fk_table` (`$fk_field`) ON DELETE $ondelete ON UPDATE $onupdate,";
							} else $sql = "ALTER TABLE `$table` ADD FOREIGN KEY ( `$name` )  REFERENCES `$fk_table` (`$fk_field`) ON DELETE $ondelete ON UPDATE $onupdate";
							// If and only if this is NOT a new table, execute the ALTER query. Otherwise return the sql
							return ($execute and $new_table === false)? $this->query($sql) : $sql;
						} else {
							$this->log('errors', array('message' => $this->language['invalid_fk']));
						}
					} else {
						$this->log('errors', array('message' => $this->language['no_fk'] . $table));
					}
				} else {
					$this->log('errors', array('message' => $this->language['invalid_data_field_class']));
				}
			} else {
				foreach ($data as $datum) {
					if ($datum instanceof db_field) {
						if ($datum->fk) {
							$fk = explode('.', $datum->fk);
							if (count($fk) == 2) {
								$name = $this->escape($datum->name);
								$fk_table = $this->escape($fk[0]);
								$fk_field = $this->escape($fk[1]);
								$data->format_fk();
								$ondelete = $this->escape($datum->fk_ondelete);
								$onupdate = $this->escape($datum->fk_onupdate);
								// If this is a new table, generate a CONSTRAINT query, otherwise ALTER
								if ($new_table) {
									$const = md5($table . $name . time());
									$sql .= "CONSTRAINT `{$table}_{$const}` FOREIGN KEY (`$name`) REFERENCES `$fk_table` (`$fk_field`) ON DELETE $ondelete ON UPDATE $onupdate,\n";
								} else $sql .= "ALTER TABLE `$table` ADD FOREIGN KEY ( `$name` )  REFERENCES `$fk_table` (`$fk_field`) ON DELETE $ondelete ON UPDATE $onupdate;\n";
							} else {
								$this->log('errors', array('message' => $this->language['invalid_fk']));
							}
						} else {
							$this->log('errors', array('message' => $this->language['no_fk'] . $table));
						}
					} else {
						$this->log('errors', array('message' => $this->language['invalid_data_field_class']));
						return false;
					}
				}
				// Only proceed executing the queries if it is not for a new table (ALTER statements)
				if ($execute and $new_table === false){
					// Loop through each query separated by a semicolon and execute each one separately
					if (trim($sql)) {
						// This preg match looks for semicolons as the last statement, not inside comments and single quotes
						$queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $sql);
						foreach ($queries as $query) {
							if (strlen(trim($query)) > 0) {
								$this->query($query);
							}
						}
					}
					return true;
				} else return $sql;
			}
		}
		return false;
	}

	/**
	 * Removes a specific constraint in the table
	 * Since we do not always know the name of the contraint (MySQL autogenerates the name),
	 * we will have to get the table properties first and then issue another query specifying the constraint name
	 *
	 * Note: this is an expensive function due to numerous DB calls as well as finding the constraint name in results
	 *
	 * @param string $table Specify the table name where to remove this relationship
	 * @param mixed $fk Usually we only know the field name we want to remove
	 * @param mixed $constraint If we know the constraint name, we will only execute one query
	 * @return bool Returns true if contraint was removed successfuly, false otherwise
	 */
	public function unrelate($table, $fk = null, $constraint = null){
		$table = $this->escape($table);
		if ($constraint and $fk){
			$constraint = $this->escape($constraint);
			$fk = $this->escape($fk);
			$this->query("ALTER TABLE `$table` DROP FOREIGN KEY `$constraint`");
			$this->query("ALTER TABLE `$table` DROP KEY `$fk`");
			return true;
		} elseif (!$fk) {
			$this->log('errors', array('message' => $this->language['no_fk'] . $table));
			return false;
		} else {
			$result = stristr($this->export_table($table), 'constraint');
			if (!$result){
				$this->log('errors', array('message' => $this->language['no_constraints']));
				return false;
			} else {
				$result = explode('`' . $fk, $result);
				$result = explode('`', $result[0]);
				$constraint = $result['1'];
				$this->query("ALTER TABLE `$table` DROP FOREIGN KEY `$constraint`");
				$this->query("ALTER TABLE `$table` DROP KEY `$fk`");
				return true;
			}
		}
	}

	/**
	 * Generates a table creation query
	 *
	 * @param $name Table name
	 * @return mixed Returns the query or false on error
	 */
	public function export_table($name){
		$result = $this->fetch_assoc_all($this->query("SHOW CREATE TABLE `$name`"));
		if ($result and is_array($result) and $sql = $result[0]['Create Table'] and stripos($sql, 'create table') !== false) return $sql;
		else return false;
	}

	/**
	 * Shows details about each field in a given table
	 *
	 * @param $name Table name
	 * @return mixed Returns the result array or false on error
	 */
	public function describe_table($name){
		$result = $this->fetch_assoc_all($this->query("DESCRIBE `$name`"));
		if ($result and is_array($result)) {
			return count($result) == 1? $result[0] : $result;
		} else return false;
	}

	/**
	 * Returns an associative array that corresponds to the fetched row and moves the internal data pointer ahead.
	 * The data is taken from the resource created by the previous query or from the resource given as argument.
	 *
	 * <code>
	 * // create the database object
	 * // run a query
	 * $db->query('SELECT * FROM table WHERE criteria = ?', array($criteria));
	 *
	 * // iterate through the found records
	 * while ($row = $db->fetch_assoc()) {
	 *
	 *     // code goes here
	 *
	 * }
	 * </code>
	 *
	 * @param  mixed    $resource   (Optional) Resource to fetch.
	 *
	 *                                 <i>If not specified, the resource returned by the last run query is used.</i>
	 *
	 * @return mixed                   Returns an associative array that corresponds to the fetched row and moves the
	 *                                 internal data pointer ahead, or FALSE if there are no more rows.
	 */
	public function fetch_assoc($resource = '') {
		// if an active connection exists
		if ($this->connected()) {

			// if no resource was specified, and a query was run before, assign the last resource
			if ($resource == '' && isset($this->last_result)) {
				$resource = & $this->last_result;
			}

			// if $resource is a valid resource, fetch and return next row from the result set
			if (is_resource($resource)) {
				return mysql_fetch_assoc($resource);
			} elseif (is_integer($resource) && isset($this->cached_results[$resource])) {
				// if $resource is a pointer to an array taken from cache
				// get the current entry from the array and advance the pointer
				$result = each($this->cached_results[$resource]);
				// return as an associative array
				return $result[1];
				// if $resource is invalid
			} else {
				// save debug information
				$this->log('errors', array('message' => $this->language['not_a_valid_resource'] . '. Encountered inside "' . __FUNCTION__ . '()" method.'));
			}

		}

		// we don't have to report any error as either the connected() method already did
		// or did so the checking for valid resource
		return false;
	}

	/**
	 * Returns an associative array containing all the rows from the resource created by the previous query or from the
	 * resource given as argument and moves the internal pointer to the end.
	 *
	 * <code>
	 * // fetch all the rows as an associative array
	 * $records = $db->fetch_assoc_all($db->query('SELECT * FROM table WHERE criteria = ?', array($criteria)));
	 * </code>
	 *
	 * @param  mixed       $resource   (Optional) Resource to fetch.
	 *                                 <i>If not specified, the resource returned by the last run query is used.</i>
	 *
	 * @param  string      $index      (Optional) A column name from the records, containing unique values.
	 *                                 If specified, each entry in the returned array will have its index equal to the
	 *                                 the value of the specified column for each particular row.
	 *                                 <i>If not specified, returned array will have numerical indexes, starting from 0.</i>
	 *
	 * @return mixed                   Returns an associative array containing all the rows from the resource created
	 *                                 by the previous query or from the resource given as argument and moves the
	 *                                 internal pointer to the end. Returns FALSE on error.
	 */
	public function fetch_assoc_all($resource = '', $index = '') {
		// if an active connection exists
		if ($this->connected()) {

			// if no resource was specified, and a query was run before, assign the last resource
			if ($resource == '' && isset($this->last_result)) {
				$resource = & $this->last_result;
			}

			if (is_resource($resource) || (is_integer($resource) && isset($this->cached_results[$resource]))) {

				// this is the array that will contain the results
				$result = array();

				// move the pointer to the start of $resource
				// if there are any rows available (notice the @)
				if ($this->seek(0, $resource)) // iterate through the records
				{
					while ($row = $this->fetch_assoc($resource)) {
						// if $index was specified and exists in the returned row, add data to the result
						if (trim($index) != '' && isset($row[$index])) {
							$result[$row[$index]] = $row;
						} else {
							// if $index was not specified or does not exists in the returned row, add data to the result
							$result[] = $row;
						}
					}
				}

				// return the results
				return $result;

				// if $resource is invalid
			} else {
				$this->log('errors', array('message' => $this->language['not_a_valid_resource'] . '. Encountered inside "' . __FUNCTION__ . '()" method.'));
			}

		}

		// we don't have to report any error as either the connected() method already did
		// or did so the checking for valid resource
		return false;
	}

	/**
	 * Returns an object with properties that correspond to the fetched row and moves the internal data pointer ahead.
	 * The data is taken from the resource created by the previous query or from the resource given as argument.
	 *
	 * <code>
	 * // run a query
	 * $db->query('SELECT * FROM table WHERE criteria = ?', array($criteria));
	 *
	 * // iterate through the found records
	 * while ($row = $db->fetch_obj()) {
	 *
	 *     // code goes here
	 *
	 * }
	 * </code>
	 *
	 * @param  mixed    $resource   (Optional) Resource to fetch.
	 *
	 *                                 <i>If not specified, the resource returned by the last run query is used.</i>
	 *
	 * @return mixed                   Returns an object with properties that correspond to the fetched row and moves
	 *                                 the internal data pointer ahead, or FALSE if there are no more rows.
	 */
	public function fetch_obj($resource = '') {
		// if an active connection exists
		if ($this->connected()) {

			// if no resource was specified, and a query was run before, assign the last resource
			if ($resource == '' && isset($this->last_result)) {
				$resource = & $this->last_result;
			}

			// if $resource is a valid resource, fetch and return next row from the result set
			if (is_resource($resource)) {
				return mysql_fetch_object($resource);
			} elseif (is_integer($resource) && isset($this->cached_results[$resource])) {

				// get the current entry from the array and advance the pointer
				$result = each($this->cached_results[$resource]);
				// if we're not past the end of the array
				if ($result !== false) {
					// create a new generic object
					$obj = new stdClass();
					// populate the object's properties
					foreach ($result[1] as $key => $value) {
						$obj->$key = $value;
					}
					// if we're past the end of the array
					// make sure we return FALSE
				} else {
					$obj = false;
				}

				// return as object
				return $obj;

				// if $resource is invalid
			} else {
				$this->log('errors', array('message' => $this->language['not_a_valid_resource'] . '. Encountered inside "' . __FUNCTION__ . '()" method.'));
			}

		}

		// we don't have to report any error as either the connected() method already did
		// or did so the checking for valid resource
		return false;
	}

	/**
	 * Returns an associative array containing all the rows (as objects) from the resource created by the previous query
	 * or from the resource given as argument and moves the internal pointer to the end.
	 *
	 * <code>
	 *
	 * // fetch all the rows as an associative array
	 * $records = $db->fetch_obj_all($db->query('SELECT * FROM table WHERE criteria = ?', array($criteria)));
	 *
	 * </code>
	 *
	 * @param mixed $resource (Optional) Resource to fetch.
	 *                                 <i>If not specified, the resource returned by the last run query is used.</i>
	 *
	 * @param string $index            (Optional) A column name from the records, containing unique values.
	 *                                 If specified, each entry in the returned array will have its index equal to the
	 *                                 the value of the specified column for each particular row.
	 *                                 <i>If not specified, returned array will have numerical indexes, starting from 0.</i>
	 *
	 * @return mixed                   Returns an associative array containing all the rows (as objects) from the resource
	 *                                 created by the previous query or from the resource given as argument and moves
	 *                                 the internal pointer to the end. Returns FALSE on error.
	 */
	public function fetch_obj_all($resource = '', $index = '') {
		// if an active connection exists
		if ($this->connected()) {

			// if no resource was specified, and a query was run before, assign the last resource
			if ($resource == '' && isset($this->last_result)) {
				$resource = & $this->last_result;
			}

			if (is_resource($resource) || (is_integer($resource) && isset($this->cached_results[$resource]))) {

				// this is the array that will contain the results
				$result = array();
				// move the pointer to the start of $resource
				// if there are any rows available (notice the @)
				if ($this->seek(0, $resource)) // iterate through the resource data
				{
					while ($row = $this->fetch_obj($resource)){
						// if $index was specified and exists in the returned row, add data to the result
						if (trim($index) != '' && isset($row->$index)) {
							$result[$row->$index] = $row;
						} else {
							// if $index was not specified or does not exists in the returned row, add data to the result
							$result[] = $row;
						}
					}
				}

				// return the results
				return $result;

				// if $resource is invalid
			} else {
				$this->log('errors', array('message' => $this->language['not_a_valid_resource'] . '. Encountered inside "' . __FUNCTION__ . '()" method.'));
			}

		}

		// we don't have to report any error as either the connected() method already did
		// or did so the checking for valid resource
		return false;
	}

	/**
	 * Wrapper for the fetch_obj_all() method defined above
	 * The difference is that it accepts a 'SELECT' query as its parameters,
	 * passes it to query() method and then feeds the results to fetch_obj_all()
	 * Returns an associative array containing all the rows (as objects) from the query
	 *
	 * <code>
	 *
	 * // run a query, get an array of objects as a result
	 * $result = $db->get_data('SELECT * FROM table WHERE criteria = ?', array($criteria), true);
	 *
	 * </code>
	 *
	 * @param  string  $sql            MySQL statement to execute.
	 * @param  mixed   $replacements   (Optional) An array with as many items as the total parameter markers ("?", question
	 *                                 marks) in <i>$sql</i>. Each item will be automatically {@link escape()}-ed and
	 *                                 will replace the corresponding "?".
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $cache          (Optional) Instructs the script on whether it should cache the query's results
	 *                                 or not. Can be either FALSE - meaning no caching - or an integer representing the
	 *                                 number of seconds after which the cached results are considered to be expired
	 *                                 and the query will be executed again.
	 *
	 *                                 The caching method is specified by the value of the {@link caching_method} property.
	 *
	 *                                 Default is FALSE.
	 *
	 * @return mixed                   Returns an associative array containing all the rows (as objects) from the resource
	 *                                 created by the previous query or from the resource given as argument and moves
	 *                                 the internal pointer to the end. Returns FALSE on error.
	 */
	public function get_data($sql, $replacements = '', $cache = false) {
		if ($sql){
			if (stristr($sql, 'select')){
				$this->query($sql, $replacements, $cache);
				if (is_resource($this->last_result)){
					$result = $this->fetch_obj_all();
					if (count($result) == 1) $result = $result[0];
					return $result;
				} else {
					return $this->last_result;
				}
			} else {
				$this->log('errors', array('message' => $this->language['not_select']));
				$this->show_debug_console();
			}
		}
		return false;
	}

	/**
	 * Returns an array of phpiphany objects
	 *
	 * @param string $query The query to parse (should return pip entities)
	 * @return mixed Returns array of pip entities or false if nothing found
	 */
	public function get_pip_entities($query) {
		// Do not proceed if no query was specified
		if ($query) {
			// if an active connection exists
			if ($this->connected()) {

				// Run the query
				$this->query($query);

				// Assign the last resource
				$resource = & $this->last_result;

				if (is_resource($resource) || (is_integer($resource) && isset($this->cached_results[$resource]))) {

					// this is the array that will contain the results
					$result = array();

					// move the pointer to the start of $resource
					// if there are any rows available
					if ($this->seek(0, $resource)){
						while ($row = $this->fetch_assoc($resource)) {
							// add data to the result as a pip entity
							if (isset($row['guid'])) $result[] = orm::get($row['guid']);
						}
					}

					// return the results
					return $result;

				// if $resource is invalid
				} else {
					$this->log('errors', array('message' => $this->language['not_a_valid_resource'] . '. Encountered inside "' . __FUNCTION__ . '()" method.'));
				}
			}
		}

		// we don't have to report any error as either the connected() method already did
		// or did so the checking for valid resource
		return false;
	}

	/**
	 * Returns an array of associative arrays with information about the columns in the MySQL result linked to the specified result identifier
	 * Each entry will have the column's name as index and, associtated, an array with the following keys:
	 *
	 * - name
	 * - table
	 * - def
	 * - max_length
	 * - not_null
	 * - primary_key
	 * - multiple_key
	 * - unique_key
	 * - numeric
	 * - blob
	 * - type
	 * - unsigned
	 * - zerofill
	 *
	 * <code>
	 * // run a query
	 * $db->query('SELECT * FROM table');
	 *
	 * // print information about the columns
	 * printr($db->get_columns());
	 * </code>
	 *
	 *
	 *
	 * *****NOTE*****
	 * This is a VERY EXPENSIVE function as the table size might be enourmous.
	 * Only use it if you need to display the columns AND the data inside that table
	 * (even then, you might need to LIMIT your query as the DB might time out fetching billion records, for instance)
	 * If you need column names only, use @link get_table_columns()
	 * With that function you can also specify a particular column you want to see details for
	 *
	 *
	 *
	 * @param  resource|string    $resource   (Optional) Resource to fetch columns information from.
	 *                                 <i>If not specified, the resource returned by the last run query is used.</i>
	 *
	 * @return mixed                   Returns an associative array with information about the columns in the MySQL
	 *                                 result associated with the specified result identifier or FALSE on error.
	 */
	public function get_columns($resource = '') {
		// if an active connection exists
		if ($this->connected()) {

			// if no resource was specified, and a query was run before, assign the last resource
			if ($resource == '' && isset($this->last_result)) {
				$resource = & $this->last_result;
			}

			// if $resource is a valid resource
			if (is_resource($resource)) {

				$result = array();
				// get the number of columns in the resource
				$columns = mysql_num_fields($resource);
				// iterate through the columns in the resource set
				for ($i = 0; $i < $columns; $i++) {
					// fetch column information
					$column_info = mysql_fetch_field($resource, $i);
					// add information to the array of results
					// converting it first to an associative array
					$result[$column_info->name] = get_object_vars($column_info);
				}
				// return information
				return $result;

				// if $resource is a pointer to an array taken from cache
				// return information that was stored in the cached file
			} elseif (is_integer($resource) && isset($this->cached_results[$resource])) {
				return $this->column_info;
			} else {
				$this->log('errors', array('message' => $this->language['not_a_valid_resource'] . '. Encountered inside "' . __FUNCTION__ . '()" method.'));
			}

		}

		// we don't have to report any error as either the connected() method already did
		// or did so the checking for valid resource
		return false;
	}

	/**
	 * Updates a table column definition
	 *
	 *
	 * *****NOTE*****
	 * If you're updating TEXT field and indexing it, MySQL will return "BLOB/TEXT column 'column' used in key specification without a key length"
	 * With full BLOB or TEXT without the length value, MySQL is unable to guarantee the uniqueness of the column as it’s of variable and dynamic size.
	 * The solution to the problem is to remove the TEXT or BLOB column from the index or unique constraint, or set another field as primary key.
	 * If you can’t do that, and wanting to place a limit on the TEXT or BLOB column, try to use VARCHAR type and place a limit of length on it.
	 * 
	 * 
	 * 
	 * @param string $table Table name where to update this column
	 * @param string $column Column name. If none specified, will create a new column
	 * @param db_field $field Field object
	 * @return bool Returns true on success, false on error
	 */
	public function update_column($table, $column, $field){
		$table = $this->escape($table);
		$field = $this->escape($field);

		$null = $field->null? 'NULL' : 'NOT NULL';
		$ai = $field->ai? 'AUTO_INCREMENT' : '';
		$type = preg_match('/(DATE|TEXT|TINYTEXT|MEDIUMTEXT|LONGTEXT|BLOB|TINYBLOB|MEDIUMBLOB|LONGBLOB)/i', $field->type)? $field->type : "$field->type($field->size)";

		// Edit a column or create new?
		if ($column){
			$column = $this->escape($column);
			return $this->query("ALTER TABLE `$table` CHANGE `$column` `$field->name` $type $null $ai")? true : false;
		} else {
			$index = '';
			if ($field->index){
				if ($field->index === 'primary') $index = 'PRIMARY KEY';
				elseif ($field->index === 'unique') $index = ", ADD PRIMARY KEY ( `$field->name` ), ADD UNIQUE ( `$field->name` )";
				elseif ($field->index === 'text') $index = ", ADD PRIMARY KEY ( `$field->name` ), ADD FULLTEXT ( `$field->name` )";
				elseif ($field->index === true or $field->index == 1) $index = ", ADD PRIMARY KEY ( `$field->name` ), ADD INDEX ( `$field->name` )";
			}
			return $this->query("ALTER TABLE `$table` ADD `$field->name` $type $null $ai $index")? true : false;
		}
	}

	/**
	 * Drops a specific column in the supplied table
	 *
	 * @param string $table_name Table name
	 * @param string $column_name Column name
	 * @return bool Returns true on successful column delete, false on error
	 */
	public function drop_column($table_name, $column_name){
		$table_name = $this->escape($table_name);
		$column_name = $this->escape($column_name);
		$sql = "ALTER TABLE `$table_name` DROP `$column_name`";
		return $this->query($sql)? true : false;
	}

	/**
	 * Returns the MySQL link identifier associated with the current connection to the MySQL server.
	 *
	 * Why a separate method? Because the library uses "lazy connection" (it is not actually connecting to the database
	 * until the first query is executed) there's no link identifier available when calling the {@link connect()} method.
	 *
	 * <code>
	 * // create the database object
	 * $db = new database();
	 *
	 * // nothing is returned by this method!
	 * $db->connect('host', 'username', 'password', 'database');
	 *
	 * // get the link identifier
	 * $link = $db->get_link();
	 * </code>
	 *
	 * @return identifier  Returns the MySQL link identifier associated with the current connection to the MySQL server.
	 */
	public function get_link() {
		// if an active connection exists
		// return the MySQL link identifier associated with the current connection to the MySQL server
		if ($this->connected()) {
			return $this->link_identifier;
		}

		// if script gets this far, return false as something must've been wrong
		return false;
	}

	/**
	 * Returns information about the columns of a given table, as an associative array.
	 *
	 * <code>
	 * // get column information for a table named "table_name"
	 * $db->get_table_columns('table_name');
	 * </code>
	 *
	 * @param  string  $table   Name of table to return column information for.
	 * @param  string  $column  Name of column to get the details for (optional)
	 *
	 * @return array            Returns information about the columns of a given table, as an associative array.
	 */
	public function get_table_columns($table, $column = '') {
		$where = $column? 'WHERE `Field` = "' . $this->escape($column) . '"' : '';
		// fetch and return data
		return $this->fetch_assoc_all($this->query('SHOW COLUMNS FROM `' . $this->escape($table) . '`' . $where), 'Field');
	}

	/**
	 * Returns an associative array with a lot of useful information on all or specific tables only.
	 *
	 * <code>
	 * // return status information on tables having their name starting with "users"
	 * $tables = get_table_status('users%');
	 * </code>
	 *
	 * @param  string  $pattern    (Optional) Instructs the method to return information only on tables whose name matches
	 *                             the given pattern. Can be a table name or a pattern with "%" as a wildcard.
	 * @return array               Returns an associative array with a lot of useful information on all or specific tables only.
	 */
	public function get_table_status($pattern = '') {
		// run the query
		$this->query('SHOW TABLE STATUS ' . (trim($pattern) != '' ? 'LIKE ?' : ''), array($pattern));

		// fetch and return data
		return $this->fetch_assoc_all('Name');
	}

	/**
	 * Returns an array with all the tables in the current database.
	 *
	 * <code>
	 * // get all tables from database
	 * $tables = get_tables();
	 * </code>
	 *
	 * @return array   An array with all the tables in the current database.
	 */
	public function get_tables() {
		// fetch all the tables in the database
		$result = $this->fetch_assoc_all($this->query('SHOW TABLES'));
		$tables = array();

		// as the results returned by default are quite odd
		// translate them to a more usable array
		foreach ($result as $tableName) {
			$tables[] = array_pop($tableName);
		}

		// return the array with the table names
		return $tables;
	}

	/**
	 * Works similarly to PHP's implode() function, with the difference that the "glue" is always the comma which is
	 * this method's {@link escape()} arguments.
	 *
	 * <i>Useful for escaping an array's values used in SQL statements with the "IN" keyword.</i>
	 *
	 * <code>
	 * $array = array(1,2,3,4);
	 *
	 * //  INCORRECT
	 * //  this would not work as the WHERE clause in the SQL statement would become WHERE column IN ('1,2,3,4')
	 * $db->query('SELECT column FROM table WHERE column IN (?)', array($array));
	 *
	 * //  CORRECT
	 * //  this would work as the WHERE clause in the SQL statement would become WHERE column IN ('1','2','3','4') which is what we actually need
	 * $db->query('SELECT column FROM table WHERE column IN (' . $db->implode($array) . ')');
	 * </code>
	 *
	 *
	 * @param  array   $pieces     An array with items to be "glued" together
	 * @return string              Returns the string representation of all the array elements in the same order,
	 *                             escaped and with commas between each element.
	 */
	public function implode($pieces) {
		$result = '';

		// iterate through the array's items and "glue" items together
		foreach ($pieces as $piece) {
			$result .= ($result != '' ? ',' : '') . '\'' . $this->escape($piece) . '\'';
		}

		return $result;
	}

	/**
	 * Shorthand for INSERT queries.
	 *
	 * When using this method, column names will be enclosed in grave accents " ` " (thus, allowing seamless usage of
	 * reserved words as column names) and values will be automatically escaped.
	 *
	 * <code>
	 * $db->insert('table',array(
	 *         'column1'   =>  'value1',
	 *         'column2'   =>  'value2',
	 * ));
	 * </code>
	 *
	 * @param  string  $table          Table in which to insert.
	 * @param  array   $columns        An associative array where the array's keys represent the columns names and the
	 *                                 array's values represent the values to be inserted in each respective column.
	 *
	 *                                 Column names will be enclosed in grave accents " ` " (thus, allowing seamless
	 *                                 usage of reserved words as column names) and values will be automatically
	 *                                 {@link escape()}d.
	 *
	 * @param  boolean $ignore         (Optional) By default, trying to insert a record that would cause a duplicate
	 *                                 entry for a primary key would result in an error. If you want these errors to be
	 *                                 skipped set this argument to TRUE.
	 *
	 *                                 For more information see {@link http://dev.mysql.com/doc/refman/5.5/en/insert.html MySQL's INSERT IGNORE syntax}.
	 *
	 *                                 Default is FALSE.
	 *
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *
	 *                                 Default is FALSE.
	 *
	 * @return boolean                 Returns TRUE on success of FALSE on error.
	 */
	public function insert($table, array $columns, $ignore = false, $highlight = false) {

		// If the column was blank, ignore it (only for inserts; updates will overwrite with whatever value is supplied)
		// In order to pass an empty value, simply assign it a 'NULL'
		foreach ($columns as $key => $value) {
			if (!$value) unset($columns[$key]);
			else {
				// unescape the string to avoid double escape
				$columns[$key] = html_entity_decode(stripslashes($value));
			}
		}

		// enclose the column names in grave accents
		$cols = '`' . implode('`,`', array_keys($columns)) . '`';

		// parameter markers for escaping values later on
		$values = rtrim(str_repeat('?,', count($columns)), ',');

		// run the query
		$this->query('INSERT' . ($ignore ? ' IGNORE' : '') . ' INTO `' . $table . '` (' . $cols . ') VALUES (' . $values . ')', array_values($columns), false, $highlight);

		// return true if query was executed successfully
		if ($this->last_result) {
			return true;
		}

		return false;
	}

	/**
	 * Shorthand inserting multiple rows in a single query.
	 *
	 * When using this method, column names will be enclosed in grave accents " ` " (thus, allowing seamless usage of
	 * reserved words as column names) and values will be automatically escaped.
	 *
	 * <code>
	 * $db->insert_bulk('table',
	 *     array('column1', 'column2'),
	 *     array(
	 *         array('value1', 'value2'),
	 *         array('value3', 'value4'),
	 *         array('value5', 'value6'),
	 *         array('value7', 'value8'),
	 *         array('value9', 'value10')
	 *     )
	 * ));
	 * </code>
	 *
	 * @param  string  $table          Table in which to insert.
	 * @param  array   $columns        An array with columns to insert values into.
	 *
	 *                                 Column names will be enclosed in grave accents " ` " (thus, allowing seamless
	 *                                 usage of reserved words as column names).
	 *
	 * @param  array  $data           An array of an unlimited number of arrays containing values to be inserted.
	 *
	 *                                 Values will be automatically {@link escape()}d.
	 *
	 * @param  boolean $ignore         (Optional) By default, trying to insert a record that would cause a duplicate
	 *                                 entry for a primary key would result in an error. If you want these errors to be
	 *                                 skipped set this argument to TRUE.
	 *
	 *                                 For more information see {@link http://dev.mysql.com/doc/refman/5.5/en/insert.html MySQL's INSERT IGNORE syntax}.
	 *
	 *                                 Default is FALSE.
	 *
	 * @return boolean                 Returns TRUE on success of FALSE on error.
	 */
	public function insert_bulk($table, $columns, $data, $ignore = false) {
		// if $data is not an array of arrays
		if (!is_array(array_pop(array_values($data)))){
			$this->log('errors', array('message' => $this->language['data_not_an_array']));
		} else {
			// start preparing the INSERT statement
			$sql = 'INSERT' . ($ignore ? ' IGNORE' : '') . ' INTO `' . $table . '` (' . '`' . implode('`,`', $columns) . '`' . ') VALUES ';

			// iterate through the arrays and escape values
			foreach ($data as $values) {
				$sql .= '(' . $this->implode($values) . '),';
			}

			// run the query
			$this->query(rtrim($sql, ','));

			// return true if query was executed successfully
			if ($this->last_result) {
				return true;
			}

		}

		// if script gets this far, return false as something must've went wrong
		return false;
	}

	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 *
	 * @return mixed   The ID generated for an AUTO_INCREMENT column by the previous INSERT query on success,
	 *                 '0' if the previous query does not generate an AUTO_INCREMENT value, or FALSE if there was
	 *                 no MySQL connection.
	 */
	public function insert_id() {

		// if an active connection exists
		if ($this->connected()) {
			// if a query was executed before, return the AUTO_INCREMENT value
			if (isset($this->last_result)) {
				return mysql_insert_id($this->link_identifier);
			} else {
				$this->log('errors', array('message' => $this->language['not_a_valid_resource'] . '. Encountered inside "' . __FUNCTION__ . '()" method.'));
			}
		}

		// upon error, we don't have to report anything as connected() method already did
		// just return FALSE
		return false;
	}

	/**
	 * When using this method, if a row is inserted that would cause a duplicate value in a UNIQUE index or PRIMARY KEY,
	 * an UPDATE of the old row is performed.
	 *
	 * Read more at {@link http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html}.
	 *
	 * When using this method, column names will be enclosed in grave accents " ` " (thus, allowing seamless usage of
	 * reserved words as column names) and values will be automatically escaped.
	 *
	 * <code>
	 * // presuming article_id is a UNIQUE index or PRIMARY KEY, the statement below will insert a new row for given
	 * // $article_id and set the "votes" to 0. But, if $article_id is already in the database, increment the votes'
	 * // numbers.
	 * $db->insert_update('table', array(
	 *         'article_id'    =>  $article_id,
	 *         'votes'         =>  0,
	 *     ), array(
	 *         'votes'         =>  'INC(1)',
	 *     )
	 * );
	 * </code>
	 *
	 * @param  string  $table          Table in which to insert/update.
	 * @param  array   $columns        An associative array where the array's keys represent the columns names and the
	 *                                 array's values represent the values to be inserted in each respective column.
	 *
	 *                                 Column names will be enclosed in grave accents " ` " (thus, allowing seamless
	 *                                 usage of reserved words as column names) and values will be automatically
	 *                                 {@link escape()}d.
	 *
	 * @param  array   $update         (Optional) An associative array where the array's keys represent the columns names
	 *                                 and the array's values represent the values to be inserted in each respective
	 *                                 column.
	 *
	 *                                 This array represents the columns/values to be updated if the inserted row would
	 *                                 cause a duplicate value in a UNIQUE index or PRIMARY KEY.
	 *
	 *                                 If an empty array is given, the values in <i>$columns</i> will be used.
	 *                                 Column names will be enclosed in grave accents " ` " (thus, allowing seamless
	 *                                 usage of reserved words as column names) and values will be automatically
	 *                                 {@link escape()}d.
	 *
	 *                                 A special value may also be used for when a column's value needs to be
	 *                                 incremented or decremented. In this case, use <i>INC(value)</i> where <i>value</i>
	 *                                 is the value to increase the column's value with. Use <i>INC(-value)</i> to decrease
	 *                                 the column's value. See {@link update()} for an example.
	 *
	 *                                 Default is an empty array.
	 *
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *                                 Default is FALSE.
	 *
	 * @return boolean                 Returns TRUE on success of FALSE on error.
	 */
	public function insert_update($table, $columns, $update = array(), $highlight = false) {
		// if $update is not given as an array, make it an empty array
		if (!is_array($update)) {
			$update = array();
		}

		// enclose the column names in grave accents
		$cols = '`' . implode('`,`', array_keys($columns)) . '`';
		// parameter markers for escaping values later on
		$values = rtrim(str_repeat('?,', count($columns)), ',');

		// if no $update specified
		if (empty($update)) {
			// use the columns specified in $columns
			$update_cols = '`' . implode('` = ?,`', array_keys($columns)) . '` = ?';
			// use the same column for update as for insert
			$update = $columns;
			// if $update is specified
			// generate the SQL from the $update array
		} else {
			$update_cols = $this->build_sql($update);
		}

		// run the query
		$this->query('INSERT INTO `' . $table . '` (' . $cols . ') VALUES (' . $values . ') ON DUPLICATE KEY UPDATE ' . $update_cols,
					array_merge(array_values($columns), array_values($update)), false, $highlight);

		// return true if query was executed successfully
		if ($this->last_result) {
			return true;
		}

		return false;
	}

	/**
	 * Shorthand for REPLACE queries.
	 * If $overwrite is set, any records that already exist in the database will be overwritten
	 *
	 * When using this method, column names will be enclosed in grave accents " ` " (thus, allowing seamless usage of
	 * reserved words as column names) and values will be automatically escaped.
	 *
	 * <code>
	 * $db->replace('table',array(
	 *         'column1'   =>  'value1',
	 *         'column2'   =>  'value2',
	 * ));
	 * </code>
	 *
	 * @param  string  $table          Table in which to insert.
	 * @param  array   $columns        An associative array where the array's keys represent the columns names and the
	 *                                 array's values represent the values to be inserted in each respective column.
	 *
	 *                                 Column names will be enclosed in grave accents " ` " (thus, allowing seamless
	 *                                 usage of reserved words as column names) and values will be automatically
	 *                                 {@link escape()}d.
	 *
	 * @param  boolean $overwrite      (Optional) TRUE if records need to be overwritten
	 *                                 For more information see {@link http://bogdan.org.ua/2007/10/18/mysql-insert-if-not-exists-syntax.html}.
	 *                                 Default is FALSE.
	 *
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *                                 Default is FALSE.
	 *
	 * @return boolean                 Returns TRUE on success of FALSE on error.
	 */
	public function replace($table, $columns, $overwrite = false, $highlight = false) {
		// enclose the column names in grave accents
		$cols = '`' . implode('`,`', array_keys($columns)) . '`';
		// parameter markers for escaping values later on
		$values = rtrim(str_repeat('?,', count($columns)), ',');

		// if we need to overwrite data in the existing row, we will use replace
		if ($overwrite){
			$partial_sql = 'REPLACE';
		} else {
			// otherwise we use insert ignore
			$partial_sql = 'INSERT IGNORE';
		}

		// run the query
		$this->query($partial_sql . ' INTO `' . $table . '` (' . $cols . ') VALUES (' . $values . ')', array_values($columns), false, $highlight);

		// return true if query was executed successfully
		if ($this->last_result) {
			return true;
		}

		return false;
	}

	/**
	 * Joins several tables and executes CRUD queries
	 *
	 * 
	 * Usage:
	 *
	 * $db = new database();
	 *
	 * $f1 = new join_field();
	 * $f1->name = 'table.id';
	 * $f1->value = 32;
	 *
	 * $f2 = new join_field();
	 * $f2->name = 'table2.name';
	 * $f2->value = 'john';
	 * $f2->op = 'OR';
	 *
	 * $f3 = new join_field();
	 * $f3->name = 'table3.description';
	 * $f3->value = 'null';
	 * $f3->sql = 'or (1=1)';
	 *
	 * $f4 = new join_field();
	 * $f4->name = 'table.file';
	 * $f4->value = 'one';
	 *
	 * $result = $db->multitable('select', array($f1, $f4, $f2, $f3));
	 *
	 *
	 * @param string $action Action for the query to launch (SELECT|UPDATE|DELETE)
	 * @param array $fields Array of fields of join_field class
	 * @param array|null $columns Columns to select. If none are supplied, assumes all (*)
	 * @return string
	 */
	public function multitable($action, array $fields, array $columns = null){
		if (!$action){
			$this->log('errors', array('message' => $this->language['no_query_action']));
		} elseif (!$fields or count($fields) < 2){
			$this->log('errors', array('message' => $this->language['no_join_fields']));
		} else {
			$tables = $wheres = '';
			switch (strtoupper($action)) {
				case 'SELECT':
					foreach ($fields as $field){
						$tables .= $field->table . ',';
						if (isset($field->value)) {
							$value = ($field->value == 'null' or is_null($field->value))? 'NULL' : "'$field->value'";
							$wheres .= "$field->op $field->name = $value ";
						}
						if ($field->sql) $wheres .= "$field->sql ";
					}
					// Remove and add commas while removing duplicate entries in the process
					$tables = '`' . substr(implode('`, `', array_unique(explode(',', $tables))), 0, -2);
					// If no columns are specified, select all
					$columns = $columns? $columns : '*';
					// Remove first operator
					$wheres = substr($wheres, strpos($wheres, ' '));

					$sql = "SELECT $columns FROM $tables WHERE $wheres";
					return $sql;
					break;
				case 'UPDATE':
					/* @TODO Does not work, needs more arguments */
					foreach ($fields as $field){
						$tables .= $field->table . ',';
						if (isset($field->value)) {
							$value = ($field->value == 'null' or is_null($field->value))? 'NULL' : "'$field->value'";
							$wheres .= "$field->name = $value, ";
						}
					}
					// Remove and add commas while removing duplicate entries in the process
					$tables = implode(', ', array_unique(explode(',', $tables)));
					// If no columns are specified, select all
					$columns = $columns? $columns : '*';
					// Remove first operator
					$wheres = substr($wheres, strpos($wheres, ' '));

					$sql = "UPDATE $tables SET $columns WHERE $wheres";
					return $sql;
					break;
				case 'DELETE':
					$sql = "DELETE";
					break;
				default:
					$this->log('errors', array('message' => $this->language['not_a_valid_action']));
					break;
			}
		}
	}

	/**
	 * Sets the language to be used for messages in the debug console.
	 *
	 * <code>
	 * // show messages in the debug console in German
	 * $db->language('german');
	 * </code>
	 *
	 * @param  string  $language   The 2 letter name of the language file to load
	 *                             The default path for languages is %system_root%/i18n/models/database/debug
	 *                             Default language is english (en.php)
	 *
	 * @return void
	 */
	public function language($language) {
		// include the language file
		require(dirname(dirname(__FILE__)) . '/i18n/models/database/' . $language . '.php');
	}

	/**
	 * Optimizes all tables that have overhead (unused, lost space)
	 *
	 * <code>
	 * // optimize all tables in the database
	 * $db->optimize();
	 * </code>
	 *
	 * @return void
	 */
	public function optimize() {
		// fetch information on all the tables in the database
		$tables = $this->get_table_status();

		// iterate through the database's tables, and if it has overhead (unused, lost space), optimize it
		foreach ($tables as $table) {
			if ($table['Data_free'] > 0) {
				$this->query('OPTIMIZE TABLE `' . $table['Name'] . '`');
			}
		}
	}

	/**
	 * Parses a MySQL dump file (like an export from phpMyAdmin).
	 *
	 * <i>If you have to parse a very large file and your script crashes due to timeout or because of memory limitations,
	 * try the following:</i>
	 *
	 * <code>
	 * // prevent script timeout
	 * set_time_limit(0);
	 * // allow for more memory to be used by the script
	 * ini_set('memory_limit','128M');
	 * </code>
	 *
	 * @param  string  $path   Path to the file to be parsed.
	 *
	 * @return boolean         Returns TRUE on success or FALSE on failure.
	 */
	public function parse_file($path) {
		// if an active connection exists
		if ($this->connected()) {

			// read file into an array
			$file_content = file($path);

			// if file was successfully opened
			if ($file_content) {
				$query = '';
				// iterates through every line of the file
				foreach ($file_content as $sql_line) {
					// trims whitespace from both beginning and end of line
					$tsql = trim($sql_line);
					// if line content is not empty and is the line does not represent a comment
					if ($tsql != '' && substr($tsql, 0, 2) != '--' && substr($tsql, 0, 1) != '#') {
						// add to query string
						$query .= $sql_line;
						// if line ends with ';'
						if (preg_match('/;\s*$/', $sql_line)) {
							// run the query
							$this->query($query);
							// empties the query string
							$query = '';
						}
					}
				}

				return true;

				// if file could not be opened
			} else{
				$this->log('errors', array('message' => $this->language['file_could_not_be_opened']));
			}
		}

		// we don't have to report any error as connected() method already did or checking for file returned FALSE
		return false;
	}

	/**
	 * Wrapper to handle multiple queries
	 * Since the default mysql driver does not have multi_query support, this workaround breaks queries at semi-colon,
	 * and executes each query separately within a loop.
	 *
	 * @TODO Some other databases might come with this function natively supported, check for that (mysqli: $mysqli->multi_query($query))
	 * @param string $sql String with multiple queries separated by a semi colon
	 * @param bool $echo Whether to display the processed query back to the caller (useful for long queries if used with ob_flush())
	 * @return bool Returns true on successful query execution (errors are logged in the debugger) or false if $sql is empty
	 */
	public function multi_query($sql = '', $echo = false){
		if ($sql){
			$queries = explode(';', $sql);
			foreach ($queries as $query){
				if ($query and strlen($query) > 5 and $query[0] != '-') {
					if ($result = $this->query(trim($query))) {
						if ($echo) {
							ob_flush();
							flush();
							echo truncate('Processing query: ' . $query, 70) . '<br>';
						}
					} else return false;
				}
			}
			return true;
		} return false;
	}

	/**
	 * Generates a CSV file based on the select query supplied
	 *
	 * @param string $sql The query
	 * @param string $path Path to the filename where to save the output. If none give, will save in tmp folder
	 * @param bool $headers Whether or not to include headers in CSV generation. (default true)
	 * @return bool Returns true on success, false on failure
	 */
	public function csv($sql, $path = '', $headers = true){
		if ($sql){
			// If $path was empty, store the csv file in a temp directory
			if (!$path){
				$path = '/tmp/';
				dir_setup($path);
				$path .= get_loggedin_user()->guid . '_' . $_SERVER['REQUEST_TIME'] . '.csv';
			}
			$this->query($sql);
			$resource = & $this->last_result;
			// Open a file handler
			if ($fh = fopen($path, 'w')){
				if ($headers) {
					// output header row (if at least one row exists)
					$row = mysql_fetch_assoc($resource);
					if ($row) {
						fputcsv($fh, array_keys($row));
						// reset pointer back to the beginning
						$this->seek(0, $resource);
					}
				}

				while ($row = mysql_fetch_assoc($resource)) {
					fputcsv($fh, $row);
				}

				fclose($fh);
				return true;
			} else {
				// save debug information
				$this->log('errors', array('message' => 'CSV file creation error. Failed to open file handler. Encountered inside "' . __FUNCTION__ . '()" method.'));
			}
		} return false;
	}

	public function xml($sql){
		//
	}

	/**
	 * Runs MySQL query.
	 *
	 * After a SELECT query you can get the number of returned rows by reading the {@link returned_rows} property.
	 *
	 * After an UPDATE, INSERT or DELETE query you can get the number of affected rows by reading the
	 * {@link affected_rows} property.
	 *
	 * <b>Note that you don't need to return the result of this method in a variable for using it later with
	 * a fetch method, like {@link fetch_assoc()} or {@link fetch_obj()} as all these methods, if called without the
	 * resource arguments, work on the LAST returned result resource!</b>
	 *
	 * <code>
	 * // run a query
	 * $db->query('SELECT * FROM users WHERE gender = ? ', array($gender));
	 * </code>
	 *
	 * @param  string  $sql            MySQL statement to execute.
	 * @param  mixed   $replacements   (Optional) An array with as many items as the total parameter markers ("?", question
	 *                                 marks) in <i>$sql</i>. Each item will be automatically {@link escape()}-ed and
	 *                                 will replace the corresponding "?".
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $cache          (Optional) Instructs the script on whether it should cache the query's results
	 *                                 or not. Can be either FALSE - meaning no caching - or an integer representing the
	 *                                 number of seconds after which the cached results are considered to be expired
	 *                                 and the query will be executed again.
	 *
	 *                                 The caching method is specified by the value of the {@link caching_method} property.
	 *
	 *                                 Default is FALSE.
	 *
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *
	 *                                 Default is FALSE.
	 *
	 * @param  boolean $calc_rows      (Optional) If query is a SELECT query, this argument is set to TRUE, and there is
	 *                                 a LIMIT applied to the query, the value of the {@link found_rows} property (after
	 *                                 the query was run) will represent the number of records that would have been
	 *                                 returned if there was no LIMIT applied to the query.
	 *
	 *                                 This is very useful for creating pagination or computing averages. Also, note
	 *                                 that this information will be available without running an extra query. Here's
	 *                                 how {@link http://dev.mysql.com/doc/refman/5.0/en/information-functions.html#function_found-rows}
	 *
	 *                                 Default is FALSE.
	 *
	 * @return mixed                   On success, returns a resource or an array (if results are taken from the cache)
	 *                                 or FALSE on error.
	 *
	 *                                 <i>If query results are taken from cache, the returned result will be a pointer to
	 *                                 the actual results of the query!</i>
	 */
	public function query($sql, $replacements = '', $cache = false, $highlight = false, $calc_rows = false) {
		// if an active connection exists
		if ($this->connected()) {
			// remove spaces used for indentation (if any)
			$sql = preg_replace(array("/^\s+/m", "/\r\n/"), array('', ' '), $sql);
			unset($this->affected_rows);

			// if $replacements is specified but it's not an array
			if ($replacements != '' && !is_array($replacements)) {
				$this->log('unsuccessful-queries', array('query' => $sql, 'error' => $this->language['warning_replacements_not_array']));
			} elseif ($replacements != '' && is_array($replacements)) {
				foreach ($replacements as $key => $replacement){
					// Special treatment for NULL
					if ($replacement === NULL or strtoupper($replacement) == 'NULL') $replacement = 'NULL';
					// And NOW()
					elseif ($replacement == 'NOW()') $replacement = 'NOW()';
					// Escape a regular string (no integers)
					elseif (!is_integer($replacement)) $replacement = "'" . $this->escape($replacement) . "'";

					$replacement = str_replace('$', '\$', $replacement);
					$sql = preg_replace('/\?/', $replacement, $sql, 1);
				}
			}

			// $calc_rows is TRUE, we have a SELECT query and the SQL_CALC_FOUND_ROWS string is not in it
			// (we do this trick to get the numbers of records that would've been returned if there was no LIMIT applied)
			if ($calc_rows && strtolower(substr(ltrim($sql), 0, 6)) == 'select' && strpos($sql, 'SQL_CALC_FOUND_ROWS') === false) {
				// add the 'SQL_CALC_FOUND_ROWS' parameter to the query
				$sql = preg_replace('/SELECT/i', 'SELECT SQL_CALC_FOUND_ROWS', $sql, 1);
			}

			unset($this->last_result);
			// starts a timer
			list($usec, $sec) = explode(' ', microtime());
			$start_timer = (float)$usec + (float)$sec;
			$refreshed_cache = 'nocache';

			// if we need to look for a cached version of the query's results and caching is enabled
			if ($cache !== false and $this->cache) {
				// by default, we assume that the cache exists and is not expired
				$refreshed_cache = false;
				// the key to identify this particular information
				$cache_key = md5($sql);
				// if there is a cached version of what we're looking for, and data is valid
				if ($this->cached_results[] = $this->cache->read($cache_key)) {
					// assign to the last_result property the pointer to the position where the array was added
					$this->last_result = count($this->cached_results) - 1;
					// reset the pointer of the array
					reset($this->cached_results[$this->last_result]);
				}
			}

			// if query was not read from the cache
			if (!isset($this->last_result)) {
				// run the query
				//error_log('THE QUERY: ' . $sql);
				$this->last_result = mysql_query($sql, $this->link_identifier);
				// in case of error save debug information
				if ($this->fatal_error = mysql_error($this->link_identifier)) {
					$this->log('unsuccessful-queries', array('query' => $sql, 'error' => $this->fatal_error));
					$this->fatal_error = '<em>' . $this->fatal_error . '</em>' . "\n<br><strong>SQL:</strong> <span class='gray'>" . htmlentities($sql) . '</span>';
				}

				// if no test transaction, query was unsuccessful and a transaction is in progress
				if ($this->transaction_status !== 3 && !$this->last_result && $this->transaction_status !== 0) {
					// set transaction_status to 2 so that the transaction_commit know that it has to rollback
					$this->transaction_status = 2;
				}
			}

			// stops timer
			list($usec, $sec) = explode(' ', microtime());
			$stop_timer = (float)$usec + (float)$sec;
			// add the execution time to the total execution time
			// (we will use this in the debug console)
			$this->total_execution_time += $stop_timer - $start_timer;

			// if execution time exceeds max_query_time
			if ($stop_timer - $start_timer > $this->max_query_time) {
				// then send a notification mail
				mail($this->notification_address, sprintf($this->language['email_subject'], $this->notifier_domain), sprintf($this->language['email_content'], $this->max_query_time, $stop_timer - $start_timer, $sql), 'From: ' . $this->notifier_domain);
			}

			// if the query was successfully executed
			if ($this->last_result !== false) {
				// if query's result was not read from cache (meaning $this->last_result is a result resource or boolean
				// TRUE - as queries like UPDATE, DELETE, DROP return boolean TRUE on success rather than a result resource)
				if (is_resource($this->last_result) || $this->last_result === true) {
					// by default, consider this not to be a SELECT query
					$is_select = false;

					// if returned resource is a valid resource, consider query to be a SELECT query
					if (is_resource($this->last_result)) {
						$is_select = true;
					}

					// reset these values for each query
					$this->returned_rows = $this->found_rows = 0;

					// if query was a SELECT query
					if ($is_select) {
						// the returned_rows property holds the number of records returned by a SELECT query
						$this->returned_rows = $this->found_rows = mysql_num_rows($this->last_result);
						// if we need the number of rows that would have been returned if there was no LIMIT
						if ($calc_rows) {
							// get the number of records that would've been returned if there was no LIMIT
							$found_rows = mysql_fetch_assoc(mysql_query('SELECT FOUND_ROWS()', $this->link_identifier));
							$this->found_rows = $found_rows['FOUND_ROWS()'];
						}

						// if query was an action query, the affected_rows property holds the number of affected rows by
						// action queries (DELETE, INSERT, UPDATE)
					} else {
						$this->affected_rows = mysql_affected_rows($this->link_identifier);
					}

					// if query's results need to be cached and cache is enabled
					if ($is_select and $cache !== false and $this->cache) {

						// flag that we have refreshed the cache
						$refreshed_cache = true;
						$cache_data = array();
						// iterate though the query's records and save the results in a temporary variable
						while ($row = mysql_fetch_assoc($this->last_result)) {
							$cache_data[] = $row;
						}

						// if there were any records fetched, resets the internal pointer of the result resource
						if (!empty($cache_data)) {
							$this->seek(0, $this->last_result);
						}

						// we'll also be saving the found_rows, returned_rows and columns information
						array_push($cache_data, array('returned_rows' => $this->returned_rows, 'found_rows' => $this->found_rows, 'column_info' => $this->get_columns()));

						// save cache
						$cache_key = isset($cache_key) and !empty($cache_key)? $cache_key : md5($sql);
						$this->cache->save($cache_key, $cache_data);
					}
					// if query was read from cache
				} else {
					// if read from cache this must be a SELECT query
					$is_select = true;
					// the last entry in the cache file contains the returned_rows, found_rows and column_info properties
					// we need to take them off the array
					$counts = array_pop($this->cached_results[$this->last_result]);
					// set extract these properties from the values in the cached file
					$this->returned_rows = $counts['returned_rows'];
					$this->found_rows = $counts['found_rows'];
					$this->column_info = $counts['column_info'];
				}

				// if debugging is on
				if ($this->debug) {

					$warning = '';
					$result = array();

					// if rows were returned
					if ($is_select) {

						$row_counter = 0;

						// put the first rows, as defined by console_show_records, in an array to show them in the debug console
						// if query was not read from cache
						if (is_resource($this->last_result)) {
							// iterate through the records until we displayed enough records
							while ($row_counter++ < $this->console_show_records && $row = mysql_fetch_assoc($this->last_result)) {
								$result[] = $row;
							}

							// reset the pointer in the result afterwards
							if (mysql_num_rows($this->last_result) != 0) mysql_data_seek($this->last_result, 0);

							// if query was read from the cache
							// put the first rows, as defined by console_show_records, in an array to show them in the
							// debug console
						} else {
							$result = array_slice($this->cached_results[$this->last_result], 0, $this->console_show_records);
						}

						// if there were queries run already
						if (isset($this->debug_info['successful-queries'])) {

							$keys = array();

							// iterate through the run queries
							// to find out if this query was already run
							foreach ($this->debug_info['successful-queries'] as $key => $query_data) {
								if (isset($query_data['records']) && !empty($query_data['records']) && $query_data['records'] == $result) {
									$keys[] = $key;
								}
							}

							// if the query was run before issue a warning for all the queries that were found to be the same as the current one
							if (!empty($keys)) {
								foreach ($keys as $key) {
									// we create the variable as we will also use it later when adding the
									// debug information for this query
									$warning = sprintf($this->language['optimization_needed'], count($keys));
									// add the warning to the query's debug information
									$this->debug_info['successful-queries'][$key]['warning'] = $warning;
								}
							}

						}

						// if it's a SELECT query and query is not read from cache...
						if ($is_select && is_resource($this->last_result)) {
							// ask the MySQL to EXPLAIN the query
							$explain_resource = mysql_query('EXPLAIN EXTENDED ' . $sql);
							// if query returned a result
							// (as some queries cannot be EXPLAIN-ed like SHOW TABLE, DESCRIBE, etc)
							if ($explain_resource) { // put all the records returned by the explain query in an array
								while ($row = mysql_fetch_assoc($explain_resource)) {
									$explain[] = $row;
								}
							}

						}

					}

					// save debug information
					$this->log('successful-queries', array('query' => $sql, 'records' => $result, 'returned_rows' => $this->returned_rows,
															'explain' => (isset($explain) ? $explain : ''),
															'affected_rows' => (isset($this->affected_rows) ? $this->affected_rows : false),
															'execution_time' => $stop_timer - $start_timer, 'warning' => $warning,
															'highlight' => $highlight, 'from_cache' => $refreshed_cache,
															'transaction' => ($this->transaction_status !== 0 ? true : false)), false);
				}

				// return result resource
				return $this->last_result;

			}

			// in case of error save debug information
			if (!isset($this->fatal_error) or !$this->fatal_error) {
				$this->fatal_error = mysql_error($this->link_identifier);
				$this->log('unsuccessful-queries', array('query' => $sql, 'error' => $this->fatal_error));
			}
			return false;

		}

		// we don't have to report any error as connected() method already did or any of the previous checks
		return false;
	}

	/**
	 * Moves the internal row pointer of the MySQL result associated with the specified result identifier to the
	 * specified row number.
	 *
	 * The next call to a fetch method, like {@link fetch_assoc()} or {@link fetch_obj()}, would return that row.
	 *
	 * @param  integer     $row        The row you want to move the pointer to.
	 *                                 <i>$row</i> starts at 0.
	 *                                 <i>$row</i> should be a value in the range from 0 to {@link returned_rows}
	 *
	 * @param  mixed    $resource   (Optional) Resource to fetch.
	 *                                 <i>If not specified, the resource returned by the last run query is used.</i>
	 *
	 * @return boolean                 Returns TRUE on success or FALSE on failure.
	 */
	public function seek($row, $resource = '') {
		// if an active connection exists
		if ($this->connected()) {

			// if no resource was specified, and there was a previous call to the "query" method, assign the last resource
			if ($resource == '' && isset($this->last_result)) {
				$resource = & $this->last_result;
			}

			// check if given resource is valid
			if (is_resource($resource)) {
				// return the fetched row
				// but first, check if the resulting query returns any rows
				if (mysql_num_rows($resource) != 0){
					if (mysql_data_seek($resource, $row)) {
						return true;
					} elseif (error_reporting() != 0) { // if error reporting was not supressed with @
						$this->log('errors', array('message' => $this->language['could_not_seek']));
					} elseif (is_integer($resource) && isset($this->cached_results[$resource])) {

						// move the pointer to the start of the array
						reset($this->cached_results[$resource]);

						// if the pointer needs to be moved to the very first records then we don't need to do anything
						// as by resetting the array we already have that
						// simply return true
						if ($row == 0) {
							return true;
						} elseif ($row > 0) {

							// get the current info from the array and advance the pointer
							// we check it this way because otherwise we'll have the pointer moved one entry too far
							while (list($key, $value) = each($this->cached_results[$resource])) {
								if ($key == $row - 1) {
									return true;
								}
							}

							// save debug information
							$this->log('errors', array('message' => $this->language['could_not_seek']));
						}

					}
				}
				// if not a valid resource
			} else {
				$this->log('errors', array('message' => $this->language['not_a_valid_resource'] . '. Encountered inside "' . __FUNCTION__ . '()" method.'));
			}

		}

		// we don't have to report any error as connected() method already did or checking for valid resource failed
		return false;
	}

	/**
	 * Shorthand for simple SELECT queries.
	 *
	 * For complex queries (using UNION, JOIN, etc) use the {@link query()} method.
	 *
	 * When using this method, column names will be enclosed in grave accents " ` " (thus, allowing seamless usage of
	 * reserved words as column names) and values will be automatically escaped.
	 *
	 * <code>
	 * $db->select('column1, column2', 'table', 'criteria = ?', array($criteria));
	 * </code>
	 *
	 * @param  string  $columns        Any string representing valid column names as used in a SELECT statement.
	 * @param  string  $table          Table in which to search.
	 * @param  string  $where          (Optional) A MySQL WHERE clause (without the WHERE keyword).
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $replacements   (Optional) An array with as many items as the total parameter markers ("?", question
	 *                                 marks) in <i>$column</i>, <i>$table</i> and <i>$where</i>. Each item will be
	 *                                 automatically {@link escape()}-ed and will replace the corresponding "?".
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $limit          (Optional) A MySQL LIMIT clause (without the LIMIT keyword).
	 *                                 Default is "" (an empty string).
	 *
	 * @param  string  $order          (Optional) A MySQL ORDER BY clause (without the ORDER BY keyword).
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $cache          (Optional) Instructs the script on whether it should cache the query's results
	 *                                 or not. Can be either FALSE - meaning no caching - or an integer representing the
	 *                                 number of seconds after which the cached results are considered to be expired
	 *                                 and the query will be executed again.
	 *                                 The caching method is specified by the value of the {@link caching_method} property.
	 *                                 Default is FALSE.
	 *
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *                                 Default is FALSE.
	 *
	 * @param  boolean $calc_rows      (Optional) If query is a SELECT query, this argument is set to TRUE, and there is
	 *                                 a LIMIT applied to the query, the value of the {@link found_rows} property (after
	 *                                 the query was run) will represent the number of records that would have been
	 *                                 returned if there was no LIMIT applied to the query.
	 *
	 *                                 This is very useful for creating pagination or computing averages. Also, note
	 *                                 that this information will be available without running an extra query. Here's
	 *                                 how {@link http://dev.mysql.com/doc/refman/5.0/en/information-functions.html#function_found-rows}
	 *                                 Default is FALSE.
	 *
	 * @return mixed                   On success, returns a resource or an array (if results are taken from the cache)
	 *                                 or FALSE on error.
	 *
	 *                                 <i>If query results are taken from cache, the returned result will be a pointer to
	 *                                 the actual results of the query!</i>
	 */
	public function select($columns, $table, $where = '', $replacements = '', $limit = '', $order = '', $cache = false, $highlight = false, $calc_rows = false) {
		// run the query
		return $this->query('SELECT ' . $columns . ' FROM `' . $table . '`' .
							($where != '' ? ' WHERE ' . $where : '') .
							($order != '' ? ' ORDER BY ' . $order : '') .
							($limit != '' ? ' LIMIT ' . $limit : ''), $replacements, $cache, $highlight, $calc_rows);
	}

	/**
	 * Sets MySQL character set and collation.
	 *
	 * To ensure that data is both properly saved and retrieved from the database you should call this method first thing
	 * after connecting to the database. If this method is not called, a warning message will be displayed in the debug console.
	 *
	 * Warnings can be disabled by setting the {@link disable_warnings} property.
	 *
	 * @param  string  $charset    (Optional) The character set to be used by the database.
	 *
	 *                             Default is 'utf8'.
	 *
	 *                             For a list of possible values see:
	 *                             {@link http://dev.mysql.com/doc/refman/5.1/en/charset-charsets.html}
	 *
	 * @param  string  $collation  (Optional) The collation to be used by the database.
	 *
	 *                             Default is 'utf8_general_ci'.
	 *
	 *                             For a list of possible values see:
	 *                             {@link http://dev.mysql.com/doc/refman/5.1/en/charset-charsets.html}
	 *
	 * @return void
	 */
	public function set_charset($charset = 'utf8', $collation = 'utf8_general_ci') {
		// do not show the warning that this method has not been called
		unset($this->warnings['charset']);

		// set MySQL character set
		$this->query('SET NAMES "' . $this->escape($charset) . '" COLLATE "' . $this->escape($collation) . '"');
	}

	/**
	 * Shows the debug console, <i>if</i> {@link debug} is TRUE and the viewer's IP address is in the
	 * {@link debugger_ip} array (or <i>$debugger_ip</i> is an empty array).
	 *
	 * If the ip is in the whitelist, the output will be written to the temp file later to be picked up by the debug tracer
	 * To control whether the debug console will show or not set the {@link debug} property to true.</b>
	 *
	 * @TODO Make a destruct call to this method
	 *
	 * @param bool $query_only If set to true, will only return the code necessary to inject into an existing div that was renedered within parent page
	 * @return bool The method itself returns true upon generating the output, or false if the debugger is not available
	 */
	public function show_debug_console($query_only = false) {
		if ($this->debug and is_array($this->debugger_ip) and (empty($this->debugger_ip) or in_array($_SERVER['REMOTE_ADDR'], $this->debugger_ip)) and (!$this->rendered or $query_only)) {

			// if there are any warning messages iterate through them and add them to the debug console
			foreach (array_keys($this->warnings) as $warning) {
				$this->log('warnings', array('message' => $this->language['warning_' . $warning]));
			}

			// blocks to be shown in the debug console
			$blocks = array('errors' => array('counter' => 0, 'identifier' => 'e', 'generated' => '',),
							'successful-queries' => array('counter' => 0, 'identifier' => 'sq', 'generated' => '',),
							'unsuccessful-queries' => array('counter' => 0, 'identifier' => 'uq', 'generated' => '',),
							'warnings' => array('counter' => 0, 'identifier' => 'w', 'generated' => '',),
							'globals' => array('generated' => '',),);

			// there are no warnings
			$warnings = false;

			$manual_output = '';

			// prepare output for each block
			foreach (array_keys($blocks) as $block) {
				$output = '';
				// if there is any information for the current block
				if (isset($this->debug_info[$block])) {
					// iterate through the error messages
					foreach ($this->debug_info[$block] as $debug_info) {
						// increment the messages counter
						$counter = ++$blocks[$block]['counter'];
						$identifier = $blocks[$block]['identifier'];

						// if block is about queries
						if ($block == 'successful-queries' || $block == 'unsuccessful-queries') {
							// symbols in MySQL query
							$symbols = array('=', '>', '<', '*', '+', '-', ',', '.', '(', ')',);
							// escape special characters and prepare them to be used to regular expressions
							array_walk($symbols, create_function('&$value', '$value="/(" . quotemeta($value) . ")/";'));
							// strings in MySQL queries
							$strings = array("/\'([^\']*)\'/", "/\"([^\"]*)\"/",);
							// keywords in MySQL queries
							$keywords = array('ADD', 'ALTER', 'ANALYZE', 'BETWEEN', 'CHANGE', 'COMMIT', 'CREATE', 'DELETE',
											'DROP', 'EXPLAIN', 'FROM', 'GROUP BY', 'HAVING', 'INNER JOIN', 'INSERT INTO',
											'LEFT JOIN', 'LIMIT', 'ON DUPLICATE KEY', 'OPTIMIZE', 'ORDER BY', 'RENAME',
											'REPAIR', 'REPLACE INTO', 'RIGHT JOIN', 'ROLLBACK', 'SELECT', 'SET', 'SHOW',
											'START TRANSACTION', 'STATUS', 'TABLE', 'TABLES', 'TRUNCATE', 'UPDATE',
											'UNION', 'VALUES', 'WHERE', 'ON DELETE', 'ON UPDATE');

							// escape special characters and prepare them to be used as regular expressions
							array_walk($keywords, create_function('&$value', '$value="/(\b" . quotemeta($value) . "\b)/i";'));

							// more keywords (these are the keywords that we don't put a line break after in the debug console
							// when showing queries formatted and highlighted)
							$keywords2 = array('AGAINST', 'ALL', 'AND', 'AS', 'ASC', 'AUTO INCREMENT', 'AVG', 'BINARY',
											'BOOLEAN', 'BOTH', 'CASE', 'COLLATE', 'COUNT', 'DESC', 'DOUBLE', 'ELSE',
											'END', 'ENUM', 'FIND_IN_SET', 'IN', 'INT', 'IS', 'KEY', 'LIKE', 'MATCH',
											'MAX', 'MIN', 'MODE', 'NAMES', 'NOT', 'NULL', 'ON', 'OR', 'SQL_CALC_FOUND_ROWS',
											'SUM', 'TEXT', 'THEN', 'TO', 'VARCHAR', 'WHEN', 'XOR',);

							// escape special characters and prepare them to be used to regular expressions
							array_walk($keywords2, create_function('&$value', '$value="/(\b" . quotemeta($value) . "\b)/i";'));
							$query_strings = array();

							// if there are any strings in the query, store the offset where they start and the actual string in the $matches var
							if (preg_match_all('/(\'|\"|\`)([^\1\\\]*?(?:\\\.[^\1\\\]*?)*)\\1/', $debug_info['query'], $matches, PREG_OFFSET_CAPTURE) > 0) {
								// reverse the order in which strings will be replaced so that we replace strings starting with
								// the last one or else we scramble up the offsets...
								$matches[2] = array_reverse($matches[2], true);

								// iterate through the strings
								foreach ($matches[2] as $match) {
									// save the strings
									$query_strings['/' . md5($match[0]) . '/'] = preg_replace('/\$([0-9]*)/', '\\\$$1', $match[0]);
									// replace strings with their md5 hashed equivalent
									// (we do this because we don't have to highlight anything in strings)
									$debug_info['query'] = substr_replace($debug_info['query'], md5($match[0]), $match[1], strlen($match[0]));
								}
							}

							// highlight symbols
							$debug_info['query'] = preg_replace($symbols, htmlentities('<span class="symbol">$1</span>'), $debug_info['query']);

							// Exceptions to the formatting
							// E1: First is check if this is a 'CREATE TABLE' query, and if so, break the lines on commas
							if (stripos($debug_info['query'], 'create table') !== false){
								$debug_info['query'] = strtr($debug_info['query'], array(',' => htmlentities(',<br><span class="indent"></span>'),
																						htmlentities(')</span> ENGINE') => htmlentities(')</span> <br><span class="keyword">ENGINE</span>')));
							}

							// highlight strings
							$debug_info['query'] = preg_replace($strings, htmlentities('<span class="string">\'$1\'</span>'), $debug_info['query']);
							// highlight keywords
							$debug_info['query'] = preg_replace($keywords, htmlentities('<br><span class="keyword">$1</span><br><span class="indent"></span>'), $debug_info['query']);
							// highlight more keywords
							$debug_info['query'] = preg_replace($keywords2, htmlentities('<span class="keyword">$1</span>'), $debug_info['query']);

							// E2: Next replace any 'ON UPDATE' and 'ON DELETE' <br><span class="keyword">DELETE</span><br><span class="indent"></span>
							$constraint = array(htmlentities('<span class="keyword">ON</span> <br><span class="keyword">DELETE</span><br><span class="indent"></span>') => htmlentities('<span class="keyword">ON DELETE</span>'),
												htmlentities('<span class="keyword">ON</span> <br><span class="keyword">UPDATE</span><br><span class="indent"></span>') => htmlentities('<span class="keyword">ON UPDATE</span>'));
							$debug_info['query'] = strtr($debug_info['query'], $constraint);

							// convert strings back to their original values
							$debug_info['query'] = preg_replace(array_keys($query_strings), $query_strings, $debug_info['query']);
						}

						// If we want to get a single query only, return it now stripping it out of <table> and <tr> tags
						// Usually should be used in conjunction with JS: $('query_stats').adopt(new Element('tr', {'html': $output}));
						if ($query_only) {
							/* This causes notices caught by debug backtrace */
							//if (!isset($debug_info['query']) or !isset($debug_info['execution_time'])) return true;
							//else {
							// Are there any error messages issued by the database?
							if (isset($debug_info['error']) && trim($debug_info['error']) != '') {
								$time = isset($debug_info['execution_time'])? $this->human_readable_e_notation($debug_info['execution_time']) : '';
								$manual_output .= '<table id="query_stats" class="db-entry" border="1" cellspacing="0" cellpadding="0" style="display: block;">' .
									'<tr><td class="db-counter" valign="top">AJAX</td><td class="db-data"><div class="db-box db-error">' . $debug_info['error'] . '</div><div class="db-box">' .
									html_entity_decode($debug_info['query']) . '</div><div class="db-box db-actions"><ul><li class="db-time">' . $this->language['execution_time'] . ': ' .
									$time . ' ' . $this->language['miliseconds'] . '</li></ul><div class="clear"></div></div></td></tr></table>';
							}
							//}
						} else {
							// all blocks are enclosed in tables
							$output .= '
				<table cellspacing="0" cellpadding="0" border="1" id="query_stats" class="db-entry' .
										// apply a class for even rows
										($counter % 2 == 0 ? ' even' : '') .
										// should this query be highlighted
										(isset($debug_info['highlight']) && $debug_info['highlight'] == 1 ? ' db-highlight' : '') . '">
					<tr>
						<td class="db-counter" valign="top">' . str_pad($counter, 3, '0', STR_PAD_LEFT) . '</td>
						<td class="db-data">';

							// are there any error messages issued by the script?
							if (isset($debug_info['message']) && trim($debug_info['message']) != '') {
								$output .= '
							<div class="db-box db-error">' . $debug_info['message'] . '</div>';
							}

							// are there any error messages issued by MySQL?
							if (isset($debug_info['error']) && trim($debug_info['error']) != '') {
								$output .= '
							<div class="db-box db-error">' . $debug_info['error'] . '</div>';
							}

							// are there any warning messages issued by the script?
							if (isset($debug_info['warning']) && trim($debug_info['warning']) != '') {
								$output .= '
							<div class="db-box db-error">' . $debug_info['warning'] . '</div>';

								// set a flag so that we show in the minimized debug console that there are warnings
								$warnings = true;
							}

							// is there a query to be displayed?
							if (isset($debug_info['query'])) {
								$output .= '
							<div class="db-box' . (isset($debug_info['transaction']) && $debug_info['transaction']? ' db-transaction': '') . '">' .
								preg_replace('/^\<br\>/', '', html_entity_decode($debug_info['query'])) . '</div>
							';
							}

							// start generating the actions box
							$output .= '
							<div class="db-box db-actions">
								<ul>';

							// actions specific to successful queries
							if ($block == 'successful-queries') {
								// info about whether the query results were taken from cache or not
								if ($debug_info['from_cache'] != 'nocache') {
									$output .= '
									<li class="db-cache"><strong>' . $this->language['from_cache'] . '</strong></li>';
								}

								// info about execution time
								$output .= '
									<li class="db-time">' . $this->language['execution_time'] . ': ' . $this->human_readable_e_notation($debug_info['execution_time']) . ' ' .
								$this->language['miliseconds'] . ' (<strong>' . number_format($debug_info['execution_time'] * 100 / $this->total_execution_time, 2, '.', ',') . '</strong>%)</li>';

								// button for reviewing returned rows
								if ($debug_info['affected_rows'] === false) {
									$output .= '
									<li class="db-records"><a href="javascript:db_toggle(\'db-records-sq' . $counter . '\')">' .
									$this->language['returned_rows'] . ': <strong>' . $debug_info['returned_rows'] . '</strong></a></li>';
								} else {
									$output .= '
									<li class="db-affected">' . $this->language['affected_rows'] . ': <strong>' . $debug_info['affected_rows'] . '</strong></li>';
								}

								// if EXPLAIN is available (only for SELECT queries). button for reviewing EXPLAIN results
								if (is_array($debug_info['explain'])) {
									$output .= '
									<li class="db-explain"><a href="javascript:db_toggle(\'db-explain-sq' . $counter . '\')">' . $this->language['explain'] . '</a></li>';
								}

							}

							// if backtrace information is available
							if (isset($debug_info['backtrace'])) {
								$output .= '
									<li class="db-backtrace"><a href="javascript:db_toggle(\'db-backtrace-' . $identifier . $counter . '\')">' . $this->language['backtrace'] . '</a></li>';
							}

							// common actions (to top, close all)
							$output .= '
									<li class="db-close"><a href="javascript:db_minimize(\'\')">' . $this->language['min_all'] . '</a></li>';

							// wrap up actions bar
							$output .= '
								</ul>
								<div class="clear"></div>
							</div>
							';

							// data tables (backtrace, returned rows, explain)
							// let's see what tables do we need to display
							$tables = array();

							// if query did return records
							if (!empty($debug_info['records'])) {
								$tables[] = 'records';
							}

							// if explain is available
							if (isset($debug_info['explain']) && is_array($debug_info['explain'])) {
								$tables[] = 'explain';
							}

							// if backtrace is available
							if (isset($debug_info['backtrace'])) {
								$tables[] = 'backtrace';
							}

							// let's display data
							foreach ($tables as $table) {
								// start generating output
								$output .= '
							<div id="db-' . $table . '-' . $identifier . $counter . '" class="db-box db-' . $table . '-table">
								<table cellspacing="0" cellpadding="0" border="1">
									<tr>
										';

								// print table headers
								foreach (array_keys($debug_info[$table][0]) as $header) {
									$output .= '<th>' . $header . '</th>';
								}

								$output .= '
									</tr>
								';

								// print table rows and columns
								foreach ($debug_info[$table] as $index => $row) {
									$output .= '
									<tr class="' . (($index + 1) % 2 == 0 ? 'even' : '') . '">';
									foreach (array_values($row) as $column) {
										$output .= '
										<td valign="top">' . $column . '</td>';
									}
									$output .= '
									</tr>';
								}

								// wrap up data tables
								$output .= '
								</table>
							</div>';

							}

							// finish block
							$output .= '
						</td>
					</tr>
				</table>
			';
						}

					}

					// if anything was generated for the current block enclose generated output in a special div
					if ($counter > 0) {
						$blocks[$block]['generated'] = '<div id="db-' . $block . '">' . $output . '</div>';
					}

				} elseif ($block == 'globals') {

					// globals to show
					$globals = array('POST', 'GET', 'SESSION', 'COOKIE', 'FILES', 'SERVER');
					// start building output
					$output = '
				<div id="db-globals-submenu">
					<ul>';

					// iterate through the superglobals to show
					foreach ($globals as $global) {
						$output .= '
						<li><a href="javascript:db_toggle(\'db-globals-' . strtolower($global) . '\')">$_' . $global . '</a></li>';
					}

					// finish building the submenu
					$output .= '
					</ul>
					<div class="clear"></div>
				</div>
					';

					// iterate thought the superglobals to show
					foreach ($globals as $global) {
						// make the superglobal available
						global ${'_' . $global};
						// add to the generated output
						$output .= '
				<table cellspacing="0" cellpadding="0" border="1" id="db-globals-' . strtolower($global) . '" class="db-entry">
					<tr>
						<td class="db-counter" valign="top">001</td>
						<td class="db-data">
							<div class="db-box"><strong>$_' . $global . '</strong>
								<pre>' . htmlentities(var_export(${'_' . $global}, true)) . '</pre>
							</div>
						</td>
					</tr>
				</table>';

					}

					// enclose generated output in a special div
					$output = '
			<div id="db-globals">' . $output . "\n			</div>\n		";
					$blocks[$block]['generated'] = $output;
				}
			}

			// if there's an error, show the console
			if ($blocks['unsuccessful-queries']['counter'] > 0 || $blocks['errors']['counter'] > 0) {
				$this->minimize_console = false;
			}

			if ($query_only and isset($manual_output)) return trim($manual_output);

			// finalize output by enclosing the debug console's menu and generated blocks in a container
			$output = '
		<div id="db" style="display:' . ($this->minimize_console ? 'none' : 'block') . '">
			<ul class="db-main">';

			// are there any error messages?
			// button for reviewing errors
			if ($blocks['errors']['counter'] > 0) {
				$output .= '
				<li><a href="javascript:db_toggle(\'db-errors\')">' . $this->language['errors'] . ': <span>' . $blocks['errors']['counter'] . '</span></a></li>';
			}

			// common buttons
			$output .= '
				<li><a href="javascript:db_toggle(\'db-successful-queries\')">' . $this->language['successful_queries'] . ': <span id="db-successful-queries-counter">' . $blocks['successful-queries']['counter'] . '</span>&nbsp;(' . $this->human_readable_e_notation($this->total_execution_time) . ' ' . $this->language['miliseconds'] . ')</a></li>
				<li><a href="javascript:db_toggle(\'db-unsuccessful-queries\')">' . $this->language['unsuccessful_queries'] . ': <span id="db-unsuccessful-queries-counter">' . $blocks['unsuccessful-queries']['counter'] . '</span></a></li>';

			if (isset($this->debug_info['warnings'])) {
				$output .= '
				<li><a href="javascript:db_toggle(\'db-warnings\')">' . $this->language['warnings'] . ': <span>' . count($this->warnings) . '</span></a></li>';
			}

			$output .= '
				<li><a href="javascript:db_toggle(\'db-globals-submenu\')">' . $this->language['globals'] . '</a></li>';

			// wrap up debug console's menu
			$output .= '
			</ul>
			<div class="clear"></div>
			';

			foreach (array_keys($blocks) as $block) {
				$output .= $blocks[$block]['generated'];
			}

			// wrap up
			$output .= '</div> <!-- db debug div -->';

			// add the minified version of the debug console
			$output .= '
		<div id="db-mini">
			<a href="javascript:db_toggle(\'console\')">' . $blocks['successful-queries']['counter'] . ($warnings? '<span>!</span>' : '') . ' / ' . $blocks['unsuccessful-queries']['counter'] . '</a>
		</div>
';

			// Write the output to a session which would later be picked up by a debug tracer
			//global $temp;
			//$temp->add('', $output);
			$_SESSION['pip_temp_pad'] .= $output;

			// Disallow calling this function twice
			$this->rendered = true;
			
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Starts the transaction process.
	 *
	 * Transactions work only with databases that support transaction-safe table types. In MySQL, these are InnoDB or
	 * BDB table types. Working with MyISAM tables will not raise any errors but statements will be executed
	 * automatically as soon as they are called (just like if there was no transaction).
	 *
	 * <code>
	 * // start transactions
	 * $db->transaction_start();
	 *
	 * // run queries
	 *
	 * // if all the queries since "transaction_start" are valid, write data to database;
	 * // if any of the queries had an error, ignore all queries and treat them as if they never happened
	 * $db->transaction_complete();
	 * </code>
	 *
	 * @param  boolean     $test_only      (Optional) Starts the transaction system in "test mode", causing the queries
	 *                                     to be rolled back (when {@link transaction_complete()} is called ) - even if
	 *                                     all queries are valid.
	 *                                     Default is FALSE.
	 *
	 * @return boolean                     Returns TRUE on success or FALSE on error.
	 */
	public function transaction_start($test_only = false) {

		$sql = 'START TRANSACTION';

		// if a transaction is not in progress
		if ($this->transaction_status === 0) {
			// set flag so that the query method will know that a transaction is in progress
			$this->transaction_status = ($test_only ? 3 : 1);
			// try to start transaction
			$this->query($sql);
			// returns TRUE, if query was executed successfully
			if ($this->last_result) {
				return true;
			}
			return false;
		}

		// save debug information
		$this->log('unsuccessful-queries', array('query' => $sql, 'error' => $this->language['transaction_in_progress']), false);

		return false;

	}

	/**
	 * Ends a transaction which means that if all the queries since {@link transaction_start()} are valid, it writes
	 * the data to the database, but if any of the queries had an error, ignore all queries and treat them as if they
	 * never happened.
	 *
	 * <code>
	 * // start transactions
	 * $db->transaction_start();
	 *
	 * // run queries
	 *
	 * // if all the queries since "transaction_start" are valid, write data to the database;
	 * // if any of the queries had an error, ignore all queries and treat them as if they never happened
	 * $db->transaction_complete();
	 * </code>
	 *
	 * @return boolean                     Returns TRUE on success or FALSE on error.
	 */
	public function transaction_complete() {

		$sql = 'COMMIT';

		// if a transaction is in progress
		if ($this->transaction_status !== 0) {
			// if this was a test transaction or there was an error with one of the queries in the transaction
			if ($this->transaction_status === 3 || $this->transaction_status === 2) {
				// rollback changes
				$this->query('ROLLBACK');
				// set flag so that the query method will know that no transaction is in progress
				$this->transaction_status = 0;
				// if it was a test transaction return TRUE or FALSE otherwise
				return ($this->transaction_status === 3 ? true : false);
			}

			// if all queries in the transaction were executed successfully and this was not a test transaction

			// commit transaction
			$this->query($sql);
			// set flag so that the query method will know that no transaction is in progress
			$this->transaction_status = 0;

			// if query was successful
			if ($this->last_result) {
				return true;
			}

			// if query was unsuccessful
			return false;

		}

		// if no transaction was in progress
		// save debug information
		$this->log('unsuccessful-queries', array('query' => $sql, 'error' => $this->language['no_transaction_in_progress']), false);

		return false;

	}

	/**
	 * Checks whether a table exists in the current database.
	 *
	 * <code>
	 * // checks whether table "users" exists
	 * table_exists('users');
	 * </code>
	 *
	 * @param  string  $table      The name of the table to check if it exists in the database.
	 *
	 * @return boolean             Returns TRUE if table given as argument exists in the database or FALSE if not.
	 *
	 */
	public function table_exists($table) {
		// check if table exists in the database
		return $this->fetch_assoc($this->query('SHOW TABLES LIKE ?', array($table))) !== false ? true : false;
	}

	/**
	 * Shorthand for truncating tables.
	 *
	 * <i>Truncating a table is quicker then deleting all rows, as stated in the MySQL documentation at
	 * {@link http://dev.mysql.com/doc/refman/4.1/en/truncate.html}. Truncating a table also resets the value of the
	 * AUTO INCREMENT column.</i>
	 *
	 * <code>
	 * $db->truncate('table');
	 * </code>
	 *
	 * @param  string  $table          Table to truncate.
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *
	 *                                 Default is FALSE.
	 *
	 * @return boolean                 Returns TRUE on success of FALSE on error.
	 */
	public function truncate($table, $highlight = false) {
		// run the query
		$this->query('TRUNCATE `' . $table . '`', '', false, $highlight);

		// returns TRUE, if query was executed successfully
		if ($this->last_result) {
			return true;
		}

		return false;
	}

	/**
	 * Shorthand for UPDATE queries.
	 *
	 * When using this method, column names will be enclosed in grave accents " ` " (thus, allowing seamless usage of
	 * reserved words as column names) and values will be automatically escaped.
	 *
	 * After an update, see {@link affected_rows} to find out how many rows were affected.
	 *
	 * <code>
	 * $db->update('table', array(
	 *         'column1'   =>  'value1',
	 *         'column2'   =>  'value2',
	 *     ), 'criteria = ?', array($criteria)
	 * );
	 * </code>
	 *
	 * @param  string  $table          Table in which to update.
	 * @param  array   $columns        An associative array where the array's keys represent the columns names and the
	 *                                 array's values represent the values to be inserted in each respective column.
	 *
	 *                                 Column names will be enclosed in grave accents " ` " (thus, allowing seamless
	 *                                 usage of reserved words as column names) and values will be automatically
	 *                                 {@link escape()}d.
	 *
	 *                                 A special value may also be used for when a column's value needs to be
	 *                                 incremented or decremented. In this case, use <i>INC(value)</i> where <i>value</i>
	 *                                 is the value to increase the column's value with. Use <i>INC(-value)</i> to decrease
	 *                                 the column's value:
	 *
	 *                                 <code>
	 *                                 $db->update('table', array('column' => 'INC(?)'), 'criteria = ?', array($value, $criteria));
	 *                                 </code>
	 *
	 *                                 ...is equivalent to
	 *
	 *                                 <code>
	 *                                 $db->query('UPDATE table SET column = colum + ? WHERE criteria = ?', array($value, $criteria));
	 *                                 </code>
	 *
	 * @param  string  $where          (Optional) A MySQL WHERE clause (without the WHERE keyword).
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @param  mixed   $replacements   (Optional) An array with as many items as the total parameter markers ("?", question
	 *                                 marks) in <i>$column</i>, <i>$table</i> and <i>$where</i>. Each item will be
	 *                                 automatically {@link escape()}-ed and will replace the corresponding "?".
	 *
	 *                                 Default is "" (an empty string).
	 *
	 * @param  boolean $highlight      (Optional) If set to TRUE, the debug console will open automatically and will
	 *                                 show the query.
	 *
	 *                                 Default is FALSE.
	 *
	 * @return boolean                 Returns TRUE on success of FALSE on error
	 */
	public function update($table, $columns, $where = '', $replacements = '', $highlight = false) {
		// if $replacements is specified but it's not an array
		if ($replacements != '' && !is_array($replacements)) {
			// save debug information
			$this->log('unsuccessful-queries', array('query' => '', 'error' => $this->language['warning_replacements_not_array']));

			return false;
		}

		// generate the SQL from the $columns array
		$cols = $this->build_sql($columns);
		// run the query
		$this->query('UPDATE `' . $table . '` SET ' . $cols . ($where != '' ? ' WHERE ' . $where : ''),
					array_merge(array_values($columns), $replacements == '' ? array() : $replacements), false, $highlight);
		
		// returns TRUE if query was executed successfully
		if ($this->last_result) {
			return true;
		}

		return false;
	}

	/**
	 * Writes debug information to a <i>db.log</i> log file at {@link log_path} <i>if</i> {@link debug} is TRUE and the
	 * viewer's IP address is in the {@link debugger_ip} array (or <i>$debugger_ip</i> is an empty array).
	 *
	 * <i>This method must be called after all the queries in a script!</i>
	 * <i>Make sure you're calling it BEFORE {@link show_debug_console()} so that you can see in the debug console if
	 * writing to the log file was successful or not.</i>
	 *
	 * @return void
	 */
	public function write_log() {
		if ($this->debug && is_array($this->debugger_ip) && (empty($this->debugger_ip) || in_array($_SERVER['REMOTE_ADDR'], $this->debugger_ip))) {
			$data = '';
			// iterate through the debug information
			if (isset($this->debug_info['successful-queries']) and $this->debug_info['successful-queries']){
					foreach ($this->debug_info['successful-queries'] as $debug_info) {

					// the following regular expressions strips newlines and indenting from the MySQL string, so that
					// we have it in a single line
					$pattern = array("/\s*(.*)\n|\r/", "/\n|\r/");
					$replace = array(' $1', ' ');

					// write to log file
					$data .= print_r('###################' . "\n" . '# DATE:           #: ' . date('Y M d H:i:s') . "\n" . '# QUERY:          #: ' . trim(preg_replace($pattern, $replace, $debug_info['query'])) . "\n" .
									// if execution time is available
									// (is not available for unsuccessful queries)
									(isset($debug_info['execution_time']) ? '# ' . strtoupper($this->language['execution_time']) . ': #: ' . $this->human_readable_e_notation($debug_info['execution_time']) . ' ' . $this->language['miliseconds'] . "\n" : '') .
									// if there is a warning message
									(isset($debug_info['warning']) && $debug_info['warning'] != '' ?
										'# WARNING:        #: ' . strip_tags($debug_info['warning']) . "\n" : '') .
									// if there is an error message
									(isset($debug_info['error']) && $debug_info['error'] != '' ?
										'# ERROR:          #: ' . $debug_info['error'] . "\n" : '') .
									// if not an action query, show whether the query was returned from the cache or was executed
									($debug_info['affected_rows'] === false ?
										'# FROM CACHE:     #: ' . (isset($debug_info['from_cache']) && $debug_info['from_cache'] === true ? 'YES' : 'NO') . "\n" : '') .
										'# BACKTRACE:      #:' . "\n", true);

					// write full backtrace info
					foreach ($debug_info['backtrace'] as $backtrace) {
						$data .= print_r('#' . "\n" . '# FILE #: ' . $backtrace['file'] . "\n" . '# LINE #: ' . $backtrace['line'] . "\n" . '# FUNCTION #: ' . $backtrace['function'] . "\n", true);
					}

					// finish writing to the log file
					$data .= '###################' . "\n\n";
				}
			}
			if ($data) {
				$filename = new dater() . '.log';
				$temp = new pip_disk('db_stats');
				$temp->save($filename, $data);
			}
		}
	}

	/**
	 * Given an associative array where the array's keys represent column names and the array's values represent the
	 * values to be associated with each respective column, this method will enclose column names in grave accents " ` "
	 * (thus, allowing seamless usage of reserved words as column names) and automatically {@link escape()} value.
	 *
	 * It will also take care of particular cases where the INC keyword is used in the values, where the INC keyword is
	 * used with a replacement market ("?", question mark) or where a value is a single question mark - which throws an
	 * error message.
	 *
	 * This method may also alter the original variable given as argument, as it is passed by reference!
	 *
	 * @param $columns
	 * @return string
	 */
	private function build_sql(&$columns) {
		$sql = '';

		// start creating the SQL string and enclose field names in `
		foreach ($columns as $column_name => $value) {
			// unescape the string to avoid double escape
			$columns[$column_name] = html_entity_decode(stripslashes($value));

			// if value is just a parameter marker ("?", question mark)
			if (trim($value) == '?') {
				$this->log('unsuccessful-queries', array('error' => sprintf($this->language['cannot_use_parameter_marker'], print_r($columns, true))));
			} elseif (preg_match('/INC\((\-{1})?(.*?)\)/i', $value, $matches) > 0) {
				// translate to SQL
				$sql .= ($sql != '' ? ', ' : '') . '`' . $column_name . '` = `' . $column_name . '` ' . ($matches[1] == '-' ? '-' : '+') . ' ?';
				// if INC() contains an actual value and not a parameter marker ("?", question mark)
				// add the actual value to the array with the replacement values
				if ($matches[2] != '?') {
					$columns[$column_name] = $matches[2];
				} else {
					// if we have a parameter marker ("?", question mark) instead of a value, it means the replacement value
					// is already in the array with the replacement values, and that we don't need it here anymore
					unset($columns[$column_name]);
				}
				// the usual way
			} else {
				$sql .= ($sql != '' ? ', ' : '') . '`' . $column_name . '` = ?';
			}
		}

		// return the built sql
		return $sql;
	}

	/**
	 * Checks if the connection to the MySQL server has been previously established by the connect() method.
	 *
	 * @return bool
	 */
	public function connected() {

		// if there's no connection to a MySQL database
		if (!$this->link_identifier) {
			// If no credentials were supplied, issue an error
			if (!$this->credentials['host'] and !$this->credentials['user'] and !$this->credentials['password']){
				$this->log('errors', array('message' => $this->language['no_credentials']));
				$this->show_debug_console();
				return false;
			}

			// tries to connect to the MySQL database
			if (!($this->link_identifier = mysql_connect($this->credentials['host'], $this->credentials['user'], $this->credentials['password'], $this->credentials['is_new']))) {
				// if connection could not be established save debug information
				$this->fatal_error = mysql_error();
				$this->log('errors', array('message' => $this->language['could_not_connect_to_database'], 'error' => $this->fatal_error));
				return false;
			}

			// if connection could be established
			// select the database
			if (!($this->database = mysql_select_db($this->credentials['database'], $this->link_identifier))) {
				// if database could not be selected save debug information
				$this->fatal_error = mysql_error($this->link_identifier);
				$this->log('errors', array('message' => $this->language['could_not_select_database'], 'error' => $this->fatal_error));
				return false;
			}
		}
		// return TRUE if there is no error
		return true;
	}

	/**
	 * PHP's microtime() will return elapsed time as something like 9.79900360107E-5 when the elapsed time is too short.
	 * This function takes care of that and returns the number in the human readable format.
	 *
	 * @param string $value The microtime
	 * @return string Returns the microtime in human-readable format
	 *
	 */
	public function human_readable_e_notation($value) {
		// use value as literal
		$value = (string)$value;

		// if the power is present in the value
		if (preg_match('/E\-([0-9]+)$/', $value, $matches) > 0) {
			// convert to human readable format
			$value = '0.' . str_repeat('0', $matches[1] - 1) . preg_replace('/\./', '', substr($value, 0, -strlen($matches[0])));
		}

		// return the value
		return number_format($value * 1000, 3);
	}

	/**
	 * Handles saving of debug information
	 *
	 * @param string $category
	 * @param string $data
	 *
	 */
	private function log($category, $data) {
		// if debugging is on
		if ($this->debug) {

			// if category is different than "warnings"
			// (warnings are generated internally)
			if ($category != 'warnings') {
				// get backtrace information
				$backtrace_data = debug_backtrace();
				// unset first entry as it refers to the call to this particular method
				unset($backtrace_data[0]);
				$data['backtrace'] = array();
				// iterate through the backtrace information
				foreach ($backtrace_data as $backtrace) {
					$data['backtrace'][] = array(
						$this->language['file'] => (isset($backtrace['file']) ? $backtrace['file'] : ''),
													$this->language['function'] => $backtrace['function'] . '()',
													$this->language['line'] => (isset($backtrace['line']) ? $backtrace['line'] : ''),
					);
				}
			}

			// saves debug information
			$this->debug_info[$category][] = $data;
		}
	}

	/**
	 * Destruct method to close the connection and write to log query analytics
	 * 
	 */
	public function __destruct(){
		if ($this->debug) $this->write_log();

		$this->close();
	}
}
