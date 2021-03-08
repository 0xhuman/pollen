<?php

// Check if user is registered   |  $userAvailable
function activeUser($phoneNumber) {
  $sql = "SELECT * FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%' LIMIT 1";
  $userQuery = $db->query($sql);

  global $userAvailable;
  $userAvailable = $userQuery->fetch_assoc();

  //// Check if the user is available (yes)->Serve the menu; (no)->Register the user
  //if ($userAvailable && $userAvailable['city'] != NULL && $userAvailable['username'] != NULL && $userAvailable['pin'] != NULL)

}

// Get user information  |  $userCity  $userName
function userCity($phoneNumber) {
  $sql = "SELECT * FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%' LIMIT 1";
  $userQuery = $db->query($sql);
  $userAvailable = $userQuery->fetch_assoc();

  global $userCity; global $userName;
  $userCity = strtolower($userAvailable['city']);
  $userCity = strtolower($userAvailable['username']);
}

// Initiate user session
function newSession($sessionID, $phoneNumber) {
  $sql = "INSERT INTO `session_levels`(`session_id`,`phoneNumber`,`level`) VALUES('" . $sessionId . "','" . $phoneNumber . "',1)";
  $resultUserLvl0 = mysqli_query($db, $sql);
}

// Update user level  |  $level
function updateLevel($newLevel, $sessionID) {
  $sql = "UPDATE `session_levels` SET `level`='" . $newLevel . "' where `session_id`='" . $sessionId . "'";
  $db->query($sql);

  $sql2 = "select level from session_levels where session_id ='" . $sessionId . " '";
  $levelQuery = $db->query($sql2);
  global $level;
  if ($result = $levelQuery->fetch_assoc())
  {
      $level = $result['level'];
  }
}

// Get user level  |  $level
function userLevel($sessionID) {
  $sql = "select level from session_levels where session_id ='" . $sessionID . " '";
  $levelQuery = $db->query($sql);
  global $level;
  if ($result = $levelQuery->fetch_assoc())
  {
      $level = $result['level'];
  }
}




///////////////////  KOTANIPAY   ///////////////


// Get auth token  |  $authToken  (MUST RUN THIS EACH TIME YOU RUN A KP FUNCTION)
function kpAuth($phoneNumber) {
  global $authToken;

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
function kpKYC($authToken) {
  //function kpKYC($firstName, $lastName, $phoneNumber, $dateofbirth, $documentType, $documentNumber) {

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
      'dateofbirth' => $dateofbirth ,
      'email' => $email ,
      'phoneNumber' => $phoneNumber2 ,
      'documentType' => $documentType ,
      'documentNumber' => $documentNumber ,
      'firstName' => $firstName ,
      'lastName' => $lastName ,
  );

  // PUSH KYC TO API
  $ch = curl_init('https://europe-west3-kotanimac.cloudfunctions.net/savingsacco/api/kyc');
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
  $responseData = json_decode($APIresponse, TRUE);

  global $kycStatus;
  $kycStatus = $responseData['status'];

}

// Get user balance  |  $userBalanceCUSD  $userBalanceCELO  // CHANGE TO ACCEPT PHONE NUMBER  ******************
function userBalance() {

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
  $responseData = json_decode($APIresponse, TRUE);

  $userBalanceCUSD = $responseData['balance']['cusd'];
  $userBalanceCELO = $responseData['balance']['celo'];

}

// Pollen Pay  |  $payResponse['status'] == 'success', 'unverified', 'error', 'failed'
function pollenPay($sender, $receiver, $amount) {
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


// Get user circles  |  $userCircles
function userCircles($phoneNumber) {
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
  $sqlCircleBalance = "SELECT balance FROM circles WHERE circleID LIKE '%" . $circleID . "%'";
  $resultCircleBalance = mysqli_query($db, $sqlCircleBalance);
  $circleBalanceAvailable = mysqli_fetch_assoc($resultCircleBalance);

  global $circleBalance;
  $circleBalance = $circleBalanceAvailable['balance'];
}

// Get circle members |  $circleMembers(name: phone#)
function circleMembers($circleID) {
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
function logcircleSelect($userResponse, $phoneNumber, $sessionID) {
  $sql = "SELECT * FROM circleMembers WHERE phonenumber LIKE '%" . $phoneNumber . "%' AND circleIndex LIKE '%" . $userResponse . "'";
  $circleQuery = $db->query($sql);
  $circlesAvailable = $circleQuery->fetch_assoc();

  global $circleSelect;
  $circleSelect = $circlesAvailable['circleID'];

  // Add CIRCLESELECT to db
  $sqlcircleSelect = "UPDATE session_levels SET circleSelect ='" . $circleSelect . "' where `session_id`='" . $sessionID . "'";
  $db->query($sqlcircleSelect);
}

// Get circle select  |  $circleSelect  (must run after logging circleSelect)
function circleSelect($sessionID) {
  $sqlselect = "SELECT * FROM session_levels WHERE session_id LIKE '%" . $sessionId . "%'";
  $selectQuery = $db->query($sqlselect);
  $selectAvailable = $selectQuery->fetch_assoc();
  global $circleSelect;
  $circleSelect = $selectAvailable['circleSelect'];
}



// Get active proposal  |  $proposalAvailable  |  $voteCheckAvailable  |  $proposalAvailable['txnhash']['phonenumber'] ['action'] ['value']
function proposalAvailable($circleID, $phoneNumber) {
  $sqlProposal = "SELECT * FROM circleProposals WHERE circleID LIKE '%" . $circleID . "%' && result is NULL";
  $proposalQuery = $db->query($sqlProposal);

  global $proposalAvailable;
  $proposalAvailable = $proposalQuery->fetch_assoc();

  if ($proposalAvailable != NULL)
  {
      global $voteCheckAvailable;
      // WHILE LOOP SO WE CAN DO MULTIPLE VOTES AT A TIME :)))))))

      // CHECK IF USER HAS ALREADY VOTED
      $sqlVoteCheck = "SELECT * FROM votes WHERE phonenumber LIKE '%" . $phoneNumber . "%' && txnhash LIKE '%" . $txnhash . "%'";
      $voteCheckQuery  = $db->query($sqlVoteCheck);
      $voteCheckAvailable = $voteCheckQuery->fetch_assoc();

  }

}

// Get circle governance rules  |  $quorum  |  $threshold  |  $memberCount
function governance($circleID) {
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
function $circleDeposit($amount, $phoneNumber, $circleID) {
  // Fetch current txn txnhash
  $sqlTxnCount = "SELECT txnhash FROM circleTxns";
  $txnCountQuery = $db->query($sqlTxnCount);
  $txnCount = mysqli_num_rows($txnCountQuery);
  global $txnID
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



///////////////  PAYMENTS ON DB   ///////////////


// Get user balance  |  $userBalanceDB
function userBalanceDB($phoneNumber) {
  $sqlUserBalance = "SELECT * FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%' LIMIT 1";
  $userBalanceQuery = $db->query($sqlUserBalance);
  $userBalanceAvailable = $userBalanceQuery->fetch_assoc();

  global $userBalanceDB;
  $userBalanceDB = $userBalanceAvailable['balance'];
}



///////////////   CROP STUFF  ///////////////

// Get maize prices  |  $maizePrices
function maizePrices(){
  $sqlMaize = "SELECT * FROM maizePrices";
  $maizeQuery = $db->query($sqlMaize);

  global $maizePrices;
  $maizePrices = "END Prices for September 2020 (ZMW/kg): \n ";

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
