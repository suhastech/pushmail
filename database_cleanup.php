<?php
/*
  A script that'll prune all old emails from database. 
  You better setup a cron to do this regularly.
*/
include('common.php');
mysql_connect($dbhost, $dbuser, $dbpassword) or die(mysql_error());
mysql_select_db($dbname) or die(mysql_error());


$query = "SELECT * FROM db"; 
	 
$result = mysql_query($query) or die(mysql_error());
$time = time();

while($row = mysql_fetch_array($result)){
	if (($time - $row['time']) > 518400) // The time difference in seconds
	{
	mysql_query("DELETE FROM db WHERE id='".$row['id']."'") ;
	echo "Deleted ".$row['id']."<br />";
	}
	
}