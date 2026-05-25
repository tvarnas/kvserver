<?php

require 'MySQLDatabase.php';

// ----------------------------------------------------------------------------
function f_print_table_data($mysql_db, $table) {

	$a_result_set = $mysql_db->read("SELECT * from ".$table);
	$j_result_set = json_encode($a_result_set, JSON_PRETTY_PRINT);

	echo('<pre>');
	echo("TABLE ".$table.":\n");
	$a_result_set = $mysql_db->read("SELECT * from ".$table);
	echo($j_result_set . "\n");
	# echo("\n-------------------------------\n");
	echo('</pre>');
}
// ----------------------------------------------------------------------------
function f_print_separator_msg($msg='') {

	echo('<pre>');
	echo("\n===============================\n");
	echo($msg);
	echo("\n===============================\n");
	echo('</pre>');
}
// ----------------------------------------------------------------------------
$mysql_db = new MySqlDatabase();

try {
	$mysql_db->connect();
} catch (Exception $ex) {
	error_log($ex->getMessage());
	throw new Exception('Failed to connect to database.');
}

$json_result_set = null;

try {
	$A_ID = 1;
	$KV_GROUP = 'TEMP';
	$KV_KEY = 'TEMP';

	f_print_separator_msg('Database start state...');
	f_print_table_data($mysql_db, 'kv_pair');

	f_print_separator_msg('Inserting entry into database...');
	$mysql_db->write(
		"INSERT INTO kv_pair (a_id, kv_group, kv_key, kv_value) VALUES(".$A_ID.", '".$KV_GROUP."', '".$KV_KEY."', 'This is a temp string!')");

	f_print_table_data($mysql_db, 'kv_pair');

	sleep(1);

	f_print_separator_msg('Updating entry in database...');
	$stmt ="UPDATE kv_pair SET kv_value='Temp string has been modified...' WHERE (a_id=$A_ID AND kv_group='$KV_GROUP' AND kv_key='$KV_KEY')";

	error_log($stmt);
	$mysql_db->write($stmt);

	f_print_table_data($mysql_db, 'kv_pair');

	f_print_separator_msg('Deleting entry in database...');
	$mysql_db->write("DELETE FROM kv_pair WHERE a_id=".$A_ID." AND kv_key='".$KV_KEY."'");

	f_print_table_data($mysql_db, 'kv_pair');

} catch (Exception $ex) {
	error_log($ex->getMessage());
	throw new Exception('Failed to read database.');
}

echo('<pre>');
echo($json_result_set);
echo('</pre>');
?>
