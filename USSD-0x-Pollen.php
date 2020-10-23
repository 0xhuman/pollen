<?php

// ensure this code runs only after a POST from AT
if (!empty($_POST)) {
    require_once('USSD-dbConnect.php');
    require_once('USSD-ATGateway.php');
    require_once('USSD-config.php');

    // receive the POST from AT
    $sessionId   = $_POST['sessionId'];
    $serviceCode = $_POST['serviceCode'];
    $phoneNumber = $_POST['phoneNumber'];
    $text        = $_POST['text'];

    // Explode the text to get the value of the latest interaction - think 1*1
    $textArray    = explode('*', $text);
    $userResponse = trim(end($textArray));

    // Set the default level of the user
    $level = 0;

    // Define R, the variable used to auto incriment menu option numbers (for dynamic length DB queries)
    $r = 1;

    // Check the level of the user from the DB and retain default level if none is found for this session
    $sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
    $levelQuery = $db->query($sql);
    if ($result = $levelQuery->fetch_assoc()) {
        $level = $result['level'];
    }

    // Check if the user is in the db
    $sql7          = "SELECT * FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%' LIMIT 1";
    $userQuery     = $db->query($sql7);
    $userAvailable = $userQuery->fetch_assoc();


    // Pull user city from DB
    $userCity = $userAvailable['city'];

    // Pull User Secret pin
    $sqlpinCheck = "SELECT * FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%'";
    $pinCheckQuery  = $db->query($sqlpinCheck);
    $pinCheckAvailable = $pinCheckQuery->fetch_assoc();
    $pinCheck = $pinCheckAvailable['pin'];

    $reverse = array_reverse($textArray);


    // Check if the user is available (yes)->Serve the menu; (no)->Register the user
    if ($userAvailable && $userAvailable['city'] != NULL && $userAvailable['username'] != NULL && $userAvailable['pin'] != NULL) {
          switch ($level) {
                  case "0":
                        // Graduate user to next level & Serve Main Menu
                        $sql0 = "INSERT INTO `session_levels`(`session_id`,`phoneNumber`,`level`) VALUES('" . $sessionId . "','" . $phoneNumber . "',1)";
                        $resultUserLvl0 = mysqli_query($db, $sql0);

                        //Serve our services menu
                        $response = "CON Hi " . ucfirst($userAvailable['username']) . ". Welcome back! Please choose a service. 
                        1. My Circles 
                        2. Join a Circle 
                        3. Weather Station ";
                        //$response .= "4. Crop Prices";

                        $sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
                        $levelQuery = $db->query($sql);
                        if ($result = $levelQuery->fetch_assoc()) {
                            $level = $result['level'];
                        }

                        //$response .= "4. " . $level1 . " \n";
                        header('Content-type: text/plain');
                        echo $response;
                        break;


                 case "1":
                    $lv0v = $userResponse;  // Do i need to move this inside each if statement?
                    // Graduate user to level 2
                    $sqlLevel1 = "UPDATE `session_levels` SET `level`=2 where `session_id`='" . $sessionId . "'";
                    $db->query($sqlLevel1);

                    // MY CIRCLES
                    if ($userResponse == 1 || $userResponse == 5){
                      $response = "CON Please choose a circle.\n";

                      $sqlmyCircles    = "SELECT circleID FROM circleMembers WHERE phonenumber LIKE '%" . $phoneNumber . "%' LIMIT 3";
                      $resultCircle = mysqli_query($db, $sqlmyCircles);
                      $circleID = "";

                      while($circleID = mysqli_fetch_assoc($resultCircle)) {  // DOES THIS NEED TO BE FETCH ARRAY????
                        // Fetch circleID
                        $circleID = ucfirst($circleID['circleID']);
                        // Set C# variables to circleID know order/index
                        ${"c" . $r} = $circleID;
                        // Set circleIndex in DB so we can access this in other swtich cases
                        $sqlcircleIndex = "UPDATE circleMembers SET circleIndex = '" . $r . "' WHERE phonenumber LIKE '%" . $phoneNumber . "%' AND circleID LIKE '%" . $circleID . "' ";
                        $db->query($sqlcircleIndex);
                      $response .= "" . $r . ". " . $circleID . "\n";
                      $r++;
                    } //END of While loop
                      $sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
                      $levelQuery = $db->query($sql);
                      if ($result = $levelQuery->fetch_assoc()) {
                          $level = $result['level'];     }

                    }

                    // JOIN A CIRCLE
                    elseif ($userResponse == 2){
                      $response = "CON Please enter your circle invite code. \n";

                      $sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
                      $levelQuery = $db->query($sql);
                      if ($result = $levelQuery->fetch_assoc()) {
                          $level = $result['level'];
                      }
                    }

                    // WEATHER STATION
                    elseif ($userResponse == 3){
                      $response = "CON Weather Station 
                      1. " . ucfirst($userCity) . " Weather
                      or Enter a city in Zambia ";

                      $sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
                      $levelQuery = $db->query($sql);
                      if ($result = $levelQuery->fetch_assoc()) {
                          $level = $result['level'];
                      }
                    }

                    else {
                      // Demote user to level 1
                      $sqlLevel1 = "UPDATE `session_levels` SET `level`=1 where `session_id`='" . $sessionId . "'";
                      $db->query($sqlLevel1);
                      
                      $response = "CON Invalid response. Please type the number for the service you would like to use.
                      1. My Circles 
                      2. Join a Circle 
                      3. Weather Station ";
                    }

                    header('Content-type: text/plain');
                    echo $response;
                    
                    break;

                case "2":

                // Graduate user to level 3
                $sqlLevel3 = "UPDATE `session_levels` SET `level`=3 where `session_id`='" . $sessionId . "'";
                $db->query($sqlLevel3);

                   // CIRCLE ACTIONS (2a)
                   if ($reverse[1] == 1) {
                   //if ($textArray[0] == 1){

                     // Fetch circleMembers data
                     $sqlcircle2 = "SELECT * FROM circleMembers WHERE phonenumber LIKE '%" . $phoneNumber . "%' AND circleIndex LIKE '%" . $userResponse . "'";
                     $circleQuery2  = $db->query($sqlcircle2);
                     $circlesAvailable = $circleQuery2->fetch_assoc();
                     $circleSelect = $circlesAvailable['circleID'];

                     // Add CIRCLESELECT to db
                     $sqlcircleSelect = "UPDATE session_levels SET circleSelect ='" . $circleSelect . "' where `session_id`='" . $sessionId . "'";
                     $db->query($sqlcircleSelect);

                     // CHECK IF PROPOSAL AVAILABLE
                     $sqlProposal = "SELECT * FROM circleProposals WHERE circleID LIKE '%" . $circleSelect . "%' && result is NULL";
                     $proposalQuery  = $db->query($sqlProposal);
                     $proposalAvailable = $proposalQuery->fetch_assoc();
                     if ($proposalAvailable != NULL) {

                       // WHILE LOOP SO WE CAN DO MULTIPLE VOTES AT A TIME :)))))))

                       // Pull proposer #, action, and value from db query
                       $txnhash = $proposalAvailable['txnhash'];
                       $proposer = $proposalAvailable['phonenumber'];
                       $action = $proposalAvailable['action'];
                       $value = $proposalAvailable['value'];

                       // CHECK IF USER HAS ALREADY VOTED
                       //$sqlVoteCheck = "SELECT * FROM votes WHERE circleID LIKE '%" . $circleSelect . "%' && txnhash LIKE '%" . $txnhash . "%'";
                       //$voteCheckQuery  = $db->query($sqlVoteCheck);
                       //$voteCheckAvailable = $voteCheckQuery->fetch_assoc();
                       //if ($voteCheckAvailable != NULL) { if statement below }

                         if ($userResponse >= 1 && $userResponse <= 5) {

                           // GET CIRCLE BALANCE
                           $sqlCircleBalance = "SELECT balance FROM circles WHERE circleID LIKE '%" . $circleSelect . "%'";
                           $resultCircleBalance = mysqli_query($db, $sqlCircleBalance);
                           $circleBalanceAvailable = mysqli_fetch_assoc($resultCircleBalance);
                           $circleBalance = $circleBalanceAvailable['balance'];

                           //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                           $response = "CON Welcome to " . ucfirst($circleSelect) . ". \n Circle Balance: " . $circleBalance . " \n Please choose an action: 
                           0. Vote
                           1. View Balances 
                           2. Pay-in Funds 
                           3. Request Funds 
                           4. Leave Circle ";
                       //$response .= "5. Go back \n";
                        }
                          // end of votecheck
                      } //end of proposal available

                     else { //if ($proposalAvailable = NULL)
                       if ($userResponse >= 1 && $userResponse <= 5) {

                         // GET CIRCLE BALANCE
                         $sqlCircleBalance = "SELECT balance FROM circles WHERE circleID LIKE '%" . $circleSelect . "%'";
                         $resultCircleBalance = mysqli_query($db, $sqlCircleBalance);
                         $circleBalanceAvailable = mysqli_fetch_assoc($resultCircleBalance);
                         $circleBalance = $circleBalanceAvailable['balance'];

                         //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                         $response = "CON Welcome to " . ucfirst($circleSelect) . ". \n Circle Balance: " . $circleBalance . " \n Please choose an action:
                         1. View Balances 
                         2. Pay-in Funds 
                         3. Request Funds 
                         4. Leave Circle ";
                     }}
                       //echo ${"c" . $userResponse};
                       //echo $c1;

                   } //end of if ($lv0v == 1){

                 // INVITE CODE CHECK
                 elseif ($reverse[1] == 2){
                   // Check if circle exists and phone number is valid
                   $sqlJoin = "SELECT * FROM circleInvites WHERE invitee LIKE '%" . $phoneNumber . "%' && circleID LIKE '" . $userResponse . "'";
                   $joinQuery = $db->query($sqlJoin);
                   $joinAvailable = $joinQuery->fetch_assoc();

                   // SUCCESSFUL JOIN
                   if ($joinAvailable != NULL){
                     $sqlAddMember = "INSERT INTO `circleMembers`(`circleID`,`phoneNumber`) VALUES('" . $userResponse . "','" . $phoneNumber . "')";
                     $resultAddMember = mysqli_query($db, $sqlAddMember);
                     $sqlIncreaseMemberCount = "UPDATE circles SET memberCount = memberCount + 1 WHERE circleID LIKE '" . $userResponse . "'";
                     $resultIncreaseMemberCount = mysqli_query($db, $sqlIncreaseMemberCount);
                     $response = "CON Congrats! You have successfully joined " . ucfirst($userResponse) . ". Please choose an action:
                     1. View Balances 
                     2. Pay-in Funds 
                     3. Request Funds ";

                   }

                   // DENIAL .... try to add two denials (one for invalid # and one for invalid code)
                   else {
                     $response = "END Sorry, your invite code is not valid or you are not a member of this circle.";
                   }
               }

               // WEATHER STATION LEVEL 2
               elseif ($reverse[1] == 3){

                 if ($userResponse == 1) {
                   // WEATHER API JAZZ
                   $userCityL = strtolower($userCity);

                   $response = "END The 8 day forecast for " . ucfirst($userCity) . " is: \n";
                   }

                else {
                  $userCityL = strtolower($userResponse);

                  $response = "END The 8 day forecast for " . ucfirst($userResponse) . " is: \n";
                  }

                  // Pull LAT LON MAP
                  $sqlmap    = "SELECT * FROM coordinates WHERE LOWER(city) LIKE '%" . $userCityL . "%' LIMIT 1";
                  $mapQuery  = $db->query($sqlmap);
                  $mapAvailable = $mapQuery->fetch_assoc();
                  $lon       = $mapAvailable['lon'];
                  $lat       = $mapAvailable['lat'];

                  // MAKE API CALL TO OPENWEATHERMAP
                  $jsonfile = file_get_contents("https://api.openweathermap.org/data/2.5/onecall?lat=" . $lat . "&lon=" . $lon . "&exclude=minutely,hourly&appid=4af2b0a91ec1e9a97c54df34b2d3a119");
                  $jsondata = json_decode($jsonfile, true);

                  // Loop through days
                  foreach ($jsondata['daily'] as $day => $value) {
                      //print_r($day);
                      $desc     = ucfirst($value['weather'][0]['description']);
                      $max_temp = round($value['temp']['max'] - 273.15);
                      $min_temp = round($value['temp']['min'] - 273.15);
                      $pressure = $value['pressure'];
                      $response .= "-" . $desc . ": " . $min_temp . "-" . $max_temp . "C \n";
                  }

                } // End of elseif textarrray =3

                $sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
                $levelQuery = $db->query($sql);
                if ($result = $levelQuery->fetch_assoc()) {
                    $level = $result['level'];
                }

                header('Content-type: text/plain');
                echo $response;

              break;


            case "3":

            // Graduate user to level 4
            $sqlLevel4 = "UPDATE `session_levels` SET `level`=4 where `session_id`='" . $sessionId . "'";
            $db->query($sqlLevel4);

            // ReFetch circleSelect data
            $sqlselect = "SELECT * FROM session_levels WHERE session_id LIKE '%" . $sessionId . "%'";
            $selectQuery  = $db->query($sqlselect);
            $selectAvailable = $selectQuery->fetch_assoc();
            $circleSelect = $selectAvailable['circleSelect'];

              // VIEW BALANCES
              if ($userResponse == 1) {

                $response = "END Here is the member balances for " . ucfirst($circleSelect) . ": \n";

                // Fetch members of the circle
                $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' ";
                $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
                //$sqlcircleMemberBalance = "SELECT userBalance FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' && phonenumber LIKE '%" . $phoneNumber . "%'";
                //$resultCircleMemberBalance = mysqli_query($db, $sqlcircleMemberBalance);
                $circleMembers = "";
                //$membersName = "";
                //$circleMemberBalance = "";

                //Loop through circleMembers
                while($circleMembers = mysqli_fetch_assoc($resultCircleMembers) ) { //&& $circleMemberBalance = mysqli_fetch_assoc($resultCircleMemberBalance)
                  $circleMembers = $circleMembers['phonenumber'];
                  $sqlcircleMemberBalance = "SELECT userBalance FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' && phonenumber LIKE '%" . $circleMembers . "%'";
                  $resultCircleMemberBalance = mysqli_query($db, $sqlcircleMemberBalance);
                  $circleMemberBalance = mysqli_fetch_assoc($resultCircleMemberBalance);
                  $circleMemberBalance = $circleMemberBalance['userBalance'];

                  $sqlmembersName = "SELECT username FROM users WHERE phonenumber LIKE '%" . $circleMembers . "%' ";
                  $resultMembersName = mysqli_query($db, $sqlmembersName);
                  $membersNameAvailable = mysqli_fetch_assoc($resultMembersName);
                  $membersName = ucfirst($membersNameAvailable['username']);

                  //$response .= "" . $r . ". " . $circleMembers . ": " . $circleMemberBalance . "\n";
                  $response .= "" . $r . ". " . $membersName . ": " . $circleMemberBalance . "\n";
                  //$response .= "" . $r . ". " . $circleMembers . "\n";
                  $r++;
                }
              }

              // PAY-IN FUNDS
              elseif ($userResponse == 2) {
                //CON enter amount, confirm & pin, receipt
                $response = "CON Please enter an amount to pay-in (KWACHA): \n";
               }

              // REQUEST FUNDS
              elseif ($userResponse == 3) {
                //CON enter amount, confirm & pin, receipt
                $response = "CON Please enter an amount to request (KWACHA): \n";
            }

              // LEAVE CIRCLE
              elseif ($userResponse == 4) {

                // Check if user is in debt !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

                // Serve Menu
                $response = "CON Are you sure you want to leave " . $circleSelect . "?
                1. Yes
                2. No ";
            }

            // SERVE PROPOSAL
            elseif ($userResponse == 0) {

              // GET PROPOSAL INFO
              $sqlProposal = "SELECT * FROM circleProposals WHERE circleID LIKE '%" . $circleSelect . "%' && result is NULL";
              $proposalQuery  = $db->query($sqlProposal);
              $proposalAvailable = $proposalQuery->fetch_assoc();

              if ($proposalAvailable != NULL) {

                // WHILE LOOP SO WE CAN DO MULTI
                // SAVE PROPOSAL INDEX SO WE KNOW WHICH ONE THEY SELECTED??????

                // GET PROPOSAL Data
                $txnhash = $proposalAvailable['txnhash'];
                $proposer = $proposalAvailable['phonenumber'];
                $action = $proposalAvailable['action'];
                $value = $proposalAvailable['value'];

                // Fetch proposer's name
                $sqlProposerName = "SELECT username FROM users WHERE phonenumber LIKE '%" . $proposer . "%' ";
                $resultProposerName = mysqli_query($db, $sqlProposerName);
                $proposerNameAvailable = mysqli_fetch_assoc($resultProposerName);
                $proposerName = ucfirst($proposerNameAvailable['username']);

                // WITHDRAWAL
                if ($action == "Withdrawal") {
                  $response = "CON " . $proposerName . " has requested " . $value . " KWA from " . ucfirst($circleSelect) . "\n";
                }

                // MEMBER ADD
                elseif ($action == "MemberAdd") {
                  $response = "CON " . $proposerName . " has requested to add " . $value . " to " . ucfirst($circleSelect) . "\n";
                }

                $response .= "1. Vote YES \n";
                $response .= "0. Vote NO \n";

              }

              // NO ACTIVE PROPOSALS
              else {
                $response = "END No active proposals for " . ucfirst($circleSelect) . "";
              }

            } // END OF PROPOSAL

              // GO BACK
              //elseif ($userResponse == 5) {
                //demote user to level 1
                //$sqlLevelDemote = "UPDATE `session_levels` SET `level`=1 where `session_id`='" . $sessionId . "'";
                //$db->query($sqlLevelDemote);

                //$sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
                //$levelQuery = $db->query($sql);
                //if ($result = $levelQuery->fetch_assoc()) {
                    //$level = $result['level'];

              //} }

          // default:
            //$response = "CON You have to choose a service. \n";
            //header('Content-type: text/plain');
            //echo $response;

            // Update PHP level variable
            $sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
            $levelQuery = $db->query($sql);
            if ($result = $levelQuery->fetch_assoc()) {
                $level = $result['level'];
                }
            header('Content-type: text/plain');
            echo $response;

            break;

          case "4":

          // ReFetch circleSelect data
          $sqlselect = "SELECT * FROM session_levels WHERE session_id LIKE '%" . $sessionId . "%'";
          $selectQuery  = $db->query($sqlselect);
          $selectAvailable = $selectQuery->fetch_assoc();
          $circleSelect = $selectAvailable['circleSelect'];

          // UPGRADE TO LEVEL 5
          $sqlLevel5 = "UPDATE `session_levels` SET `level`=5 where `session_id`='" . $sessionId . "'";
          $resultLevel5 = mysqli_query($db, $sqlLevel5);

              if ($reverse[1] == 1) {
                // query specific user data...likely requires you to log what member they selected
              }

              // Circle Deposit Receipt
              elseif ($reverse[1] == 2) {

                // Fetch current txn txnhash
                $sqlTxnCount = "SELECT txnhash FROM circleTxns";
                $txnCountQuery  = $db->query($sqlTxnCount);
                $txnCount = mysqli_num_rows($txnCountQuery);
                $newTxnCount = $txnCount++;
                
                // Log transaction in DB
                $sqlTxn = "INSERT INTO `circleTxns`(`txnhash`,`phoneNumber`,`circleID`,`amount`) VALUES('" . $newTxnCount . "','" . $phoneNumber . "','" . $circleSelect . "','" . $userResponse. "')";
                $resultTxn = mysqli_query($db, $sqlTxn);

                // Update circle balance
                $sqlBalanceUpdate = "UPDATE `circles` SET `balance`= `balance` + '" . $userResponse . "' where `circleID`='" . $circleSelect . "'";
                $resultBalanceUpdate = mysqli_query($db, $sqlBalanceUpdate);

                // Update user-circle balance
                $sqlUserBalanceUpdate = "UPDATE `circleMembers` SET `userBalance`= `userBalance` + '" . $userResponse . "' where `circleID`='" . $circleSelect . "' && `phonenumber` LIKE '%" . $phoneNumber . "%'";
                $resultUserBalanceUpdate = mysqli_query($db, $sqlUserBalanceUpdate);

                $sqlCircleBalance = "SELECT balance FROM circles WHERE circleID LIKE '%" . $circleSelect . "%'";
                $resultCircleBalance = mysqli_query($db, $sqlCircleBalance);
                $circleBalanceAvailable = mysqli_fetch_assoc($resultCircleBalance);
                $circleBalance = $circleBalanceAvailable['balance'];

                $response = "END Congrats! You have deposited " . $userResponse . " into " . ucfirst($circleSelect) . "\n";
                $response .= "Circle Balance: " . $circleBalance . " KWA \n";
              }

              // Money Request Receipt
              elseif ($reverse[1] == 3) {

                // Query circleBalance, check if userResponse > circleBalance
                $sqlCircleBalance = "SELECT balance FROM circles WHERE circleID LIKE '%" . $circleSelect . "%'";
                $resultCircleBalance = mysqli_query($db, $sqlCircleBalance);
                $circleBalanceAvailable = mysqli_fetch_assoc($resultCircleBalance);
                $circleBalance = $circleBalanceAvailable['balance'];

                if ($circleBalance > $userResponse) {

                  // Fetch current # of proposals so we can update txnhash
                  $sqlProposalCount = "SELECT txnhash FROM circleProposals";
                  //$sqlProposalCount = "SELECT txnhash FROM circleProposals WHERE circleID LIKE '%" . $circleSelect . "%'";
                  $proposalCountQuery  = $db->query($sqlProposalCount);
                  $proposalCount = mysqli_num_rows($proposalCountQuery);
                  $newProposalCount = $proposalCount++;
                  //$sqlProposalCount = "SELECT COUNT(txnhash) FROM circleProposals WHERE circleID LIKE '%" . $circleSelect . "%' && result = null";
                  //$proposalCountQuery  = $db->query($sqlProposalCount);
                  //$proposalCountAvailable = $proposalCountQuery->fetch_assoc();
                  //$newProposalCount = $proposalCountAvailable['COUNT(txnhash)'] + 1;

                  //print_r($proposalCountAvailable);

                  // Log withdrawal request in proposals db
                  $sqlWithdrawalRequest = "INSERT INTO `circleProposals`(`circleID`,`txnhash`,`phonenumber`,`action`,`value`) VALUES('" . $circleSelect . "','" . $newProposalCount . "','" . $phoneNumber . "','Withdrawal','" . $userResponse . "')";
                  $resultWithdrawalRequest = mysqli_query($db, $sqlWithdrawalRequest);

                  // SEND SMS
                  // Fetch members of the circle
                  $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' ";
                  $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
                  $circleMembers = "";

                  // Fetch Requestor's name
                  $sqlRequesterName = "SELECT username FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%' ";
                  $resultRequesterName = mysqli_query($db, $sqlRequesterName);
                  $requesterNameAvailable = mysqli_fetch_assoc($resultRequesterName);
                  $requesterName = ucfirst($requesterNameAvailable['username']);

                  //Loop through circleMembers
                  while($circleMembers = mysqli_fetch_assoc($resultCircleMembers)) {
                    $circleMembers = $circleMembers['phonenumber'];

                    // SEND SMS VIA AT GATEWAY
                    $message = "Proposal: Withdraw Funds \n Circle: " . $circleSelect . "\n  Requestor: " . $requesterName . ", " . $phoneNumber . " \n Amount: " . $userResponse . " KWA \n Dial into USSD to vote *384*313233#";
                    $recipient = $circleMembers;
                    $gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                    $gateway->sendMessage($recipient,$message);
                  }
                  $response = "END Your request for " . $userResponse . " KWA from " . ucfirst($circleSelect) . " has been submitted for vote. \n";
                  $response .= "Proposal ID: " . $newProposalCount . "\n";
                  //$response .= "" . $proposalCount . "";

                }

                else {
                  $response = "END Circle funds not sufficient. You can request a max of " . $circleBalance . " KWA \n";
                }

              }

              // VOTE !!!!!!
              elseif ($reverse[1] == 0) {
                // LOG PROPOSAL SELECT
                // Query txnhash

                // GET PROPOSAL INFO....change to use txnhash
                $sqlProposal = "SELECT * FROM circleProposals WHERE circleID LIKE '%" . $circleSelect . "%' && result is NULL";
                $proposalQuery  = $db->query($sqlProposal);
                $proposalAvailable = $proposalQuery->fetch_assoc();

                // WHILE LOOP SO WE CAN DO MULTI

                // GET PROPOSAL Data
                $txnhash = $proposalAvailable['txnhash'];
                $proposer = $proposalAvailable['phonenumber'];
                $action = $proposalAvailable['action'];
                $value = $proposalAvailable['value'];

                // GET GOVERNANCE DATA FROM CIRCLES (QUORUM, THRESHOLD, memberCount)
                $sqlGovernance = "SELECT * FROM circles WHERE circleID LIKE '%" . $circleSelect . "%'";
                $governanceQuery  = $db->query($sqlGovernance);
                $governanceAvailable = $governanceQuery->fetch_assoc();
                $quorum = $governanceAvailable['quorum'];
                $threshold = $governanceAvailable['threshold'];
                $memberCount = $governanceAvailable['memberCount'];

                // VALID VOTE
                if ($userResponse == 1 || $userResponse == 0) {

                  // YES VOTE
                  if ($userResponse == 1) {
                    // INCRIMENT YES COUNT
                    $sqlYesIncriment = "UPDATE `circleProposals` SET `yesCount` = `yesCount` + 1 where txnhash LIKE '%" . $txnhash . "%'";
                    $db->query($sqlYesIncriment);

                    // LOG VOTE ... $vote = vote, txnhash
                    $sqlVoteLog = "INSERT INTO `votes`(`txnhash`,`phonenumber`,`vote`) VALUES('" . $txnhash . "','" . $phoneNumber . "','YES')";
                    $resultVoteLog = mysqli_query($db, $sqlVoteLog);

                    // INCRIMENT VOTE COUNT IN PROPOSALS TABLE
                    $sqlVoteIncriment = "UPDATE `circleProposals` SET `voteCount` = `voteCount` + 1 where txnhash LIKE '%" . $txnhash . "%'";
                    $db->query($sqlVoteIncriment);

                    // GET NEW YESCOUNT AND VOTECOUNT
                    $votesQuery  = $db->query($sqlProposal);
                    $votesAvailable = $votesQuery->fetch_assoc();
                    $voteCount = $votesAvailable['voteCount'];
                    $yesCount = $votesAvailable['yesCount'];

                    // QUORUM MET
                    if ( ($voteCount / $memberCount) >= $quorum) {

                      // THRESHOLD MET
                      if ( ($yesCount / $voteCount) >= $threshold) {
                        $result = "APPROVED";

                      }

                      // THRESHOLD NOT MET
                      elseif ( $voteCount >= 1) {
                        $result = "DENIED";
                      }

                      // Actions that execute for both threshold met and not met
                      // UPDATE PROPOSAL RESULT
                      $sqlProposalResult = "UPDATE `circleProposals` SET `result` = '" . $result . "' where txnhash LIKE '%" . $txnhash . "%'";
                      $db->query($sqlProposalResult);

                      //SMS
                      // fetch circle MEMBERS
                      $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' ";
                      $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
                      $circleMembers = "";

                      // fetch requestor name
                      $sqlProposerName = "SELECT username FROM users WHERE phonenumber LIKE '%" . $proposer . "%' ";
                      $resultProposerName = mysqli_query($db, $sqlProposerName);
                      $proposerNameAvailable = mysqli_fetch_assoc($resultProposerName);
                      $proposerName = ucfirst($proposerNameAvailable['username']);

                      while($circleMembers = mysqli_fetch_assoc($resultCircleMembers)) {
                        $circleMembers = $circleMembers['phonenumber'];

                        // WITHDRAWAL
                        if ($action == "Withdrawal") {
                          $message = "" . $proposerName . "'s request to withdraw " . $value . " KWA from " . ucfirst($circleSelect) . " was " . $result . "";

                          // Fetch current txn txnhash
                          $sqlTxnCount = "SELECT txnhash FROM circleTxns";
                          $txnCountQuery  = $db->query($sqlTxnCount);
                          $txnCount = mysqli_num_rows($txnCountQuery);
                          $newTxnCount = $txnCount++;
                          
                          // Log transaction in DB
                          $sqlTxn = "INSERT INTO `circleTxns`(`txnhash`,`phoneNumber`,`circleID`,`amount`) VALUES('" . $newTxnCount . "','" . $phoneNumber . "','" . $circleSelect . "','" . $value. "')";
                          $resultTxn = mysqli_query($db, $sqlTxn);

                          // Update circle balance
                          $sqlBalanceUpdate = "UPDATE `circles` SET `balance`= `balance` - '" . $value . "' where `circleID`='" . $circleSelect . "'";
                          $resultBalanceUpdate = mysqli_query($db, $sqlBalanceUpdate);

                          // Update user-circle balance
                          $sqlUserBalanceUpdate = "UPDATE `circleMembers` SET `userBalance`= `userBalance` - '" . $value . "' where `circleID`='" . $circleSelect . "' && `phonenumber` LIKE '%" . $phoneNumber . "%'";
                          $resultUserBalanceUpdate = mysqli_query($db, $sqlUserBalanceUpdate);
                        }
                        
                        elseif ($action == "MemberAdd") {
                          $message = "" . $proposerName . "'s request to add " . $value . " to " . ucfirst($circleSelect) . " was " . $result . "";
                        }
                        
                        $recipient = $circleMembers;
                        $gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                        $gateway->sendMessage($recipient,$message);
                      }

                    } // end of quroum met

                   // QUORUM NOT MET
                   else {

                   }



                 } // end of yes vote

                  // NO VOTE
                  else {
                    // LOG VOTE ... $vote = vote, txnhash
                    $sqlVoteLog = "INSERT INTO `votes`(`txnhash`,`phoneNumber`,`vote`) VALUES('" . $txnhash . "','" . $phoneNumber . "','NO')";
                    $resultVoteLog = mysqli_query($db, $sqlVoteLog);

                    // INCRIMENT VOTE COUNT IN PROPOSALS TABLE
                    $sqlVoteIncriment = "UPDATE `circleProposals` SET `voteCount` = `voteCount` + 1 where txnhash LIKE '%" . $txnhash . "%'";
                    $db->query($sqlVoteIncriment);

                    // GET NEW YESCOUNT AND VOTECOUNT
                    $votesQuery  = $db->query($sqlProposal);
                    $votesAvailable = $votesQuery->fetch_assoc();
                    $voteCount = $votesAvailable['voteCount'];
                    $yesCount = $votesAvailable['yesCount'];

                    // QUORUM MET
                    if ( ($voteCount / $memberCount) >= $quorum) {

                      // THRESHOLD MET
                      if ( ($yesCount / $voteCount) >= $threshold) {
                        $result = "APPROVED";

                      }

                      // THRESHOLD NOT MET
                      else {
                        $result = "DENIED";
                      }

                      // Actions that execute for both threshold met and not met XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                      // UPDATE PROPOSAL RESULT
                      $sqlProposalResult = "UPDATE `circleProposals` SET `result` = '" . $result . "' where txnhash LIKE '%" . $txnhash . "%'";
                      $db->query($sqlProposalResult);

                      //SMS
                      // fetch circle MEMBERS
                      $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' ";
                      $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
                      $circleMembers = "";

                      // fetch requestor name
                      $sqlProposerName = "SELECT username FROM users WHERE phonenumber LIKE '%" . $proposer . "%' ";
                      $resultProposerName = mysqli_query($db, $sqlProposerName);
                      $proposerNameAvailable = mysqli_fetch_assoc($resultProposerName);
                      $proposerName = ucfirst($proposerNameAvailable['username']);

                      while($circleMembers = mysqli_fetch_assoc($resultCircleMembers)) {
                        $circleMembers = $circleMembers['phonenumber'];
                        // WITHDRAWAL
                        if ($action == "Withdrawal") {
                          $message = "" . $proposerName . "'s request to withdraw " . $value . " KWA from " . ucfirst($circleSelect) . " was " . $result . "";
                        }
                        elseif ($action == "MemberAdd") {
                          $message = "" . $proposerName . "'s request to add " . $value . " to " . ucfirst($circleSelect) . " was " . $result . "";
                        }
                        $recipient = $circleMembers;
                        $gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                        $gateway->sendMessage($recipient,$message);
                      }

                    } // end of quroum met

                   // QUORUM NOT MET
                   else {

                   }




                 } // end of no vote




                  // ACTIONS THAT EXECUTE FOR BOTH YES AND NO VOTES

                  // Print results
                  $response = "END Thank you for voting. Check SMS for final results.";







                } //end of valid vote

              // ERROR VOTE
              else {
                $response = "END Error. You must vote with 1 (YES) or 0 (NO). \n Please dial back and try again.";
              }

            } // end of VOTE


          header('Content-type: text/plain');
          echo $response;

              break;
      } // End of Switch (LEVEL)
} //end of if ($userAvailable && $userAvailable['city'])


else{
  // Register the user
  if($userResponse==""){
    // On receiving a Blank. Advise user to input correctly based on level
    switch ($level) {
        case 0:
          // Graduate the user to the next level, so you dont serve them the same menu
           $sql10b = "INSERT INTO `session_levels`(`session_id`, `phoneNumber`,`level`) VALUES('".$sessionId."','".$phoneNumber."', 1)";
           $db->query($sql10b);

           // Insert the phoneNumber, since it comes with the first POST
           $sql10c = "INSERT INTO `users`(`phonenumber`) VALUES ('".$phoneNumber."')";
           $db->query($sql10c);

           // Serve the menu request for name
           $response = "CON Welcome to Pollen! Please enter your name.";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            break;

        case 1:
          // Request again for name
            $response = "CON Your name is not supposed to be empty. Please enter your name.\n";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            break;

        case 2:
          // Request fir city again
        $response = "CON City not supposed to be empty. Please reply with your city \n";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            break;

        //case 3:
              // Request for secretPin again
            //$response = "CON Secret pin is not supposed to be empty. Please create a 4-digit secret pin (e.g. 4173) \n";

              // Print the response onto the page so that our gateway can read it
              //header('Content-type: text/plain');
              //echo $response;
                //break;

        default:
              // ERROR
            $response = "END Apologies, something went wrong... \n";

              // Print the response onto the page so that our gateway can read it
              header('Content-type: text/plain');
              echo $response;
                break;
    }
  }

  else{
    //11. Update User table based on input to correct level

    switch ($level) {
        case "0":
           // Serve the menu request for name
           $response = "END This level should not be seen...";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            break;

        case "1":
          // Update Name, Request for city
            $sql11b = "UPDATE `users` SET `username`='".$userResponse."' WHERE `phonenumber` LIKE '%". $phoneNumber ."%'";
            $db->query($sql11b);

            //11c. We graduate the user to the city level
            $sql11c = "UPDATE `session_levels` SET `level`=2 WHERE `session_id`='".$sessionId."'";
            $db->query($sql11c);

            //We request for the city
            $response = "CON Hi " . ucfirst($userResponse) . "! Please enter your city.";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            break;


        case "2":
              // Update city, Request for secretPin
              $sql11d = "UPDATE `users` SET `city`='".$userResponse."' WHERE `phonenumber` LIKE '%". $phoneNumber ."%'";
              //$sql11d = "UPDATE `users` SET `pin`='".$userResponse."' WHERE `phonenumber` LIKE '%". $phoneNumber ."%'";
              $db->query($sql11d);

                // We graduate the user to the city level
                $sqlsecretPinLevel = "UPDATE `session_levels` SET `level`=3 WHERE `session_id`='".$sessionId."'";
                $db->query($sqlsecretPinLevel);

                //We request for the secretPin
                $response = "CON Welcome from " . ucfirst($userResponse) . ". \n \n Before we continue, please set a 4-digit secret pin. We will ask you for it whenever you perform an important action (i.e. requesting funds).";



              // Print the response onto the page so that our gateway can read it
              header('Content-type: text/plain');
              echo $response;
              break;

        case "3":
        $sql13 = "UPDATE `users` SET `pin`='".$userResponse."' WHERE `phonenumber` LIKE '%". $phoneNumber ."%'";
        $db->query($sql13);
        // We graduate the user to level 8
        $sql14 = "UPDATE `session_levels` SET `level`=8 WHERE `session_id`='".$sessionId."'";
        $db->query($sql14);
        $response = "END Congratulations, you're registered with Pollen! \n \n Do not forget your secret pin (" . $userResponse . ")! \n \n Please dial back to view the main menu.";
        //$response = "CON Let's make sure you remember it! Please enter you secret pin.";
        //header('Content-type: text/plain');
        echo $response;
        break;


        //case "8":
        //$sqlpinCheck = "SELECT * FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%'";
        //$pinCheckQuery  = $db->query($sqlpinCheck);
        //$pinCheckAvailable = $pinCheckQuery->fetch_assoc();
        //$pinCheck = $pinCheckAvailable['pin'];

        //if ($userResponse == $pinCheck) {
          // We graduate the user to level 9
          //$sql15 = "UPDATE `session_levels` SET `level`=9 WHERE `session_id`='".$sessionId."'";
          //$db->query($sql15);
          //$response = "END Sweet! Congratualations on registering with Pollen :) Dial back to see services.";
          //header('Content-type: text/plain');
          //echo $response;
      //  }
      //  else {
          //$sqlDemotePin = "UPDATE `session_levels` SET `level`=8 WHERE `session_id`='".$sessionId."'";
          //$db->query($sqlDemotePin);
          //$response = "CON Oops, that did't match what you told us. Please try again.";
          //header('Content-type: text/plain');
          //echo $response;
        //}
      //  break;


        default:
          // Request for city again
        $response = "END Apologies, something went wrong... \n";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            //break;
    }
  }}}

?>
