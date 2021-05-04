<?php

// line 430

require ('USSD-dbConnect.php');
require_once ('USSD-ATGateway.php');
require_once ('USSD-config.php');

// Check if user is registered   |  $userAvailable
function activeUser($phoneNumber) {
  global $db;
  $sql = "SELECT * FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%' LIMIT 1";
  $userQuery = $db->query($sql);

  global $userAvailable;
  $userAvailable = $userQuery->fetch_assoc();

  //// Check if the user is available (yes)->Serve the menu; (no)->Register the user
  //if ($userAvailable && $userAvailable['city'] != NULL && $userAvailable['username'] != NULL && $userAvailable['pin'] != NULL)

}

// Get user information  |  $userCityL  $userName
function userCity($phoneNumber) {
  global $db;
  $sql = "SELECT * FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%' LIMIT 1";
  $userQuery = $db->query($sql);
  $userAvailable = $userQuery->fetch_assoc();

  global $userCityL; global $userName; global $userPin;
  $userCityL = strtolower($userAvailable['city']);
  $userName = strtolower($userAvailable['username']);
  $userPin = $userAvailable['pin'];
}

// Initiate user session
function newSession($sessionId, $phoneNumber) {
  global $db;
  $sql = "INSERT INTO `session_levels`(`session_id`,`phoneNumber`,`level`) VALUES('" . $sessionId . "','" . $phoneNumber . "',1)";
  $resultUserLvl0 = mysqli_query($db, $sql);
}

// Update user level  |  $level
function updateLevel($newLevel, $sessionId) {
  global $db;
  $sql = "UPDATE `session_levels` SET `level`='" . $newLevel . "' where `session_id`='" . $sessionId . "'";
  $db->query($sql);
  //
  // $sql2 = "select level from session_levels where session_id ='" . $sessionId . " '";
  // $levelQuery = $db->query($sql2);
  // global $level;
  // if ($result = $levelQuery->fetch_assoc())
  // {
  //     $level = $result['level'];
  // }
}

// Get user level  |  $level
function userLevel($sessionId) {
  global $db;
  $sql = "select level from session_levels where session_id ='" . $sessionId . " '";
  $levelQuery = $db->query($sql);
  global $level;
  if ($result = $levelQuery->fetch_assoc())
  {
      $level = $result['level'];
  }
}







///////////////////  WITHDRAW   ///////////////


  // Interpret KotaniPay Escrow response
  // Retrieve user information
  // Determine telecom
  // Execute respective telecom momo withdraw (output = status and txnid)
      // log txn in db
  // Send success/failure response to KotaniPay escrow api
  // Get txn status from kotanipay escrow api
      // log txnhash in db
  // Output message to user


// Get KotaniPay Escrow Deposit Status  |  $escrowStatus

// Get User KYC Info  |  $city  |  $firstName  |  $lastName  |  $email  \  $telecom(mtn, airtel, zamtel, error)
function kycInfo($phoneNumber) {
  global $db;
  $sql = "SELECT * FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%' LIMIT 1";
  $userQuery = $db->query($sql);
  $userAvailable = $userQuery->fetch_assoc();

  global $city; global $firstName; global $lastName; global $email; global $telecom;
  $city = ucfirst(strtolower($userAvailable['city']));
  $firstName = ucfirst(strtolower($userAvailable['username']));
  $lastName = ucfirst(strtolower($userAvailable['lastName']));
  $email = strtolower($userAvailable['email']);

  $zamtel = "26095";
  $mtn = "26096";
  $airtel = "26097";

  // if zamtel
  if(strpos($phoneNumber, $zamtel) !== false){
      $telecom = "zamtel";
  }

  // if mtn
  elseif(strpos($phoneNumber, $mtn) !== false){
      $telecom = "mtn";
  }

  // if airtel
  elseif(strpos($phoneNumber, $airtel) !== false){
      $telecom = "airtel";
  }

  else{$telecom = "error"};


}

// ASDFASDFADSF
function mtnWithdraw($phoneNumber, $amount, $email) {

}

// ASDFASDFADSF
function zamtelWithdraw($phoneNumber, $amount, $email) {

}

