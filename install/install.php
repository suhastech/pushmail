<?php
include('../common.php');
/**
 * Installs all tables in the mysql.sql file, using the default mysql connection
 */



mysql_connect($dbhost, $dbuser, $dbpassword) or die(mysql_error());
if (mysql_errno())
{
	die(' Error '.mysql_errno().': '.mysql_error());
}
mysql_select_db($dbname) or die(mysql_error());


$sql = file_get_contents(dirname(__FILE__) . '/mysql.sql');
$ps  = explode('#--SPLIT--', $sql);

foreach ($ps as $p)
{
	$p = preg_replace('/^\s*#.*$/m', '', $p);
	
	mysql_query($p);
	if (mysql_errno())
	{
		die(' Error '.mysql_errno().': '.mysql_error());
	}
}
echo 'done!';

?>