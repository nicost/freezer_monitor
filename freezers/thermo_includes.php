<?php

// J.DeRisi 2013
// Minor edits by Nico Stuurman, 2015
// Copyright Regents of the University of California
//
// ##################### THERMOPROJECT INCLUDES ####################

require_once 'PHPMailerAutoload.php';
require_once 'EMailInfo.php';


// CREATE THE DATABASE
//  rrdtool create jsw-16.rrd --step 300 DS:freezer1:GAUGE:600:-100:30 RRA:AVERAGE:0.5:1:2016


// array[0] = id
// array[1] = dbname
// array[2] = legend name
// array[3] = line color
// array[4] = address


/*  DAT file
1   derisi-f1   JD-F1   FF0000   tcp://169.230.5.150
2   derisi-f2   JD-F2   B23638   tcp://169.230.5.150
3   jsw-f3   JW-F3   2B00FF   tcp://169.230.5.150
*/

/*
  If you've got the receiver on DHCP, use netcat to portscan for it:

  for i in {100..200}; do nc -v -n -z -w1 169.230.34.$i 2000-2000; done

This would scan between 100-200 at port 2000.
*/



// Title for the RRD Graphs, one per receiver.
$graphname['tcp://111.111.11.111'] = "Freezer Farm";

// Title of all the webpages shown
$title = 'Freezer temperatures';

// Time Zone

// SET THE CRITICAL TEMPERATURE THRESHOLD
// This triggers the warning emails

$threshold = -60;

// Your timezone, see: http://php.net/manual/en/timezones.php
date_default_timezone_set('America/Los_Angeles');

// These do not need to be changed unless you change the name of these files
$datafile = "freezer_config.dat";
$contactfile="freezers_contact.dat";


//##########################################################################
//##########################################################################
function getContacts($dbname,$contactfile) {

   $emaillist = array();
   $master = array();
   $freezerarray = array();
   $address = "NA";
   print "\nReading email contact file: $contactfile\n";

   $data = file_get_contents($contactfile);
   if (empty($data)) { 
      print "Fatal error. No data in $contactfile\n"; die; 
   }
   $lines = explode("\n",$data);
   $j = 0;
   foreach ($lines as $line) {
      $fields = explode("\t",$line);
      if (count($fields) == 3) {
         $freezer = $fields[0];
         $email = $fields[1];
         $name = $fields[2];

         if ($dbname == $freezer) {
            $emaillist[0] = $email;
            $emaillist[1] = $name;
            $master[]=$emaillist;
         }
      }
   }
   return($master);
}


//##########################################################################
//##########################################################################
function sendAlert($temp,$threshold,$dbname,$label,$name,$contactfile) {
   print "Sending mail: $temp, $threshold, $label, $name \t";
   $mail = getMailer();

   $master = getContacts($dbname,$contactfile);
   foreach($master as $namepair) {
      $email = $namepair[0];
      $name = $namepair[1];
      print "Emailing: $email, $name\n";
      $mail->addAddress($email,$name);
   }

   if ($temp != 999) {
       $subject = "Freezer WARNING. $name, $temp";
       $body = "Freezer Label: $label\n";
       $body .="30min Average: $temp\nAlarm Threshold: $threshold\n";
   }
   else { // temp == 999, signal that receiver did not work
       $body = "Unable to connect to freezer sensor: $label \n";
       $subject = "Freezer Connection Error: $label";
   }


   $mail->Subject = $subject;
   $mail->Body    = $body;


   if ( ( !$mail->send() ) )  {
      echo 'Message could not be sent.';
      echo 'Mailer Error: ' . $mail->ErrorInfo;
      exit;
   }

   echo 'Message has been sent';
   return;
}

function sendNoReceiverAlarm($sysadminEmail) {
   $mail = getMailer();

   $email = $sysadminEmail;
   $name = "Freezer King";
   print "Emailing: $email, $name\n";
   $mail->addAddress($email,$name);

   $body = "Unable to connect to Freezer Tranceiver\n";
   $subject = "Freezer Connection Error: $name";

   $mail->Subject = $subject;
   $mail->Body    = $body;


   if ( ( !$mail->send()))  {
      echo 'Message could not be sent.';
      echo 'Mailer Error: ' . $mail->ErrorInfo , '\n';
      exit;
   }

   //echo 'Message has been sent';
   return;
}


