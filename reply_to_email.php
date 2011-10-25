<?php 
include ('common.php');
?>
<html>
            <head>
            <meta name="txtweb-appkey" content="<?php echo $txtwebappkey; ?>" />
            </head>
            <body>
			<?php
			
		if(isset($_GET['id']))
{
// mysql connect		
mysql_connect($dbhost, $dbuser, $dbpassword) or die(mysql_error());

mysql_select_db($dbname) or die(mysql_error());

$id = $_GET['id'];

$result = mysql_query("SELECT * FROM db WHERE id='$id'");
// If the required email exists in the table
if (mysql_num_rows($result) > 0)
{

$row=mysql_fetch_array($result);

$mobile = $_GET['txtweb-mobile'];
// fetch the Unique identifier to decrypt
$togetui = mysql_query("SELECT * FROM users WHERE mobile='$mobile'");
$arraytable=mysql_fetch_array($togetui);

// Decrypts the email with the Unique Identifier as the key.
$email = mysql_aes_decrypt($row['body'],intval($arraytable['ui'] ));

$delivere = explode('Delivered-To:', $email);
$delivered = explode("\n", $delivere[1]);
require_once('class/rfc822_addresses.php');  
require_once('class/mime_parser.php');  
//create the email parser class  
$mime=new mime_parser_class;  
$mime->ignore_syntax_errors = 1;  
$parameters=array(  
    'Data'=>$email,  
);  
  
$mime->Decode($parameters, $decoded);  
  
  
//get the name and email of the sender  
if ($decoded[0]['ExtractedAddresses']['reply-to:'][0]['address'])
{
$fromName = $decoded[0]['ExtractedAddresses']['reply-to:'][0]['name'];  
$fromEmail = $decoded[0]['ExtractedAddresses']['reply-to:'][0]['address'];  
}
else
{
$fromName = $decoded[0]['ExtractedAddresses']['from:'][0]['name'];  
$fromEmail = $decoded[0]['ExtractedAddresses']['from:'][0]['address'];  
  }
//get the subject  
$subject = $decoded[0]['Headers']['subject:'];  
  
$removeChars = array('<','>');  
  
  
  
//get the message body  
if(substr($decoded[0]['Headers']['content-type:'],0,strlen('text/plain')) == 'text/plain' && isset($decoded[0]['Body'])){  
  
    $body = $decoded[0]['Body'];  
  
} elseif(substr($decoded[0]['Parts'][0]['Headers']['content-type:'],0,strlen('text/plain')) == 'text/plain' && isset($decoded[0]['Parts'][0]['Body'])) {  
  
    $body = $decoded[0]['Parts'][0]['Body'];  
  
} elseif(substr($decoded[0]['Parts'][0]['Parts'][0]['Headers']['content-type:'],0,strlen('text/plain')) == 'text/plain' && isset($decoded[0]['Parts'][0]['Parts'][0]['Body'])) {  
  
    $body = $decoded[0]['Parts'][0]['Parts'][0]['Body'];  
  
}  
function get_string_parameters($string) { 
$result = array(); 
if( empty($string) || !is_string($string) ) return $result; 
$string = str_replace('&amp;', '&', $string); 
$params_array = explode('&', $string); 
foreach($params_array as $value) { 
$tmp_array = explode('=', $value); 
if( count($tmp_array) != 2) continue; 
$result[$tmp_array[0]] = $tmp_array[1]; 
} 
return $result; 
} 
// Explaination of the code here: http://suhastech.com/how-to-fetch-new-line-paragraph-character-n-from-a-get-post-request/
// Lets use the query string directly from the server var and override the super-global 
$getstuff = get_string_parameters($_SERVER['QUERY_STRING']); 
$paragraphs = explode("%OA", $getstuff['reply']);
$replymessage = "";
foreach ($paragraphs as $paragraph)
{
 $replymessage = $replymessage.urldecode($paragraph)."\n";
}
if (isset($body))
{
$final = $replymessage."\n\nOn ".$decoded[0]['Headers']['date:'].", ".$fromName." <".$fromEmail."> wrote: \n>".str_replace("\n","\n>", $body);

}

else
{
$final = $replymessage;
}


foreach ($decoded[0]['ExtractedAddresses']['to:'] as $toaddress)
{
// mathc delivered to 
if (trim($delivered[0]) == $toaddress['address'])
{
$toEmail = $toaddress['address'];
$toName = $toaddress['name'];
}
}
if (isset($toEmail))
{
 
set_include_path($zendpath.'library/'.get_include_path());
require_once('Zend/Mail/Message.php');
require_once('Zend/Mail.php');
$mail = new Zend_Mail();
$mail->setBodyText($final);
$mail->setFrom($toEmail, $toName);
$mail->addTo( $fromEmail , $fromName);
$replyeh = str_split($subject, 3);
if ($replyeh[0] == "Re:")
{
$mail->setSubject($subject);
}
else
{
$mail->setSubject("Re: ".$subject);
}
$mail->send();
echo "Email sent to $fromName - $fromEmail ";
}
else {
echo "Wierd, I'm wondering how this email landed into your account. You were not in the 'to' list";
}
}
else

{
echo "Sorry, email couldn't be found. ";
}
}
else
{
echo "No ID? You sure, you're not messing with the system?";
}



			
			?>
			
</body>
</html>