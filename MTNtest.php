<?php

// To Run: host code on a server, go to webpage, refresh page to re-execute script.

function celoToken() {
  $ch = curl_init('https://europe-west3-kotanimac.cloudfunctions.net/savingsacco/api/login');
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
       ));
       global $authResponse;
       $authResponse = curl_exec($ch);
       // Check for errors
       //if($authResponse === FALSE){
           //die(curl_error($ch)); }
       $authData = json_decode($authResponse, TRUE);
       global $authToken;
       $authToken = $authData['token'];
       return $authResponse;
       return $authToken;
}

function celo($url, $authToken, $postData) {
  // Define var, then map to an array $phoneNumber2 = '+254701234567'; $postData = array('phoneNumber' => $phoneNumber2 ,);
  $ch = curl_init("' . $url . '");
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
          'Authorization: bearer '.$authToken,
          'Content-Type: application/json'),
      CURLOPT_POSTFIELDS => json_encode($postData) ));
  // Send the request
  $APIresponse = curl_exec($ch);
  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch)); }
  // Decode the response
  $responseData = json_decode($APIresponse, TRUE);
  return $responseData;
}

function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function mtnUser() {
  //define vars
  $callbackURL = 'XXXXXXXXXXXXXXXXXXXXXXXXXX';                          // CHANGE ME
  $postData = array(
      'providerCallbackHost' => $callbackURL ,);

  $ch = curl_init('https://sandbox.momodeveloper.mtn.com/v1_0/apiuser');
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Host: sandbox.momodeveloper.mtn.com',
        'X-Reference-Id: XXXXXXXXXXXXXXXXXXXXXXXXXX',                   // CHANGE ME
        'Ocp-Apim-Subscription-Key: XXXXXXXXXXXXXXXXXXXXXXXXXX',        // CHANGE ME
        'Content-Type: application/json'),
      CURLOPT_POSTFIELDS => json_encode($postData) ));
  // Send the request
  $APIresponse = curl_exec($ch);
  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch)); }
  // Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);
  global $response;
  $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  return $responseData;
  return $response;
  //mtnUser();
  //echo $response."";
  //echo $responseData['message']."\n";
  //echo $responseData['code']."\n";

}

function mtnTokenCollect() {
  // API User
  $api_user = 'XXXXXXXXXXXXXXXXXXXXXXXXXX';                          // CHANGE ME
  $api_key = 'XXXXXXXXXXXXXXXXXXXXXXXXXX';                           // CHANGE ME
  global $api_user_and_key; global $basic_auth;
  $api_user_and_key  = $api_user . ':' . $api_key;
  // Basic Authorization
  $basic_auth = "Basic " . base64_encode($api_user_and_key);
  $postData = null;

  $ch = curl_init('https://sandbox.momodeveloper.mtn.com/collection/token/');
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Host: sandbox.momodeveloper.mtn.com',            
        'Authorization: '. $basic_auth,
        'Ocp-Apim-Subscription-Key: XXXXXXXXXXXXXXXXXXXXXXXXXX',    // CHANGE ME
        'Content-Type: application/json'),
      CURLOPT_POSTFIELDS => $postData ));
  // Send the request
  $APIresponse = curl_exec($ch);

  // Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);
  global $response;
  $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch)); }



  elseif ($response == 200) {
    echo "TokenC:success! ";
  }

  elseif ($response == 401) {
    echo "TokenC:error! ";
  }

  else { echo "TokenC:dang! "; }

  //print_r($responseData);
  //echo $response;

  global $bearer_token;
  $bearer_token = 'Bearer ' . $responseData['access_token'];
  //$bearer_token = 'Bearer ' . $responseData->access_token;

  return $responseData;
  return $response;
  return $bearer_token;
}

function mtnCollect($uu_id, $bearerToken, $postJSON) {

  $ch = curl_init('https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay');
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Host: sandbox.momodeveloper.mtn.com', 
        'Authorization: '. $bearerToken,
        'X-Reference-Id: ' . $uu_id,
        'X-Target-Environment: sandbox',
        'Ocp-Apim-Subscription-Key: XXXXXXXXXXXXXXXXXXXXXXXXXX',    // CHANGE ME
        'Content-Type: application/json'),
      CURLOPT_POSTFIELDS => $postJSON ));
  // Send the request
  $APIresponse = curl_exec($ch);

  //print_r($postJSON);

    // Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);
  global $response;
  $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  //print_r($APIresponse);

  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch)); }

  elseif($response == 202)
  { echo "Collect:success! "; }

  elseif($response == 400)
  { echo "Collect:badrequest!400 "; }

  elseif($response == 409)
  { echo "Collect:conflict!409 "; }

  elseif($response == 500)
  { echo "Collect:internalservererror!500 "; }

  else { echo "Collect:fudge " .$response; }
}