//##########################################################################
//##########################################################################

function getFreezers($filename) {

    $masterarray = array();
    $freezerarray = array();
    $address = "NA";

   $data = file_get_contents($filename);
   if (empty($data)) { print "Fatal error. No data in $filename\n"; die; }
    $lines = explode("\n",$data);
    $j = 0;
    //print_r($lines);
    foreach ($lines as $line) {
       $fields = explode("\t",$line);
       if (count($fields) == 5) {
           $temp = $fields[4];  // tcp address
           if ($temp != $address) {
              $freezerarray = array();
              }
           $address = $temp; $myid = $fields[0]; // freezer-id
           $freezerarray[$myid] = $fields;
           $masterarray[$address] = $freezerarray;
          $j++;
          }
       }
   return($masterarray);
}

//##########################################################################
//##########################################################################
function getData($address) {

   $logfile=FALSE;
   $mytime = date("M d Y H:i:s", time());

   $inq="";
   $temp_array = array();
   $fp=fsockopen($address,2000,$errno,$errstr,5);
   if ($fp === false) {
      return 999;
   }
   stream_set_timeout($fp,3);  // Three second time out

   if (!$fp) {
      print "Could not connect to $address\n";
      if ($logfile) {
         $myfile = fopen('logfile', 'a') or die;
         fwrite($myfile,$address."\t".$mytime."\tNo connection.\n");
         fclose($myfile);
         return 999;
      }
   }

   fwrite($fp,"ERDGALL\n");
   sleep(1);
   $in  =fread($fp,128);  $in .=fread($fp,128);  $in .=fread($fp,128);
   fclose($fp);

   print "\nRAW:\n$in\n";
   print "\nRAW-Q:\n$inq\n";
   $mesg = "";
   if ($logfile) {
      $myfile = fopen('logfile', 'a') or die;
      if (empty($in))  { 
         $mesg .=" DataEmpty "; print "Empty Q: $address\n"; 
      }
      else { $mesg .=" DataOK "; 
      }
      if (empty($inq)) { 
         $mesg .=" EmptyQ "; print "Empty Q: $address\n"; 
      }
      else { 
         $mesg .=" Q-OK "; 
      }
      fwrite($myfile,$address."\t".$mytime."\t".$mesg."\n");
      fclose($myfile);
   }
   $myarray= explode("\n",$in);

   $count = 0;
   foreach($myarray as $line) {
      $elements = explode(" ",$line);
      if (!empty($elements[3])) {
         $my_id = $elements[0];
         $my_temp = $elements[3];
         $temp_array[$my_id] = $my_temp;
         $count++;
      }
   }
   return $temp_array;
}

//##########################################################################
//##########################################################################
function logData($db_name, $mytemp) {
          $filename = $db_name.".rrd";
          $temperature = parseFloat($mytemp);
          $command = "rrdtool update $filename N:$temperature";
          $output = shell_exec($command);
          print "$command\n";
            return true;
}

