<!-- READ_INI.PHP -->
<pre>
	<?php

		// Get ini config file in local directory
		// Get current directory
		$dir = __DIR__;

		// Find all config files (*.ini) within current dir
		$configs = glob("$dir/*.ini");

		// Exit if no config files were found
		if(count($configs) < 1){
			echo("Unable to find any data files in local directory.\n");
			exit();
		}
		// Get first config file of possible configs
		$inifile =  $configs[0];
		echo("\nData file: $inifile\n");

		echo("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n");

		// CASE 1a: Parse data file with list of headers
		$iniobj = parse_ini_file($inifile, true);

		// Print parsed contents (array)
		print_r($iniobj);

		// Get & print value for OffsiteFiles
		$offsitefiles = $iniobj["ip_address"]["offsitefiles"];
		print_r("offsitefiles = $offsitefiles\n");

		echo("\n─────────────────────────────────────\n");

		// CASE 1b: Iterate through ini array
		foreach ($iniobj as $section => $subarray) {
			echo "\n[$section]\n";
			foreach ($subarray as $key => $value) {
				echo "    $key: $value\n";
			}
		}
		echo("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n");

		// CASE 2b: Parse data file as one big array
		$iniobj = parse_ini_file($inifile);

		// Print parsed contents (array)
		print_r($iniobj);

		// Get & print value for SerresPi5b
		$serrespi5b = $iniobj["serrespi5b"];
		print_r("serrespi5b = $serrespi5b\n");

		echo("\n─────────────────────────────────────\n\n");

		// CASE 2b: Iterate through ini array
		foreach ($iniobj as $key => $value) {
			echo "$key: $value\n";
		}
	?>
</pre>
