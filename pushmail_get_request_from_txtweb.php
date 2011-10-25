<?php 
// Loads the Config file
include('common.php');
?>
<html>
            <head>
            <meta name="txtweb-appkey" content="<?php echo $txtwebappkey; ?>" />
            </head>
            <body>
<?php
// Get the Unique mobile hash from the user requesting. 
$mobile = $_GET['txtweb-mobile'];

mysql_connect($dbhost, $dbuser, $dbpassword) or die(mysql_error());

mysql_select_db($dbname) or die(mysql_error());

$result = mysql_query("SELECT * FROM users WHERE mobile='$mobile'");
// Checks if the mobile hash already exists in the Database
if (mysql_num_rows($result) > 0)
{
// If yes, get the old Unique identifier is used which is an integer. 
$row=mysql_fetch_array($result);
$ui = $row['ui'];
}
else
{

	$insert_new_entry = mysql_query("INSERT INTO users 
(mobile) VALUES('$mobile') ");
$ui = mysql_insert_id(); // The Autoincremented index (int) is created.
}

/* Uses a function (in common.php) to convert int to alpha ID. Ask the user to setup email forwarders to alphaID@yourdomain.com. 
   Next Step: Catch all emails to this domain and pipe to 'fetch_email_from_pipe.php'.
*/
echo "Your forwarding address is &lt;".alphaID($ui)."@".$domainname."&gt;. <br /> Check out http://suhastech.com/mail for detailed tutorial. ";


?>
</body>
</html>

