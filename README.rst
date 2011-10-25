**@pushmail Version 1. Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php.**

**What is @pushmail**

It is an SMS app on the txtweb.com platform with which users can get instant email notifications via SMS. If they choose to, they can read full emails and reply to them. The user just have to setup a simple email forwarder. http://suhastech.com/mail

**The "I just want to get this working" guide:**

I have spent a few extra hours to make sure this can be setup quickly. I try not to just dump the code. So, you can find some comments explaining the mechanism.

Download the source.

1) Fill the common.php files with the constants. The comments will guide you through.

2) Setup a catch all email daemon and pipe it to the file "fetch_email_from_pipe.php". Make sure it's executable (chmod 755).

3) Install the Zend PHP framework.

4) Setup a cron that executes "database_cleanup.php" regularly. It prunes old emails from the database.

5) Install the MySQL schema by running install/install.php

6) Fork and improve the code. ;)

**Contact Information**

You can add a comment in http://suhastech.com/open-source-pushmail-sms-php/ or contact me at http://suhastech.com/contact/

**Brief Overview of \"How this works\"** \(Just for the curious ones\)

Read the txtweb documentation to get the complete picture. http://www.txtweb.com/tutorials-and-resources

In this system, user's email username/password, mobile number or other information are not accessible even to the admin.

I have two tables.

Table 1 has two column, one the "user identifier" (integer, which is really an auto incremented index) and the other with the "mobile number hash" (encrypted form of the mobile number) which is sourced from the txtweb APIs. This is later used to send push email notifications using the txtweb APIs.

When you send "@pushmail" (or whatever app handle you have registered) to the txtweb mobile number, the app generates a unique user identifier integer, converts the integer to a unique alpha ID (say xyz) and saves the mobile hash in the above mentioned table. Sends you back a text message to the user with instructions to setup a forwarder to the email address *xyz@yourdomain.com*

I have a daemon setup which catches all emails to *yourdomain.com*.

As soon as the user forwards an email to *xyz@yourdomain.com*, it catches the email, looks for the user identifier code, converted back to integer. If a match is found in table 1, the mobile hash is extracted and sends a message "Reply Z to open this email... Subject, From Address". It saves the email on another table with columns "auto incremented index" and "encrypted email body".

It generates a unique ID (auto incremented) for the email and saves the email encrypted with the user identifier integer as the key.

When user replies Z, it goes to another page with GET request, *?id=1234&txtweb-mobile=the_mobilehash*.

It looks for the table where *id=1234* (that was generated when you got the email), fetches whatever is in your encrypted email body column). It then fetches the User identifier integer using the mobile hash (the first column). Decrypts (symmetric decryption) the email using the unique identifier interger, this ONLY works if the user identifier code is right, else you will find gibberish text. So, **only the user** can open the email.

**The unsolvable problems** \(Atleast, for me\)

1) If a hacker who somehow gets all my database files and wants to open 1 of your emails (There's nothing more he can do, trust me), he could  try a simple "for loop" that tries to decrypt trying all the unique identifier integer (currently 6000). Difficult but still doable with some crunching power. So, make sure you have secured your servers. Still, the hacker has no way of telling what's who's. Not a big security hole I guess.

2) The email parsing technique. With literally thousands of RFC rules, different email clients sending in their own standards, I just couldn't write a "one code fits all" code to parse the emails. Hopefully, someone will improve this.