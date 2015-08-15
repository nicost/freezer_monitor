<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Freezer Temperatures</title>
	<meta name="generator" content="BBEdit 8.2" />
	<meta http-equiv="refresh" content="300" content="text/html; charset=utf-8" >
	<meta id="viewport" name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
	<link rel="stylesheet" href="iphone.css" />

</head>
<body>

<center>
<img src="receiver1.png" alt="" /><br>

<a href="remove-locks.php" class="green_btn_class">Activate All E-mail Alerts</a><br><br>
<a href="lock-all.php" class="red_btn_class">Block All E-mail Alerts</a><br>

<?php
include 'thermo_includes.php';
$myarray = getFreezers($datafile);
$j=0;
foreach ($myarray as $receiver => $array_of_freezers) {
	print "Receiver: $receiver<br>\n";
   foreach($array_of_freezers as $id => $freezer_array) {
		$db_name = $freezer_array[1];
	 	$label   = $freezer_array[2];
		print "$label \t : $db_name \t : ";

	  // Check for a lockfile
	  $filename = $label.".lock";
			 
	  if (file_exists($filename)) {
		  print "$label locked, will not send email until cleared.<br>\n";
		} else {
		print "$label not locked, will send email on alarm.<br>\n";
		}
	}
}
?>


</center>
</body>
</html>