// After MOMO Payout, log txn and push to kotanipay to release escrow
function withdrawPayout($phoneNumber, $amount, $sessionId, $telecom, $status) {
  global $db;
  $sql = "INSERT INTO `withdraws`(`session_id`,`phoneNumber`,`amount`,`telecom`,`momoStatus`) VALUES('" . $sessionId . "','" . $phoneNumber . "',,'" . $amount . "',,'" . $telecom . "',,'" . $status . "')";
  $sqlExec = mysqli_query($db, $sql);

  if ($status == "success" {
    // Send status to kotanipay escrow api
    // Get kotanipay escrow api response on status \ $finalStatus
    //if($finalStatus == )
  }

  else {

  }
}







///////////////////  KOTANIPAY   ///////////////


// Get auth token  |  $authToken  (MUST RUN THIS EACH TIME YOU RUN A KP FUNCTION)
function kpAuth($phoneNumber) {
  global $db; global $authToken;

  $chauth = curl_init('https://europe-west3-kotanimac.cloudfunctions.net/savingsacco/api/login');
  curl_setopt_array($chauth, array(
    CURLOPT_POST => TRUE,
    CURLOPT_RETURNTRANSFER => TRUE,
     ));
    $authResponse = curl_exec($chauth);
    $authData = json_decode($authResponse, TRUE);
    $authToken = $authData['token'];

}

// KYC   \  $kycStatus  (either 'success' 'active' )       ******************************************
function kpKYC($phoneNumber) {
  //function kpKYC($firstName, $lastName, $phoneNumber, $dateofbirth, $documentType, $documentNumber) {
  global $db;
  // Define KYC Variables  (REPLACE WITH INFO FROM DATABASE)
  $dateofbirth = '1999-08-18';
  $email = 'ericphotons@gmail.com';
  $phoneNumber2 = '+254701234567';
  $documentType = 'ID';
  $documentNumber = '123456789';
  $firstName = 'Eric';
  $lastName = 'Cuellar';

  // Format data to push
  $postData = array(
      'phoneNumber' => $phoneNumber2 ,
      'documentType' => $documentType ,
      'documentNumber' => $documentNumber ,
      'firstName' => $firstName ,
      'lastName' => $lastName ,
      'dateofbirth' => $dateofbirth ,
      'email' => $email ,
  );

  // PUSH KYC TO API
  $ch = curl_init('https://europe-west3-kotanimac.cloudfunctions.net/savingsacco/api/memberkyc');
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
          //'Authorization: bearer '.$authToken,
          'Content-Type: application/json'
      ),
      CURLOPT_POSTFIELDS => json_encode($postData)
  ));

  // Send the request
  $APIresponse = curl_exec($ch);

  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch));
  }
  global $responseData;
  // Decode the response
  $responseData = json_decode($APIresponse, TRUE);

  global $kycStatus;
  $kycStatus = $responseData['status'];

}

// Get user balance  |  $userBalanceCUSD  $userBalanceCELO  $userAddress // CHANGE TO ACCEPT PHONE NUMBER  ******************
function userBalance($phoneNumber) {
  global $db;
  //define vars
  $phoneNumber2 = '+254701234567';
  //$phoneNumber2 = '+26097302802';
  global $postData;
  $postData = array(
      'phoneNumber' => $phoneNumber2 ,
  );

  $ch = curl_init('https://europe-west3-kotanimac.cloudfunctions.net/savingsacco/api/getbalance');
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
          //'Authorization: bearer '.$authToken,
          'Content-Type: application/json'
      ),
      CURLOPT_POSTFIELDS => json_encode($postData)
  ));

  // Send the request
  $APIresponse = curl_exec($ch);

  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch));
  }

  // Decode the response
  $responseData = json_decode($APIresponse, TRUE);

  global $userBalanceCUSD; global $userBalanceCELO; global $userAddress;
  $userBalanceCUSD = $responseData['balance']['cusd'];
  $userBalanceCELO = $responseData['balance']['celo'];
  $userAddress = $responseData['address'];

}

