<?php

	# Define a temporary master data array: KVDATA
	$KVDATA = array(
		"offsitefiles"=>"107.13.76.102",
		"beedevel04"=>"192.168.1.124",
		"serrespi5b"=>"2.85.50.55");

	#-------------------------------------------------------
	# Display master data array

	function showDataArray() {
		var_dump($KVDATA);
	}
	#-------------------------------------------------------
	# Get value from key within kvdata array
	# Returns a 'null' value if key is not found;
	# assumes that no key will have a null value.

	function getValueFromKey($key) {
		return $KVDATA[$key] ?? NULL;
	}
	#-------------------------------------------------------
	# Insert key/value pair in kvdata array
	# Returns true/false boolean if successful

	function addKeyValue($key, $value) {

		# Insert key/value pair
		$KVDATA[$key] = $value;

		# Confirm change was made
		if ($KVDATA[$key] == $value) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	#-------------------------------------------------------
	# Remove key/value pair within kvdata array
	# Returns true/false boolean if successful
	
	function removeKeyValue($key){

		# Delete key/value pair
		unset($KVDATA[$key]);

		# Confirm deletion was made
		if (getValueFromKey($key) == NULL) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	#-------------------------------------------------------
	# Save (i.e. write) kvdata array to json file
	# Note: file should have a .json extension

	function saveData($file){

		# Check if target json file exists
		if (file_exists($file) != TRUE) {
			echo "Unable to find requested file: $file";
			return;
		}

		# Encode and write kvdata array to file
		file_put_contents($file,
			json_encode($KVDATA, JSON_PRETTY_PRINT));
	}

	#-------------------------------------------------------
	# Load (i.e. read) kvdata from json file
	# Note: file should have a .json extension

	function loadData($file){

		# Check if target json file exists
		if (file_exists($file) != TRUE) {
			echo("Unable to find requested file: $file");
			return;
		}

		# Read and decode json file into a kvdata array
		# Note: the 'true' param is key
		$KVDATA = json_decode(file_get_contents($file), true);
	}

	#-------------------------------------------------------
?>
