<?php

/**
 * DatabaseWrapper for easily accessing a reusing PDO procedures
 *
 * @package    DatabaseWrapper
 * @author     LMH
 * @link       https://github.com/mrlewismharris/php-database-wrapper
 * @modified   2021-06-06
 * @version    1.0
 */

class DatabaseWrapper
{
	//global vars
  //database settings
	private $dbSettings;
  
  //the procedure to use in the PDO connection
	private $procedure;
  
  //the arguments to use with the PDO connection
	private $args;
  
  //var for returning the errors
	private $errors;
  
  //the PDO object
  private $conn;

  /**
   * DatabaseWrapper class constructor/instantiation
   * @param String $host IP address of the database
   * @param String $port Port of the database
   * @param String $schema Name of the schema within the database
   * @param String $username Database username
   * @param String $password Database password
   * @param Array $extras Any extra PDO settings you may wish to use
   */
	function __construct($host = '', $port = '', $schema = '', $username = '', $password = '', $extras = [])
	{
		//set database settings (you probably want to change these...)
		$this->dbSettings = [
			'host' => 'localhost',
			'port' => '3306',
			'schema' => 'test',
			'username' => 'root',
			'password' => '',
      //add extra PDO settings if necessary
      'extra' => []
		];
    
    //Or, get from a global variable - must be declared earlier in load order
    //$this->dbSettings = $GLOBALS['dbSettings'];
    
    //Or, if the construction args are set, use them - but all need to be set
    if ($host !== '' && $port !== '' && $schema !== '' && $username !== '' && $password !== '') {
      $this->dbSettings = [
        'host' => $host,
        'port' => $port,
        'schema' => $schema,
        'username' => $username,
        'password' => $password,
        'extra' => $extras
      ];
    }
	}

	function __destruct() {}
  
  /**
   * Test the PDO connection to the database using the settings provided
   * @return boolean If the connection was successful
   */
  public function testConnection()
  {
    //just return the connection from the instantiation of PDO
    try {
      new PDO("mysql:host={$this->dbSettings['host']};dbname={$this->dbSettings['schema']}", $this->dbSettings['username'], $this->dbSettings['password'], $this->dbSettings['extra']);
    } catch (PDOException $e) {
      if (strlen($this->errors)>0) { $this->errors .= "Database connection error - Initial connection could not be made to the server"; }
      return false;
    }
    return true;
  }
  
  /**
   * Clear all class properties
   */
  public function clear()
  {
    $this->procedure = "";
    $this->args = "";
    $this->errors = "";
  }

	/**
   * Set the procedure you want to use
   * @param String $procedure Set the procedure with the pseudo-name/title, or a custom procedure if it isn't in the execute method switch statement
   */
	public function setProcedure(String $procedure)
	{
		//check procedure is actually set and not null or empty
		if ($procedure !== "" && isset($procedure)) {
			$this->procedure = $procedure;
		} else {
			//add error to errors string on the object (adds a ", " if it's not the first)
			if (strlen($this->errors) > 0) {
				$this->errors .= ", ";
			}
			$this->errors .= "Database connection error - Invalid connection procedures";
		}
	}

  /**
   * Set the arguments for the stored procedure
   * @param Array $args Set the procedure's arguments as an associative array (e.g. ["key" => "f82ba7sMc1"] will work in "SELECT `username` FROM users WHERE `key`=:key")
   */
	public function setArguments(Array $args)
	{
		//make sure arguments aren't empty
		if ($args !== "" && isset($args))
		{
			$this->args = $args;
		} else {
			//add error to errors string on the object (adds a ", " if it's not the first)
			if (strlen($this->errors) > 0)
			{
				$this->errors .= ", ";
			}
			$this->errors .= "Database connection error - Invalid connection arguments";
		}
	}

  /**
   * After setting procedure and arguments, execute the sql procedure
   * @return String/Boolean false if failed (then read the errors), or returns the results
   */
	public function execute()
	{
		//check the procedures and arguments are set
		if ($this->procedure !== "" && $this->args !== "" && $this->errors == "")
		{
			//the variable procedure to set
			$chosenProcedure = "";
      
      //find the corresponding procedure
			switch ($this->procedure)
			{
				// Example Procedures
        // Simple SQL procedure
        case "getAllUsernames":
          $chosenProcedure = "SELECT username FROM `users`";
          break;
        // Procedure with arguments/stored variables - needs to validate the array count
        case "getUserByUsername":
					if (count($this->args) == 1) {
            $chosenProcedure = "SELECT * FROM `users` WHERE `key`=:key";
          }
          break;
        // Empty (this should never happen!)
				case "":
					$chosenProcedure = "";
					if (strlen($this->errors) > 0) { $this->errors .= ", "; }
					$this->errors .= "DatabaseWrapper procedure not set, empty";
					break;
        // Use a custom procedure
				default:
					$chosenProcedure = $this->procedure;
					if (strlen($this->errors) > 0) { $this->errors .= ", "; }
					$this->errors .= "DatabaseWrapper set procedure isn't in the switch statement";
					break;
			}
			
			//try pdo function
			if (strlen($this->errors) == 0)
			{
				try
				{
					//create conn PDO object, with database settings
					$this->conn = new PDO(
						"mysql:host={$this->dbSettings['host']};dbname={$this->dbSettings['schema']}", 
						$this->dbSettings['username'], 
						$this->dbSettings['password']
					);
					//set PDO specific attributes for error mode and result mode
					$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					//prepare PDO SQL connection with the SQL procedure
					$stmt = $this->conn->prepare($chosenProcedure);
					$stmt->execute($this->args);
          return $stmt;
				}
				catch(PDOException $e)
				{
					//User friendly output:
					if (strlen($this->errors) > 0) { $this->errors .= ", "; }
					$this->errors .= "Database connection error - This has been logged and the admin has been notified";
					return false;
				}
				$this->conn=null;
			}
			else
			{
				//if procedures aren't set
				if (strlen($this->errors) > 0) { $this->errors .= ", "; }
				$this->errors .= "Invalid DatabaseWrapper procedure or arguments";
				return false;
			}
		}
	}
  
  /**
   * Return PDO's lastInsertId
   * @return int False if failed, or return the object ID
   */
  public function lastInsertId() {
    return $this->conn->lastInsertId();
  }
  
  /**
   * Getter for errors
   * @return String A string of all the errors
   */
  public function getErrors() {
    return $this->errors;
  }
  
}
