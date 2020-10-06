<?php

//Connection Credentials (000webhost.com)
$servername = 'localhost';
$username = 'id14808068_eric';
$password = "izJ1zYP]Zeturo[p";    //new db izJ1zYP]Zeturo[p
$database = "id14808068_ussd";
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