// Pollen Pay  |  $payResponse['status'] == 'success', 'unverified', 'error', 'failed'
function pollenPay($sender, $receiver, $amount, $authToken) {
  global $db;
  // Define SEND VARIABLES
  //$recipient = '0720670789';
  $phoneNumber2 = '254701234567';
  //$recipient = $reverse[2];
  //$amount = $reverse[1];

  // The data to send to the API
  $postData = array(
      'phoneNumber' => $phoneNumber2 ,
      'recipient' => $recipient ,
      'amount' => $amount ,
  );

  // Setup cURL
  $ch = curl_init('https://europe-west3-kotanimac.cloudfunctions.net/savingsacco/api/sendfunds');
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
          'Authorization: bearer '.$authToken,
          'Content-Type: application/json'
      ),
      CURLOPT_POSTFIELDS => json_encode($postData)
  ));

  // Send the request
  $APIresponse = curl_exec($ch);

  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch));
  }

  // Decode the response
  global $payResponse;
  $payResponse = json_decode($APIresponse, TRUE);

}









///////////////   SAVINGS CIRCLES  ///////////////


// Get user circles  |  $userCircles  (== NULL?)
function userCircles($phoneNumber) {
  global $db;
  $sqlmyCircles = "SELECT circleID FROM circleMembers WHERE phonenumber LIKE '%" . $phoneNumber . "%'";
  $resultCircle = mysqli_query($db, $sqlmyCircles);
  $circleID = "";

  // Check if user is in a circle
  $circleCheckQuery =$db->query($sqlmyCircles);
  $circleCheckAvailable = $circleCheckQuery->fetch_assoc();

  $r = 1;
  global $userCircles;
  $userCircles = "";

  // User IS in circles
  if ($circleCheckAvailable != NULL) {

    while ($circleID = mysqli_fetch_assoc($resultCircle))
    { // DOES THIS NEED TO BE FETCH ARRAY????
        // Fetch circleID
        $circleID = ucfirst($circleID['circleID']);
        // Set C# variables to circleID know order/index
        ${"c" . $r} = $circleID;
        // Set circleIndex in DB so we can access this in other swtich cases
        $sqlcircleIndex = "UPDATE circleMembers SET circleIndex = '" . $r . "' WHERE phonenumber LIKE '%" . $phoneNumber . "%' AND circleID LIKE '%" . $circleID . "' ";
        $db->query($sqlcircleIndex);
        $userCircles .= "" . $r . ". " . $circleID . "\n";
        $r++;
    } //END of While loop

  }

  // User is NOT in a circle
  else {
    $userCircles = NULL;
  }
}

// Get circle balance  |  $circleBalance
function circleBalance($circleID) {
  global $db;
  $sqlCircleBalance = "SELECT balance FROM circles WHERE circleID LIKE '%" . $circleID . "%'";
  $resultCircleBalance = mysqli_query($db, $sqlCircleBalance);
  $circleBalanceAvailable = mysqli_fetch_assoc($resultCircleBalance);

  global $circleBalance;
  $circleBalance = $circleBalanceAvailable['balance'];
}

// Get circle members |  $circleMembers(name: phone#)
function circleMembers($circleID) {
  global $db;
  $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleID . "%' ";
  $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
  $memberList = "";

  $r = 1;
  global $circleMembers;  $circleMembers = '';

  //Loop through circleMembers
  while ($memberList = mysqli_fetch_assoc($resultCircleMembers))
  { //&& $circleMemberBalance = mysqli_fetch_assoc($resultCircleMemberBalance)
      $memberList = $memberList['phonenumber'];

      $sqlmembersName = "SELECT username FROM users WHERE phonenumber LIKE '%" . $memberList . "%' ";
      $resultMembersName = mysqli_query($db, $sqlmembersName);
      $membersNameAvailable = mysqli_fetch_assoc($resultMembersName);
      $membersName = ucfirst($membersNameAvailable['username']);

      $circleMembers .= "" . $r . ". " . $membersName . ": " . $memberList . " \n";
      $r++;
  }


}

