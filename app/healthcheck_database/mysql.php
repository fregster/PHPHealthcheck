<?php 

/**
 * Database healthcheck class
 * Extends database wrapper for MySQL checks
 * @author paulf
 *
 */
class healthcheck_database_mysql extends healthcheck_database {
	function __construct($dsn = array()){
		$this->dsn = $dsn;
	}
	
	public function check_slave(){
		return $this->select('SHOW SLAVE STATUS');
	}
	
}