<?php
//##########################################################################
// Freezer logging and graphing with RRDTOOL
// Joseph DeRisi   2013
//##########################################################################

include 'thermo_includes.php';


// ############################ MAIN #############################

print "<pre>";

print "Reading config data...\n";


$myarray = getFreezers($datafile);
$rec_count = count($myarray);
print "Found $rec_count receivers.\n";


// ############################ GET TEMPERATURES and LOG DATA
foreach ($myarray as $receiver => $array_of_freezers) {
   print "\n";
   print "Address: $receiver\t"; 
   
   $mytemps = getData($receiver);   // gets all temps for receiver. [id]=>[temp]
   if ($mytemps != 999) {
   
      $tempcount = count($mytemps);
      print "$tempcount temperatures\n";
      // Log the data into the rrd database
         
      foreach ($mytemps as $freezer_id => $temperature) {
         $freezer_array = $array_of_freezers[$freezer_id];  // get details of individual freezer
         print "\t Updating freezer $freezer_id \t $temperature\n";        
         $db_name = $freezer_array[1];              // get the database name
         $result = logData($db_name,$temperature);
         if (!$result) { 
            print "logData did not return TRUE.\n"; 
         }
      }   
   }
   else { 
      print "No connection, skipping this receiver."; 
		$receiverLockFile = substr($receiver, 6) . ".lock";
		if (file_exists($receiverLockFile)) {
		   print " Lock file detected, no alarm send";
		}
		else {
		   sendNoReceiverAlarm( getSysadminEmail() );
			$myLock = fopen($receiverLockFile, "w");
			if ($myLock == false) {
		   	print "Could not create lock file $receiverLockFile";
			} else {
				fwrite ($myLock, "Locked");
				fclose ($myLock);
			}
      }
   }
}

// ############################  MAKE GRAPHS
print "\n";

$j=0;

foreach ($myarray as $receiver => $array_of_freezers) {
   $j++;
   $result = makeGraph($receiver,$array_of_freezers,$graphname,$j);
}


print "Done.\n";
print "</pre></html>";

exit();

?>
