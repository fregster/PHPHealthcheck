<?php

require_once 'healthcheck_database.php';

/**
 * Databse healthcheck business logic class
 * @author paulf
 *
 */
class healthcheck {
	private $node = 'localhost';
	private $db = false;
	private $dsn = array();
	private $max_replication_delay = 3;
	private $config = array(
				'token' => false,
				'debug' => false,
				'file_monitor_trigger' => false,
				'file_monitor_exists' => false,
				'file_force_response' => false,
				'sql_slave' => false,
				'sql_slave_max_delay' => 3,
				'string_enabled' => 'ENABLED',
				'string_disabled' => 'DISABLED',
				'node' => null,
				'error_log' => false,
                );

	/**
	 * The constructor method
	 * Should contain the DSN and an optional set of configuration options
	 * 
	 * @param array $dsn
	 * @param array $config
	 */
	function __construct($dsn = array(), $config = array(), $host = null) {
		//Get and cache the node name to lookup in the DB
		if( isset($config['node']) ) {
			$host = $config['node'];
		}

		if($host != null) {
			$this->node = $host;
		} else {
			$this->node = $_SERVER['HTTP_HOST'];
		}
		
		//If the healthcheck app is disabled for the response to be the output of this file
		if($this->config['file_force_response'] && file_exists($this->config['file_force_response']) && is_readable($this->config['file_force_response'])){
			$this->show_error(file_get_contents($this->config['file_force_response']));
		}

		//Build the database interface object
		$this->db = new healthcheck_database($dsn);
		
		//Merge in the configuration settings
		$this->config = array_merge($this->config, $config);
		
		//Quick security check, if were configured to use a security token it's been changed from the default example
		if($this->config['token'] && $this->config['token'] == md5('randomstring')){
			$this->show_error('You need to change your token from the default value');
		}
	}
	
	/**
	 * Checks the validity of any error log file and disables it on error.
	 * 
	 * @return multitype:boolean number string NULL
	 */
	private function check_error_log(){
		if($this->config['error_log'] && (!file_exists($this->config['error_log']) || !is_writable($this->config['error_log'])) ){
			$this->config['error_log'] = false;
		}
		return $this->config['error_log'];
	}

	/**
	 * Method to turn on or off slave replication checks
	 * 
	 * @param bool $check
	 * @param int $max_delay
	 */
	public function check_slave_status($check = false, $max_delay = 3) {
		$this->config['sql_slave'] = $check;
		$this->config['sql_slave_max_delay'] = $max_delay;
	}

	/**
	 * Default verbose check method
	 * 
	 * @return string
	 */
	public function check() {
		if($this->config['token'] != false && (!isset($_GET['token']) || $_GET['token'] != $this->config['token'])){
			if($this->config['debug']){
				echo $this->config['token'];
			}
			return 'Permission denied';
		}

		//Check for the maintenance file (File must not be there for the site to be online)
		if($this->config['file_monitor_trigger'] != false && file_exists($this->config['file_monitor_trigger'])) {
			$this->send_error_header();
			return $this->config['string_disabled'];
		}

		//Check for the existance of the monitor file (File must be there for the site to be online)
		if($this->config['file_monitor_exists'] != false && !file_exists($this->config['file_monitor_exists'])) {
                        $this->send_error_header();
                        return $this->config['string_disabled'];
                }

		$sql_result = $this->db->select('SELECT * FROM '. $this->db->quote('status', 'table') .' WHERE ' . $this->db->quote('node', 'col') . '=?', $this->node);
		
		if($sql_result){
			if($sql_result['enabled']){
				$output = $this->config['string_enabled'];
			} else {
				$this->send_error_header();
				$output = $this->config['string_disabled'];
			}
		} else {
			$this->show_error($this->config['string_disabled'] . ' : Server offline or '.$this->node.' not in DB');
		}

		$output .= $this->check_slave();

		return $output;
	}

	private function send_error_header(){
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 30'); //30 seconds
	}

	/**
	 * Internal method for queriying the backend DB and processing the output
	 * Verbose response
	 * @return string
	 */
	private function check_slave() {
		if($this->config['sql_slave']) {
			
			$sql_result = $this->db->check_slave();

			if($sql_result && $sql_result['Slave_IO_Running'] == 'Yes' && $sql_result['Slave_SQL_Running'] == 'Yes'){
				$output = ' Slave online';
				if($sql_result['Seconds_Behind_Master'] < $this->max_replication_delay){
					$output .= '. Replication is up to date (' . $sql_result['Seconds_Behind_Master'] . ')';
				} else {
					$output .= '. Replication is stale (' . $sql_result['Seconds_Behind_Master'] . ')';
				}
			} else {
				$output = ' Slave offline';
				$this->send_error_header();
			}

			return $output;
		}
	}

	/**
	 * Default boolean response check
	 * 
	 * @return boolean
	 */
	public function online() {
		if($this->check() == $this->config['string_enabled']){
			return true;
		}

		return false;
	}

	/**
	 * Internal method for exit on error
	 * 
	 * @param string $error_message
	 */
	private function show_error($error_message){
		
		//Check if we should be logging to a file
		if($this->check_error_log()){
			if(!error_log($error_message, 3, $this->config['error_log'])){
				error_log($error_message); //Fail back to OS loging
			}
		}
		
		//Exit the script and print the error message
		die("<p style='color:red'> $error_message </p>");
	}
}

