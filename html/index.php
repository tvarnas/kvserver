<!DOCTYPE html>
<html>
	<head></head>
	<body>
		<span style="font-size: 18px; font-family: 'Fira Mono';">
			MariaDB query:'SELECT * FROM kv_pair' table
		</span>

		<?php
			require 'MySQLDatabase.php';

			# Instantiate & connect DB object
			$msql = new MySQLDatabase();
			$msql->connect();
		
			# Start formatted text section
			echo "<pre>";

			# Get list of all unique kv_groups
			$query_string  = "SELECT DISTINCT kv_group FROM kv_pair ";
			$query_string .= "ORDER BY kv_group ASC";
			$obj_query = $msql->read($query_string);

			# Add database query results into a kv_group list
			$kv_group_list = [];
			foreach ($obj_query as $entry){
				$kv_group_list[] = "$entry[kv_group]";
			}

			# Create master kv_pairs list
			$master_array = [];

			# Iterate through all kv_groups and create a
			# nested associative array for each kv_group
			foreach ($kv_group_list as $kv_group_entry){

				# Init empty associative array for kv_group entries
				$kv_group_array = [];

				# Set database query to get all entries within a given kv_group
				$query_string  = "SELECT kv_key, kv_value FROM kv_pair ";
				$query_string .= "WHERE kv_group ='$kv_group_entry' ";
				$query_string .= "ORDER BY kv_key ASC";

				# Database query of all key/value pairs for kv_group
				$obj_query = $msql->read($query_string);

				# Iterate through all entries in obj_list
				# adding them to the kv_group list array
				$kv_group_list = [];
				foreach ($obj_query as $entry) {

					# Set key/value variables
					$key   = $entry['kv_key'];
					$value = $entry['kv_value'];

					# Fill kv-group associative array
					$kv_group_list[$key] = $value;
				}
				# Add kv_group data array into master kv_entries list
				$master_array[$kv_group_entry] = $kv_group_list;
			}

			# Convert master array to formatted json string
			$master_array_json = json_encode(
				$master_array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
			echo $master_array_json; 

			# End formatted sections
			echo "</pre>";

			# Disconnect database object
			$msql->disconnect();
		?>
	</body>
</html>
