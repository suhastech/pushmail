#!/usr/bin/php -q
<?php
/*
  All you need to do with this is setup a catch all email daemon to yourdomain.com and pipe it
  to this script. Make sure this is executable. "chmod -v 755 fetch_email_from_pipe.php"
*/
include ('common.php');

//Listen to incoming e-mails
$sock = fopen ("php://stdin", 'r');
$email = '';

//Read e-mail into buffer
while (!feof($sock))
{
    $email .= fread($sock, 1024);
}

// Get the For: Headers from the RAW email.

$for1 = explode ("for <", $email);
$for2 = explode (">;", $for1[1]);
$for = $for2[0];
// Just a little check
if (preg_match("/".$domainname."/i", $for))
{ 
$ui = alphaID(trim(str_replace('@'.$domainname,'',$for)), true);

mysql_connect($dbhost, $dbuser, $dbpassword) or die(mysql_error());

mysql_select_db($dbname) or die(mysql_error());

$result = mysql_query("SELECT * FROM users WHERE ui='$ui'");
// If the user exists
if (mysql_num_rows($result ) > 0)
{
// Parse the email to fetch Email 
$row=mysql_fetch_array($result);
$mobile = $row['mobile'];

require_once('class/rfc822_addresses.php');  
require_once('class/mime_parser.php');  

$mime=new mime_parser_class;  
$mime->ignore_syntax_errors = 1;  
$parameters=array(  
    'Data'=>$email,  
);  
  
$mime->Decode($parameters, $decoded);  
  
  
// Get the name and email of the sender  
if (isset($decoded[0]['ExtractedAddresses']['from:'][0]['address']))
{
$fromEmail = $decoded[0]['ExtractedAddresses']['from:'][0]['address'];  

if (isset($decoded[0]['ExtractedAddresses']['from:'][0]['name']))
{
$fromName = $decoded[0]['ExtractedAddresses']['from:'][0]['name'];  
$finalFrom = $fromName.' - '.$fromEmail;
}
else
{
$finalFrom = $fromEmail;
}
  }
  

  
// Get the subject  
$elements = imap_mime_header_decode($decoded[0]['Headers']['subject:']);
  $subject = "";
for ($i=0; $i<count($elements); $i++) {
$subject= $subject.$elements[$i]->text;
}
  
 
$time= time();


$encrypted_email = mysql_real_escape_string(mysql_aes_encrypt($email, intval($ui)));


	$newinsert = mysql_query("INSERT INTO db 
(body, time) VALUES('$encrypted_email','$time' ) ") or die(mysql_error()); 
$id = mysql_insert_id();
/*
This was just a debugging code snippet that would let me (with my Unique Identifier) fix parsing errors.
if (intval($ui) == 6670)
{
$subject = $subject."<br />ID: ".$id;
}
*/

// Sends an SMS notification with From, Subject awith the hidden ID of the email. If the user chooses, he/she can open the whole email later.
if (isset($id))
{

if (isset($subject))
{
$subject = utf8_decode ($subject);
}
else
{
$subject = "No Subject";
}
if (isset($finalFrom))
{
$from = $finalFrom;
}
else
{
$from = "No from Address";
}
$body = "<a class=\"txtweb-menu-to\" href='".$scriptpath."read_full_email_from_db.php?id=".$id."'>open this email</a><br />Subject: ".$subject."<br />From: ".$from;

$txtwebmessage = '<html><head><title>Sync Pad</title><meta name="txtweb-appkey" content="'.$txtwebappkey.'" /></head><body>'.$body.'</body></html>';

require_once "class/rest.php";

$rest = new RESTclient();

$inputs = array();
$inputs["txtweb-mobile"] = $mobile;
$inputs["txtweb-message"] = $txtwebmessage;
$inputs["txtweb-pubkey"] = $txtwebpubkey;

$url = "http://api.txtweb.com/v1/push";
$rest->createRequest("$url","POST",$inputs);
$rest->sendRequest();
$output = $rest->getResponse();
}

}

}
?>