// Get circle member balances  |  $circleMemberBalances
function circleMemberBalances($circleID) {
  global $db;
  $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleID . "%' ";
  $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
  $memberList = "";

  $r = 1;

  global $circleMemberBalances; $circleMemberBalances = "";

  //Loop through circleMembers
  while ($memberList = mysqli_fetch_assoc($resultCircleMembers))
  { //&& $circleMemberBalance = mysqli_fetch_assoc($resultCircleMemberBalance)
      $memberList = $memberList['phonenumber'];
      $sqlcircleMemberBalance = "SELECT userBalance FROM circleMembers WHERE circleID LIKE '%" . $circleID . "%' && phonenumber LIKE '%" . $memberList . "%'";
      $resultCircleMemberBalance = mysqli_query($db, $sqlcircleMemberBalance);
      $circleMemberBalance = mysqli_fetch_assoc($resultCircleMemberBalance);
      $circleMemberBalance = $circleMemberBalance['userBalance'];

      $sqlmembersName = "SELECT username FROM users WHERE phonenumber LIKE '%" . $memberList . "%' ";
      $resultMembersName = mysqli_query($db, $sqlmembersName);
      $membersNameAvailable = mysqli_fetch_assoc($resultMembersName);
      $membersName = ucfirst($membersNameAvailable['username']);

      $circleMemberBalances .= "" . $r . ". " . $membersName . ": " . $circleMemberBalance . " \n";
      $r++;
  }


}



// Log circle select  |  $circleSelect
function logcircleSelect($userResponse, $phoneNumber, $sessionId) {
  global $db;
  $sql = "SELECT * FROM circleMembers WHERE phonenumber LIKE '%" . $phoneNumber . "%' AND circleIndex LIKE '%" . $userResponse . "'";
  $circleQuery = $db->query($sql);
  $circlesAvailable = $circleQuery->fetch_assoc();

  global $circleSelect;
  $circleSelect = $circlesAvailable['circleID'];

  // Add CIRCLESELECT to db
  $sqlcircleSelect = "UPDATE session_levels SET circleSelect ='" . $circleSelect . "' where `session_id`='" . $sessionId . "'";
  $db->query($sqlcircleSelect);
}

// Get circle select  |  $circleSelect  (must run after logging circleSelect)
function circleSelect($sessionId) {
  global $db;
  $sqlselect = "SELECT * FROM session_levels WHERE session_id LIKE '%" . $sessionId . "%'";
  $selectQuery = $db->query($sqlselect);
  $selectAvailable = $selectQuery->fetch_assoc();
  global $circleSelect;
  $circleSelect = $selectAvailable['circleSelect'];
}



// Get active proposal  |  $proposalAvailable  |  $voteCheckAvailable  |  $proposalAvailable['txnhash']['phonenumber'] ['action'] ['value']
function proposalAvailable($circleID, $phoneNumber) {
  global $db;
  $sqlProposal = "SELECT * FROM circleProposals WHERE circleID LIKE '%" . $circleID . "%' && result is NULL";
  $proposalQuery = $db->query($sqlProposal);

  global $proposalAvailable; global $txnhash; global $proposer; global $action; global $value;
  $proposalAvailable = $proposalQuery->fetch_assoc();


  if ($proposalAvailable != NULL)
  {
      global $voteCheckAvailable;
      // WHILE LOOP SO WE CAN DO MULTIPLE VOTES AT A TIME :)))))))
      $txnhash = $proposalAvailable['txnhash'];
      $proposer = $proposalAvailable['phonenumber'];
      $action = $proposalAvailable['action'];
      $value = $proposalAvailable['value'];
      // CHECK IF USER HAS ALREADY VOTED
      $sqlVoteCheck = "SELECT * FROM votes WHERE phonenumber LIKE '%" . $phoneNumber . "%' && txnhash LIKE '%" . $txnhash . "%'";
      $voteCheckQuery  = $db->query($sqlVoteCheck);
      $voteCheckAvailable = $voteCheckQuery->fetch_assoc();

  }

}

// Get circle governance rules  |  $quorum  |  $threshold  |  $memberCount
function governance($circleID) {
  global $db;
  $sqlGovernance = "SELECT * FROM circles WHERE circleID LIKE '%" . $circleSelect . "%'";
  $governanceQuery = $db->query($sqlGovernance);
  $governanceAvailable = $governanceQuery->fetch_assoc();
  global $quorum; global $threshold; global $memberCount;
  $quorum = $governanceAvailable['quorum'];
  $threshold = $governanceAvailable['threshold'];
  $memberCount = $governanceAvailable['memberCount'];
}

// line 1200 is where this gets spicy


