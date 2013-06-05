<?php
/**
 * Database model::debug method::english language file
 * Original work by by Stefan Gabos <contact@stefangabos.ro>
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package i18n
 * @since 1.0
 *
 */

$this->language = array(

	'affected_rows'                         => 'affected rows',
	'backtrace'                             => 'backtrace',
	'cache_path_not_writable'               => 'Could not cache query. Make sure path exists and is writable.',
	'cannot_use_parameter_marker'           => 'Cannot use a parameter marker ("?", question mark) in <br><br><pre>%s</pre><br>Use an actual value instead as it will be automatically escaped.',
	'min_all'                               => 'minimize all',
	'could_not_connect_to_database'         => 'Could not connect to database',
	'no_credentials'                        => 'No credentials were supplied to access the database. Either issue a $db->connect() from the page you\'re trying to access
                                                or enable auto connect property in the config object: $config->autoconnect = true',
	'could_not_connect_to_memcache_server'  => 'Could not connect to the memcache server',
	'could_not_seek'                        => 'could not seek to specified row',
	'could_not_select_database'             => 'Could not select database',
	'could_not_write_to_log'                => 'Could not write to log file. Make sure the folder exists and is writable.',
	'email_subject'                         => 'Slow query on %s!',
	'email_content'                         => "The following query exceeded normal running time of %s seconds by running %s seconds: \n\n %s",
	'errors'                                => 'errors',
	'execution_time'                        => 'execution time',
	'explain'                               => 'explain',
	'data_not_an_array'                     => 'The third argument of <em>insert_bulk()</em> needs to be an array of arrays.',
	'file'                                  => 'file',
	'file_could_not_be_opened'              => 'Could not open file',
	'from_cache'                            => 'from cache',
	'function'                              => 'function',
	'globals'                               => 'globals',
	'line'                                  => 'line',
	'not_select'                            => 'Invalid query type. <em>get_data()</em> method accepts <strong>only</strong> SELECT statements!',
	'memcache_extension_not_installed'      => 'Memcache extension not found.<br><span>To use memcache as caching method, PHP version must be 4.3.3+,
	                                            be compiled with the <a href="http://pecl.php.net/package/memcache">memcached</a> extension,
	                                            and needs to be configured with <em>--with-zlib[=DIR]</em>.</span>',
	'miliseconds'                           => 'ms',
	'mysql_error'                           => 'MySQL error',
	'no_transaction_in_progress'            => 'No transaction in progress.',
	'not_a_valid_resource'                  => 'Not a valid resource (make sure you specify a resource as argument for fetch_assoc()/fetch_obj() if you are executing a query inside the loop)',
	'optimization_needed'                   => '<strong>WARNING</strong>: The first few results returned by this query are the same as returned by <strong>%s</strong> other queries!',
	'returned_rows'                         => 'returned rows',
	'successful_queries'                    => 'successful queries',
	'transaction_in_progress'               => 'Transaction could not be started as another transaction is in progress.',
	'unsuccessful_queries'                  => 'unsuccessful queries',
	'warning_charset'                       => 'No default charset and collections were set. Call set_charset() after connecting to the database.',
	'warning_replacements_not_array'        => '<em>$replacements</em> must be an arrays of values',
	'warning_replacements_wrong_number'     => 'the number of items to replace is different than the number of items in the <em>$replacements</em> array',
	'warning_wait_timeout'                  => 'The value of MySQL\'s <em>wait_timeout</em> variable is set to %s. The <em>wait_timeout</em> variable represents the time, in seconds,
                                                that MySQL will wait before killing an idle connection. After a script finishes execution, the MySQL connection is not actually terminated
                                                but it is put in an idle state and is being reused if the same user requires a database connection (a very common scenario is when users
                                                navigate through the pages of a website). The default value of <em>wait_timeout</em> is 28800 seconds, or 8 hours.
                                                If you have lots of visitors this can lead to a
                                                <em><a href="http://dev.mysql.com/doc/refman/5.5/en/too-many-connections.html" target="_blank">Too many connections</a></em> error,
                                                as eventualy there will be times when no
                                                <a href="http://dev.mysql.com/doc/refman/5.5/en/server-system-variables.html#sysvar_max_connections" target="_blank">free connections</a> will be available.
                                                The recommended value is 300 seconds (5 minutes).',
	'warnings'                              => 'warnings',
	'no_fk'                                 => 'No foreign key specified for the relationship table ',
	'invalid_fk'                            => 'Invalid foreign key specified. Field FK should be in the format "table_name.field_name" in another table you\'re trying to build relationship for',
	'invalid_data_field_class'              => 'Data supplied to the relate() method is not an instance of a <strong>db_field</strong> class!',
	'no_constraints'                        => 'No constraints found in this table!',
	'no_query_action'                       => 'No query action was specified for <em>multitable()</em> method!',
	'not_a_valid_action'                    => 'Not a valid query action was specified for <em>multitable()</em> method! At this point only SELECT, UPDATE and DELETE are supported',
	'no_join_Fields'                        => 'No fields/tables were specified for <em>multitable()</em> method (more than one table is expected to be joined)!',

);

