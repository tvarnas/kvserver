<?php

# VERSION_1_0
class MySQLDatabase {

	protected $db_conn         = null;
	protected $stmt            = null;
	protected $cred_class_name = null;
	protected $cred_class_inst = null;

	public const JSON_NUMERIC  = 'json_numeric';
	public const JSON_STRING   = 'json_string';
	public const JSON_BOOLEAN  = 'json_boolean';
	public const JSON_NULL     = 'json_null';

	// ------------------------------------------------------------------------
	public function __construct($cred_class_name='DBCredentials') {
		$this->cred_class_name = $cred_class_name;
	}

	// ------------------------------------------------------------------------
	public function __destruct() {
		$this->disconnect();
	}

	// ------------------------------------------------------------------------
	public function connect() {

		try {

			$class_file = $this->cred_class_name . '.php';
    		if (file_exists($class_file)) {
				require_once $class_file;
				$this->cred_class_inst = new $this->cred_class_name();
 			} else {
				throw new Exception('Credential file not found.');
			}

			$this->db_conn = new mysqli(
				$this->cred_class_inst::DB_HOST,
				$this->cred_class_inst::DB_USER,
				$this->cred_class_inst::DB_PASSWORD, 
				$this->cred_class_inst::DB_NAME
				);
			$this->db_conn->set_charset("utf8mb4");
			$this->db_conn->query("SET time_zone = '+00:00'"); // set time to UTC

			if ($this->db_conn->connect_error) {
    			throw new Exception($this->db_conn->connect_error);
			}

		} catch (Exception $ex) {
			error_log( "MySQLDatabase->connect(): " . $ex->getMessage());
			throw new Exception('Failed to connect to database. '. $ex->getMessage());
		}
	}

	// ------------------------------------------------------------------------
	private function convert_code_to_datatype_const($type_code) {

		switch ($type_code) {
			case MYSQLI_TYPE_TINY:
				return self::JSON_BOOLEAN;

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

			case MYSQLI_TYPE_TINY_BLOB:
			case MYSQLI_TYPE_MEDIUM_BLOB:
			case MYSQLI_TYPE_LONG_BLOB:
			case MYSQLI_TYPE_BLOB:
				return self::JSON_STRING;

			default:
				error_log("Unknown type: $type_code");
				return self::JSON_STRING;
		}
	}

	// ------------------------------------------------------------------------
	public function read($raw_stmt='') {

		try {

			if ($this->db_conn == null) {
				error_log('Not connected to a database. Must first call MySQLDatabase.connect().');
				throw new Exception('Not connected to database.');
			}

			$result_set = $this->db_conn->query($raw_stmt);

			$list_of_row_assoc_arrays = [];

			if ($result_set->num_rows > 0) {

				// Loop through each row
				while($row = $result_set->fetch_row()) {
					$row_assoc_array = [];

					$fields = $result_set->fetch_fields();
					$ctr = 0;
					foreach ($fields as $field) {
						# error_log("Column Name: " . $field->name);
						# error_log("Type Code: " . $field->type);
						# error_log($this->convert_code_to_datatype_const($field->type));
						# error_log($row[$ctr]);
						# error_log('------------------------');

						$col_datatype = $this->convert_code_to_datatype_const($field->type);
						$json_value = $row[$ctr];
						if ($row[$ctr] == 'null') {
							$json_value = null;
						} 
						if ($col_datatype == self::JSON_NUMERIC) {
							$json_value = $row[$ctr] + 0; // "Evil" type-juggling!!!
						} elseif ($col_datatype == self::JSON_BOOLEAN) {
							if ($row[$ctr] == 0) {
								$json_value = false;
							} else {
								$json_value = true;
							}
						}

						$row_assoc_array[$field->name] = $json_value;
						$ctr++;
					}

					array_push($list_of_row_assoc_arrays, $row_assoc_array);
				}

				# return json_encode($list_of_row_assoc_arrays, JSON_PRETTY_PRINT);
				return $list_of_row_assoc_arrays;

			} else {
				return array();
			}

		} catch (Exception $ex) {
			$this->disconnect();
			error_log("MySQLDatabase->read(): ". $ex->getMessage());
			throw new Exception('Failed read from database: ' . $ex->getMessage());
		}
	}

	// ------------------------------------------------------------------------
	public function write($raw_stmt='') {

		try {

			if ($this->db_conn == null) {
				error_log('Not connected to a database. Must first call MySQLDatabase.connect().');
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
