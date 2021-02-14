<?php

// Make Config file with credentials
// require_once ('zamtelConfig.php');

// Collections
function collections($conID, $thrdptyid, $pwd, $amt, $msisdn, $shrtcd, $convId) {

  $curl = curl_init("https://apps.zamtel.co.zm/ZampayRest/Req?ConversationId={$conID}&ThirdPartyID={$thrdptyid}&Password={$pwd}&Amount={$amt}&Msisdn={$msisdn}&Shortcode={$shrtcd}&ConversationId={$convId}");
  //$curl = curl_init("https://apps.zamtel.co.zm/ZampayRest/Req?&ThirdPartyID={$thrdptyid}&Password={$pwd}&Amount={$amt}&Msisdn={$msisdn}&Shortcode={$shrtcd}&ConversationId={$convId}");
  $APIresponse = curl_exec($curl);

  // Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);
  global $response;
  $response = curl_getinfo($curl, CURLINFO_HTTP_CODE);

  // Success
  if ($responseData['status'] == "0") {
    echo "Payment Successful!";
  }

  // Wrong Pin
  elseif ($responseData['status'] == "E8003") {
    echo $responseData['message'];
  }

  // Balance Insufficient
  elseif ($responseData['status'] == "2006") {
    echo $responseData['message'];
  }

  // System is Unable to Process Request
  elseif ($responseData['status'] == "E8027") {
    echo $responseData['message'];
  }

  // Internal Error
  elseif ($responseData['status'] == "-1") {
    echo $responseData['message'];
  }

  else {
    echo "Unknown error";
    print_r($responseData);
  }

}

function disbursements($thrdptyid, $pwd, $amt, $msisdn, $shrtcd, $id, $cred, $convId) {

  $curl = curl_init("https://apps.zamtel.co.zm/ZampayRest/B2CReq?&ThirdPartyID={$thrdptyid}&Password={$pwd}&Amount={$amt}&Msisdn={$msisdn}&Shortcode={$shrtcode}&identifier={$id}&credential={$cred}&ConversationId={$convId}");
  $APIresponse = curl_exec($curl);

  // Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);
  global $response;
  $response = curl_getinfo($curl, CURLINFO_HTTP_CODE);

  // Success
  if ($responseData['status'] == "0") {
    echo "Payment Successful!";
  }

  // Initiator Authentication Error
  elseif ($responseData['status'] == "2001") {
    echo $responseData['message'];
  }

  // Balance Insufficient
  elseif ($responseData['status'] == "2006") {
    echo $responseData['message'];
  }

  // Rule Limited
  elseif ($responseData['status'] == "2005") {
    echo $responseData['message'];
  }

  // System Unable to Process Request
  elseif ($responseData['status'] == "E8027") {
    echo $responseData['message'];
  }

  else {
    echo "Unknown error ";
    print_r($responseData);
  }

}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// Collection Credentials
$conID = "12552";
$thirdPartyID = "CllT_Caller";
$password = "eS8eeP9Td6q5nAcxvty8HCp5iTLiQM9/";
$amount = "1";
$msisdn = "260957551750";
$shortCode = "000127";
//$shortCode = "000088";
$conversationID = "xx87sxc23";

// Disbursement Credentials
$shortCodeD = "000079";
$id = "ZSICOper";
$credential = "lVkB0lMpZnljrGp47PoIKe9vI4Jaal2l";
$convIdD = "121212";


collections($conID, $thirdPartyID, $password, $amount, $msisdn, $shortCodeD, $conversationID);

//disbursements($thirdPartyID, $password, $amount, $msisdn, $shortCode, $id, $credential, $convIdD);

?>
