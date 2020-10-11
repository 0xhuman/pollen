<?php

//Connection Credentials (000webhost.com) INSERT YOUR OWN STUFF
$servername = 'localhost';
$username = '';
$password = "";   
$database = "";
//$dbport = 3306;

// Create connection
$db = new mysqli($servername, $username, $password, $database);

// Check connection
if ($db->connect_error) {
    header('Content-type: text/plain');
    //log error to file/db $e-getMessage()
    die("END An error was encountered. Please try again later");

}
//echo "Connected successfully (".$db->host_info.")";


?>
