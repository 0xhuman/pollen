<?php
// ensure this code runs only after a POST from AT
if (!empty($_POST))
{
    require_once ('USSD-dbConnect.php');
    require_once ('USSD-ATGateway.php');
    require_once ('USSD-config.php');
    require_once ('pollen-functions.php');

      // receive the POST from AT
      $sessionId = $_POST['sessionId'];
      $serviceCode = $_POST['serviceCode'];
      $phoneNumber = $_POST['phoneNumber'];
      $text = $_POST['text'];

      // Explode the text to get the value of the latest interaction - think 1*1
      $textArray = explode('*', $text);
      $userResponse = trim(end($textArray));

      // Set the default level of the user
      $level = 0;

      // Check the level of the user from the DB and retain default level if none is found for this session
      userLevel($sessionId);

      // Check if user is registered   |  $userAvailable
      activeUser($phoneNumber);

      userCity($phoneNumber);

      // Reverse Text Array for backtracking
      $reverse = array_reverse($textArray);

    // Check if the user is available (yes)->Serve the menu; (no)->Register the user
    if ($userAvailable && $userAvailable['city'] != NULL && $userAvailable['username'] != NULL && $userAvailable['pin'] != NULL)
    {
        switch ($level)
        {


            // LEVEL 0 - HOME MENU

            case "0":
                // Initiate user session
                newSession($sessionId, $phoneNumber);

                //Serve our services menu
                $response = "CON Hi " . ucfirst($userName) . ". Welcome back! \n Please choose a service.
                        1. My Circles
                        2. Join a Circle
                        3. Weather Station
                        4. Maize Prices
                        5. Livestock Services ";
                //$response .= "4. Crop Prices";

                userLevel($sessionId);
                header('Content-type: text/plain');
                echo $response;
            break;




            // LEVEL 1 - Circle Select, Join a Circle, Weather Station

            case "1":

                // Update user level  |  $level
                updateLevel(2, $sessionId);

                // MY CIRCLES
                if ($userResponse == 1)
                {
                    $response = "CON Please choose a circle.\n";

                    userCircles($phoneNumber);

                    // User IS in a circle
                    if($userCircles != NULL) {
                      $response .= $userCircles;
                    }

                    // User is NOT in a circle
                    else {
                      $response = "CON You are not in any circles. Reply b to go back: ";
                    }
                }

                // JOIN A CIRCLE
                elseif ($userResponse == 2)
                {
                    $response = "CON Please enter your circle invite code. \n";
                }

                // WEATHER STATION
                elseif ($userResponse == 3)
                {
                    $response = "CON Weather Station
                      1. " . ucfirst($userCityL) . " Weather
                      or Enter a city in Zambia ";
                }

                // MAIZE PRICES
                elseif ($userResponse == 4)
                {
                    $response = "CON Please select your maize type
                      1. Maize (White)
                      2. Roller Maize Meal ";
                }

                // LIVESTOCK SERVICES
                elseif ($userResponse == 5 || ($textArray[0] == 5 && strtolower($userResponse) == 'b'))
                {
                    $response = "CON Please choose a service:
                      1. Common Diseases
                      2. Animal Feeding
                      3. Contact a Vet
                      4. Access Extension Services
                      5. Tell us your animal experience
                      6. What would you like to learn? ";
                }

                // POLLEN PAY
                elseif ($userResponse == 0)
                {
                  userBalanceDB($phoneNumber);
                  // KOTANIPAY API
                  // get auth token
                  kpAuth($phoneNumber);
                  // ADD PHONE NUMBER      *************
                  userBalance($phoneNumber);

                  $response = "CON Your Balance
                  cUSD: " . $userBalanceCUSD . ", CELO: " . $userBalanceCELO . "
                   1. Send Funds
                   2. Deposit Mobile Money
                   3. Withdraw to Mobile Money
                   4. Transaction History
                   5. KotaniPay KYC Test ";
                }

                else
                {
                    // Demote user to level 1
                    $sqlLevel1 = "UPDATE `session_levels` SET `level`=1 where `session_id`='" . $sessionId . "'";
                    $db->query($sqlLevel1);

                    $response = "CON Invalid response. Please type the number for the service you would like to use.
                      1. My Circles
                      2. Join a Circle
                      3. Weather Station
                      4. Maize Prices
                      5. Livestock Services ";
                }

                userLevel($sessionId);

                header('Content-type: text/plain');
                echo $response;

            break;




            // LEVEL 2 - Circle Actions, Invite Code Check, Weather Station L2

            case "2":

                // Update user level  |  $level
                updateLevel(3, $sessionId);

                // GO BACK
                if (strtolower($userResponse) == 'b') {
                  $response = "CON Please choose a service.
                  1. My Circles
                  2. Join a Circle
                  3. Weather Station
                  4. Maize Prices
                  5. Livestock Services ";
                  // Demote user to level 1
                  updateLevel(1, $sessionId);
                }

                // WEATHER STATION LEVEL 2
                elseif ( $textArray[0] == 3)
                {

                    if ($userResponse == 1)
                    {
                      // Check if valid City
                      $sqlmapcheck = "SELECT * FROM coordinates WHERE LOWER(city) LIKE '%" . $userCityL . "%' LIMIT 1";
                      $mapcheckQuery = $db->query($sqlmapcheck);
                      $mapcheckAvailable = $mapcheckQuery->fetch_assoc();

                      // Valid city
                      if ($mapcheckAvailable && $mapcheckAvailable['lon']!= NULL && $mapcheckAvailable['lat']!= NULL) {
                        $response = "END The 8 day forecast for " . ucfirst($userCityL) . " is: \n";
                        require_once('pollen-weather-station.php');
                      }

                      // Invalid City
                      else {
                        $response = "CON We're sorry, but either " . ucfirst($userResponse) . " is not yet supported or it is not a city in Zambia. Please try the closest city to your region:";

                        // Demote user to level 2
                        updateLevel(2, $sessionId);
                      }
                    }

                    else
                    {
                        $userCityL = strtolower($userResponse);

                        // Check if valid City
                        $sqlmapcheck = "SELECT * FROM coordinates WHERE LOWER(city) LIKE '%" . $userCityL . "%' LIMIT 1";
                        $mapcheckQuery = $db->query($sqlmapcheck);
                        $mapcheckAvailable = $mapcheckQuery->fetch_assoc();

                        // Valid city
                        if ($mapcheckAvailable && $mapcheckAvailable['lon']!= NULL && $mapcheckAvailable['lat']!= NULL) {
                          $response = "END The 8 day forecast for " . ucfirst($userResponse) . " is: \n";
                          require_once('pollen-weather-station.php');
                        }

                        // Invalid City
                        else {
                          $response = "CON We're sorry, but either " . ucfirst($userResponse) . " is not yet supported or it is not a city in Zambia. Please try the closest city to your region:";

                          // Demote user to level 2
                          updateLevel(2, $sessionId);
                        }

                    }

                } // End of elseif textarrray =3

                // POLLEN PAY ACTIONS
                elseif ($reverse[1] == 0)
                {

                  // SEND
                  if ($userResponse == 1)
                  {
                    $response = "CON Please type the recipients phone number with the country code an no spaces or symbols (e.g. 260971234567)";
                  }

                  // DEPOSIT
                  elseif ($userResponse == 2)
                  {
                    $response = "CON Please enter the amount (ZWA) you would like to deposit from you mobile money account: ";
                  }

                  // WITHDRAW
                  elseif ($userResponse == 3)
                  {
                    $response = "CON Please enter the amount (ZWA) you would like to withdraw to your mobile money account: ";
                  }

                  // HISTORY
                  elseif ($userResponse == 4)
                  {
                    $response = "END Transaction history: \n";
                    // Loop through txn history
                  }



                    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX


                        // KYC TEST
                        elseif ($userResponse == 5)
                        {
                          // KOTANIPAY API KYC TEST
                          // Get auth token  |  $authToken  (MUST RUN THIS EACH TIME YOU RUN A KP FUNCTION)
                          kpAuth($phoneNumber);

                          // KYC   \  $kycStatus  (either 'success' 'active' )       ******************************************
                          kpKYC($phoneNumber);
                          userBalance($phoneNumber);

                            // SUCCESS
                            if ($kycStatus == 'success')
                            {
                              $response = "END Congrats, you have completed our KYC! Dial back to use our services.";
                            }
                            // ERROR: USER EXISTS
                            elseif ($kycStatus == 'active')
                            {
                              $response = "END Oops, looks like you've already signed up! Dial back to use our services. \n";
                              //print_r($responseData1);

                              // Balance print
                              $response .= "" . $userAddress . "\n" . $userBalanceCUSD . "\n" . $userBalanceCELO ."" ;
                            }
                            // ERROR: ELSE
                            else
                            {
                              $response = "END Oops, looks like we're having some issues. Please try again later or contact support if the issue continues. \n";
                              $response .= "Error: " . $kycStatus['status'] . "" . $responseData . "";
                            }

                        }  // end of KYC test

                    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX


                }

                // CIRCLE ACTIONS (2a)
                elseif ($reverse[1] == 1 && ( $userResponse == 1 || $userResponse == 2 || $userResponse == 3 || $userResponse == 4 || $userResponse == 5 ))
                {

                    // Log $circleSelect
                    logcircleSelect($userResponse, $phoneNumber, $sessionId);

                    // GET CIRCLE BALANCE
                    circleBalance($circleSelect);

                    // Get active proposal  |  $proposalAvailable  |  $voteCheckAvailable  |  $proposalAvailable['txnhash']['phonenumber'] ['action'] ['value']
                    proposalAvailable($circleSelect, $phoneNumber);

                    if ($proposalAvailable != NULL)
                    {

                        // If response is 1/5 and user has not voted
                        if ($voteCheckAvailable == NULL)
                        {
                            //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                            $response = "CON Welcome to " . ucfirst($circleSelect) . ". \n Circle Balance: " . $circleBalance . " ZMW \n Please choose an action:
                           0. Vote
                           1. View Balances
                           2. Pay-in Funds
                           3. Request Funds
                           4. Leave Circle ";
                            //$response .= "5. Go back \n";

                        }

                        else
                        {
                          $response = "CON Welcome to " . ucfirst($circleSelect) . ". \n Circle Balance: " . $circleBalance . " ZMW \n Please choose an action:
                       1. View Balances
                       2. Pay-in Funds
                       3. Request Funds
                       4. Leave Circle ";
                        }
                        // end of votecheck

                    } //end of proposal available

                    else
                    {
                        $response = "CON Welcome to " . ucfirst($circleSelect) . ". \n Circle Balance: " . $circleBalance . " ZMW \n Please choose an action:
                         1. View Balances
                         2. Pay-in Funds
                         3. Request Funds
                         4. Leave Circle ";
                        }
                    }

                //} //end of if ($lv0v == 1){




                ///////////////     ///////////////   ///////////////   ///////////////   ///////////////
  ///////////////       ///////////////         ///////////////       ///////////////       ///////////////       ///////////////
          ///////////////         ///////////////           ///////////////             ///////////////         ///////////////


                // INVITE CODE CHECK
                elseif ($reverse[1] == 2)
                {
                    // Check if circle exists and phone number is valid
                    $sqlJoin = "SELECT * FROM circleInvites WHERE invitee LIKE '%" . $phoneNumber . "%' && circleID LIKE '" . $userResponse . "'";
                    $joinQuery = $db->query($sqlJoin);
                    $joinAvailable = $joinQuery->fetch_assoc();

                    $inviteCode = strtolower($userResponse);

                    // SUCCESSFUL JOIN
                    if ($joinAvailable != NULL && $joinAvailable['status'] != 1)
                    {
                      // Add CIRCLESELECT to db
                      $sqlcircleSelect = "UPDATE session_levels SET circleSelect ='" . $userResponse . "' where `session_id`='" . $sessionId . "'";
                      $db->query($sqlcircleSelect);

                        // Add member to circle via circleMembers table
                        $sqlAddMember = "INSERT INTO `circleMembers`(`circleID`,`phoneNumber`) VALUES('" . $inviteCode . "','" . $phoneNumber . "')";
                        $resultAddMember = mysqli_query($db, $sqlAddMember);

                        // Incriment memberCount in circles table
                        $sqlIncreaseMemberCount = "UPDATE circles SET memberCount = memberCount + 1 WHERE circleID LIKE '" . $userResponse . "'";
                        $resultIncreaseMemberCount = mysqli_query($db, $sqlIncreaseMemberCount);

                        // Void Invite Code
                        $sqlInviteVoid = "UPDATE circleInvites SET status = 1 WHERE circleID LIKE '" . $userResponse . "' && invitee LIKE '%" . $phoneNumber . "%'";
                        $resultInviteVoid = mysqli_query($db, $sqlInviteVoid);

                        $response = "CON Congrats! You have successfully joined " . ucfirst($userResponse) . ". Please choose an action:
                     1. View Balances
                     2. Pay-in Funds
                     3. Request Funds ";

                    }

                    elseif ($joinAvailable != NULL && $joinAvailable['status'] == 1)
                    {
                      $response = "CON You have already joined " . ucfirst($userResponse) . ". Please choose an action:
                      1. My Circles
                      2. Join a Circle
                      3. Weather Station
                      4. Maize Prices
                      5. Livestock Services ";

                      // Demote user to level 1
                      $sqlLevel1 = "UPDATE `session_levels` SET `level`=1 where `session_id`='" . $sessionId . "'";
                      $db->query($sqlLevel1);
                    }

                    // DENIAL .... try to add two denials (one for invalid # and one for invalid code)
                    else
                    {
                        $response = "END Sorry, your invite code is not valid or you are not a member of this circle.";
                    }
                }


                ///////////////     ///////////////   ///////////////   ///////////////   ///////////////
  ///////////////       ///////////////         ///////////////       ///////////////       ///////////////       ///////////////
          ///////////////         ///////////////           ///////////////             ///////////////         ///////////////




                // WEATHER STATION HERE

                // MAIZE PRICES
                elseif ($reverse[1] == 4 && ( $userResponse == 1 || $userResponse == 2 )) // || $reverse[2] == 4
                {
                  // Get maize prices  |  $maizePrices
                  maizePrices();
                  $response = $maizePrices;
                }

                // LIVESTOCK SERVICE SELECT
                elseif ($reverse[1] == 5 || ($reverse[3] == 5 && $reverse[1] == 'b'))
                {
                  // Update to level 6 (cattle level)
                  updateLevel(6, $sessionId);

                  // Common Diseases
                  if ($userResponse == 1) {
                    $response = "CON Select a disease to learn more, or enter b to go back:
                    1. Foot and Mouth
                    2. East Coast Fever
                    3. Mastitis ";
                  }

                  // Animal Feeding
                  elseif ($userResponse == 2) {
                    $response = "CON Select an option to learn more, or enter b to go back:
                    1. Types of Food
                    2. Water Intake and Hygiene
                    3. Calf Management ";
                  }

                  // Contact a Vet
                  elseif ($userResponse == 3) {
                    $response = "CON Select a vet for specialties and contact info, or enter b to go back:
                    1. Aison Chizu - Kasisi
                    2. Aldare - Lusaka
                    3. Charles - Kasenga ";
                  }

                  // Animal Extension Services
                  elseif ($userResponse == 4) {
                    $response = "CON Select a service for more info, or enter b to go back:
                    1. Diseases and Vaccines
                    2. Breeding Program
                    3. Genetics ";
                  }

                  // Tell us your animal experience
                  elseif ($userResponse == 5) {
                    $response = "CON Please share any tips, tricks or best practices you've learned to help others: ";
                  }

                  // What would you like to learn?
                  elseif ($userResponse == 6) {
                    $response = "CON Please enter the digits for which services you would like to learn more about. You can enter multiple choices:
                    1. Feeding
                    2. Cattle Management
                    3. Artificial Insemination
                    4. Calves Management
                    5. Breeding ";
                  }

                  // Invalid responses
                  else {
                    $response = "CON Invalid response. Please enter the digit for a service below:
                      1. Common Diseases
                      2. Animal Feeding
                      3. Contact a Vet
                      4. Access Extension Services
                      5. Tell us your animal experience
                      6. What would you like to learn? ";
                    // Demote User to level 2
                    updateLevel(2, $sessionId);
                  }
                }

                else
                {
                  $response = "CON Invalid input. Please try again.
                  1. My Circles
                  2. Join a Circle
                  3. Weather Station
                  4. Maize Prices
                  5. Livestock Services ";

                  // Demote user to level 1
                  updateLevel(1, $sessionId);
                }

                userLevel($sessionId);

                header('Content-type: text/plain');
                echo $response;

            break;




            // LEVEL 3 - View Balances, Pay in Funds, Request Funds, Leave Circle, Serve Proposal

            case "3":

                // Graduate user to level 4
                updateLevel(4, $sessionId);

                // ReFetch circleSelect data
                circleSelect($sessionId);

                // POLLEN PAY
                if ($textArray[0] == 0 || $reverse[2] == 0)
                {
                  // SEND (amount)
                  if ($reverse[1] == 1)
                  {
                    $response = "CON Enter the amount (ZWA) you would like to send: ";
                  }

                  // DEPOSIT (pin)
                  elseif ($reverse[1] == 2)
                  {
                    $response = "CON Please enter your pin to deposit " .$userResponse. " ZWA: ";
                  }

                  // WITHDRAW (pin)
                  elseif ($reverse[1] == 3)
                  {
                    $response = "CON Please enter your pin to withdraw " .$userResponse. " ZWA: ";
                  }

                  else {
                    $response = "CON Error";
                  }

                  // ERROR else
                }


                // SAVINGS CIRCLES
                elseif ($textArray[0] == 1 || $reverse[2] == 1)
                {
                  // VIEW BALANCES
                  if ($userResponse == 1)
                  {
                      // Get circle member balances  |  $circleMemberBalances
                      circleMemberBalances($circleSelect);
                      $response = "CON " . ucfirst($circleSelect) . " balances \n";
                      $response .= $circleMemberBalances;
                  }

                  // PAY-IN FUNDS
                  elseif ($userResponse == 2)
                  {
                      //CON enter amount, confirm & pin, receipt
                      $response = "CON Please enter an amount to pay-in (KWACHA): \n";
                  }

                  // REQUEST FUNDS
                  elseif ($userResponse == 3)
                  {
                    // CHECK IF PROPOSAL AVAILABLE
                    proposalAvailable($circleSelect, $phoneNumber);

                    if ($proposalAvailable != NULL)
                    {
                      $response = "END You cannot request funds when another withdrawal request is active for " . ucfirst($circleSelect) . ". \n";
                      //$response .= "Reply b to go back.";
                    }

                    else {
                      //CON enter amount, confirm & pin, receipt
                      $response = "CON Please enter an amount to request (KWACHA): \n";
                    }


                  }

                  elseif ($userResponse == 4) {

                  }

                  // LEAVE CIRCLE
                  elseif ($userResponse == 5)
                  {

                      // Check if user is in debt !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                      // Serve Menu
                      $response = "CON Are you sure you want to leave " . $circleSelect . "?
                  1. Yes
                  2. No ";
                  }

                  // SERVE PROPOSAL
                  elseif ($userResponse == 0)
                  {

                      // Get active proposal  |  $proposalAvailable  |  $voteCheckAvailable  |  $proposalAvailable['txnhash']['phonenumber'] ['action'] ['value']
                      proposalAvailable($circleSelect, $phoneNumber);

                      // CHECK IF USER HAS ALREADY VOTED
                      //$sqlVoteCheck = "SELECT * FROM votes WHERE circleID LIKE '%" . $circleSelect . "%' && phonenumber LIKE '%" . $phoneNumber . "%' && txnhash LIKE '%" . $txnhash . "%'";
                      //$voteCheckQuery  = $db->query($sqlVoteCheck);
                      //$voteCheckAvailable = $voteCheckQuery->fetch_assoc();

                      if ($proposalAvailable != NULL ) {  //&& $voteCheckAvailable != NULL

                          // Fetch proposer's name
                          $sqlProposerName = "SELECT username FROM users WHERE phonenumber LIKE '%" . $proposer . "%' ";
                          $resultProposerName = mysqli_query($db, $sqlProposerName);
                          $proposerNameAvailable = mysqli_fetch_assoc($resultProposerName);
                          $proposerName = ucfirst($proposerNameAvailable['username']);

                          // User has voted
                          if ($voteCheckAvailable != NULL)
                          {
                            $response = "END There are no proposals for you to vote on.";
                            //demote and list circle select menu
                          }

                          else {

                            // WITHDRAWAL
                            if ($action == "Withdrawal")
                            {
                                $response = "CON " . $proposerName . " has requested " . $value . " ZMW from " . ucfirst($circleSelect) . "\n";
                            }

                            // MEMBER ADD
                            elseif ($action == "MemberAdd")
                            {
                                $response = "CON " . $proposerName . " has requested to add " . $value . " to " . ucfirst($circleSelect) . "\n";
                            }

                            $response .= "1. Vote YES \n";
                            $response .= "0. Vote NO \n";

                        }

                      }

                      // You have already VOTED
                      //elseif ($proposalAvailable != NULL)
                      //{
                        //$response = "END You have already voted on the active proposal(s). Check SMS for final results.";
                      //}

                      // NO ACTIVE PROPOSALS
                      else
                      {
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
                } //end of SAVINGS CIRCLES

                else
                {
                  $response = "CON Please select a service:
                  1. My Circles
                  2. Join a Circle
                  3. Weather Station
                  4. Maize Prices
                  5. Livestock Services ";

                  //Demote user to level 1
                  updateLevel(1, $sessionId);
                }

                // Update PHP level variable
                userLevel($sessionId);
                header('Content-type: text/plain');
                echo $response;

            break;


            // LEVEL 6 : CATTLE SERVICES
            case "6": //cattle

              // GO BACK TO CATTLE SERVICE SELECT
              if (strtolower($userResponse == 'b'))
              {
                $response = "CON Please select a service:
                1. Common Diseases
                2. Animal Feeding
                3. Contact a Vet
                4. Access Extension Services
                5. Tell us your animal experience
                6. What would you like to learn? ";

              // Demote User to level 2
              updateLevel(2, $sessionId);
              }

              else {
                $response = "CON Invalid input. Please try again.
                1. My Circles
                2. Join a Circle
                3. Weather Station
                4. Maize Prices
                5. Livestock Services";

                // Demote user to level 1
                updateLevel(1, $sessionId);
              }

              userLevel($sessionId);
              header('Content-type: text/plain');
              echo $response;

            break;




            case "4":

                // ReFetch circleSelect data
                circleSelect($sessionId);

                // UPGRADE TO LEVEL 5
                updateLevel(5, $sessionId);

                // GO BACK
                //if (strtolower($userResponse) == 'b') {
                  //$truncate1 = array_pop($textArray);
                  //$truncate2 = array_pop($textArray);
                  //$truncate3 = array_pop($textArray);

                  // Demote user to level 3
                  //$sqlLevel3 = "UPDATE `session_levels` SET `level`=3 where `session_id`='" . $sessionId . "'";
                  //$db->query($sqlLevel3);
                //}

                // POLLEN PAY
                if ($textArray[0] == 0 || $reverse[3] == 0)
                {
                  // SEND (pin)
                  if ($reverse[2] == 1)
                  {
                    $response = "CON Please enter your pin to send " .$userResponse. " ZWA to ".$reverse[1]. ": ";
                  }

                  // DEPOSIT (API call and receipt)
                  elseif ($reverse[2] == 2)
                  {
                    // PIN CORRECT
                    if ($userResponse == $userPin)
                    {
                      // Execute KOTANIPAY request

                      // SUCCESSFUL
                      $response = "END You have successfully deposited " . $reverse[1] . " ZWA to Pollen.";
                      // ERROR

                    }
                    else
                    {
                      $response = "END The pin you entered is not correct. Please dial back and try again. ";
                    }
                  }


                  // WITHDRAW (API call and receipt)
                  elseif ($reverse[2] == 3)
                  {
                    // PIN CORRECT
                    if ($userResponse == $userPin)
                    {
                      // Execute KOTANIPAY request

                      // SUCCESSFUL
                      // if(success)
                      $response = "END You have successfully withdrawn " . $reverse[1] . " ZWA from Pollen to your mobile money account.";
                      // ERROR

                    }
                    else
                    {
                      $response = "END The pin you entered is not correct. Please dial back and try again. ";
                    }
                  }
                }

                // SAVINGS CIRCLES
                elseif ($textArray[0] == 1 || $reverse[3] == 1)
                {

                  // Member Circle Txn History
                  if ($reverse[1] == 1)
                  {
                      // query specific user data...likely requires you to log what member they selected

                  }

                  // Circle Deposit Receipt
                  elseif ($reverse[1] == 2)
                  {

                      circleDeposit($userResponse, $phoneNumber, $circleSelect);

                      $response = "END Congrats! You have deposited " . $userResponse . " into " . ucfirst($circleSelect) . "\n";
                      $response .= "Circle Balance: " . $circleBalance . " ZMW \n";
                  }

                  // Money Request Reason
                  elseif ($reverse[1] == 3)
                  {

                      // Query circleBalance, check if userResponse > circleBalance
                      $sqlCircleBalance = "SELECT balance FROM circles WHERE circleID LIKE '%" . $circleSelect . "%'";
                      $resultCircleBalance = mysqli_query($db, $sqlCircleBalance);
                      $circleBalanceAvailable = mysqli_fetch_assoc($resultCircleBalance);
                      $circleBalance = $circleBalanceAvailable['balance'];

                      if ($circleBalance >= $userResponse)
                      {
                        $response = "CON What do you need the funds for?";
                      }

                      else
                      {
                          $response = "END Circle funds not sufficient. You can request a max of " . $circleBalance . " ZMW \n";
                      }

                  }

                  // VOTE !!!!!!
                  elseif ($reverse[1] == 0)
                  {
                      // LOG PROPOSAL SELECT
                      // Query txnhash
                      // GET PROPOSAL INFO....change to use txnhash
                      $sqlProposal = "SELECT * FROM circleProposals WHERE circleID LIKE '%" . $circleSelect . "%' && result is NULL";
                      $proposalQuery = $db->query($sqlProposal);
                      $proposalAvailable = $proposalQuery->fetch_assoc();

                      // WHILE LOOP SO WE CAN DO MULTI
                      // GET PROPOSAL Data
                      $txnhash = $proposalAvailable['txnhash'];
                      $proposer = $proposalAvailable['phonenumber'];
                      $action = $proposalAvailable['action'];
                      $value = $proposalAvailable['value'];

                      // GET GOVERNANCE DATA FROM CIRCLES (QUORUM, THRESHOLD, memberCount)
                      $sqlGovernance = "SELECT * FROM circles WHERE circleID LIKE '%" . $circleSelect . "%'";
                      $governanceQuery = $db->query($sqlGovernance);
                      $governanceAvailable = $governanceQuery->fetch_assoc();
                      $quorum = $governanceAvailable['quorum'];
                      $threshold = $governanceAvailable['threshold'];
                      $memberCount = $governanceAvailable['memberCount'];

                      // VALID VOTE
                      if ($userResponse == 1 || $userResponse == 0)
                      {

                          // YES VOTE
                          if ($userResponse == 1)
                          {
                              // INCRIMENT YES COUNT
                              $sqlYesIncriment = "UPDATE `circleProposals` SET `yesCount` = `yesCount` + 1 where txnhash LIKE '%" . $txnhash . "%'";
                              $db->query($sqlYesIncriment);

                              // LOG VOTE ... $vote = vote, txnhash
                              $sqlVoteLog = "INSERT INTO `votes`(`txnhash`,`phonenumber`,`vote`) VALUES('" . $txnhash . "','" . $phoneNumber . "','YES')";
                              $db->query($sqlVoteLog);

                              // INCRIMENT VOTE COUNT IN PROPOSALS TABLE
                              $sqlVoteIncriment = "UPDATE `circleProposals` SET `voteCount` = `voteCount` + 1 where txnhash LIKE '%" . $txnhash . "%'";
                              $db->query($sqlVoteIncriment);

                              // GET NEW YESCOUNT AND VOTECOUNT
                              $votesQuery = $db->query($sqlProposal);
                              $votesAvailable = $votesQuery->fetch_assoc();
                              $voteCount = $votesAvailable['voteCount'];
                              $yesCount = $votesAvailable['yesCount'];

                              // QUORUM MET
                              if (($voteCount / $memberCount) >= $quorum)
                              {

                                  // THRESHOLD MET
                                  if (($yesCount / $voteCount) >= $threshold)
                                  {
                                      $result = "APPROVED";

                                      // KOTANIPAY API

                                  }

                                  // THRESHOLD NOT MET
                                  elseif ($voteCount >= 1)
                                  {
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

                                  while ($circleMembers = mysqli_fetch_assoc($resultCircleMembers))
                                  {
                                      $circleMembers = $circleMembers['phonenumber'];

                                      // WITHDRAWAL
                                      if ($action == "Withdrawal")
                                      {
                                          $message = "" . $proposerName . "'s request to withdraw " . $value . " ZMW from " . ucfirst($circleSelect) . " was " . $result . "";

                                          // Fetch current txn txnhash
                                          $sqlTxnCount = "SELECT txnhash FROM circleTxns";
                                          $txnCountQuery = $db->query($sqlTxnCount);
                                          $txnCount = mysqli_num_rows($txnCountQuery);
                                          $newTxnCount = $txnCount++;

                                          // Log transaction in DB
                                          $sqlTxn = "INSERT INTO `circleTxns`(`dw`,`txnhash`,`phoneNumber`,`circleID`,`amount`) VALUES('W','" . $newTxnCount . "','" . $phoneNumber . "','" . $circleSelect . "','" . $value . "')";
                                          $resultTxn = mysqli_query($db, $sqlTxn);

                                          // Update circle balance
                                          $sqlBalanceUpdate = "UPDATE `circles` SET `balance`= `balance` - '" . $value . "' where `circleID`='" . $circleSelect . "'";
                                          $resultBalanceUpdate = mysqli_query($db, $sqlBalanceUpdate);

                                          // Update user-circle balance
                                          $sqlUserBalanceUpdate = "UPDATE `circleMembers` SET `userBalance`= `userBalance` - '" . $value . "' where `circleID`='" . $circleSelect . "' && `phonenumber` LIKE '%" . $phoneNumber . "%'";
                                          $resultUserBalanceUpdate = mysqli_query($db, $sqlUserBalanceUpdate);
                                      }

                                      elseif ($action == "MemberAdd")
                                      {
                                          $message = "" . $proposerName . "'s request to add " . $value . " to " . ucfirst($circleSelect) . " was " . $result . "";
                                      }

                                      $recipient = $circleMembers;
                                      $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                                      //$gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                                      $gateway->sendMessage($recipient, $message);
                                  }

                              } // end of quroum met
                              // QUORUM NOT MET
                              else
                              {

                              }

                          } // end of yes vote
                          // NO VOTE
                          else
                          {
                              // LOG VOTE ... $vote = vote, txnhash
                              $sqlVoteLog = "INSERT INTO `votes`(`txnhash`,`phoneNumber`,`vote`) VALUES('" . $txnhash . "','" . $phoneNumber . "','NO')";
                              $resultVoteLog = mysqli_query($db, $sqlVoteLog);

                              // INCRIMENT VOTE COUNT IN PROPOSALS TABLE
                              $sqlVoteIncriment = "UPDATE `circleProposals` SET `voteCount` = `voteCount` + 1 where txnhash LIKE '%" . $txnhash . "%'";
                              $db->query($sqlVoteIncriment);

                              // GET NEW YESCOUNT AND VOTECOUNT
                              $votesQuery = $db->query($sqlProposal);
                              $votesAvailable = $votesQuery->fetch_assoc();
                              $voteCount = $votesAvailable['voteCount'];
                              $yesCount = $votesAvailable['yesCount'];

                              // QUORUM MET
                              if (($voteCount / $memberCount) >= $quorum)
                              {

                                  // THRESHOLD MET
                                  if (($yesCount / $voteCount) >= $threshold)
                                  {
                                      $result = "APPROVED";

                                  }

                                  // THRESHOLD NOT MET
                                  else
                                  {
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

                                  while ($circleMembers = mysqli_fetch_assoc($resultCircleMembers))
                                  {
                                      $circleMembers = $circleMembers['phonenumber'];
                                      // WITHDRAWAL
                                      if ($action == "Withdrawal")
                                      {
                                          $message = "" . $proposerName . "'s request to withdraw " . $value . " ZMW from " . ucfirst($circleSelect) . " was " . $result . "";
                                      }
                                      elseif ($action == "MemberAdd")
                                      {
                                          $message = "" . $proposerName . "'s request to add " . $value . " to " . ucfirst($circleSelect) . " was " . $result . "";
                                      }
                                      $recipient = $circleMembers;
                                      $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                                      //$gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                                      $gateway->sendMessage($recipient, $message);
                                  }

                              } // end of quroum met
                              // QUORUM NOT MET
                              else
                              {

                              }

                          } // end of no vote


                          // ACTIONS THAT EXECUTE FOR BOTH YES AND NO VOTES
                          // Print results
                          $response = "END Thank you for voting. Check SMS for final results.";

                      } //end of valid vote
                      // ERROR VOTE
                      else
                      {
                          $response = "END Error. You must vote with 1 (YES) or 0 (NO). \n Please dial back and try again.";
                      }

                  } // end of VOTE
                } // END OF SAVINGS CIRCLES

                header('Content-type: text/plain');
                echo $response;

            break;



           case "5":

            // POLLEN PAY SEND (API call and receipt)
            if ($textArray[0] == 0 || $reverse[4] == 0)
            {
              // PIN CORRECT
              if ($userResponse == $userPin)
              {
                // Execute KOTANIPAY request
                $chauth = curl_init('https://europe-west3-kotanimac.cloudfunctions.net/savingsacco/api/login');
                curl_setopt_array($chauth, array(
                  CURLOPT_POST => TRUE,
                  CURLOPT_RETURNTRANSFER => TRUE,
                   ));

                  $authResponse = curl_exec($chauth);
                  $authData = json_decode($authResponse, TRUE);

                // Define Auth Token
                $authToken = $authData['token'];

                // Define SEND VARIABLES
                //$recipient = '0720670789';
                $phoneNumber2 = '254701234567';
                $recipient = $reverse[2];
                $amount = $reverse[1];

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

                //$response = "CON A ".$responseData['status']."\n";
                //print_r($responseData, TRUE);

                  // SUCCESS
                  if ($responseData['status'] == 'success')
                  {
                    $response = "END You have successfully sent " . $reverse[1] . " ZWA to " . $reverse[2] . ". ";
                  }

                  // ERROR 1
                  elseif ($responseData['status'] == 'unverified')
                  {
                    $response = "END Oops, you have not signed up for Pollen yet! Please dial back and sign up.";
                  }

                  // ERROR 2
                  elseif ($responseData['status'] == 'error')
                  {
                    $response = "END Oops, " . $recipient . " is not registered with Pollen. \n";
                  }

                  // ERROR 2
                  elseif ($responseData['status'] == 'failed')
                  {
                    $response = "END Oops, you don't have enough funds to fufill this request. ";
                    $response .= "Error: " . $responseData['status'] . "";
                  }


                // ERROR

              }

              // INVALID PIN
              else
              {
                $response = "END The pin you entered is not correct. Please dial back and try again. ";
              }
            }

            // WITHDRAWAL RECEIPT
            elseif ($textArray[0] == 1 || $reverse[4] == 1) {

              // ReFetch circleSelect data
              $sqlselect = "SELECT * FROM session_levels WHERE session_id LIKE '%" . $sessionId . "%'";
              $selectQuery = $db->query($sqlselect);
              $selectAvailable = $selectQuery->fetch_assoc();
              $circleSelect = $selectAvailable['circleSelect'];

              // Query circleBalance, check if userResponse > circleBalance
              $sqlCircleBalance = "SELECT balance FROM circles WHERE circleID LIKE '%" . $circleSelect . "%'";
              $resultCircleBalance = mysqli_query($db, $sqlCircleBalance);
              $circleBalanceAvailable = mysqli_fetch_assoc($resultCircleBalance);
              $circleBalance = $circleBalanceAvailable['balance'];

              // Fetch current # of proposals so we can update txnhash
              $sqlProposalCount = "SELECT txnhash FROM circleProposals";
              //$sqlProposalCount = "SELECT txnhash FROM circleProposals WHERE circleID LIKE '%" . $circleSelect . "%'";
              $proposalCountQuery = $db->query($sqlProposalCount);
              $proposalCount = mysqli_num_rows($proposalCountQuery);
              $newProposalCount = $proposalCount++;

              // Log withdrawal request in proposals db
              $sqlWithdrawalRequest = "INSERT INTO `circleProposals`(`circleID`,`txnhash`,`phonenumber`,`action`,`value`,`desc`) VALUES('" . $circleSelect . "','" . $newProposalCount . "','" . $phoneNumber . "','Withdrawal','" . $reverse[1] . "','" . $userResponse . "')";
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
              while ($circleMembers = mysqli_fetch_assoc($resultCircleMembers))
              {
                  $circleMembers = $circleMembers['phonenumber'];

                  // SEND SMS VIA AT GATEWAY
                  $message = "Proposal: Withdraw Funds \n Circle: " . $circleSelect . "\n  Requestor: " . $requesterName . ", " . $phoneNumber . " \n Amount: " . $reverse[1] . " ZMW \n Reason: " . $userResponse . "\n Dial into USSD to vote *384*313233#";
                  $recipient = $circleMembers;
                  $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                  //$gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                  $gateway->sendMessage($recipient, $message);
              }
              $response = "END Your request for " . $reverse[1] . " ZMW from " . ucfirst($circleSelect) . " has been submitted for vote. \n";
              $response .= "Proposal ID: " . $newProposalCount . "\n";
              $response .= "" . $username . "";
              //$response .= "" . $proposalCount . "";

            }

            header('Content-type: text/plain');
            echo $response;

            break;


        } // End of Switch (LEVEL)

    } //end of if ($userAvailable && $userAvailable['city'])

// Register
    else
    {
        // Register the user
        if ($userResponse == "")
        {
            // On receiving a Blank. Advise user to input correctly based on level
            switch ($level)
            {
                case 0:
                    // Graduate the user to the next level, so you dont serve them the same menu
                    $sql10b = "INSERT INTO `session_levels`(`session_id`, `phoneNumber`,`level`) VALUES('" . $sessionId . "','" . $phoneNumber . "', 1)";
                    $db->query($sql10b);

                    // Insert the phoneNumber, since it comes with the first POST
                    $sql10c = "INSERT INTO `users`(`phonenumber`) VALUES ('" . $phoneNumber . "')";
                    $db->query($sql10c);

                    // Serve the menu request for name
                    $response = "CON Welcome to Pollen! Please enter your first name.";

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

        else
        {
            //11. Update User table based on input to correct level
            switch ($level)
            {
                case "0":
                    // Serve the menu request for name
                    $response = "END This level should not be seen...";

                    // Print the response onto the page so that our gateway can read it
                    header('Content-type: text/plain');
                    echo $response;
                break;

                case "1":
                    // Update Name, Request for Last name
                    $firstName = ucfirst($userResponse);
                    $sql11b = "UPDATE `users` SET `username`='" . $firstName . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
                    $db->query($sql11b);

                    //11c. We graduate the user to the last name level
                    $sql11c = "UPDATE `session_levels` SET `level`=2 WHERE `session_id`='" . $sessionId . "'";
                    $db->query($sql11c);

                    //We request for last name
                    $response = "CON Hi " . ucfirst($userResponse) . "! Please enter your last name.";

                    // Print the response onto the page so that our gateway can read it
                    header('Content-type: text/plain');
                    echo $response;
                break;

                case "2":
                    // Update Last Name, Request for city
                    $lastName = ucfirst($userResponse);
                    $sql22 = "UPDATE `users` SET `lastName`='" . $lastName . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
                    $db->query($sql22);

                    //11c. We graduate the user to the city level
                    $sql2a = "UPDATE `session_levels` SET `level`=3 WHERE `session_id`='" . $sessionId . "'";
                    $db->query($sql2a);

                    //We request for the city
                    $response = "CON Hi " . ucfirst($reverse[1]) . " " . ucfirst($userResponse) . "! Please enter your city.";

                    // Print the response onto the page so that our gateway can read it
                    header('Content-type: text/plain');
                    echo $response;
                break;

                case "3":

                    // Format userResponse
                    $cityInput = ucfirst($userResponse);
                    // Update city, Request for secretPin
                    $sql11d = "UPDATE `users` SET `city`='" . $cityInput . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
                    //$sql11d = "UPDATE `users` SET `pin`='".$userResponse."' WHERE `phonenumber` LIKE '%". $phoneNumber ."%'";
                    $db->query($sql11d);

                    // We graduate the user to the city level
                    $sqlsecretPinLevel = "UPDATE `session_levels` SET `level`=4 WHERE `session_id`='" . $sessionId . "'";
                    $db->query($sqlsecretPinLevel);

                    //We request for the secretPin
                    $response = "CON Welcome from " . ucfirst($userResponse) . ". Last question: what crop(s) do you grow? Enter the digits for all that apply (e.g. 136)
                    1. Maize (White)
                    2. Roller Maize Meal
                    3. Cotton
                    4. Soyabeans
                    5. Bees
                    6. Cattle
                    7. Chickens
                    8. Other ";

                    // Print the response onto the page so that our gateway can read it
                    header('Content-type: text/plain');
                    echo $response;
                break;

                case "4":
                    // Update city, Request for secretPin
                    $sql4e = "UPDATE `users` SET `crops`='" . $userResponse . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
                    $db->query($sql4e);

                    // We graduate the user to pin level level
                    $sqlsecretPinLevel = "UPDATE `session_levels` SET `level`=5 WHERE `session_id`='" . $sessionId . "'";
                    $db->query($sqlsecretPinLevel);

                    //We request for the secretPin
                    $response = "CON Nice! Before we continue, please set a 4-digit secret pin. We will ask you for it whenever you perform an important action (i.e. requesting funds).";

                    // Print the response onto the page so that our gateway can read it
                    header('Content-type: text/plain');
                    echo $response;
                break;

                case "5":
                    $sql13 = "UPDATE `users` SET `pin`='" . $userResponse . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
                    $db->query($sql13);
                    // We graduate the user to level 8
                    $sql14 = "UPDATE `session_levels` SET `level`=8 WHERE `session_id`='" . $sessionId . "'";
                    $db->query($sql14);
                    $response = "END Congratulations, you're registered with Pollen! \n \n Do not forget your secret pin (" . $userResponse . ")! \n \n Please dial back to view the main menu.";
                    //$response = "CON Let's make sure you remember it! Please enter you secret pin.";
                    //header('Content-type: text/plain');
                    echo $response;

                    // Email admin
                    $msg = "Name: " . ucfirst($textArray[0]) . " " . ucfirst($textArray[1]) . "
                            Location: " . ucfirst($textArray[2]) . "
                            Crops: " . $textArray[3] . "
                            Phone Number: " . $phoneNumber ;
                    $msg = wordwrap($msg,70);
                    mail("ericphotons@gmail.com","New User from ".$textArray[2],$msg);

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
        }
    }
}

?>
