<?php

/**
 * DatabaseWrapper for easily accessing a reusing PDO procedures
 *
 * @package    LoginSkeleton
 * @author     LMH
 * @link       https://github.com/mrlewismharris/php-database-wrapper
 * @modified   2021-06-06
 */

namespace LoginSkeleton;

use \PDO;

class DatabaseWrapper
{
	//get database connection settings from the settings file in app directory
	private $dbSettings;
	private $procedure;
	private $args;
	private $errors;
  //need to make the PDO object accessible to the whole scope of this class
  private $conn;

	function __construct()
	{
		//get the dbSettings from settings file
		$this->dbSettings = $GLOBALS['settings']['settings']['pdo_settings'];
	}

	function __destruct() {}
  
  //clear all of the private variables
  public function clear() {
    $this->procedure = "";
    $this->args = "";
    $this->errors = "";
  }

	//set the procedure to take place
	public function setProcedure($procedure)
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
	public function setArguments($args)
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

	//after attaching the procedure and arguments, exec the sql procedure
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
						"mysql:host={$this->dbSettings['host']};dbname={$this->dbSettings['db_name']}", 
						$this->dbSettings['user_name'], 
						$this->dbSettings['user_password']
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
