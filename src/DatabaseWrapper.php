<?php

/**
 * DatabaseWrapper for easily accessing a reusing PDO procedures
 *
 * @package    DatabaseWrapper
 * @author     LMH
 * @link       https://github.com/mrlewismharris/php-database-wrapper
 * @modified   2021-06-06
 */

namespace DatabaseWrapper;

use \PDO;

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
   */
	function __construct($host = '', $port = '', $schema = '', $username = '', $password = '')
	{
		//set database settings (you probably want to change these...)
		$this->dbSettings = [
			'host' => 'localhost',
			'port' => '3306',
			'schema' => 'test',
			'username' => 'root',
			'password' => ''
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
        'password' => $password
      ];
    }
	}

	function __destruct() {}
  
  /**
   * Test the PDO connection to the database using the settings provided
   * @return boolean If the connection was successful
   */
  public function testConnection() : boolean
  {
    //just return the connection from the instantiation of PDO
    return new PDO("mysql:host={$this->dbSettings['host']};dbname={$this->dbSettings['schema']}", $this->dbSettings['username'], $this->dbSettings['password']);
  }
  
  //clear all of the private variables for reuse
  public function clear()
  {
    $this->procedure = "";
    $this->args = "";
    $this->errors = "";
  }

	//set the procedure you want to use
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

	//set the arguments for the sql procedure
	public function setArgs(Array $args)
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

	//after procedure and arguments, execute the sql procedure
	//multiple return types, so no return type can be declared
	public function execute()
	{
		//check the procedures and arguments are set
		if ($this->procedure !== "" && $this->args !== "" && $this->errors == "")
		{
			//the variable procedure to set
			$chosenProcedure = "";
			switch ($this->procedure)
			{
				/* GET USERS */
        case "getAllUsernames":
          $chosenProcedure = "SELECT username FROM `users`";
          break;
        case "getUserByKey":
					if (count($this->args) == 1) {
            $chosenProcedure = "SELECT * FROM `users` WHERE `key`=:key";
          }
          break;
        /* DEFAULT AND EXTRA PROCEDURES */
				case "":
					$chosenProcedure = "";
					if (strlen($this->errors) > 0)
					{
						$this->errors .= ", ";
					}
					$this->errors .= "DatabaseWrapper procedure not set, empty";
					break;
				default:
					$chosenProcedure = "";
					if (strlen($this->errors) > 0)
					{
						$this->errors .= ", ";
					}
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
					//set PDO specific attributes for error mode and modes to return
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
  
  // return PDO's lastInsertId
  public function lastInsertId() {
    return $this->conn->lastInsertId();
  }
  
  //simply get all errors
  public function getErrors() {
    return $this->errors;
  }
  
}
