<?php
// CRON Job script to monitor rrd databases and send emails
// See thermo_includes.php for setting threshold value, email addresses etc.
// JDeRisi 2013

include 'thermo_includes.php';

print "<pre>";


$myarray = getFreezers($datafile);
$rec_count = count($myarray);
print "Fetching last 30 minutes for $rec_count receivers.\n";


$j=0;
foreach ($myarray as $receiver => $array_of_freezers) {
   print "Receiver: $receiver\n";
   foreach($array_of_freezers as $id => $freezer_array) {
      $db_name = $freezer_array[1];
      $label   = $freezer_array[2];   
      print "$label \t : $db_name \t : ";
      $result = getTempAvg($db_name);
    
      if ($result != 999) {
         print ": \tAVG: $result \t";
         $check= checkResult($result, $threshold);
         if (!$check) {
         
             // Check for a lockfile
             $filename = $label.".lock";
             if (file_exists($filename)) {
               print " Lock file detected. No alert sent."; }
            else {
               sendAlert($result,$threshold,$db_name,$label,$graphname[$receiver],$contactfile);
               $mylock = fopen($filename,"w");
               if ($mylock == false) { 
                  print "Could not creat lock file $filename"; 
               }
               else {
                  fwrite($mylock,"Locked");
                  fclose($mylock);
               }
            }
         }
         
         print "\n";
      }        
      else { 
          print ": NO TEMPS AVAILABLE. ERROR. ";
          // Check for a lockfile
             
         $filename = $label.".lock";
             
         if (file_exists($filename)) {
            print " Lock file detected. No alert sent."; 
         }
         else {
            sendAlert($result,$threshold,$db_name,$label,$graphname[$receiver], $contactfile);
            $mylock = fopen($filename,"w");
             if ($mylock == false) { 
               print "Could not create lock file $filename"; }
             else {
                fwrite($mylock,"Locked");
                fclose($mylock);
             }
         }
         print "\n";
      }
    }
   print "\n";
}
 
// Make Graphs
print "Making graphs...\n";
$j=0;
foreach ($myarray as $receiver => $array_of_freezers) {
   $j++;
   $result = makeGraph($receiver,$array_of_freezers,$graphname,$j);
}
print "Done\n";   
 
print "</pre>";
?>
