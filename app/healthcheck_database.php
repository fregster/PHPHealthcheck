<?php 

/**
 * Database healthcheck class
 * Database wrapper for SQL checks
 * @author paulf
 *
 */
class healthcheck_database {
	//$db is the PDO database handle
	protected $db = false;
	
	//$dsn stores the database connection string
	protected $dsn = false;
	
	//$instance stores and instance of it's self to allow the self extending
	protected static $instance;
	
	//$quotes stores the array of correct quote types for the database engine
	protected $quotes = array(
				'table'	=> '`',
				'col'	=> '`',
				'terminate'	=> ';',
				'database' => '',
			);
	
	//$use_prepared_statments is a boolean (Not yet setup) for disabiling paramatised statments if required
	protected $use_prepared_statments = true;
	
	/**
	 * Database constuctor method
	 * Will return the class of the correct type dependant on the DSN host spec
	 * 
	 * @param array $dsn
	 * @return object
	 */
	function __construct($dsn = array()){
		
		//Store the DSN in object for future reference
		$this->dsn = $dsn;
		
		//If the class is not setup (To stop recursion) then set the correct type up
		if (!isset(self::$instance))
		{
			//We build the class name based on the database type in use
			$c = __CLASS__.'_'.$dsn['type'];
			$path = INCLUDE_PATH . 'app' . DIRECTORY_SEPARATOR . 'healthcheck_database' . DIRECTORY_SEPARATOR . $dsn['type'] . '.php';
			
			//Check is the class file exists and include it
			if(file_exists($path)){
				require_once $path;
			}
			
			//Check the class is actually available to us
			if(!class_exists($c))
			{
				return false;
			}
			
			//Build a new instance with the correct class type
			self::$instance = new $c($dsn);
		}
	
		//Return the instance type
		return self::$instance;
	}

	/**
	 * Internal method for setting up PDO and connecting to the DB
	 * 
	 * @return boolean on sucess or error string on failure
	 */
	protected function connect($force = false){
		//Check if the DB connection has already been setup
		if(!$this->db || $force){
			try {
				//This might be better been moved into the extended objects but for now this works well
				switch ($this->dsn['type']) {
	    			case 'sqlite':
						$this->db = new PDO('sqlite:' . $this->dsn['host']);
						break;
	        
	    			default:
						$connectionOptions = array (
							PDO::ATTR_TIMEOUT => '5',
						);
						$this->db = new PDO($this->dsn['type'] . ':host=' . $this->dsn['host'] . ';dbname=' . $this->dsn['name'], $this->dsn['user'], $this->dsn['pass'], $connectionOptions);
				}
				//Database errors should be an exception
				$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			}
			catch(PDOException $e) {
				return false;
				//Something went wrong so return a verbose error to the caller
				return $e->getMessage();
			}
		}
		
		//Success!
		return true;
	}
	
	/**
	 * Internal function to quote a data type
	 * 
	 * @param string $input
	 * @param string $type
	 * @return string|boolean
	 */
	public function quote($input, $type){
		if(isset($this->quotes[$type])){
			return $this->quotes[$type] . $input . $this->quotes[$type];
		}
		
		return false;
	}
	
	/**
	 * If the database type supports replication this method will check it's status
	 * Returns false on failure and array of data on success
	 * 
	 * @return boolean
	 */
	public function check_slave(){
		return false;
	}
	
	/**
	 * SQL Select statments should be passed through here for execution
	 * Returns array of rows on success and false on failure
	 * 
	 * @param string $sql
	 * @param string|array $params
	 * @return array|boolean
	 */
	public function select($sql, $params = array()){
		//We only connect to the DB when required so check now
		if($this->connect()){
			//Prepare the SQL statment and set the fetchmode
			$statment = $this->db->prepare($sql);
			if($statment){

				$statment->setFetchMode(PDO::FETCH_ASSOC);
		
				//Process any params that have been passed in
				if(!is_array($params)){
					$params = array($params);
				}
		
				//Bind and execute the SQL
				if($statment->execute($params)){
					return $statment->fetch();
				}
			}
		}
		//Something went wrong return fail
		return false;
	}

	/**
	 * 
	 * @param string $sql
	 * @param array $params
	 * @return boolean
	 */
	public function insert($sql, $params = array()){
		//We only connect to the DB when required so check now
		if($this->connect()){
                
			//Prepare the SQL statment
			$statment = $this->db->prepare($sql);
			if($statment){
				//Bind our insert values and run
				return $statment->execute($params);
			}
                }
		//Something went wrong return fail
		return false;
	}
}
