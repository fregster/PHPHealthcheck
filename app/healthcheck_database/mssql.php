<?php 

/**
 * Database healthcheck class
 * Extends database wrapper for MSSQL checks
 * @author paulf
 *
 */
class healthcheck_database_mssql extends healthcheck_database {	
	function __construct($dsn = array()){
		$this->dsn = $dsn;
	}
}