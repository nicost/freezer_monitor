<?php>

/**
 * Edit this file!
 * Add here the account information for the email account used to send out
 * the freezer alerts.  Any email account will do.
 */
require_once 'PHPMailerAutoload.php';

function getMailer() {
   $mail = new PHPMailer;
   $mail->isSMTP();                         // Set mailer to use SMTP
   $mail->Host = 'smtp.gmail.com';          // Specify main and backup server
   $mail->Port = 587;
   $mail->SMTPAuth = true;                  // Enable SMTP authentication
   $mail->Username = 'freezers@gmail.com';
   $mail->Password = 'absdefgSecret';
   $mail->SMTPSecure = 'tls';               // Enable encryption, 'ssl' also accepted
   $mail->From = 'freezers@gmail.com';
   $mail->FromName = 'Freezer Alert System';

   return $mail;
}

function getSysAdminEmail() {
   return "sysadmin@gmail.com";
}
