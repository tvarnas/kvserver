<!DOCTYPE html>
<html>
	<!-- Sample html page with embedded php data-->
	<head>
	</head>
	<body>
		This is an example of hidden php data
		<pre>
			<?php
				# This portion of the page is completely hidden
				# from all web_browsers and cURL calls
				# key=>value assoc.array data (kvdata)
				$kvdata = array(
					"offsitefiles"=>"107.13.76.102",
					"beedevel04"=>"109.168.1.124",
					"serrespi5b"=>"2.85.50.55");
				#var_dump($kvdata);
			?>
		</pre>
		The data was imbedded between these sentences.
	</body>
</html>