function mtnCheckCollect($uu_id, $bearerToken) {

  $ch = curl_init("https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay/{$uu_id}");
  //$ch = curl_init('https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay/{'.$uu_id'}';

  curl_setopt_array($ch, array(
      CURLOPT_HTTPGET => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Host: sandbox.momodeveloper.mtn.com',
        'Authorization: '. $bearerToken,
        'X-Reference-Id: '. $uu_id,
        'X-Target-Environment: sandbox',
        'Ocp-Apim-Subscription-Key: XXXXXXXXXXXXXXXXXXXXXXXXXX',                       // CHANGE ME
        'Content-Type: application/string'), //'Content-Type: application/json'),
      //CURLOPT_POSTFIELDS => $uu_id,
      ));
  // Send the request
  $APIresponse = curl_exec($ch);

    // Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);
  global $response;
  $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  //print_r($APIresponse);

  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch));
      echo "Checkcollect:die";}

  elseif($response == 200)
  { echo "Checkcollect:success! "; }//echo $responseData['status']; }

  elseif($response == 400)
  { echo "Checkcollect:badrequest!400 "; }

  elseif($response == 404)
  { echo "Checkcollect:notfound!404 "; }

  elseif($response == 500)
  { echo "Checkcollect:internalservererror!500"; }

  else { echo "Checkcollect:fudge " .$response; }
}

function mtnCollectBalance($bearerToken) {

  $ch = curl_init('https://sandbox.momodeveloper.mtn.com/collection/v1_0/account/balance');
  curl_setopt_array($ch, array(
      CURLOPT_HTTPGET => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Host: sandbox.momodeveloper.mtn.com',
        'Authorization: '. $bearerToken,
        'X-Target-Environment: sandbox',
        'Ocp-Apim-Subscription-Key: XXXXXXXXXXXXXXXXXXXXXXXXXX',                     // CHANGE ME
        'Content-Type: application/json'),
      //CURLOPT_POSTFIELDS => $postJSON
    ));

  // Send the request
  $APIresponse = curl_exec($ch);

    // Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);

  if (array_key_exists('message', $responseData)) {
    if ($responseData['message'] == "Authorization failed. Insufficient permissions.") {
      echo "Balance: Authorization failed. Insufficient permissions. ";
    }
    elseif ($responseData['message'] == "Requested resource was not found.") {
      echo "Balance: Requested resource was not found. ";
    }
    elseif ($responseData['message'] == "Access to target environment is forbidden.") {
      echo "Balance: Access to target environment is forbidden. ";
    }
    else { echo "Balance: " . $responseData['message'] . " "; }
    }


  elseif (array_key_exists('availableBalance', $responseData)) {
    echo "Balance: ";
    echo $responseData['availableBalance'];
    echo "" . $responseData['currency'] . " ";
  }

  else { echo "Balance: fudge"; }

}

function mtnTokenDisburse() {
  // API User
  $api_user = 'XXXXXXXXXXXXXXXXXXXXXXXXXX';                               // CHANGE ME
  $api_key = 'XXXXXXXXXXXXXXXXXXXXXXXXXX';                                // CHANGE ME
  global $api_user_and_key; global $basic_auth;
  $api_user_and_key  = $api_user . ':' . $api_key;
  // Basic Authorization
  $basic_auth = "Basic " . base64_encode($api_user_and_key);
  $postData = null;

  $ch = curl_init('https://sandbox.momodeveloper.mtn.com/disbursement/token/');
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Host: sandbox.momodeveloper.mtn.com',
        'Authorization: '. $basic_auth,
        'Ocp-Apim-Subscription-Key: XXXXXXXXXXXXXXXXXXXXXXXXXX',            // CHANGE ME
        'Content-Type: application/json'),
      CURLOPT_POSTFIELDS => $postData ));

  // Send the request
  $APIresponse = curl_exec($ch);

  // Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);
  global $response;
  $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch)); }

  elseif ($response == 200) {
    echo "TokenD:success! ";
  }

  elseif ($response == 401) {
    echo "TokenD:error! ";
  }

  else { echo "TokenD:dang! "; }

  //print_r($responseData);
  //echo $response;

  global $bearer_tokenD;
  //$bearer_tokenD = 'Bearer ' . $responseData['access_token'];
  $bearer_tokenD = 'Bearer ' . $responseData['access_token'];

  return $responseData;
  return $response;
  return $bearer_tokenD;
  //echo $bearer_tokenD;
}

function mtnDisburse($uu_id2, $bearerToken2, $postJSON2) {
  $ch = curl_init('https://sandbox.momodeveloper.mtn.com/disbursement/v1_0/transfer');
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Host: sandbox.momodeveloper.mtn.com',
        'Content-Type: application/json',
        'Authorization: '. $bearerToken2,
        'X-Reference-Id: '. $uu_id2,
        'X-Target-Environment: sandbox',
        'Ocp-Apim-Subscription-Key: XXXXXXXXXXXXXXXXXXXXXXXXXX'),                       // CHANGE ME
      CURLOPT_POSTFIELDS => $postJSON2 ));
  // Send the request
  $APIresponse = curl_exec($ch);

    // Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);
  global $response;
  $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch)); }

  elseif($response == 202)
  { echo "Disburse:success! "; }

  elseif($response == 400)
  { echo "Disburse:badrequest!400 "; }

  elseif($response == 409)
  { echo "Disburse:conflict!409 "; }

  elseif($response == 500)
  { echo "Disburse:internalservererror!500 "; }

  else { echo "Disburse:fudge " .$response; }

  //print_r($responseData);
  //print_r($APIresponse);
}