//##########################################################################
//##########################################################################
function makeGraph($receiver,$array_of_freezers,$graphname,$j) {
   $title = $graphname[$receiver];
   print "Graphing Receiver: $receiver \t $title\n";
   $command_line = "";

   foreach ($array_of_freezers as $freezer_id => $freezer_details) {
         $db_name = $freezer_details[1];
         $legend  = $freezer_details[2];
         $color   = $freezer_details[3];
         $result = getTempAvg($db_name);

         if ($result == 999 ) {
            $result = "--"; }

         //Check for lockfile
         $filename = $legend.".lock";

             if (file_exists($filename)) {
                $lockfilemark="*"; }
             else { $lockfilemark=""; }



         $command_line .= " DEF:temp$freezer_id=$db_name.rrd:freezer1:AVERAGE LINE2:temp$freezer_id#$color:\"$legend($result)$lockfilemark\" ";
         }
   $begin = "rrdtool graph receiver$j.png ";
   $end   = " --disable-rrdtool-tag --upper-limit -20 --lower-limit -100 -D --rigid --font LEGEND:10:\"Arial Bold\" --font TITLE:13:\"Arial\" --title=\"$title\" -w 310 -h 275";
   $end  .= " --color BACK#000000 --color FONT#FFFFFF --color SHADEA#000000 --color SHADEB#000000 --color AXIS#FFFFFF --color CANVAS#000000 --color MGRID#FFFF33";

//    $end   = " --slope-mode --disable-rrdtool-tag --upper-limit -20 --lower-limit -100 -D --rigid --vertical-label \"Deg C\" --title=\"$title\" -w 310 ";


   $final_command = $begin.$command_line.$end;
   $output = shell_exec($final_command);
   //print "\n".$final_command."\n\n";
}

//##########################################################################
//##########################################################################
function parseFloat($ptString) {                // From PHP.net examples
            if (strlen($ptString) == 0) {
                    return false;  }
            $pString = str_replace(" ", "", $ptString);
            if (substr_count($pString, ",") > 1)
                $pString = str_replace(",", "", $pString);
            if (substr_count($pString, ".") > 1)
                $pString = str_replace(".", "", $pString);
            $pregResult = array();
            $commaset = strpos($pString,',');
            if ($commaset === false) {$commaset = -1;}
            $pointset = strpos($pString,'.');
            if ($pointset === false) {$pointset = -1;}
            $pregResultA = array();
            $pregResultB = array();
            if ($pointset < $commaset) {
                preg_match('#(([-]?[0-9]+(\.[0-9])?)+(,[0-9]+)?)#', $pString, $pregResultA); }
            preg_match('#(([-]?[0-9]+(,[0-9])?)+(\.[0-9]+)?)#', $pString, $pregResultB);
            if ((isset($pregResultA[0]) && (!isset($pregResultB[0])
                    || strstr($preResultA[0],$pregResultB[0]) == 0
                    || !$pointset))) {
                $numberString = $pregResultA[0];
                $numberString = str_replace('.','',$numberString);
                $numberString = str_replace(',','.',$numberString);  }
            elseif (isset($pregResultB[0]) && (!isset($pregResultA[0])
                    || strstr($pregResultB[0],$preResultA[0]) == 0
                    || !$commaset)) {
                $numberString = $pregResultB[0];
                $numberString = str_replace(',','',$numberString); }
            else {  return false; }
            $result = (float)$numberString;
            return $result;
}

//##########################################################################
//##########################################################################

function getTempAvg($db_name) {

   $now = time();
   $start = $now - 2100;       // 6 reads, or 30 minutes
   $end = $now - 600;          // make sure to get the last reading.
   $file = $db_name.".rrd";

   $command = "rrdtool fetch $file AVERAGE --start $start --end $end";
   $output = shell_exec($command);
   //print "Output:\n$output\n";
   $lines = explode("\n",$output);
   $mycount = count($lines);

   $sum = 0; $count =0;

   for ($j=2; $j<8; $j++) {
      $elements = explode(" ",$lines[$j]);
      $text_temp = $elements[1];
      if (is_numeric($text_temp)) {
         $my_temp = $text_temp + 0.0;
         $short_temp = sprintf("%2.1f",$my_temp);
         //print "$short_temp, ";
         $sum = $sum + $short_temp;
         $count ++;
         }
      }

    if ($count > 0) {

      $last = $short_temp;
      $avg = sprintf("%2.1f",($sum/$count));
      return($avg);
      }
   else return(999);


}

//##########################################################################
//##########################################################################
function checkResult($temp,$threshold) {

   if ($temp > $threshold) {
      print "WARNING T:$threshold ";


      return(FALSE);
   }
   else {
      return(TRUE);
      }

 }

//##########################################################################
//##########################################################################



?>