// Log circle deposit   |  $txnID  |  $circleBalance  |  $circleSelect
function circleDeposit($amount, $phoneNumber, $circleID) {
  global $db;
  // Fetch current txn txnhash
  $sqlTxnCount = "SELECT txnhash FROM circleTxns";
  $txnCountQuery = $db->query($sqlTxnCount);
  $txnCount = mysqli_num_rows($txnCountQuery);
  global $txnID;
  $txnID = $txnCount++;

  // Log transaction in DB
  $sqlTxn = "INSERT INTO `circleTxns`(`dw`,`txnhash`,`phoneNumber`,`circleID`,`amount`) VALUES('D','" . $txnID . "','" . $phoneNumber . "','" . $circleID . "','" . $amount . "')";
  $resultTxn = mysqli_query($db, $sqlTxn);

  // Update circle balance
  $sqlBalanceUpdate = "UPDATE `circles` SET `balance`= `balance` + '" . $amount . "' where `circleID`='" . $circleID . "'";
  $resultBalanceUpdate = mysqli_query($db, $sqlBalanceUpdate);

  // Update user-circle balance
  $sqlUserBalanceUpdate = "UPDATE `circleMembers` SET `userBalance`= `userBalance` + '" . $amount . "' where `circleID`='" . $circleID . "' && `phonenumber` LIKE '%" . $phoneNumber . "%'";
  $resultUserBalanceUpdate = mysqli_query($db, $sqlUserBalanceUpdate);

  $sqlCircleBalance = "SELECT balance FROM circles WHERE circleID LIKE '%" . $circleID . "%'";
  $resultCircleBalance = mysqli_query($db, $sqlCircleBalance);
  $circleBalanceAvailable = mysqli_fetch_assoc($resultCircleBalance);
  global $circleBalance;
  $circleBalance = $circleBalanceAvailable['balance'];

  global $circleSelect; $circleSelect = $circleID;
}

// Withdraw request receipt line 1500


// Days left in month  |  $daysRemainingMonth
function daysRemainingMonth() {
  $now = new \DateTime();
  global $daysRemainingMonth;
  $daysRemainingMonth = (int)$now->format('t') - (int)$now->format('d');
  return $daysRemainingMonth;
}






///////////////  PAYMENTS ON DB   ///////////////


// Get user balance  |  $userBalanceDB
function userBalanceDB($phoneNumber) {
  global $db;
  $sqlUserBalance = "SELECT * FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%' LIMIT 1";
  $userBalanceQuery = $db->query($sqlUserBalance);
  $userBalanceAvailable = $userBalanceQuery->fetch_assoc();

  global $userBalanceDB;
  $userBalanceDB = $userBalanceAvailable['balance'];
}



///////////////  FX RATES APIs   ///////////////

// Get ZMW/USD Exchange Rate  |  $fxRate
function fxRates() {
  $app_id = '96ab537c20c74fc6b59a367cc402a373';
  $oxr_url = "https://openexchangerates.org/api/latest.json?app_id=" . $app_id;

  // Open CURL session:
  $ch = curl_init($oxr_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // Get the data:
  $json = curl_exec($ch);
  curl_close($ch);

  // Decode JSON response:
  $oxr_latest = json_decode($json);

  global $fxRate; $fxRate = $oxr_latest->rates->GBP;

  // USE
  // fxRates();
  // $response .= "" . $fxRate . "";

  // Access the rates inside the parsed object, like so:
  // printf(
  //     "1 %s equals %s GBP at %s",
  //     $oxr_latest->base,
  //     $oxr_latest->rates->GBP,
  //     date('H:i jS F, Y', $oxr_latest->timestamp)
  // );
}


///////////////   CROP STUFF  ///////////////

// Get maize prices  |  $maizePrices
function maizePrices(){
  global $db;
  $sqlMaize = "SELECT * FROM maizePrices";
  $maizeQuery = $db->query($sqlMaize);

  global $maizePrices;
  $maizePrices = "END Prices last updated September 2020 (ZMW/kg): \n ";

  //Loop through cities
  while ($maizeAvailable = mysqli_fetch_assoc($maizeQuery))
  {
      $city = $maizeAvailable['city'];

      // MAIZE WHITE
      if ($userResponse == 1) {
        $price = $maizeAvailable['mPrice'];
      }

      // ROLLER MAIZE MEAL
      elseif ($userResponse == 2) {
        $price = $maizeAvailable['rmmPrice'];
      }

      $maizePrices .= "" . $city . ": " . $price . " \n ";
  }
}

///////////////  WEATHER STATION ///////////////

// CHECK IF VALID CITY!!!





//  |  output: $userCity
?>