function mtnCheckDisburse($uu_id2, $bearerToken2) {

  $ch = curl_init("https://sandbox.momodeveloper.mtn.com/disbursement/v1_0/transfer/{$uu_id2}");

  curl_setopt_array($ch, array(
      CURLOPT_HTTPGET => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Host: sandbox.momodeveloper.mtn.com',
        'Authorization: '. $bearerToken2,
        'X-Reference-Id: '. $uu_id2,
        'X-Target-Environment: sandbox',
        'Ocp-Apim-Subscription-Key: XXXXXXXXXXXXXXXXXXXXXXXXXX',                                    // CHANGE ME
        'Content-Type: application/string'), //'Content-Type: application/json'),
      //CURLOPT_POSTFIELDS => $uu_id,
      ));
  // Send the request
  $APIresponse = curl_exec($ch);

    // Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);
  global $response;
  $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  //print_r($APIresponse);

  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch));
      echo "Checkcollect:die";}

  elseif($response == 200)
  { echo "Checkcollect:success! "; }//echo $responseData['status']; }

  elseif($response == 400)
  { echo "Checkcollect:badrequest!400 "; }

  elseif($response == 404)
  { echo "Checkcollect:notfound!404 "; }

  elseif($response == 500)
  { echo "Checkcollect:internalservererror!500"; }

  else { echo "Checkcollect:fudge " .$response; }
}

function mtnDisburseBalance($bearerToken2) {

  $ch = curl_init('https://sandbox.momodeveloper.mtn.com/disbursement/v1_0/account/balance');
  curl_setopt_array($ch, array(
      CURLOPT_HTTPGET => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Host: sandbox.momodeveloper.mtn.com',
        'Authorization: '. $bearerToken2,
        'X-Target-Environment: sandbox',
        'Ocp-Apim-Subscription-Key: XXXXXXXXXXXXXXXXXXXXXXXXXX',                    // CHANGE ME
        'Content-Type: application/json'),
    ));

  // Send the request
  $APIresponse = curl_exec($ch);

    // Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);

  if (array_key_exists('message', $responseData)) {
    if ($responseData['message'] == "Authorization failed. Insufficient permissions.") {
      echo "Balance: Authorization failed. Insufficient permissions. ";
    }
    elseif ($responseData['message'] == "Requested resource was not found.") {
      echo "Balance: Requested resource was not found. ";
    }
    elseif ($responseData['message'] == "Access to target environment is forbidden.") {
      echo "Balance: Access to target environment is forbidden. ";
    }
    else { echo "Balance: " . $responseData['message'] . " "; }
    }


  elseif (array_key_exists('availableBalance', $responseData)) {
    echo "Balance: ";
    echo $responseData['availableBalance'];
    echo "" . $responseData['currency'] . " ";
  }

  else { echo "Balance: fudge"; }

}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX


// Here we test the code

mtnTokenCollect();

  //global $uuid;
  $uuid = gen_uuid();

  // Request To Pay
  $amount = 100;
  $currency = "EUR";
  $number = "46733123453";
  $timestamp = date('Ymd_Gis');
  //$payer = json_encode(array('partyIdType' => "MSISDN",'partyId' => $number,  ));

  $REQUEST_BODY = json_encode(array(
  'amount' => $amount,
  'currency' => $currency,
  'externalId' => $timestamp,
  'payer' => array(
    'partyIdType' => "MSISDN",'partyId' => $number,),
  'payerMessage' => "Payment of K".$amount,
  'payeeNote' => "Payment of K".$amount." from ".$number,
  ));

  //$txnid = json_encode(array( 'referenceId'=> $uuid ));

  mtnCollect($uuid, $bearer_token, $REQUEST_BODY);

  mtnCheckCollect($uuid, $bearer_token);

  mtnCollectBalance($bearer_token);

mtnTokenDisburse();

  //echo $bearer_tokenD;

  $uuid2 = gen_uuid();

  $timestamp2 = date('Ymd_Gis');

  $REQUEST_BODY2 = json_encode(array(
  'amount' => $amount,
  'currency' => $currency,
  'externalId' => $timestamp2,
  'payee' => array(
    'partyIdType' => "MSISDN",'partyId' => $number,),
  'payerMessage' => "Payment of K".$amount,
  'payeeNote' => "Payment of K".$amount." from ".$number,
  ));

  mtnDisburse($uuid2, $bearer_tokenD, $REQUEST_BODY2);

  mtnCheckDisburse($uuid2, $bearer_tokenD);

  mtnDisburseBalance($bearer_tokenD);






// UUID: 16a77639-34b8-443b-bea6-82c2019f62f2
/////// API key : de6460edd32c4140bcfa041f605fe583
// providerCallbackHost": "http://pollen-ussd.000webhostapp.com/pollen-test1.php


?>
