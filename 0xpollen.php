<?php
// ensure this code runs only after a POST from AT
if (!empty($_POST))
{
    //require_once ('USSD-dbConnect.php');
    require_once ('gd-dbConnect.php');
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

      // Get ZWK/USD FX Rate (output = $fxRate)
      fxRates();

      // Reverse Text Array for backtracking
      $reverse = array_reverse($textArray);

    // Check if the user is available (yes)->Serve the menu; (no)->Register the user
    if ($userAvailable && $userAvailable['city'] != NULL && $userAvailable['username'] != NULL && $userAvailable['pin'] != NULL)
    {
        switch ($level)
        {


            // ---------------------------------------------------------------------------------

            // LEVEL 0 - HOME MENU

            case "0":
                // Initiate user session
                newSession($sessionId, $phoneNumber);

                //Serve our services menu
                $response = "CON Hi " . ucfirst($userName) . ". Welcome back! \n Please choose a service.
                  1. Saving Circles
                  2. Send a Payment
                  3. Request a Payment
                  4. View Balances
                  5. Deposit/Withdraw MoMo
                  6. Help ";

                userLevel($sessionId);
                header('Content-type: text/plain');
                echo $response;

            break;


            // ---------------------------------------------------------------------------------



            // LEVEL 1 - Circle Select, Send Payment, Request Payment, View Balances, Help

            case "1":

              // CIRCLE SELECT
              if ($userResponse == 1)
              {
                  updateLevel("circleSelect", $sessionId);
                  userCirclesProgress($phoneNumber);

                  // User IS in a circle
                  if($userCircles != NULL) {
                    daysRemainingMonth();
                    $response = "CON Please choose a circle.\n";
                    $response .= "(" . $daysRemainingMonth . " days left for this period) \n";
                    $response .= $userCircles;
                    $response .= "" . $r . ". Join a Circle";
                  }

                  // User is NOT in a circle
                  else {
                    updateLevel(1, $sessionId);

                    $response = "CON You are not in any circles. Please join a circle using an invite code provided by an existing member.
                      1. Saving Circles
                      2. Send a Payment
                      3. Request a Payment
                      4. View Balances
                      5. Deposit/Withdraw MoMo
                      6. Help ";
                  }

              }

              // SEND A PAYMENT
              elseif ($userResponse == 2)
              {
                  updateLevel("sendPayment", $sessionId);
                  $response = "CON Please enter the phone number of your recipient (e.g. 260971234567):\n";
              }

              // REQUEST A PAYMENT
              elseif ($userResponse == 3)
              {
                  updateLevel("requestPayment", $sessionId);
                  $response = "CON Please enter the phone number you wish to request money from (e.g. 260971234567):\n";
              }

              // VIEW BALANCES
              elseif ($userResponse == 4)
              {
                updateLevel("viewBalances", $sessionId);
                // make a function to output an array of user circleNames (and get steve to make one)
                // run each index of the array through kpGetSaccoInfo (get $balance)

                // Get user wallet balance
                kpAuth($phoneNumber);
                userBalance($authToken, $phoneNumber);

                //Get FX RATE
                //fxRates();

                $walletBalance = $userBalanceCUSD * $fxRate;
                $walletBalance = round($walletBalance, 2);

                userCircleBalances($phoneNumber);
                userCirclesProgress($phoneNumber);

                  $response = "CON Wallet Balance: K" . $walletBalance . "\n";
                  $response .= $userCircleBalances;
                  //$response .= $userCircles;
              }

              // MOMO
              elseif ($userResponse == 5)
              {
                updateLevel("momo", $sessionId);

                // Get user wallet balance
                kpAuth($phoneNumber);
                userBalance($authToken, $phoneNumber);

                //Get FX RATE
                //fxRates();

                $walletBalance = $userBalanceCUSD * $fxRate;
                $walletBalance = round($walletBalance, 2);
                $response = "CON Convert MoMo to USD or vice versa. ";
                $response .= "(Pollen Balance: K" . $walletBalance . ")\n";
                $response .= "1. Deposit MoMo \n";
                $response .= "2. Withdraw to MoMo\n";
                //$response .= "" . $fxRate . "\n";
                //$response .= "" . $userBalanceCUSD . "";
                //$response .= "" . $userAddress . "";

              }

              // HELP
              elseif ($userResponse == 6)
              {
                updateLevel("help", $sessionId);

                $response = "CON How may we help you?
                  1. What are saving circles?
                  2. How do Pollen circles work?
                  3. Can someone steal my money?
                  4. How do I join my first circle?
                  5. Can I make my own circle?
                  6. I forgot my secret pin!
                  7. I lost my phone!
                  8. Other help";
              }

              // CREATE CIRCLE
              elseif ($userResponse == 333)
              {
                  updateLevel("createCircle", $sessionId);
                  $response = "CON Enter a circle name:";
              }

              else
              {
                  updateLevel(1, $sessionId);

                  $response = "CON Hi " . ucfirst($userName) . ". Welcome back! \n Please choose a service.
                          1. Saving Circles
                          2. Send a Payment
                          3. Request a Payment
                          4. View Balances
                          5. Deposit/Withdraw MoMo
                          6. Help ";
              }

              userLevel($sessionId);

              header('Content-type: text/plain');
              echo $response;

            break;



          // ---------------------------------------------------------------------------------



            // CIRCLE SELECT - Join Circle + Circle Actions

            case "circleSelect":

              logcircleSelect($userResponse, $phoneNumber, $sessionId);
              updateLevel("circleActions", $sessionId);

              userCirclesProgress($phoneNumber);

              // Join new Circle
              if ($userResponse == $r) {
                updateLevel("joinCircle", $sessionId);
                $response = "CON Please enter your invite code: \n";
              }



              // Circle Actions
              elseif ($userResponse < $r) {

                //updateLevel("circleActions", $sessionId);

                circleSelect($sessionId);
                circleBalance($circleSelect);

                // KotaniPay STUFF
                //kp2Auth($phoneNumber);
                //kpGetSaccoInfo($circleSelect, $authToken);
                // $balance

                // Get active proposal  |  $proposalAvailable  |  $voteCheckAvailable  |  $proposalAvailable['txnhash']['phonenumber'] ['action'] ['value']
                proposalAvailable($circleSelect, $phoneNumber);

                // Get user debts outstanding for a specific circle  |  $debtsCheckAvailable (== NULL?)  |  $userCircleDebt
                getUserCircleDebt($phoneNumber, $circleSelect);

                if ($proposalAvailable != NULL && $debtsCheckAvailable != NULL)
                {

                    // If response is 1/5 and user has not voted
                    if ($voteCheckAvailable == NULL)
                    {
                      //updateLevel("vote", $sessionId);
                      //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                      $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                       11. Pay off Loan: K" . $userCircleDebt . "!
                       0. Vote
                       1. Deposit Funds
                       2. Request Loan
                       3. Active Loans
                       4. Balances
                       5. Deposit History
                       6. Previous Proposals
                       7. Invite New Member
                       8. Leave Circle";

                    }

                    else
                    {
                      $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                      11. Pay off Loan: K" . $userCircleDebt . "!
                      1. Deposit Funds
                      2. Request Loan
                      3. Active Loans
                      4. Balances
                      5. Deposit History
                      6. Previous Proposals
                      7. Invite New Member
                      8. Leave Circle";
                    }
                    // end of votecheck

                } //end of proposal available

                elseif ($proposalAvailable != NULL)
                {

                    // If response is 1/5 and user has not voted
                    if ($voteCheckAvailable == NULL)
                    {
                      //updateLevel("vote", $sessionId);
                      //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                      $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                       0. Vote
                       1. Deposit Funds
                       2. Request Loan
                       3. Active Loans
                       4. Balances
                       5. Deposit History
                       6. Previous Proposals
                       7. Invite New Member
                       8. Leave Circle";

                    }

                    else
                    {
                      $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                      1. Deposit Funds
                      2. Request Loan
                      3. Active Loans
                      4. Balances
                      5. Deposit History
                      6. Previous Proposals
                      7. Invite New Member
                      8. Leave Circle";
                    }

                }

                elseif ($debtsCheckAvailable != NULL)
                {

                    //updateLevel("vote", $sessionId);
                    //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                    $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                     11. Pay off Loan: K" . $userCircleDebt . "!
                     1. Deposit Funds
                     2. Request Loan
                     3. Active Loans
                     4. Balances
                     5. Deposit History
                     6. Previous Proposals
                     7. Invite New Member
                     8. Leave Circle";

                  }

                else
                {
                  $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                  1. Deposit Funds
                  2. Request Loan
                  3. Active Loans
                  4. Balances
                  5. Deposit History
                  6. Previous Proposals
                  7. Invite New Member
                  8. Leave Circle";
                }
                // end of votecheck

              }


              // Incorrect input
              else {
                updateLevel("circleSelect", $sessionId);
                userCirclesProgress($phoneNumber);

                // User IS in a circle
                if($userCircles != NULL) {
                  daysRemainingMonth();
                  $response = "CON Incorrect input! Please choose a circle.\n";
                  $response .= "(" . $daysRemainingMonth . " days left for this period) \n";
                  $response .= $userCircles;
                  $response .= "" . $r . ". Join a Circle";
                }

                // User is NOT in a circle
                else {
                  updateLevel(1, $sessionId);

                  $response = "CON You are not in any circles. Please join a circle using an invite code provided by an existing member.
                    1. Saving Circles
                    2. Send a Payment
                    3. Request a Payment
                    4. View Balances
                    5. Deposit/Withdraw MoMo
                    6. Help ";
                }

                }



              userLevel($sessionId);

              header('Content-type: text/plain');
              echo $response;

            break;


            // ---------------------------------------------------------------------------------


            // JOIN A CIRCLE

            case "joinCircle":

              joinCircle($userResponse, $phoneNumber, $sessionId);

              // SUCCESSFUL JOIN
              if ($status == "success")
              {
                // Add CIRCLESELECT to db
                $sqlcircleSelect = "UPDATE session_levels SET circleSelect ='" . $userResponse . "' where `session_id`='" . $sessionId . "'";
                $db->query($sqlcircleSelect);

                updateLevel("circleActions", $sessionId);


                  $response = "CON Congrats! You have successfully joined " . ucfirst($userResponse) . ". Please choose an action:
                   1. Deposit Funds
                   2. Request Loan
                   3. Active Loans
                   4. Balances
                   5. Deposit History
                   6. Previous Proposals
                   7. Invite New Member
                   8. Leave Circle";

              }

              elseif ($status == "exists")
              {
                $response = "CON You have already joined " . ucfirst($userResponse) . ". Please choose a service.
                  1. Saving Circles
                  2. Send a Payment
                  3. Request a Payment
                  4. View Balances
                  5. Deposit/Withdraw MoMo
                  6. Help ";

                updateLevel(1, $sessionId);
              }

              // DENIAL .... try to add two denials (one for invalid # and one for invalid code)
              else
              {
                  $response = "END Sorry, your invite code is not valid or you are not a member of this circle.";
              }

              userLevel($sessionId);

              header('Content-type: text/plain');
              echo $response;

            break;

            // ---------------------------------------------------------------------------------


            // CREATE A CIRCLE **********

            case "createCircle":

              // Governance
              if ($indexLevel == NULL) {

                circleMembers($userResponse);

                // Circle name available
                if ($circleMembers == NULL) {

                  updateindexLevel("governance", $sessionId);
                  ram($userResponse, $sessionId);
                  $response = "CON Select a governance model:
                  1. One vote per person
                  2. One vote per Kwacha
                  3. Administrator
                  4. Multi-Administrator";

                }

                // Circle name is taken
                else {

                  $response = "CON Sorry, but that name is already taken. Try a new name:";

                }



              }


              elseif ($indexLevel == "governance") {

                getRamz($sessionId);

                // Valid Response
                if ($userResponse == 1 || $userResponse == 2 || $userResponse == 3 || $userResponse == 4) {

                  // Admin or multiadmin
                  if ($userResponse == 3 || $userResponse == 4) {

                    updateindexLevel("setAdmin1", $sessionId);

                    if ($userResponse == 3) {
                      ram2("admin", $sessionId);
                      $response = "CON Enter the phone number of the circle admin (e.g. 260971234567):";
                    }

                    elseif ($userResponse == 4) {
                      ram2("multi-admin", $sessionId);
                      $response = "CON Enter the phone number of the circle admin #1 (e.g. 260971234567):";
                    }

                  }

                  // Not admin or multiadmin
                  else {
                    updateindexLevel("depositGoal", $sessionId);
                    $response = "CON Enter a monthly deposit goal (K):";

                    // Log governance
                    if ($userResponse == 1) {
                      ram2("democracy", $sessionId);
                    }
                    elseif ($userResponse == 2) {
                      ram2("weighted", $sessionId);
                    }

                  }
                }

                // Invalid response
                else {

                  $response = "CON Incorrect response! Please try again. Select a governance model:
                  1. One vote per person
                  2. One vote per Kwacha
                  3. Administrator
                  4. Multi-Administrator";


                }




              }


              elseif ($indexLevel == "setAdmin1") {

                getRam2($sessionId);

                if ($ram2 == "admin") {

                  $userResponse = "+" . $userResponse . "";
                  activeUser($userResponse);

                  // User exists
                  if ($userAvailable != NULL) {
                    ram5($userResponse, $sessionId);
                    updateindexLevel("depositGoal", $sessionId);
                    username($userResponse);
                    $response = "CON " . $username . " has been set as the circle admin.\n";
                    $response .= "Enter a monthly deposit goal (K):";
                  }

                  // User does not exist
                  else {
                    $response = "CON That phone number has not registered for Pollen. Please enter a number of someone who has signed up (e.g. 260971234567), or invite them an dial back:";
                  }

                }

                elseif ($ram2 == "multi-admin") {

                  $userResponse = "+" . $userResponse . "";
                  activeUser($userResponse);

                  // User exists
                  if ($userAvailable != NULL) {
                    ram5($userResponse, $sessionId);
                    updateindexLevel("setAdmin2", $sessionId);
                    username($userResponse);
                    $response = "CON " . $username . " has been set as admin #1.\n";
                    $response .= "Enter the phone number of admin #2:";
                  }

                  // User does not exist
                  else {
                    $response = "CON That phone number has not registered for Pollen. Please enter a number of someone who has signed up (e.g. 260971234567), or invite them an dial back:";
                  }

                }

              }
              elseif ($indexLevel == "setAdmin2") {

                $userResponse = "+" . $userResponse . "";
                activeUser($userResponse);

                // User exists
                if ($userAvailable != NULL) {
                  ram6($userResponse, $sessionId);
                  updateindexLevel("setAdmin3", $sessionId);
                  username($userResponse);
                  $response = "CON " . $username . " has been set as admin #2.\n";
                  $response .= "Enter the phone number of admin #3:";
                }

                // User does not exist
                else {
                  $response = "CON That phone number has not registered for Pollen. Please enter a number of someone who has signed up (e.g. 260971234567), or invite them an dial back:";
                }

              }
              elseif ($indexLevel == "setAdmin3") {

                $userResponse = "+" . $userResponse . "";
                activeUser($userResponse);

                // User exists
                if ($userAvailable != NULL) {
                  ram7($userResponse, $sessionId);
                  updateindexLevel("depositGoal", $sessionId);
                  username($userResponse);
                  $response = "CON " . $username . " has been set as admin #3.\n";
                  $response .= "Enter a monthly deposit goal (K):";
                }

                // User does not exist
                else {
                  $response = "CON That phone number has not registered for Pollen. Please enter a number of someone who has signed up (e.g. 260971234567), or invite them an dial back:";
                }

              }


              // Late Penalty
              elseif ($indexLevel == "depositGoal") {

                if ($userResponse >= 0 && $userResponse < 1000000) {
                  updateindexLevel("latePenalty", $sessionId);
                  ram3($userResponse, $sessionId);
                  $response = "CON Enter a late penalty, the K amount deducted from a user's circle balance when they fail to meet the deposit goal.\n";
                  $response .= "(Enter 0 for no late penalty)";
                }

                else {
                  $response = "CON Invalid value. Please enter a deposit goal that is greater than or equal to K0 and less than K1000000:";
                }

              }


              // Info Check
              elseif ($indexLevel == "latePenalty") {

                if ($userResponse >= 0) {

                  ram4($userResponse, $sessionId);
                  getRamz($sessionId);
                  updateindexLevel("infoCheck", $sessionId);
                  $response = "CON Please review the information before creating the circle:
                    Circle Name: " . $ram . "
                    Governance: " . $ram2 . "\n";

                  if ($ram2 == "admin") {
                    $response .= "Admin: " . $ram5 . "\n";
                  }

                  elseif ($ram2 == "multi-admin") {
                    $response .= "Admin #1: " . $ram5 . "\n";
                    $response .= "Admin #2: " . $ram6 . "\n";
                    $response .= "Admin #3: " . $ram7 . "\n";
                  }

                  $response .= "Deposit Goal : K" . $ram3 . "
                  Late Penalty: K" . $ram4 . "
                  1. Confirm
                  0. Start over";

                }

                else {
                  $response = "CON Invalid response! Enter a late penalty, the K amount deducted from a user's circle balance when they fail to meet the deposit goal (e.g. K100).\n";
                  $response .= "(Enter 0 for no late penalty)";
                }
              }


              // Initial Deposit *********
              elseif ($indexLevel == "infoCheck") {

                // Initial Deposit  ******* CREATE CIRCLE ON KP
                if ($userResponse == 1) {
                  updateindexLevel("inviteMember", $sessionId);
                  $response = "CON Great, you are almost done! Enter a phone number to invite your first member (e.g. 260971234567):\n";
                  $response .= "0. Skip";
                }

                // Start over
                else {

                  updateLevel("createCircle", $sessionId);
                  ram("", $sessionId);
                  ram2("", $sessionId);
                  ram3("", $sessionId);
                  ram4("", $sessionId);
                  $response = "CON Enter a circle name:";

                }

              }


              elseif ($indexLevel == "inviteMember") {

                if ($userResponse == 0) {
                  updateindexLevel("circleCreated", $sessionId);
                  getRamz($sessionId);

                  $address = "0xeric";

                  // Create circle
                  createCircle($ram, $ram2, $ram3, $ram4, $address, $phoneNumber, $ram5, $ram6, $ram7);

                  $response = "END You have successfully created " . ucfirst($ram) . "! Don't forget to invite members from the circle menu.";

                }

                else {
                  $userResponse = "+" . $userResponse . "";
                  activeUser($userResponse);

                  // User exists
                  if ($userAvailable != NULL) {

                    //updateindexLevel("inviteCheck", $sessionId);
                    getRam($sessionId);
                    inviteMember($ram, $phoneNumber, $userResponse);
                    username($phoneNumber);

                    // Send SMS
                    $message = "Hey! " . $username . " (" . $phoneNumber . ") has invited you to join their saving circle. Dial in to Pollen and navigate to Saving Circles > Join Circle and enter your invite code: " . $ram . "";
                    $recipient = $userResponse;
                    $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                    $gateway->sendMessage($recipient, $message);


                    username($userResponse);

                    $response = "CON " . $username . " has been invited via SMS. Enter a new number to invite another member:";
                    $response .= "0. Skip";
                  }

                  // User does not exist
                  else {

                    updateindexLevel("inviteUser", $sessionId);
                    $response = "CON " . $userResponse . " has not signed up for Pollen. Would you like us to send them an SMS to join Pollen along with an invite code for your circle?\n";
                    $response .= "1. Yes \n";
                    $response .= "0. No";

                    // Log user response in db
                    $sql = "UPDATE session_levels SET ram = '" . $userResponse . "' WHERE session_id LIKE '%" . $sessionId . "%'";
                    $db->query($sql);

                  }

                }

              }


              elseif ($indexLevel == "inviteUser") {

                if ($userResponse == 1) {

                  updateindexLevel("inviteMember", $sessionId);

                  // Get phonenumber from db
                  $sql = "SELECT ram FROM session_levels WHERE session_id LIKE '%" . $sessionId . "%' ";
                  $result = mysqli_query($db, $sql);
                  $resultAvailable = mysqli_fetch_assoc($result);
                  $invitee = $resultAvailable['ram'];

                  $invitee = "+" . $invitee . "";
                  getRam($sessionId);
                  inviteMember($ram, $phoneNumber, $invitee);
                  username($phoneNumber);

                  // Send SMS
                  $message = "Hey! " . $username . " (" . $phoneNumber . ") has invited you to join their Pollen saving circle. Dial in to *384*31322333#, setup your account, and navigate to Saving Circles > Join Circle and enter your invite code: " . $ram . "";
                  $recipient = $invitee;
                  $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                  $gateway->sendMessage($recipient, $message);

                  $response = "CON " . $invitee . " has been invited via SMS. Enter a new number to invite another member:";
                  $response .= "0. Skip";
                }

                elseif ($userResponse == 0) {

                  updateindexLevel("circleCreated", $sessionId);
                  getRamz($sessionId);

                  $address = "0xeric";

                  // Create circle
                  createCircle($ram, $ram2, $ram3, $ram4, $address, $phoneNumber, $ram5, $ram6, $ram7);

                  $response = "END You have successfully created " . ucfirst($ram) . "! Don't forget to invite members from the circle menu.";
                }

                else {
                  $response = "CON Invalid response, try again. ";
                  $response = "" . $userResponse . " has not signed up for Pollen. Would you like us to send them an SMS to join Pollen along with an invite code for your circle?\n";
                  $response .= "1. Yes \n";
                  $response .= "0. No";
                }

              }


              userLevel($sessionId);

              header('Content-type: text/plain');
              echo $response;

            break;


            // ---------------------------------------------------------------------------------


            // CIRCLE ACTIONS

            case "circleActions":

              circleSelect($sessionId);



              // PAY OFF DEBT
              if ($userResponse == "11") {
                updateLevel("payDebt", $sessionId);
                getUserCircleDebt($phoneNumber, $circleSelect);

                // User is in debt
                if ($debtsCheckAvailable != NULL) {
                  $response = "CON You owe K" . $userCircleDebt . " to " . ucfirst($circleSelect) . "
                    Enter an amount to repay:";
                }

                // User is not in debt
                else {
                  $response = "END You do not have any active loans with " . ucfirst($circleSelect) . "!";
                }


              }


              //VOTE
              elseif ($userResponse == "0") {

                // Get active proposal  |  $proposalAvailable  |  $voteCheckAvailable  |  $proposalAvailable['txnhash']['phonenumber'] ['action'] ['value']
                proposalAvailable($circleSelect, $phoneNumber);
                username($proposer);

                //$response = "CON " . $username . "";

                if ($proposalAvailable != NULL) {

                  if ($voteCheckAvailable == NULL) {

                    updateLevel("vote", $sessionId);

                    // WITHDRAWAL
                    if ($action == "Withdrawal")
                    {
                        $response = "CON " . $username . " has requested K" . $value . " from " . ucfirst($circleSelect) . ". \n Reason: " . $desc . "\n";
                    }

                    // MEMBER ADD
                    elseif ($action == "MemberAdd")
                    {
                        $response = "CON " . $username . " has requested to add " . $value . " to " . ucfirst($circleSelect) . "\n";
                    }

                    $response .= "1. Vote YES \n";
                    $response .= "0. Vote NO \n";
                  }

                  else {
                    $response = "END You have already voted on this proposal...";
                  }

                }

                else {
                  $response = "END There are no proposals for you to vote on...";
                }




              }


              // DEPOSIT FUNDS
              elseif ($userResponse == 1) {
                updateLevel("circleDeposit", $sessionId);
                userCircleProgress($phoneNumber, $circleSelect);

                $response = "CON Enter an amount to deposit:
                  (K" . $amountRemaining . " till you reach the goal)";


              }


              // REQUEST LOAN
              elseif ($userResponse == 2) {
                updateLevel("requestLoan", $sessionId);
                getUserCircleDebt($phoneNumber, $circleSelect);

                if ($debtsCheckAvailable == NULL) {
                  $response = "CON Enter an amount you would like to borrow: ";
                }

                else {
                  updateLevel("payDebt", $sessionId);
                  $response = "CON You cannot request a loan from " . ucfirst($circleSelect) . " when you owe K" . $userCircleDebt . "
                    Enter an amount to repay:";

                }
              }


              // ACTIVE LOANS
              elseif ($userResponse == 3) {
                updateLevel("circleEND", $sessionId);
                circleDebts($circleSelect);
                $response = "CON Active loans for " . ucfirst($circleSelect) . "\n";
                $response .= $circleDebts;
              }



              // BALANCES
              elseif ($userResponse == 4) {

                updateLevel("circleEND", $sessionId);
                circleMemberBalances($circleSelect);
                $response = "CON Member balances for " . ucfirst($circleSelect) . ": \n";
                $response .= $circleMemberBalances;

              }



              // DEPOSIT HISTORY
              elseif ($userResponse == 5) {

                updateLevel("circleEND", $sessionId);
                memberMonthlyProgress($circleSelect);
                $response = "CON Monthly deposit progress: \n";
                $response .= $monthlyProgress;
              }



              // PREVIOUS PROPOSALS
              elseif ($userResponse == 6) {

                updateLevel("circleEND", $sessionId);
                previousProposals($circleSelect);
                $response =  "CON Previous proposals for " . ucfirst($circleSelect) . "\n";
                $response .= $previousProposals;

              }



              // INVITE NEW MEMBER
              elseif ($userResponse == 7) {

                updateLevel("inviteMember", $sessionId);

                $response = "CON Enter the phone number you would like to invite to " . ucfirst($circleSelect) . " (eg 260971234567):";


              }



              // LEAVE CIRCLE
              elseif ($userResponse == 8) {

                getUserCircleDebt($phoneNumber, $circleSelect);
                proposalAvailable($circleSelect, $phoneNumber);

                // Check if in debt
                if ($debtsCheckAvailable != NULL) {
                  $response = "END You owe K" . $userCircleDebt . " to " . ucfirst($circleSelect) . ". You cannot leave until you repay your debt.\n";
                }

                // Check if they have an active proposal
                elseif ($proposer == $phoneNumber) {
                  $response = "END You have an active loan request for K" . $amount . " and cannot leave until voting is done.";
                }

                // Confirm leave circle
                else {
                  $response = "CON Are you sure you want to leave " . ucfirst($circleSelect) . "?\n";
                  $response .= "(Your circle balance will be withdrawn to your Pollen wallet)\n";
                  $response .= "1. YES \n 2. NO";
                  updateLevel("leaveCircle", $sessionId);

                }


              }



              // else
              else {
                updateLevel("circleActions", $sessionId);
                //updateLevel("circleActions", $sessionId);

                circleSelect($sessionId);
                circleBalance($circleSelect);

                // Get active proposal  |  $proposalAvailable  |  $voteCheckAvailable  |  $proposalAvailable['txnhash']['phonenumber'] ['action'] ['value']
                proposalAvailable($circleSelect, $phoneNumber);

                  // Get user debts outstanding for a specific circle  |  $debtsCheckAvailable (== NULL?)  |  $userCircleDebt
                  getUserCircleDebt($phoneNumber, $circleSelect);

                  if ($proposalAvailable != NULL && $debtsCheckAvailable != NULL)
                  {

                      // If response is 1/5 and user has not voted
                      if ($voteCheckAvailable == NULL)
                      {
                        //updateLevel("vote", $sessionId);
                        //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                        $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                         11. Pay off Loan: K" . $userCircleDebt . "!
                         0. Vote
                         1. Deposit Funds
                         2. Request Loan
                         3. Active Loans
                         4. Balances
                         5. Deposit History
                         6. Previous Proposals
                         7. Invite New Member
                         8. Leave Circle";

                      }

                      else
                      {
                        $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                        11. Pay off Loan: K" . $userCircleDebt . "!
                        1. Deposit Funds
                        2. Request Loan
                        3. Active Loans
                        4. Balances
                        5. Deposit History
                        6. Previous Proposals
                        7. Invite New Member
                        8. Leave Circle";
                      }
                      // end of votecheck

                  } //end of proposal available

                  elseif ($proposalAvailable != NULL)
                  {

                      // If response is 1/5 and user has not voted
                      if ($voteCheckAvailable == NULL)
                      {
                        //updateLevel("vote", $sessionId);
                        //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                        $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                         0. Vote
                         1. Deposit Funds
                         2. Request Loan
                         3. Active Loans
                         4. Balances
                         5. Deposit History
                         6. Previous Proposals
                         7. Invite New Member
                         8. Leave Circle";

                      }

                      else
                      {
                        $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                        1. Deposit Funds
                        2. Request Loan
                        3. Active Loans
                        4. Balances
                        5. Deposit History
                        6. Previous Proposals
                        7. Invite New Member
                        8. Leave Circle";
                      }

                  }

                  elseif ($debtsCheckAvailable != NULL)
                  {

                      //updateLevel("vote", $sessionId);
                      //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                      $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                       11. Pay off Loan: K" . $userCircleDebt . "!
                       1. Deposit Funds
                       2. Request Loan
                       3. Active Loans
                       4. Balances
                       5. Deposit History
                       6. Previous Proposals
                       7. Invite New Member
                       8. Leave Circle";

                    }

                  else
                  {
                    $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                    1. Deposit Funds
                    2. Request Loan
                    3. Active Loans
                    4. Balances
                    5. Deposit History
                    6. Previous Proposals
                    7. Invite New Member
                    8. Leave Circle";
                  }
              }

              userLevel($sessionId);

              header('Content-type: text/plain');
              echo $response;

            break;







            // ---------------------------------------------------------------------------------






            // DEPOSIT FUNDS
            case "circleDeposit":

              circleSelect($sessionId);

              // Start -- Request pin
              if ($indexLevel == NULL) {

                kpAuth($phoneNumber);
                userBalance($authToken, $phoneNumber);

                if ($userResponse <= $userBalanceCUSD) {
                  $response = "CON Please enter your pin to deposit K" . $userResponse . ":\n";
                  $response .= "Balance = " . $userBalanceCUSD . "";
                  updateindexLevel("check", $sessionId);
                  ram($userResponse, $sessionId);
                }

                else {
                  $response = "CON You only have K" . $userBalanceCUSD . " to deposit. Enter a new amount:";
                }
              }


              // PIN CHECK
              elseif ($indexLevel == "check") {

                if ($userResponse == $userPin) {
                  getRam($sessionId);
                  circleDeposit($ram, $phoneNumber, $circleSelect);
                  $response = "END You have successfully deposited K" . $ram . " into " . ucfirst($circleSelect) . "\n";
                  $response .= "New circle balance = K" . $circleBalance . "\n";
                  $response .= "Transaction ID: " . $txnID . "\n";
                }

                else {
                  $response = "CON Incorrect pin. Please try again:";
                }

              }



              userLevel($sessionId);

              header('Content-type: text/plain');
              echo $response;

            break;

            // ---------------------------------------------------------------------------------


            // PAY OFF DEBT
            case "payDebt":

               circleSelect($sessionId);

               payDebt($phoneNumber, $userResponse, $circleSelect);


               userLevel($sessionId);

               header('Content-type: text/plain');
               echo $response;

            break;


            // ---------------------------------------------------------------------------------


            // VOTE
            case "vote":

               circleSelect($sessionId);
               proposalAvailable($circleSelect, $phoneNumber);
               username($proposer);


               // Request Secret Pin
               if ($indexLevel == NULL) {

                 ram($userResponse, $sessionId);

                 // Yes vote
                 if ($userResponse == 1) {
                  $response = "CON Enter your secret pin to vote YES:";
                  updateindexLevel("check", $sessionId);
                 }

                 // No Vote
                 elseif ($userResponse == 0) {
                   $response = "CON Enter your secret pin to vote NO:";
                   updateindexLevel("check", $sessionId);
                 }

                 // Incorrect input
                 else {
                   // WITHDRAWAL
                   if ($action == "Withdrawal")
                   {
                       $response = "CON Incorrect input! Try again. \n" . $username . " has requested K" . $value . " from " . ucfirst($circleSelect) . ". \n Reason: " . $desc . "\n";
                   }

                   // MEMBER ADD
                   elseif ($action == "MemberAdd")
                   {
                       $response = "CON Incorrect input! Try again. \n" . $username . " has requested to add " . $value . " to " . ucfirst($circleSelect) . "\n";
                   }

                   $response .= "1. Vote YES \n";
                   $response .= "0. Vote NO \n";
                 }

               }


               // Check Secret pin
               elseif ($indexLevel == "check") {

                 getRam($sessionId);

                 // Correct Pin
                 if ($userResponse == $userPin) {

                   updateindexLevel("", $sessionId);
                   updateLevel(0, $sessionId);

                   logVote($phoneNumber, $ram, $circleSelect);

                   // Quorum met
                   if ($result != NULL) {

                     //SMS
                     // fetch circle MEMBERS
                     $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%'";
                     $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
                     $circleMembers = "";

                     $response = "CON " . $message . "";

                     // SEND SMS
                     while ($circleMembers = mysqli_fetch_assoc($resultCircleMembers))
                     {
                         $circleMembers = $circleMembers['phonenumber'];

                         $recipient = $circleMembers;

                         $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                         //$gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                         $gateway->sendMessage($recipient, $message);
                     }


                   }

                   // Quorum not yet met
                   else {
                     $response = "CON Thank you for voting! The final results will be sent via SMS.";
                   }

                   $response .= "
                   1. Saving Circles
                   2. Send a Payment
                   3. Request a Payment
                   4. View Balances
                   5. Deposit/Withdraw MoMo
                   6. Help ";


                 }

                 // Incorrect Pin
                 else {

                   // Yes vote
                   if ($ram == 1) {
                    $response = "CON Incorrect pin! Please enter your secret pin to vote YES:";
                    updateindexLevel("check", $sessionId);
                   }

                   // No Vote
                   elseif ($ram == 0) {
                     $response = "CON Incorrect pin! Please enter your secret pin to vote NO:";
                     updateindexLevel("check", $sessionId);
                   }

                 }

               }



               userLevel($sessionId);

               header('Content-type: text/plain');
               echo $response;

            break;


            // ---------------------------------------------------------------------------------


            // REQUEST LOAN  XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
             case "requestLoan":

              circleSelect($sessionId);

              // GET CIRCLE BALANCE KP  XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
              circleBalance($circleID);

              // Give Reason
              if ($indexLevel == NULL) {

                if ($userResponse <= $circleBalance) {
                  ram($userResponse, $sessionId);
                  updateindexLevel("reason", $sessionId);
                  $response = "CON Why do you need K" . $userResponse . "?";
                }

                else {
                  $response = "CON You cannot request more than the circle balance (K" . $circleBalance . ")";
                }

              }

              // Prompt Secret Pin
              elseif ($indexLevel == "reason") {

                updateindexLevel($userResponse, $sessionId);
                getRam($sessionId);
                $response = "CON Please enter your secret pin to request a loan of K" . $ram . " from " . ucfirst($circleSelect) . ":";

              }

              // Check Secret Pin
              else {

                getRam($sessionId);

                // CORRECT PIN
                if ($userResponse == $userPin) {

                  username($phoneNumber);
                  loanRequest($phoneNumber, $circleSelect, $ram, $indexLevel);

                  // SEND SMS
                  // Fetch members of the circle
                  $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' ";
                  $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
                  $circleMembers = "";

                  //Loop through circleMembers
                  while ($circleMembers = mysqli_fetch_assoc($resultCircleMembers))
                  {
                      $circleMembers = $circleMembers['phonenumber'];

                      // SEND SMS VIA AT GATEWAY
                      $message = "Proposal: Withdraw Funds \n Circle: " . $circleSelect . "\n  Requestor: " . $username . ", " . $phoneNumber . " \n Amount: K" . $ram . " \n Reason: " . $indexLevel . "\n Dial into USSD to vote *384*313233#";
                      $recipient = $circleMembers;
                      $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                      //$gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                      $gateway->sendMessage($recipient, $message);
                  }

                  $response = "END Your request for K" . $ram . " from " . ucfirst($circleSelect) . " has been submitted for vote. Your Proposal ID = " . $propID . "\n";

                }

                // INCORRECT PIN
                else {
                  $response = "CON Incorrect pin! Please your secret pin to request K" . $ram . " from " . ucfirst($circleSelect) . ":";
                }

              }

              userLevel($sessionId);

              header('Content-type: text/plain');
              echo $response;

             break;


             // ---------------------------------------------------------------------------------


             // INVITE NEW MEMBER
              case "inviteMember":

               circleSelect($sessionId);


               // Check if User exists and Request Pin
               if ($indexLevel == NULL) {

                 //updateindexLevel("pin", $sessionId);
                 $invitee = "+" . $userResponse . "";
                 ram($invitee, $sessionId);
                 activeUser($invitee);
                 governance($circleSelect);

                 if ($model == "admin") {

                   admins($circleSelect);

                   // User is an admin
                   if ($phoneNumber == $admin1 || $phoneNumber == $admin2 || $phoneNumber == $admin3) {

                     // Invitee is a user
                     if ($userAvailable != NULL) {

                       username($invitee);
                       $response = "CON Enter your pin to invite " . $username . " to " . ucfirst($circleSelect) . ":";

                     }

                     // Invitee is NOT a user
                     else {

                       updateindexLevel("new", $sessionId);
                       $response = "CON " . $userResponse . " is not registered on Pollen. Would you like to invite them to join Pollen and your circle?
                        1. Yes
                        0. No";

                     }

                   }

                   else {

                     // Invitee is a user
                     if ($userAvailable != NULL) {

                       username($invitee);
                       $response = "CON Enter your pin to submit your request to invite " . $username . " to " . ucfirst($circleSelect) . ":";

                     }

                     // Invitee is NOT a user
                     else {

                       updateindexLevel("new", $sessionId);
                       $response = "CON " . $userResponse . " is not registered on Pollen. Would you like to invite them to join Pollen and your circle?
                        1. Yes
                        0. No";

                     }

                   }

                 }

                 else {

                   // Invitee is a user
                   if ($userAvailable != NULL) {

                     username($invitee);
                     $response = "Enter your pin to submit your request to invite " . $username . " to " . ucfirst($circleSelect) . ":";

                   }

                   // Invitee is NOT a user
                   else {

                     updateindexLevel("new", $sessionId);
                     $response = "CON " . $userResponse . " is not registered on Pollen. Would you like to invite them to join Pollen and your circle?
                      1. Yes
                      0. No";

                   }


                 }


               }


               // Check user response on invite new user
               elseif ($indexLevel == "new") {

                 getRam($sessionId);
                 $ram = substr($ram, 1);
                 governance($circleSelect);

                 // Request pin
                 if ($userResponse == 1) {
                   updateindexLevel("pinInvite", $sessionId);

                   if ($model == "admin") {

                     admins($circleSelect);

                     if ($phoneNumber == $admin1) {

                       $response = "CON Enter your pin to invite " . $ram . " to " . ucfirst($circleSelect) . ":";

                     }

                     else {
                       $response = "CON Enter your pin to invite " . $ram . " to Pollen. Your request to invite them to " . ucfirst($circleSelect) . " will be put up for vote:";
                     }

                   }

                   else {

                     $response = "CON Enter your pin to invite " . $ram . " to Pollen. Your request to invite them to " . ucfirst($circleSelect) . " will be put up for vote:";

                   }

                 }

                 // Quite member invite
                 elseif ($userResponse == 0) {

                   updateLevel(1, $sessionId);
                   updateindexLevel("", $sessionId);
                   ram("", $sessionId);

                   $response = "CON Hi " . ucfirst($userName) . ". Welcome back! \n Please choose a service.
                     1. Saving Circles
                     2. Send a Payment
                     3. Request a Payment
                     4. View Balances
                     5. Deposit/Withdraw MoMo
                     6. Help ";

                 }

                 // Invalid response
                 else {
                   $ram = substr($ram, 1);
                   $response = "CON Invalid response, try again. " . $ram . " is not registered on Pollen. Would you like to invite them to join Pollen and your circle?
                    1. Yes
                    0. No";

                 }

               }



               elseif ($indexLevel == "pin") {

                 if ($userResponse == $userPin) {

                   getRam($sessionId);

                   governance($circleSelect);

                   if ($model == "admin") {

                     admins($circleSelect);

                     if ($phoneNumber == $admin1) {

                       // Add invite to database
                       inviteMember($circleSelect, $phoneNumber, $ram);

                       // Log circle action?

                       // Invite user via SMS to Pollen and Circle
                       username($phoneNumber);

                       $message = "Hey! " . $username . " (" . $phoneNumber . ") has invited you to join their Pollen saving circle. Dial in to *384*31322333#, navigate to Saving Circles > Join Circle and enter your invite code: " . $ram . "";
                       $recipient = $ram;
                       $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                       $gateway->sendMessage($recipient, $message);

                       $invitee = substr($ram, 1);
                       $response = "END " . $invitee . " has been invited to " . ucfirst($circleSelect) . " via SMS!";

                     }

                     else {

                       // Log proposal in DB
                       inviteRequest($phoneNumber, $ram, $circleSelect);
                       username($phoneNumber);

                       // Text members?
                       $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' ";
                       $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
                       $circleMembers = "";

                       //Loop through circleMembers
                       while ($circleMembers = mysqli_fetch_assoc($resultCircleMembers))
                       {
                           $circleMembers = $circleMembers['phonenumber'];

                           // SEND SMS VIA AT GATEWAY
                           $message = "Proposal: Invite New Member \n Circle: " . $circleSelect . "\n  Requestor: " . $username . ", " . $phoneNumber . " \n Proposed New Member: " . $ram . " \n Dial into USSD to vote *384*313233#";
                           $recipient = $circleMembers;
                           $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                           //$gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                           $gateway->sendMessage($recipient, $message);
                       }

                       $invitee = substr($ram, 1);
                       $response = "END Your request to invite " . $invitee . " to " . ucfirst($circleSelect) . " has been submitted for vote.";

                     }

                   }

                   else {

                     // Log proposal in DB
                     inviteRequest($phoneNumber, $ram, $circleSelect);
                     username($phoneNumber);

                     // Text members?
                     $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' ";
                     $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
                     $circleMembers = "";

                     //Loop through circleMembers
                     while ($circleMembers = mysqli_fetch_assoc($resultCircleMembers))
                     {
                         $circleMembers = $circleMembers['phonenumber'];

                         // SEND SMS VIA AT GATEWAY
                         $message = "Proposal: Invite New Member \n Circle: " . $circleSelect . "\n  Requestor: " . $username . ", " . $phoneNumber . " \n Proposed New Member: " . $ram . " \n Dial into USSD to vote *384*313233#";
                         $recipient = $circleMembers;
                         $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                         //$gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                         $gateway->sendMessage($recipient, $message);
                     }

                     $invitee = substr($ram, 1);
                     $response = "END Your request to invite " . $invitee . " to " . ucfirst($circleSelect) . " has been submitted for vote.";

                   }

                 }

                 else {
                   $response = "CON Incorrect pin, please try again:";
                 }

               }



               elseif ($indexLevel == "pinInvite") {

                 if ($userResponse == $userPin) {

                   getRam($sessionId);

                   governance($circleSelect);

                   if ($model == "admin") {

                     admins($circleSelect);

                     if ($phoneNumber == $admin1) {

                       // Add invite to database
                       inviteMember($circleSelect, $phoneNumber, $ram);

                       // Log circle action?

                       // Invite user via SMS to Pollen and Circle
                       username($phoneNumber);

                       $message = "Hey! " . $username . " (" . $phoneNumber . ") has invited you to join their Pollen saving circle. Dial in to *384*31322333#, setup your account, and navigate to Saving Circles > Join Circle and enter your invite code: " . $ram . "";
                       $recipient = $ram;
                       $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                       $gateway->sendMessage($recipient, $message);

                       $invitee = substr($ram, 1);
                       $response = "END " . $invitee . " has been invited to Pollen and " . ucfirst($circleSelect) . " via SMS!";

                     }

                     else {

                       // Log proposal in DB
                       inviteRequest($phoneNumber, $ram, $circleSelect);
                       username($phoneNumber);

                       // Text members?
                       $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' ";
                       $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
                       $circleMembers = "";

                       //Loop through circleMembers
                       while ($circleMembers = mysqli_fetch_assoc($resultCircleMembers))
                       {
                           $circleMembers = $circleMembers['phonenumber'];

                           // SEND SMS VIA AT GATEWAY
                           $message = "Proposal: Invite New Member \n Circle: " . $circleSelect . "\n  Requestor: " . $username . ", " . $phoneNumber . " \n Proposed New Member: " . $ram . " \n Dial into USSD to vote *384*313233#";
                           $recipient = $circleMembers;
                           $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                           //$gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                           $gateway->sendMessage($recipient, $message);
                       }

                       // Invite user via SMS to Pollen

                       $message = "Hey! " . $username . " (" . $phoneNumber . ") has invited you to join Pollen saving circles. Dial in to *384*31322333# to convert mobile money to US Dollars, send global payments, and conduct village banking.";
                       $recipient = $ram;
                       $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                       $gateway->sendMessage($recipient, $message);

                       $invitee = substr($ram, 1);
                       $response = "END " . $invitee . " has been invited to Pollen via SMS! Your request to invite them to " . ucfirst($circleSelect) . " has been submitted for vote.";

                     }

                   }

                   else {

                     // Log proposal in DB
                     inviteRequest($phoneNumber, $ram, $circleSelect);
                     username($phoneNumber);

                     // Text members?
                     $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' ";
                     $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
                     $circleMembers = "";

                     //Loop through circleMembers
                     while ($circleMembers = mysqli_fetch_assoc($resultCircleMembers))
                     {
                         $circleMembers = $circleMembers['phonenumber'];

                         // SEND SMS VIA AT GATEWAY
                         $message = "Proposal: Invite New Member \n Circle: " . $circleSelect . "\n  Requestor: " . $username . ", " . $phoneNumber . " \n Proposed New Member: " . $ram . " \n Dial into USSD to vote *384*313233#";
                         $recipient = $circleMembers;
                         $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                         //$gateway = new AfricasTalkingGateway($username, $apikey, "sandbox");
                         $gateway->sendMessage($recipient, $message);
                     }

                     // Invite user via SMS to Pollen

                     $message = "Hey! " . $username . " (" . $phoneNumber . ") has invited you to join Pollen saving circles. Dial in to *384*31322333# to convert mobile money to US Dollars, send global payments, and conduct village banking.";
                     $recipient = $ram;
                     $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                     $gateway->sendMessage($recipient, $message);

                     $invitee = substr($ram, 1);
                     $response = "END " . $invitee . " has been invited to Pollen via SMS! Your request to invite them to " . ucfirst($circleSelect) . " has been submitted for vote.";

                   }

                 }

                 else {
                   $response = "CON Incorrect pin, please try again:";
                 }

               }


               userLevel($sessionId);

               header('Content-type: text/plain');
               echo $response;

              break;



             // ---------------------------------------------------------------------------------


             // LEAVE CIRCLE
              case "leaveCircle":

               circleSelect($sessionId);

               // Ask for Pin or Dont Leave
               if ($indexLevel == NULL) {

                 // Ask for pin
                 if ($userResponse == 1) {
                   $response = "CON Please enter your secret pin to confirm you want to leave " . ucfirst($circleSelect) . ".\n";
                   updateindexLevel("check", $sessionId);
                 }

                 // Dont leave
                 elseif ($userResponse == 2) {
                   updateLevel("circleActions", $sessionId);
                   //updateLevel("circleActions", $sessionId);

                   circleSelect($sessionId);
                   circleBalance($circleSelect);

                   // KotaniPay STUFF
                   //kp2Auth($phoneNumber);
                   //kpGetSaccoInfo($circleSelect, $authToken);
                   // $balance

                   // Get active proposal  |  $proposalAvailable  |  $voteCheckAvailable  |  $proposalAvailable['txnhash']['phonenumber'] ['action'] ['value']
                   proposalAvailable($circleSelect, $phoneNumber);

                   // Get user debts outstanding for a specific circle  |  $debtsCheckAvailable (== NULL?)  |  $userCircleDebt
                   getUserCircleDebt($phoneNumber, $circleSelect);

                   if ($proposalAvailable != NULL && $debtsCheckAvailable != NULL)
                   {

                       // If response is 1/5 and user has not voted
                       if ($voteCheckAvailable == NULL)
                       {
                         //updateLevel("vote", $sessionId);
                         //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                         $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                          11. Pay off Loan: K" . $userCircleDebt . "!
                          0. Vote
                          1. Deposit Funds
                          2. Request Loan
                          3. Active Loans
                          4. Balances
                          5. Deposit History
                          6. Previous Proposals
                          7. Invite New Member
                          8. Leave Circle";

                       }

                       else
                       {
                         $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                         11. Pay off Loan: K" . $userCircleDebt . "!
                         1. Deposit Funds
                         2. Request Loan
                         3. Active Loans
                         4. Balances
                         5. Deposit History
                         6. Previous Proposals
                         7. Invite New Member
                         8. Leave Circle";
                       }
                       // end of votecheck

                   } //end of proposal available

                   elseif ($proposalAvailable != NULL)
                   {

                       // If response is 1/5 and user has not voted
                       if ($voteCheckAvailable == NULL)
                       {
                         //updateLevel("vote", $sessionId);
                         //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                         $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                          0. Vote
                          1. Deposit Funds
                          2. Request Loan
                          3. Active Loans
                          4. Balances
                          5. Deposit History
                          6. Previous Proposals
                          7. Invite New Member
                          8. Leave Circle";

                       }

                       else
                       {
                         $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                         1. Deposit Funds
                         2. Request Loan
                         3. Active Loans
                         4. Balances
                         5. Deposit History
                         6. Previous Proposals
                         7. Invite New Member
                         8. Leave Circle";
                       }

                   }

                   elseif ($debtsCheckAvailable != NULL)
                   {

                       //updateLevel("vote", $sessionId);
                       //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                       $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                        11. Pay off Loan: K" . $userCircleDebt . "!
                        1. Deposit Funds
                        2. Request Loan
                        3. Active Loans
                        4. Balances
                        5. Deposit History
                        6. Previous Proposals
                        7. Invite New Member
                        8. Leave Circle";

                     }

                   else
                   {
                     $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                     1. Deposit Funds
                     2. Request Loan
                     3. Active Loans
                     4. Balances
                     5. Deposit History
                     6. Previous Proposals
                     7. Invite New Member
                     8. Leave Circle";
                   }
                   // end of votecheck

                 }

                 // Incorrect input
                 else {
                   $response = "CON Incorrect input. Please try again.";
                   $response .= "Are you sure you want to leave " . ucfirst($circleSelect) . "?\n";
                   $response .= "(Your circle balance will be withdrawn to your Pollen wallet)\n";
                   $response .= "1. YES \n 2. NO";
                 }

               }

               // Check if pin is correct
               elseif ($indexLevel == "check") {
                 if ($userResponse == $userPin) {
                   updateindexLevel("leave", $sessionId);
                   removeFromCircle($phoneNumber, $circleSelect);
                   $response = "END You have successfully left " . ucfirst($circleSelect) . ". Your circle balance has been withdrawn to your Pollen wallet.";
                 }
                 else {
                   $response = "CON Incorrect pin. Please enter your secret pin to leave " . ucfirst($circleSelect) . ":";
                 }
               }

               userLevel($sessionId);

               header('Content-type: text/plain');
               echo $response;

              break;


             // ---------------------------------------------------------------------------------


             // CIRCLE END / RESTART

             case "circleEND":

               updateLevel("circleActions", $sessionId);
               //updateLevel("circleActions", $sessionId);

               circleSelect($sessionId);
               circleBalance($circleSelect);

               // Get active proposal  |  $proposalAvailable  |  $voteCheckAvailable  |  $proposalAvailable['txnhash']['phonenumber'] ['action'] ['value']
               proposalAvailable($circleSelect, $phoneNumber);

                 // Get user debts outstanding for a specific circle  |  $debtsCheckAvailable (== NULL?)  |  $userCircleDebt
                 getUserCircleDebt($phoneNumber, $circleSelect);

                 if ($proposalAvailable != NULL && $debtsCheckAvailable != NULL)
                 {

                     // If response is 1/5 and user has not voted
                     if ($voteCheckAvailable == NULL)
                     {
                       //updateLevel("vote", $sessionId);
                       //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                       $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                        11. Pay off Loan: K" . $userCircleDebt . "!
                        0. Vote
                        1. Deposit Funds
                        2. Request Loan
                        3. Active Loans
                        4. Balances
                        5. Deposit History
                        6. Previous Proposals
                        7. Invite New Member
8. Leave Circle";

                     }

                     else
                     {
                       $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                       11. Pay off Loan: K" . $userCircleDebt . "!
                       1. Deposit Funds
                       2. Request Loan
                       3. Active Loans
                       4. Balances
                       5. Deposit History
                       6. Previous Proposals
                       7. Invite New Member
8. Leave Circle";
                     }
                     // end of votecheck

                 } //end of proposal available

                 elseif ($proposalAvailable != NULL)
                 {

                     // If response is 1/5 and user has not voted
                     if ($voteCheckAvailable == NULL)
                     {
                       //updateLevel("vote", $sessionId);
                       //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                       $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                        0. Vote
                        1. Deposit Funds
                        2. Request Loan
                        3. Active Loans
                        4. Balances
                        5. Deposit History
                        6. Previous Proposals
                        7. Invite New Member
8. Leave Circle";

                     }

                     else
                     {
                       $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                       1. Deposit Funds
                       2. Request Loan
                       3. Active Loans
                       4. Balances
                       5. Deposit History
                       6. Previous Proposals
                       7. Invite New Member
8. Leave Circle";
                     }

                 }

                 elseif ($debtsCheckAvailable != NULL)
                 {

                     //updateLevel("vote", $sessionId);
                     //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                     $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                      11. Pay off Loan: K" . $userCircleDebt . "!
                      1. Deposit Funds
                      2. Request Loan
                      3. Active Loans
                      4. Balances
                      5. Deposit History
                      6. Previous Proposals
                      7. Invite New Member
8. Leave Circle";

                   }

                 else
                 {
                   $response = "CON " . ucfirst($circleSelect) . " Balance: K" . $circleBalance . "
                   1. Deposit Funds
                   2. Request Loan
                   3. Active Loans
                   4. Balances
                   5. Deposit History
                   6. Previous Proposals
                   7. Invite New Member
8. Leave Circle";
                 }
                 // end of votecheck


               userLevel($sessionId);

               header('Content-type: text/plain');
               echo $response;

             break;










             // ---------------------------------------------------------------------------------




             // SEND PAYMENT   xXXXXXXXXXX (check user balance before enter pin)
             case "sendPayment":

                // Enter Amount
                if ($indexLevel == NULL) {
                  $receiver = "+" . $userResponse . "";
                  activeUser($receiver);
                  kpAuth($receiver);
                  userBalance($authToken, $receiver);

                  // Existing phone number
                  if ($userAvailable != NULL || $userAddress != NULL) {

                    updateindexLevel("amount", $sessionId);
                    ram($receiver, $sessionId);
                    username($receiver);
                    $response = "CON Enter an amount to send " . $username . ":";


                  }

                  // Non existing phone number
                  else {
                    updateindexLevel("invite", $sessionId);
                    $response = "CON Uh oh! This phone number has not yet signed up with Pollen. Would you like to invite them for free?
                      1. Invite them
                      0. Cancel";
                  }

                }

                // Enter PIN XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                elseif ($indexLevel == "amount") {

                  // CHECK THAT BALANCE IS sufficient

                  updateindexLevel("pin", $sessionId);
                  ram2($userResponse, $sessionId);
                  getRam($sessionId);
                  username($ram);
                  $response = "CON Please enter your secret pin to send K" . $userResponse . " to " . $username . ":\n";

                }

                // Check PIN
                elseif ($indexLevel == "pin") {

                  getRam2($sessionId);
                  getRam($sessionId);
                  username($ram);

                  if ($userResponse == $userPin) {
                    kpAuth($phoneNumber);
                    //payAddy($phoneNumber, $ram2, $authToken);
                    pollenPay($phoneNumber, $ram, $ram2, $authToken);

                    if ($txnStatus == "success" && $txnhash != "undefined") {
                      $response = "END You have sent " . $ram2 . " cUSD to " . $username . "\n";
                      $response .= "TxnHash: " . $txnhash . "";
                      txn($phoneNumber, $ram, $ram2, $txnhash);
                    }

                    else {
                      $response = "END There was an error sending your payment. Please dial back and try again, or contact support if the problem continues.";
                      $response .= "" . $txnStatus . " " . $txnhash . "";
                    }

                  }

                  else {

                    $response = "CON Incorrect pin! Try again. Please enter your secret pin to send K" . $ram2 . " to " . $username . ":\n";

                  }

                }

                // Invite Check
                elseif ($indexLevel == "invite") {

                  if ($userResponse == 1) {

                    updateindexLevel("inviteSent", $sessionId);
                    getRam($sessionId);
                    username($phoneNumber);
                    $message = "Hey! " . $username . " (" . $phoneNumber . ") wants to send you US Dollars on Pollen Finance. Sign up by USSD to receive payments, earn interest, and request loans! Dial *384*31322333#";
                    $recipient = $ram;
                    $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                    $gateway->sendMessage($recipient, $message);

                  }

                  elseif ($userResponse == 0) {

                    updateindexLevel("", $sessionId);
                    updateLevel(0, $sessionId);

                    $response = "CON Payment + user invite cancelled. Please choose a service.
                      1. Saving Circles
                      2. Send a Payment
                      3. Request a Payment
                      4. View Balances
                      5. Deposit/Withdraw MoMo
                      6. Help ";

                  }

                  else {
                    $response = "CON Invalid response, try again. This phone number has not yet signed up with Pollen. Would you like to invite them for free?
                      1. Invite them
                      0. Cancel";
                  }

                }


                //else { echo "fudge";}



                userLevel($sessionId);

                header('Content-type: text/plain');
                echo $response;

             break;



             // ---------------------------------------------------------------------------------




             // REQUEST PAYMENT
             case "requestPayment":

                // Enter Amount
                if ($indexLevel == NULL) {
                  $receiver = "+" . $userResponse . "";
                  activeUser($receiver);

                  // Existing phone number
                  if ($userAvailable != NULL) {

                    updateindexLevel("amount", $sessionId);
                    ram($receiver, $sessionId);
                    username($receiver);
                    $response = "CON Enter an amount to request from " . $username . ":";


                  }

                  // Non existing phone number
                  else {
                    updateindexLevel("invite", $sessionId);
                    $response = "CON Uh oh! This phone number has not yet signed up with Pollen. Would you like to invite them for free?
                      1. Invite them
                      0. Cancel";
                  }

                }

                // Enter PIN XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                elseif ($indexLevel == "amount") {

                  // CHECK THAT BALANCE IS sufficient

                  updateindexLevel("pin", $sessionId);
                  ram2($userResponse, $sessionId);
                  getRam($sessionId);
                  username($ram);
                  $response = "CON Please enter your secret pin to request K" . $userResponse . " from " . $username . ":\n";

                }

                // Check PIN
                elseif ($indexLevel == "pin") {

                  getRam2($sessionId);
                  getRam($sessionId);
                  username($ram);

                  if ($userResponse == $userPin) {


                    $response = "END You have requested " . $ram2 . " cUSD from " . $username . "\n";


                  }

                  else {

                    $response = "CON Incorrect pin! Try again. Please enter your secret pin to send K" . $ram2 . " to " . $username . ":\n";

                  }

                }

                // Invite Check
                elseif ($indexLevel == "invite") {

                  if ($userResponse == 1) {

                    updateindexLevel("inviteSent", $sessionId);
                    getRam($sessionId);
                    username($phoneNumber);
                    $message = "Hey! " . $username . " (" . $phoneNumber . ") is inviting you to Pollen Finance, a digital US Dollar wallet. Sign up by USSD to receive payments, earn interest, and request loans! Dial *384*31322333#";
                    $recipient = $ram;
                    $gateway = new AfricasTalkingGateway("sandbox", $apikey, "sandbox");
                    $gateway->sendMessage($recipient, $message);

                  }

                  elseif ($userResponse == 0) {

                    updateindexLevel("", $sessionId);
                    updateLevel(0, $sessionId);

                    $response = "CON Payment + user invite cancelled. Please choose a service.
                      1. Saving Circles
                      2. Send a Payment
                      3. Request a Payment
                      4. View Balances
                      5. Deposit/Withdraw MoMo
                      6. Help ";

                  }

                  else {
                    $response = "CON Invalid response, try again. This phone number has not yet signed up with Pollen. Would you like to invite them for free?
                      1. Invite them
                      0. Cancel";
                  }

                }


                //else { echo "fudge";}



                userLevel($sessionId);

                header('Content-type: text/plain');
                echo $response;

             break;


             // ---------------------------------------------------------------------------------




             // DEPOSIT WITHDRAW MOMO
             case "momo":

                // Ask deposit or withdraw
                if ($indexLevel == NULL) {

                  updateindexLevel("pin", $sessionId);

                  if ($userResponse == 1) {

                    ram("deposit", $sessionId);
                    $response = "CON Please enter an amount to deposit into Pollen (funds will be converted to and held in US Dollars)";

                  }

                  elseif ($userResponse == 2) {

                    ram("withdraw", $sessionId);
                    $response = "CON Please enter an amount to withdraw from Pollen to MoMo:";

                  }

                  else {
                    $response = "CON Incorrect response, try again.
                    1. Deposit MoMo
                    2. Withdraw to MoMo";
                  }
                }

                elseif ($indexLevel == "pin") {
                  ram2($userResponse, $sessionId);
                  getRam($sessionId);
                  updateindexLevel("check", $sessionId);

                  if ($ram == "deposit") {
                    $response = "CON Please enter your secret pin to deposit K" . $userResponse . ":\n";
                    $response .= "(Funds will be converted to US Dollars)";
                  }

                  elseif ($ram == "withdraw") {
                    $response = "CON Please enter your secret pin to withdraw K" . $userResponse . " to MoMo:";
                  }


                }

                // **********
                elseif ($indexLevel == "check") {

                  // **** Execute Deposit  ****
                  if ($userResponse == $userPin) {

                    getRam($sessionId);
                    getRam2($sessionId);
                    telecom($phoneNumber);

                    // **************************************************
                    if ($ram == "deposit") {

                      updateindexLevel("deposit", $sessionId);

                      // Somewhat complete ???
                      if ($telecom == "mtn" || $phoneNumber == "+18312777671") {

                        mtnTokenCollect();

                        if ($tokenStatus == "success") {
                          $uuid = gen_uuid();

                          // Request To Pay
                          $amount = $ram2;
                          //$amount = 1;
                          $currency = "ZMW";

                          //$number = substr($phoneNumber, 1);
                          $number = "260964499767"; // Chiyanika

                          $timestamp = date('Ymd_Gis');
                          //$payer = json_encode(array('partyIdType' => "MSISDN",'partyId' => $number,  ));

                          $REQUEST_BODY = json_encode(array(
                          'amount' => $amount,
                          'currency' => $currency,
                          'externalId' => $timestamp,
                          'payer' => array(
                            'partyIdType' => "MSISDN",'partyId' => $number,),
                          'payerMessage' => "Payment of K".$amount." from ".$number,
                          'payeeNote' => "Click yes to approve",
                          ));

                          mtnCollect($uuid, $bearer_token, $REQUEST_BODY);

                          if ($collectStatus == "success") {

                            mtnCheckCollect($uuid, $bearer_token);

                            if ($checkStatus == "success") {

                              if ($withdrawStatus == 1) {

                                updateindexLevel(1, $sessionId);

                                kpAuth($phoneNumber);
                                //kpDeposit($phoneNumber, $amount, $authToken);
                                $pollen = "+254701234567";
                                pollenPay($pollen, $phoneNumber, $amount, $authToken);

                                // KP transfer success
                                if ($txnStatus == "success" && $txnhash != NULL) {
                                  telecom($phoneNumber);
                                  logMomo("d", $phoneNumber, $ram2, $telecom, $txnhash);
                                  $response = "CON You have successfully deposited K" . $ram2 . "!\n";
                                  $response .= "1. Saving Circles
                                    2. Send a Payment
                                    3. Request a Payment
                                    4. View Balances
                                    5. Deposit/Withdraw MoMo
                                    6. Help ";
                                }

                                // KP TRANSFER ERROR
                                else {
                                  $response = "CON We are processing your deposit of K" . $ram2 . ".\n";
                                  $response .= "1. Saving Circles
                                    2. Send a Payment
                                    3. Request a Payment
                                    4. View Balances
                                    5. Deposit/Withdraw MoMo
                                    6. Help ";

                                  $msg = "Amount: " . $amount . "
                                          Phone Number: " . $phoneNumber . "
                                          Status: " . $txnStatus . "";
                                  $msg = wordwrap($msg,70);
                                  mail("celowhale@gmail.com","DEPOSIT ERROR",$msg);
                                }



                                // Email admin
                                $msg = "Amount: " . $amount . "
                                        Phone Number: " . $phoneNumber . "
                                        Status: " . $depositStatus . "";
                                $msg = wordwrap($msg,70);
                                mail("celowhale@gmail.com","[".$depositStatus." Deposit of K".$amount,$msg);

                              }

                              else {
                                $response = "END Error: ";
                                $response .= "" . $message . "";

                                $msg = "Amount: " . $amount . "
                                        Phone Number: " . $phoneNumber . "
                                        Status: " . $message . "";
                                $msg = wordwrap($msg,70);
                                mail("celowhale@gmail.com","DEPOSIT ERROR",$msg);
                              }


                            }

                            else {
                              $response = "END We encountered an error: ";
                              $response .= "" . $checkStatus . "";

                              $msg = "Amount: " . $amount . "
                                      Phone Number: " . $phoneNumber . "
                                      Status: " . $message . "";
                              $msg = wordwrap($msg,70);
                              mail("celowhale@gmail.com","DEPOSIT ERROR",$msg);
                            }



                          }

                          else {
                            $response = "END We encountered an error. Please try again later";
                            //ram2("mtn collect error: " . $tokenStatus . "", $sessionId);

                            $msg = "Amount: " . $amount . "
                                    Phone Number: " . $phoneNumber . "
                                    Status: MTN Collect Error" . $collectStatus . "";
                            $msg = wordwrap($msg,70);
                            mail("celowhale@gmail.com","DEPOSIT ERROR",$msg);
                          }

                        }

                        else {
                          $response = "END We encountered an error. Please try again later";

                          $msg = "Amount: " . $amount . "
                                  Phone Number: " . $phoneNumber . "
                                  Status: Token error " . $tokenStatus . "";
                          $msg = wordwrap($msg,70);
                          mail("celowhale@gmail.com","DEPOSIT ERROR",$msg);

                        }




                      }

                      // INCOMPLETE
                      elseif ($telecom == "zamtel") {

                      }

                      elseif ($telecom == "airtel") {
                        $response = "END Airtel support is coming soon!";
                      }


                    }

                    // **************************************************
                    elseif ($ram == "withdraw") {

                      updateindexLevel("deposit", $sessionId);

                      if ($telecom == "mtn" || $phoneNumber == "+18312777671") {

                        kpAuth($phoneNumber);
                        //kpDeposit($phoneNumber, $amount, $authToken);
                        $pollen = "+254701234567";
                        pollenPay($phoneNumber, $pollen, $amount, $authToken);

                        // KP transfer success
                        if ($txnStatus == "success" && $txnhash != NULL) {

                          mtnTokenDisburse();

                          if ($tokenStatus == "success") {

                            $uuid = gen_uuid();

                            $amount = $ram2;

                            $currency = "ZMW";

                            //$number = substr($phoneNumber, 1);             //fix
                            $number = "260964499767"; // Chiyanika

                            $timestamp = date('Ymd_Gis');
                            //$payer = json_encode(array('partyIdType' => "MSISDN",'partyId' => $number,  ));

                            $REQUEST_BODY = json_encode(array(
                            'amount' => $amount,
                            'currency' => $currency,
                            'externalId' => $timestamp,
                            'payee' => array(
                              'partyIdType' => "MSISDN",'partyId' => $number,),
                            'payerMessage' => "Payment of K".$amount,
                            'payeeNote' => "Payment of K".$amount." from ".$number,
                            ));

                            mtnDisburse($uuid, $bearer_token, $REQUEST_BODY);

                            if ($disburseStatus == "success") {

                              mtnCheckDisburse($uuid, $bearer_token);

                              if ($checkStatus == "success") {

                                updateindexLevel(1, $sessionId);

                                telecom($phoneNumber);
                                logMomo("w", $phoneNumber, $ram2, $telecom, $txnhash);
                                $response = "CON You have successfully withdrawn K" . $ram2 . "!\n";
                                $response .= "1. Saving Circles
                                  2. Send a Payment
                                  3. Request a Payment
                                  4. View Balances
                                  5. Deposit/Withdraw MoMo
                                  6. Help ";


                                // Email admin
                                $msg = "Amount: " . $amount . "
                                        Phone Number: " . $phoneNumber . "
                                        Status: success";
                                $msg = wordwrap($msg,70);
                                mail("celowhale@gmail.com","[Successful Withdraw of K".$amount,$msg);

                              }

                              else {
                                $response = "CON We are processing your withdraw of K" . $ram2 . ".\n";
                                $response .= "1. Saving Circles
                                  2. Send a Payment
                                  3. Request a Payment
                                  4. View Balances
                                  5. Deposit/Withdraw MoMo
                                  6. Help ";

                                $msg = "Amount: " . $amount . "
                                        Phone Number: " . $phoneNumber . "
                                        Status: " . $checkStatus . "";
                                $msg = wordwrap($msg,70);
                                mail("celowhale@gmail.com","WITHDRAW ERROR",$msg);
                              }



                            }

                            else {
                              $response = "CON We are processing your withdraw of K" . $ram2 . ".\n";
                              $response .= "1. Saving Circles
                                2. Send a Payment
                                3. Request a Payment
                                4. View Balances
                                5. Deposit/Withdraw MoMo
                                6. Help ";

                              $msg = "Amount: " . $amount . "
                                      Phone Number: " . $phoneNumber . "
                                      Status: " . $disburseStatus . "";
                              $msg = wordwrap($msg,70);
                              mail("celowhale@gmail.com","WITHDRAW ERROR",$msg);
                            }

                          }

                          else {
                            $response = "CON [token] We are processing your withdraw of K" . $ram2 . ".\n";
                            $response .= "1. Saving Circles
                              2. Send a Payment
                              3. Request a Payment
                              4. View Balances
                              5. Deposit/Withdraw MoMo
                              6. Help ";
                              $response .= "" . $responseC . "";

                            $msg = "Amount: " . $amount . "
                                    Phone Number: " . $phoneNumber . "
                                    Status: Token error " . $tokenStatus . "";
                            $msg = wordwrap($msg,70);
                            mail("celowhale@gmail.com","WITHDRAW ERROR",$msg);

                          }




                        }

                        // KP TRANSFER ERROR
                        else {
                          $response = "CON We encountered an error. Please try again:\n";
                          $response .= "1. Saving Circles
                            2. Send a Payment
                            3. Request a Payment
                            4. View Balances
                            5. Deposit/Withdraw MoMo
                            6. Help ";

                          $msg = "Amount: " . $amount . "
                                  Phone Number: " . $phoneNumber . "
                                  Status: " . $txnStatus . "";
                          $msg = wordwrap($msg,70);
                          mail("celowhale@gmail.com","DEPOSIT ERROR",$msg);
                        }




                      }

                      elseif ($telecom == "zamtel") {

                      }

                      elseif ($telecom == "airtel") {
                        $response = "END Airtel support is coming soon!";
                      }

                    }


                  }

                  else {
                    $response = "CON Incorrect secret pin, please try again:";
                  }

                }

                // INCOMPLETE
                // elseif ($indexLevel == "momoSuccess") {

                //   getRam($sessionId); getRam2($sessionId);

                //   if ($ram == "deposit") {
                //     kpAuth($phoneNumber);
                //     kpDeposit($phoneNumber, $ram2, $authToken);

                //     if ($depositStatus == "success" && $txnhash != NULL) {
                //       telecom($phoneNumber);
                //       logMomo("d", $phoneNumber, $ram2, $telecom);
                //       $response = "END You have successfully deposited K" . $ram2 . "!\n";
                //       $response .= "1. Saving Circles
                //         2. Send a Payment
                //         3. Request a Payment
                //         4. View Balances
                //         5. Deposit/Withdraw MoMo
                //         6. Help ";
                //     }

                //     else {

                //     }

                //   }

                //   // INCOMPLETE
                //   elseif ($ram = "withdraw") {



                //   }

                // }


                userLevel($sessionId);

                header('Content-type: text/plain');
                echo $response;

             break;







             // ---------------------------------------------------------------------------------






             case "help":

               // What are saving circles?
               if ($userResponse == 1) {
                 $response = "CON Saving circles are.....";
                 $response .= "0. Go Back";
               }

               // How do Pollen circles work?
               elseif ($userResponse == 2) {
                 $response = "CON How may we help you?";
                 $response .= "0. Go Back";
               }

               // Can someone steal my money?
               elseif ($userResponse == 3) {
                 $response = "CON How may we help you?";
                 $response .= "0. Go Back";
               }

               // How do I join my first circle?
               elseif ($userResponse == 4) {
                 $response = "CON How may we help you?";
                 $response .= "0. Go Back";
               }

               // Can I make my own circle?
               elseif ($userResponse == 5) {
                 $response = "CON How may we help you?";
                 $response .= "0. Go Back";
               }

               // I forgot my secret pin!
               elseif ($userResponse == 6) {
                 $response = "CON How may we help you?";
                 $response .= "0. Go Back";
               }

               // I lost my phone!
               elseif ($userResponse == 7) {
                 $response = "CON How may we help you?";
                 $response .= "0. Go Back";
               }

               // Other Help
               elseif ($userResponse == 8) {
                 $response = "CON How may we help you?";
                 $response .= "0. Go Back";
               }


               // Go Back
               elseif ($userResponse == 0) {
                 $response = "CON How may we help you?
                   1. What are saving circles?
                   2. How do Pollen circles work?
                   3. Can someone steal my money?
                   4. How do I join my first circle?
                   5. Can I make my own circle?
                   6. I forgot my secret pin!
                   7. I lost my phone!
                   8. Other help";
               }

               else
               {
                   updateLevel(1, $sessionId);

                   $response = "CON Hi " . ucfirst($userName) . ". Welcome back! \n Please choose a service.
                           1. Saving Circles
                           2. Send a Payment
                           3. Request a Payment
                           4. View Balances
                           5. Deposit/Withdraw MoMo
                           6. Help ";
               }

               userLevel($sessionId);

               header('Content-type: text/plain');
               echo $response;

             break;






        } // End of Switch (LEVEL)

    } //end of if ($userAvailable && $userAvailable['city'])




