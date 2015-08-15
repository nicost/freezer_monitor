<?php
// See thermo_includes.php for setting threshold value, email addresses etc.
// JDeRisi 2013
// Minor changes by Nico Stuurman, 2015
// Copyright Regents of the Univeristy of California

require_once 'thermo_includes.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
   <title><?php echo $title; ?></title>
	<meta name="generator" content="BBEdit 8.2" />
	<meta http-equiv="refresh" content="300" content="text/html; charset=utf-8" >
	<meta id="viewport" name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
	<link rel="stylesheet" href="iphone.css" />

</head>
<body>


<?php

print "<pre>";


$myarray = getFreezers($datafile);
$rec_count = count($myarray);
print "Removing Lock files.\n";

// 
$j=0;
foreach ($myarray as $receiver => $array_of_freezers) {
	print "Receiver: $receiver\n";
    foreach($array_of_freezers as $id => $freezer_array) {
    $db_name = $freezer_array[1];
    $label   = $freezer_array[2];   
    print "$label \t : $db_name \t : ";

    

			
			    // Check for a lockfile
			    
			    $filename = $label.".lock";
			    
			    if (file_exists($filename)) {
			    	unlink($filename);
			    	print "Removed $filename";
			 	   }      

          print "\n";
 	}
  print "\n";
 }
print "</pre>";



 // Make Graphs
$myarray = getFreezers($datafile);
$rec_count = count($myarray);
 print "Making graphs...";
 $j=0;
foreach ($myarray as $receiver => $array_of_freezers) {
   $j++;
   $result = makeGraph($receiver,$array_of_freezers,$graphname,$j);
	}
print "Done.\n";
print "</pre>";

?>
<center>
<a href="freezers_m.php" class="green_btn_class">Go to Freezer Graphs</a>
</center>

</body></html>
