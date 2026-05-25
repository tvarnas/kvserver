<?php

class MySQLDatabase {

	protected $db_conn   = null;
	protected $stmt      = null;
	protected $cred_file = null;

	public const JSON_NUMERIC  = 'json_numeric';
	public const JSON_STRING   = 'json_string';
	public const JSON_BOOLEAN  = 'json_boolean';
	public const JSON_NULL     = 'json_null';

	// ------------------------------------------------------------------------
	// Constructor

	public function __construct($cred_file='db_credentials.json') {

		// Set instance credential file
		$this->cred_file = $cred_file;
	}

	// ------------------------------------------------------------------------
	// Destructor

	public function __destruct() {

		// Disconnect instance
		$this->disconnect();
	}

	// ------------------------------------------------------------------------
	// Connect

	public function connect() {

		/*
		Note: Credential JSON file is in this form:
		{
			"dbname":   "...",
			"user":     "...",
			"password": "...",
			"host":     "...",
			"port":     3306
		}
		*/

		// Set try-catch block
		try {
			// Get data associative array for credentials file
			$json_creds = file_get_contents($this->cred_file);

			// Convert associative array to json format
			$json_obj = json_decode($json_creds);

			// Instantiate DB connection object (db_conn)
			$this->db_conn = new mysqli(
				$json_obj->host,
				$json_obj->user,
				$json_obj->password,
				$json_obj->dbname);

			// Set DB connection parameters: MySQL charset & UTC timezone
			$this->db_conn->set_charset("utf8mb4");
			$this->db_conn->query("SET time_zone = '+00:00'");

			// Throw exception if connection error
			if ($this->db_conn->connect_error) {
    			throw new Exception($this->db_conn->connect_error);
			}
		// Exception catch
		} catch (Exception $exc) {
			error_log( "MySQLDatabase->connect(): " . $exc->getMessage());
			throw new Exception(
				'Failed to connect to database. '. $exc->getMessage());
		}
	}

	// ------------------------------------------------------------------------
	// Convert code to datatype constant

	private function convert_code_to_datatype_const($type_code) {

		// JSON datatype conversion cases
		switch ($type_code) {

			// Boolean data types
			case MYSQLI_TYPE_TINY:
				return self::JSON_BOOLEAN;

			// Numeric data types
			case MYSQLI_TYPE_DECIMAL:
			case MYSQLI_TYPE_NEWDECIMAL:
			case MYSQLI_TYPE_FLOAT:
			case MYSQLI_TYPE_DOUBLE:
			case MYSQLI_TYPE_BIT:
			case MYSQLI_TYPE_SHORT:
			case MYSQLI_TYPE_LONG:
			case MYSQLI_TYPE_LONGLONG:
			case MYSQLI_TYPE_INT24:
			case MYSQLI_TYPE_YEAR:
			case MYSQLI_TYPE_ENUM:
				return self::JSON_NUMERIC;

			// String data types
			case MYSQLI_TYPE_TIMESTAMP:
			case MYSQLI_TYPE_DATE:
			case MYSQLI_TYPE_TIME:
			case MYSQLI_TYPE_DATETIME:
			case MYSQLI_TYPE_NEWDATE:
			case MYSQLI_TYPE_INTERVAL:
			case MYSQLI_TYPE_SET:
			case MYSQLI_TYPE_VAR_STRING:
			case MYSQLI_TYPE_STRING:
			case MYSQLI_TYPE_CHAR:
			case MYSQLI_TYPE_GEOMETRY:
				return self::JSON_STRING;

			// Blob string data types
			case MYSQLI_TYPE_TINY_BLOB:
			case MYSQLI_TYPE_MEDIUM_BLOB:
			case MYSQLI_TYPE_LONG_BLOB:
			case MYSQLI_TYPE_BLOB:
				return self::JSON_STRING;

			// Default to error
			default:
				error_log("Unknown type: $type_code");
				return self::JSON_STRING;
		}
	}

	// ------------------------------------------------------------------------
	// Database read statement

	public function read($raw_stmt='') {

		try {
			# Catch connection error
			if ($this->db_conn == null) {
				error_log('Not connected to a database. Must first call MySQLDatabase.connect().');
				throw new Exception('Not connected to database.');
			}

			// Get result from submitted database statement
			$result_set = $this->db_conn->query($raw_stmt);

			// Create list of associative arrays for each entry
			$list_of_row_assoc_arrays = [];

			// If results are not empty
			if ($result_set->num_rows > 0) {

				// Loop through each entry row
				while($row = $result_set->fetch_row()) {

					# Create empty list for data elements for each entry
					$row_assoc_array = [];

					# Get 
					$fields = $result_set->fetch_fields();

					# Set entry row counter
					$ctr = 0;

					# Iterate through each field and create key/value pair
					foreach ($fields as $field) {

						# Set data element information to error log
						# Set 'Name' key/value pair
						#error_log("Column Name: " . $field->name);
						# error_log("Type Code: " . $field->type);

						# Convert value to json data type
						#error_log($this->convert_code_to_datatype_const($field->type));
						#error_log($row[$ctr]);
						#error_log('------------------------');

						# Set column data type
						$col_datatype = $this->convert_code_to_datatype_const($field->type);
						$json_value = $row[$ctr];
						if ($row[$ctr] == 'null') {
							$json_value = null;
						} 
						if ($col_datatype == self::JSON_NUMERIC) {
							$json_value = $row[$ctr] + 0; // Evil type-juggling!!!
						} elseif ($col_datatype == self::JSON_BOOLEAN) {
							if ($row[$ctr] == 0) {
								$json_value = false;
							} else {
								$json_value = true;
							}
						}
						# Add value to associative array
						$row_assoc_array[$field->name] = $json_value;

						# Increment row counter
						$ctr++;
					}
					# Append entry to entry list
					array_push($list_of_row_assoc_arrays, $row_assoc_array);
				}

				# return json_encode($list_of_row_assoc_arrays, JSON_PRETTY_PRINT);
				return $list_of_row_assoc_arrays;

			} else {
				# Otherwise return array if no entries were found
				return array();
			}

		} catch (Exception $exc) {
			$this->disconnect();
			error_log("MySQLDatabase->read(): ". $exc->getMessage());
			throw new Exception(
				'Failed read from database: ' . $exc->getMessage());
		}
	}

	// ------------------------------------------------------------------------
	public function write($raw_stmt='') {

		try {

			if ($this->db_conn == null) {
				error_log(
					'Not connected to a database. Must first call MySQLDatabase.connect().');
				throw new Exception('Not connected to database.');
			}

			// NOTE! We aren't "preparing" anything because we're not typecastng parameters.
			// That is something for the 2.0 version of the code.
			// Is difficult to implement transparently!
			$this->stmt = $this->db_conn->prepare($raw_stmt);

			if (!$this->stmt->execute()) {
				throw new Exception('Database write failed: ' . $raw_stmt);
			}
			$this->stmt->close();
			$this->stmt = null;

		} catch (Exception $ex) {
			$this->disconnect();
			error_log("MySQLDatabase->write(): ". $ex->getMessage());
			throw new Exception('Failed to write to database: ' . $ex->getMessage());
		}
	}

	// ------------------------------------------------------------------------
	public function disconnect() {

		if ($this->stmt != null) {
			$this->stmt->close();
			$this->stmt = null;
		}

		if ($this->db_conn != null) {
			$this->db_conn->close();
			$this->db_conn = null;
		}
	}
}
?>