// Register
    else
    {

      if ($userResponse == "") {
        // Graduate the user to the next level, so you dont serve them the same menu
        $sql10b = "INSERT INTO `session_levels`(`session_id`, `phoneNumber`,`level`) VALUES('" . $sessionId . "','" . $phoneNumber . "', 1)";
        $db->query($sql10b);

        // Serve the menu request for name
        $response = "CON Welcome to Pollen! Please enter your first name.";
      }

      // Ask for last name
      elseif ($level == 1) {
        $firstName = ucfirst($userResponse);
        ram($firstName, $sessionId);
        updateLevel(2, $sessionId);

        //We request for last name
        $response = "CON Hi " . ucfirst($userResponse) . "! Please enter your last name.\n";
        $response .= "0. Go back";
      }

      // Ask for City
      elseif ($level == 2) {

        // Redo First Name
        if ($userResponse == "0") {

          $response = "CON Welcome to Pollen! Please enter your first name :)\n";
          //$response .= $userResponse;
          updateLevel(1, $sessionId);

        }

        else {

          $lastName = ucfirst($userResponse);
          updateLevel(3, $sessionId);
          ram2($userResponse, $sessionId);

          $response = "CON Which city/town are you located?\n";
          $response .= "0. Go back";

        }

      }

      // Ask for DOB
      elseif ($level == 3) {

        if ($userResponse == "0") {

          updateLevel(2, $sessionId);
          getRam($sessionId);
          $response = "CON Hi " . $ram . "! Please enter your last name.\n";
          $response .= "0. Go back";

        }

        else {
          $cityInput = ucfirst($userResponse);
          ram3($cityInput, $sessionId);
          updateLevel(4, $sessionId);

          $response = "CON Welcome from " . ucfirst($userResponse) . ". What is your date of birth (MMDDYYYY | eg 03281970)?\n";
          $response .= "0. Go back";
        }

      }

      // Ask for email
      elseif ($level == 4) {

        if ($userResponse == "0") {

          updateLevel(3, $sessionId);
          $response = "CON Which city/town are you located?\n";
          $response .= "0. Go back";

        }

        else {

          updateLevel(5, $sessionId);
          ram4($userResponse, $sessionId);

          $birthDate = $userResponse;
          $age = (date("md", date("U", mktime(0, 0, 0, substr($birthDate,0,-6), substr($birthDate,2,-4), substr($birthDate,-4)))) > date("md")
            ? ((date("Y") - substr($birthDate,-4)) - 1)
            : (date("Y") - substr($birthDate,-4)));

          //We request for the secretPin
          $response = "CON " . $age . " years young! What's your email? (Enter na if you don't have one):\n";
          $response .= "0. Go back";

        }

      }

      // ID
      elseif ($level == 5) {

        // Go back to ask for DOB
        if ($userResponse == "0") {
          $response = "CON What is your date of birth (MMDDYYYY | eg 03281970)?\n";
          $response .= "0. Go back";
          updateLevel(4,$sessionId);
        }

        elseif ((strpos($userResponse, '@') !== false && strpos($userResponse, '.') !== false) || $userResponse == "na") {
          updateLevel(6, $sessionId);
          ram5($userResponse, $sessionId);
          $response = "CON Last question! What is your National Registration Card ID?";
          $response .= "0. Go back";
        }

        else {
          $response = "CON Incorrect input. Your email must contain an '@' (e.g. abc@gmail.com). Please try again, or enter 'na' if you don't have an email:";
        }

      }

      // Info Check
      elseif ($level == 6) {

        if ($userResponse == "0") {
          updateLevel(5, $sessionId);
          getRamz($sessionId);

          $birthDate = $ram4;
          $age = (date("md", date("U", mktime(0, 0, 0, substr($birthDate,0,-6), substr($birthDate,2,-4), substr($birthDate,-4)))) > date("md")
            ? ((date("Y") - substr($birthDate,-4)) - 1)
            : (date("Y") - substr($birthDate,-4)));

          //We request for the secretPin
          $response = "CON " . $age . " years young! What's your email? (Enter na if you don't have one):\n";
          $response .= "0. Go back";
        }

        else {
          ram6($userResponse, $sessionId);
          updateLevel(7,$sessionId);
          getRamz($sessionId);
          $m = substr($ram4, 0, -6);
          $d = substr($ram4, 2, -4);
          $y = substr($ram4, 4);
          $response = "CON Name: " . $ram . " " . $ram2 . "
          City: " . $ram3 . "
          Date of Birth: " . $m . "/" . $d . "/" . $y . "
          Email: " . $ram5 . "
          ID: " . $ram6 . "\n";
          $response .= "1. Confirm \n";
          $response .= "0. Start over \n";

        }

      }

      // Secret Pin
      elseif ($level == 7) {

        if ($userResponse == "0") {
          $response = "CON Welcome to Pollen! Please enter your first name.";
          updateLevel(2,$sessionId);
        }

        // Ask for pin
        elseif ($userResponse == 1) {

          updateLevel(8, $sessionId);

          $response = "CON Sweet! Secure your account by entering a 4 digit secret pin (e.g. 8193):";


        }

        else {
          $response = "CON Incorrect input, try again. ";
          getRamz($sessionId);
          $m = substr($ram4, 0, -6);
          $d = substr($ram4, 2, -4);
          $y = substr($ram4, 4);
          $response = "CON Name: " . $ram . " " . $ram2 . "
          City: " . $ram3 . "
          Date of Birth (mm/dd/yyyy): " . $m . "/" . $d . "/" . $y . "
          Email: " . $ram5 . "
          ID: " . $ram6 . "\n";
          $response .= "1. Confirm \n";
          $response .= "0. Start over \n";
        }

      }

      // Secret Pin Repeat
      elseif ($level == 8) {

        if ($userResponse == "") {
          $response = "CON You must enter a 4 digit pin. Try again:";
        }

        else {
          ram7($userResponse, $sessionId);
          updateLevel(9, $sessionId);
          $response = "CON Please confirm your 4 digit pin:";
        }

      }

      // Pin Check + Register User
      elseif ($level == 9) {

        getRamz($sessionId);

        // REGISTER USER
        if ($userResponse == $ram7) {

          //getRamz($sessionId);

          // Register user with KotaniPay
          kpAuth($phoneNumber);
          kpKYC($authToken, $phoneNumber, $ram, $ram2, $ram4, $ram5, $ram6);

          if ($kycStatus == "success") {

            updateLevel(1, $sessionId);

            // Add user to DB
            $sql = "INSERT INTO `users`(`username`,`lastName`,`phonenumber`,`email`,`city`,`dob`,`pin`,`id`) VALUES ('" . $ram . "','" . $ram2 . "','" . $phoneNumber . "','" . $ram5 . "','" . $ram3 . "','" . $ram4 . "','" . $userResponse . "')";
            $result = mysqli_query($db, $sql);

            //Serve our services menu
            $response = "CON Hi " . $userName . ". Welcome to Pollen! Please choose a service.
              1. Saving Circles
              2. Send a Payment
              3. Request a Payment
              4. View Balances
              5. Deposit/Withdraw MoMo
              6. Help ";


            //Email admin
            $msg = "Name: " . $ram . " " . $ram2 . "
                    Location: " . $ram3 . "
                    DOB: " . $ram4 . "
                    Phone Number: " . $phoneNumber ;
            $msg = wordwrap($msg,70);
            mail("celowhale@gmail.com","New User from ".$ram3,$msg);

          }

          elseif ($kycStatus == "active") {

            updateLevel(1, $sessionId);

            // Add user to DB
            $sql = "INSERT INTO `users`(`username`,`lastName`,`phonenumber`,`email`,`city`,`dob`,`pin`,`id`) VALUES ('" . $ram . "','" . $ram2 . "','" . $phoneNumber . "','" . $ram5 . "','" . $ram3 . "','" . $ram4 . "','" . $userResponse . "')";
            $result = mysqli_query($db, $sql);

            //Serve our services menu
            $response = "CON Hi " . $userName . ". Welcome to Pollen! Please choose a service.
              1. Saving Circles
              2. Send a Payment
              3. Request a Payment
              4. View Balances
              5. Deposit/Withdraw MoMo
              6. Help ";


            //Email admin
            $msg = "Name: " . $ram . " " . $ram2 . "
                    Location: " . $ram3 . "
                    DOB: " . $ram4 . "
                    Phone Number: " . $phoneNumber ;
            $msg = wordwrap($msg,70);
            mail("celowhale@gmail.com","New [ACTIVE] User from ".$ram3,$msg);
          }

          else {

            $response = "CON We encountered an error attempting to register your account. This may be to an incorrect ID# or system error. An admin has been notified. Please dial back and try again.";

            //Email admin
            $msg = "Name: " . $ram . " " . $ram2 . "
                    Location: " . $ram3 . "
                    Status: " . $kycStatus . "
                    KP DESC: " . $desc . "
                    Phone Number: " . $phoneNumber ;
            $msg = wordwrap($msg,70);
            mail("celowhale@gmail.com","User registration FAILURE ".$ram3,$msg);

          }

        }

      }

      userLevel($sessionId);
      header('Content-type: text/plain');
      echo $response;





        // // Register the user
        // if ($userResponse == "")
        // {
        //     // On receiving a Blank. Advise user to input correctly based on level
        //     switch ($level)
        //     {
        //         case 0:
        //             // Graduate the user to the next level, so you dont serve them the same menu
        //             $sql10b = "INSERT INTO `session_levels`(`session_id`, `phoneNumber`,`level`) VALUES('" . $sessionId . "','" . $phoneNumber . "', 1)";
        //             $db->query($sql10b);
        //
        //             // Insert the phoneNumber, since it comes with the first POST
        //             $sql10c = "INSERT INTO `users`(`phonenumber`) VALUES ('" . $phoneNumber . "')";
        //             $db->query($sql10c);
        //
        //             // Serve the menu request for name
        //             $response = "CON Welcome to Pollen! Please enter your first name.";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //         break;
        //
        //         case 1:
        //             // Request again for name
        //             $response = "CON Your name is not supposed to be empty. Please enter your name.\n";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //         break;
        //
        //         case 2:
        //             // Request fir city again
        //             $response = "CON City not supposed to be empty. Please reply with your city \n";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //
        //         break;
        //
        //             //case 3:
        //             // Request for secretPin again
        //             //$response = "CON Secret pin is not supposed to be empty. Please create a 4-digit secret pin (e.g. 4173) \n";
        //             // Print the response onto the page so that our gateway can read it
        //             //header('Content-type: text/plain');
        //             //echo $response;
        //             //break;
        //
        //         default:
        //             // ERROR
        //             $response = "END Apologies, something went wrong... \n";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //         break;
        //     }
        // }
        //
        // else
        // {
        //     //11. Update User table based on input to correct level
        //     switch ($level)
        //     {
        //         case "0":
        //             // Serve the menu request for name
        //             $response = "END This level should not be seen...";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //         break;
        //
        //         case "1":
        //             // Update Name, Request for Last name
        //             $firstName = ucfirst($userResponse);
        //             $sql11b = "UPDATE `users` SET `username`='" . $firstName . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
        //             $db->query($sql11b);
        //
        //             updateLevel(2, $sessionId);
        //
        //             //We request for last name
        //             $response = "CON Hi " . ucfirst($userResponse) . "! Please enter your last name.";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //         break;
        //
        //         // Ask for City
        //         case "2":
        //             // Update Last Name, Request for city
        //             $lastName = ucfirst($userResponse);
        //             $sql22 = "UPDATE `users` SET `lastName`='" . $lastName . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
        //             $db->query($sql22);
        //
        //             updateLevel(3, $sessionId);
        //
        //             //We request for the city
        //             $response = "CON Which city/town are you located?\n";
        //             $response .= "0. Go back";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //         break;
        //
        //         // Ask for DOB
        //         case "3":
        //
        //             // Format userResponse
        //             $cityInput = ucfirst($userResponse);
        //             // Update city, Request for secretPin
        //             $sql11d = "UPDATE `users` SET `city`='" . $cityInput . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
        //             //$sql11d = "UPDATE `users` SET `pin`='".$userResponse."' WHERE `phonenumber` LIKE '%". $phoneNumber ."%'";
        //             $db->query($sql11d);
        //
        //             updateLevel(4, $sessionId);
        //
        //             $response = "CON Welcome from " . ucfirst($userResponse) . ". What is your date of birth (MMDDYYYY | eg 03281970)?";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //         break;
        //
        //         // Ask for email
        //         case "4":
        //             // Update city, Request for secretPin
        //             $sql4e = "UPDATE `users` SET `dob`='" . $userResponse . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
        //             $db->query($sql4e);
        //
        //             updateLevel(5, $sessionId);
        //
        //             $birthDate = $userResponse;
        //             $age = (date("md", date("U", mktime(0, 0, 0, substr($birthDate,0,-6), substr($birthDate,2,-4), substr($birthDate,-4)))) > date("md")
        //               ? ((date("Y") - substr($birthDate,-4)) - 1)
        //               : (date("Y") - substr($birthDate,-4)));
        //
        //             //We request for the secretPin
        //             $response = "CON " . $age . " years young! What's your email? (Enter na if you don't have one):";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //         break;
        //
        //
        //         // Ask for ID
        //         case "5":
        //             // Update city, Request for secretPin
        //             $sql4e = "UPDATE `users` SET `email`='" . $userResponse . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
        //             $db->query($sql4e);
        //
        //             updateLevel(6, $sessionId);
        //
        //             //We request for the secretPin
        //             $response = "CON Last question! What is your National Registration Card ID?";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //         break;
        //
        //         // Ask for secret Pin
        //         case "6":
        //             // Update city, Request for secretPin
        //             $sql4e = "UPDATE `users` SET `id`='" . $userResponse . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
        //             $db->query($sql4e);
        //
        //             updateLevel(7, $sessionId);
        //
        //             //We request for the secretPin
        //             $response = "CON Nice! Before we continue, please set a 4-digit secret pin. We will ask you for it whenever you perform an important action (i.e. requesting funds).";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //         break;
        //
        //
        //         case "7":
        //             $sql13 = "UPDATE `users` SET `pin`='" . $userResponse . "' WHERE `phonenumber` LIKE '%" . $phoneNumber . "%'";
        //             $db->query($sql13);
        //             // We graduate the user to level 8
        //             $sql14 = "UPDATE `session_levels` SET `level`=8 WHERE `session_id`='" . $sessionId . "'";
        //             $db->query($sql14);
        //             $response = "END Congratulations, you're registered with Pollen! \n \n Do not forget your secret pin (" . $userResponse . ")! \n \n Please dial back to view the main menu.";
        //             //$response = "CON Let's make sure you remember it! Please enter you secret pin.";
        //             //header('Content-type: text/plain');
        //             echo $response;
        //
        //             // Email admin
        //             $msg = "Name: " . ucfirst($textArray[0]) . " " . ucfirst($textArray[1]) . "
        //                     Location: " . ucfirst($textArray[2]) . "
        //                     Crops: " . $textArray[3] . "
        //                     Phone Number: " . $phoneNumber ;
        //             $msg = wordwrap($msg,70);
        //             mail("ericphotons@gmail.com","New User from ".$textArray[2],$msg);
        //
        //         break;
        //
        //             //case "8":
        //             //$sqlpinCheck = "SELECT * FROM users WHERE phonenumber LIKE '%" . $phoneNumber . "%'";
        //             //$pinCheckQuery  = $db->query($sqlpinCheck);
        //             //$pinCheckAvailable = $pinCheckQuery->fetch_assoc();
        //             //$pinCheck = $pinCheckAvailable['pin'];
        //             //if ($userResponse == $pinCheck) {
        //             // We graduate the user to level 9
        //             //$sql15 = "UPDATE `session_levels` SET `level`=9 WHERE `session_id`='".$sessionId."'";
        //             //$db->query($sql15);
        //             //$response = "END Sweet! Congratualations on registering with Pollen :) Dial back to see services.";
        //             //header('Content-type: text/plain');
        //             //echo $response;
        //             //  }
        //             //  else {
        //             //$sqlDemotePin = "UPDATE `session_levels` SET `level`=8 WHERE `session_id`='".$sessionId."'";
        //             //$db->query($sqlDemotePin);
        //             //$response = "CON Oops, that did't match what you told us. Please try again.";
        //             //header('Content-type: text/plain');
        //             //echo $response;
        //             //}
        //             //  break;
        //
        //
        //
        //         default:
        //             // Request for city again
        //             $response = "END Apologies, something went wrong... \n";
        //
        //             // Print the response onto the page so that our gateway can read it
        //             header('Content-type: text/plain');
        //             echo $response;
        //             //break;
        //
        //     }
        // }





    } // end register
















}

?>
