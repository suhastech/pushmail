<?php
/*
License Agreement:

Copyright (c) 2011 Suhas Sharma, Suhas Tech

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
Usage:

Get the keys by creating an app at txtweb.com. More info: http://www.txtweb.com/user/tutorials-and-resources

Set the callback URL to wherever you have "pushmail_get_request_from_txtweb.php" file.
For eg: http://suhastech.com/pushmail/pushmail_get_request_from_txtweb.php

Any Question? Contact me at http://suhastech.com/contact

Original App @pushmail

*/
$txtwebappkey = "";
$txtwebpubkey = "";

/*
Database Constants
*/
$dbhost = "localhost";
$dbuser = "";
$dbpassword = "";
$dbname = "";

// The domain which is configured to recieve the emails. The alphaID@yourdomain.com domain.
$domainname = "suhastech.com";

// The URL where these files are hosted.
$scriptpath = "http://suhastech.com/pushmail/"; // ***With Trailing Slash***


// Zend Framework is not included in the package. Download it from framework.zend.com and set the path.
$zendpath = "class/ZendFramework-1.11.7-minimal/"; // ***With Trailing Slash***

// Your call.
error_reporting (0);

/* A function to create a unique alpha ID from 
   numbers. I have a simple autoincrement index in the database to identify users. 
   Even if I have a thousands of people using this service,
   this function will still create short forwarding email address like
   alphaID@yourdomain.com. Thanks to this logic, infinite number of people 
   can use this service. 
*/
function alphaID($in, $to_num = false, $pad_up = false, $passKey = null)
{
  $index = "abcdefghijklmnopqrstuvwxyz0123456789";
  if ($passKey !== null) {
    // Although this function's purpose is to just make the
    // ID short - and not so much secure,
 
 
    for ($n = 0; $n<strlen($index); $n++) {
      $i[] = substr( $index,$n ,1);
    }
 
    $passhash = hash('sha256',$passKey);
    $passhash = (strlen($passhash) < strlen($index))
      ? hash('sha512',$passKey)
      : $passhash;
 
    for ($n=0; $n < strlen($index); $n++) {
      $p[] =  substr($passhash, $n ,1);
    }
 
    array_multisort($p,  SORT_DESC, $i);
    $index = implode($i);
  }
 
  $base  = strlen($index);
 
  if ($to_num) {
    // Digital number  <<--  alphabet letter code
    $in  = strrev($in);
    $out = 0;
    $len = strlen($in) - 1;
    for ($t = 0; $t <= $len; $t++) {
      $bcpow = bcpow($base, $len - $t);
      $out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;
    }
 
    if (is_numeric($pad_up)) {
      $pad_up--;
      if ($pad_up > 0) {
        $out -= pow($base, $pad_up);
      }
    }
    $out = sprintf('%F', $out);
    $out = substr($out, 0, strpos($out, '.'));
  } else {
    // Digital number  -->>  alphabet letter code
    if (is_numeric($pad_up)) {
      $pad_up--;
      if ($pad_up > 0) {
        $in += pow($base, $pad_up);
      }
    }
 
    $out = "";
    for ($t = floor(log($in, $base)); $t >= 0; $t--) {
      $bcp = bcpow($base, $t);
      $a   = floor($in / $bcp) % $base;
      $out = $out . substr($index, $a, 1);
      $in  = $in - ($a * $bcp);
    }
    $out = strrev($out); // reverse
  }
 
  return $out;
}
// Functions that take care of encryption/decryption.
function mysql_aes_encrypt($val,$ky) 
{ 
    $key="\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"; 
    for($a=0;$a<strlen($ky);$a++) 
      $key[$a%16]=chr(ord($key[$a%16]) ^ ord($ky[$a])); 
    $mode=MCRYPT_MODE_ECB; 
    $enc=MCRYPT_RIJNDAEL_128; 
    $val=str_pad($val, (16*(floor(strlen($val) / 16)+(strlen($val) % 16==0?2:1))), chr(16-(strlen($val) % 16))); 
    return mcrypt_encrypt($enc, $key, $val, $mode, mcrypt_create_iv( mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM)); 
}


function mysql_aes_decrypt($val,$ky) 
{ 
    $key="\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"; 
    for($a=0;$a<strlen($ky);$a++) 
      $key[$a%16]=chr(ord($key[$a%16]) ^ ord($ky[$a])); 
    $mode = MCRYPT_MODE_ECB; 
    $enc = MCRYPT_RIJNDAEL_128; 
    $dec = @mcrypt_decrypt($enc, $key, $val, $mode, @mcrypt_create_iv( @mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM ) ); 
    return rtrim($dec,(( ord(substr($dec,strlen($dec)-1,1))>=0 and ord(substr($dec, strlen($dec)-1,1))<=16)? chr(ord( substr($dec,strlen($dec)-1,1))):null)); 
} 

