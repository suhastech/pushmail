<?php 
include ('common.php');
?>
<html>
            <head>
            <meta name="txtweb-appkey" content="<?php echo $txtwebappkey; ?>" />
            </head>
            <body>
			<?php
			function callback($buffer)
{
  // Replace the next line character "\n" with "<br />". The txtweb parses pages as HTML. So, this is needed.
  return (str_replace("\n", "<br />", $buffer));
}

ob_start("callback");
			
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
// Gives a "To reply to this email" intent.
echo "To reply to this message: <br />";
?>
<form class="txtweb-form" action="<?php echo $scriptpath; ?>reply_to_email.php?id=<?php echo $id;?>" method="GET">
your-reply-message<input name="reply" type="text" />
<input type="submit" value="Submit" />
</form>
<?php

$row=mysql_fetch_array($result);

$mobile = $_GET['txtweb-mobile'];
// fetch the Unique identifier to decrypt
$togetui = mysql_query("SELECT * FROM users WHERE mobile='$mobile'");
$arraytable=mysql_fetch_array($togetui);
// Decrypts the email with the Unique Identifier as the key.
$email = mysql_aes_decrypt($row['body'],intval($arraytable['ui'] ));

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
$fromName = $decoded[0]['ExtractedAddresses']['from:'][0]['name'];  
$fromEmail = $decoded[0]['ExtractedAddresses']['from:'][0]['address'];  
  
//get the name and email of the recipient  
$toEmail = $decoded[0]['ExtractedAddresses']['to:'][0]['address'];  
$toName = $decoded[0]['ExtractedAddresses']['to:'][0]['name'];  
  
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
if (isset($body))
{
//get rid of any quoted text in the email body  
$body_array = explode("\n",$body);  
$message = "";  
foreach($body_array as $key => $value){  
  
    //remove hotmail sig  
    if($value == "_________________________________________________________________"){  
        break;  
  
    //original message quote  
    } elseif(preg_match("/^-*(.*)Original Message(.*)-*/i",$value,$matches)){  
        break;  
  
    //check for date wrote string  
    } elseif(preg_match("/^On(.*)wrote:(.*)/i",$value,$matches)) {  
        break;  
  
    //check for From Name email section  
    } elseif(preg_match("/^On(.*)$fromName(.*)/i",$value,$matches)) {  
        break;  
  
    //check for To Name email section  
    } elseif(preg_match("/^On(.*)$toName(.*)/i",$value,$matches)) {  
        break;  
  
    //check for To Email email section  
    } elseif(preg_match("/^(.*)$toEmail(.*)wrote:(.*)/i",$value,$matches)) {  
        break;  
  
    //check for From Email email section  
    } elseif(preg_match("/^(.*)$fromEmail(.*)wrote:(.*)/i",$value,$matches)) {  
        break;  
  
    //check for quoted ">" section  
    } elseif(preg_match("/^>(.*)/i",$value,$matches)){  
        break;  
  
    //check for date wrote string with dashes  
    } elseif(preg_match("/^---(.*)On(.*)wrote:(.*)/i",$value,$matches)){  
        break;  
  
    //add line to body  
    } else {  
        $message .= "$value\n";  
    }  
  
}  
  
echo "$message";  
}

else
{
echo "Sorry, no plain text part, here.";
}
}
else

{
echo "Sorry, email couldn't be found. Emails will be pruned after 24 hours from our database.";
}
}

ob_end_flush();

			
			?>
			
</body>
</html>