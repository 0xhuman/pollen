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


    // Check if the user is available (yes)->Serve the menu; (no)->Register the user
    if ($userAvailable && $userAvailable['city'] != NULL && $userAvailable['username'] != NULL) {
          $c1 = ""; $c2 = ""; $c3 = "";
          switch ($level) {
                  case "0":
                        // Graduate user to next level & Serve Main Menu
                        //$sql0 = "UPDATE `session_levels` SET `level`=1 WHERE `session_id`='".$sessionId."'";
          			        //$db->query($sql0);
                        $sql0 = "INSERT INTO `session_levels`(`session_id`,`phoneNumber`,`level`) VALUES('" . $sessionId . "','" . $phoneNumber . "',1)";
                        $resultUserLvl0 = mysqli_query($db, $sql0);



                        //Serve our services menu
                        $response = "CON Hi " . ucfirst($userAvailable['username']) . ". Welcome back! Please choose a service.\n";
                        $response .= "1. My Circles \n";
                        $response .= "2. Join a Circle \n";
                        $response .= "3. Weather Station \n";
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


                      //$sqlCircle = "select circleName from circles where JSON_SEARCH(members,'" . $phoneNumber . "') IS NOT NULL";
                      //$sqlCircle    = "SELECT * FROM circleMembers WHERE phonenumber in('" . $phoneNumber . "')";
                      $sqlmyCircles    = "SELECT circleID FROM circleMembers WHERE phonenumber LIKE '%" . $phoneNumber . "%' LIMIT 3";
                      $resultCircle = mysqli_query($db, $sqlmyCircles);
                      //$circleAvailable = $resultCircle->fetch_assoc();
                      //$circles = $resultCircle[0];

                      $circleID = "";
                      while($circleID = mysqli_fetch_assoc($resultCircle)) {  // DOES THIS NEED TO BE FETCH ARRAY????
                        // Fetch circleID
                        $circleID = $circleID['circleID'];
                        // Set C# variables to circleID know order/index
                        ${"c" . $r} = $circleID;
                        // Set circleIndex in DB so we can access this in other swtich cases
                        $sqlcircleIndex = "UPDATE circleMembers SET circleIndex = '" . $r . "' WHERE phonenumber LIKE '%" . $phoneNumber . "%' AND circleID LIKE '%" . $circleID . "' ";
                        $db->query($sqlcircleIndex);


                        //$c1 = $circleID;
                      //$response .= "1. " . $circleID . "\n"; } // DELETE THIS BRACKET???
                      $response .= "" . $r . ". " . $circleID . "\n";
                      $r++;

                    } //END of While loop



                      $sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
                      $levelQuery = $db->query($sql);
                      if ($result = $levelQuery->fetch_assoc()) {
                          $level = $result['level'];     }

                      header('Content-type: text/plain');
                      echo $response;
                      //echo $c1; echo ${"c" . $userResponse}; echo $c2;

                      // Store c variable in DB to refer in next level?
                      //$circleSelect = ${"c" . $userResponse};
                      $sqlc1 = "UPDATE users SET c1 = '" . $c1 . "' where phonenumber LIKE '" . $phoneNumber . "'";
                      $db->query($sqlc1);
                      $sqlc2 = "UPDATE users SET c2 = '" . $c2 . "' where phonenumber LIKE '" . $phoneNumber . "'";
                      $db->query($sqlc2);
                      $sqlc3 = "UPDATE users SET c3 = '" . $c3 . "' where phonenumber LIKE '" . $phoneNumber . "'";
                      $db->query($sqlc3);


                    }//}

                    // JOIN A CIRCLE
                    elseif ($userResponse == 2){
                      $response = "CON Please enter your 6-digit circle invite code. \n";

                      $sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
                      $levelQuery = $db->query($sql);
                      if ($result = $levelQuery->fetch_assoc()) {
                          $level = $result['level'];
                      }

                      header('Content-type: text/plain');
                      echo $response;
                    }

                    // WEATHER STATION
                    elseif ($userResponse == 3){
                      $response = "CON Weather Station \n";
                      $response .= "1. " . ucfirst($userCity) . " Weather \n";
                      $response .= "or Enter a city in Zambia \n";

                      $sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
                      $levelQuery = $db->query($sql);
                      if ($result = $levelQuery->fetch_assoc()) {
                          $level = $result['level'];
                      }

                      header('Content-type: text/plain');
                      echo $response;
                    }
                    break;

                case "2":

                   // CIRCLE ACTIONS (2a)
                   if ($textArray[0] == 1){
                     // Graduate user to level 3
                     $sqlLevel2 = "UPDATE `session_levels` SET `level`=3 where `session_id`='" . $sessionId . "'";
                     $db->query($sqlLevel2);

                     // LOG CIRCLE SELECT XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                     // Fetch circleMembers data
                     $sqlcircle2 = "SELECT * FROM circleMembers WHERE phonenumber LIKE '%" . $phoneNumber . "%' AND circleIndex LIKE '%" . $userResponse . "'";
                     $circleQuery2  = $db->query($sqlcircle2);
                     $circlesAvailable = $circleQuery2->fetch_assoc();
                     $circleSelect = $circlesAvailable['circleID'];
                     // Update sessionlevels data
                     $sqlcircleSelect = "UPDATE session_levels SET circleSelect ='" . $circleSelect . "' where `session_id`='" . $sessionId . "'";
                     $db->query($sqlcircleSelect);

                     if ($userResponse == 1 || $userResponse == 2 || $userResponse == 3) {
                       //$response = "CON Welome to " . $circle1 . ". Please choose an action. \n"
                       $response = "CON Welcome to " . $circleSelect . ". Please choose an action. \n";
                       $response .= "1. View Balances \n";
                       $response .= "2. Pay-in Funds \n";
                       $response .= "3. Request Funds \n";
                       $response .= "4. Leave Circle \n";
                       $response .= "5. Go back \n";

                       $sql        = "select level from session_levels where session_id ='" . $sessionId . " '";
                       $levelQuery = $db->query($sql);
                       if ($result = $levelQuery->fetch_assoc()) {
                           $level = $result['level'];
                       }

                       header('Content-type: text/plain');
                       echo $response;
                       echo ${"c" . $userResponse};
                       echo $c1;
                     }
                     else {
                       // ERROR .... try to add demote and CON
                       $response = "END Error, incorrect input. \n";
                       header('Content-type: text/plain');
                       echo $response;
                     }


                   } //end of if ($lv0v == 1){

                 // INVITE CODE CHECK
                 elseif ($textArray[0] == 2){
                  $inputCode = $userResponse;
                  // a = Match inputCode to any code (1 = true, 0 = false)
                  // b = Match PHONENUMBER to any number associated with the valid code (1 = true, 0 = false)...may need to be nested in if(inputcode=true)

                  // CONFIRMATION
                  //if ($a == 1 && $b == 1) {
                    $response = "END Congrats! You have successfully joined [circle name]. Redial the USSD menu to view the circle. \n";
                    header('Content-type: text/plain');
                    echo $response;
                  //}

                  // DENIAL
                  //if ($a == 1 && $b == 0) {
                    //$response = "END Uh oh. Looks like you are not a member of this circle. Please talk to the circle leader if this is wrong. \n";
                  //}

                  // INCORRECT
                  //if ($a == 0) {
                    //$response = "END Oops. Looks like that code isn't valid. Please try again. \n";
                  //}

               }

               // WEATHER STATION LEVEL 2
               elseif ($textArray[0] == 3){

                 if ($userResponse == 1) {
                   // WEATHER API JAZZ
                   $userCityL = strtolower($userCity);
                   $sqlmap    = "SELECT * FROM coordinates WHERE LOWER(city) LIKE '%" . $userCityL . "%' LIMIT 1";
                   $mapQuery  = $db->query($sqlmap);
                   $mapAvailable = $mapQuery->fetch_assoc();
                   $lon       = $mapAvailable['lon'];
                   $lat       = $mapAvailable['lat'];
                   $jsonfile = file_get_contents("https://api.openweathermap.org/data/2.5/onecall?lat=" . $lat . "&lon=" . $lon . "&exclude=minutely,hourly&appid=4af2b0a91ec1e9a97c54df34b2d3a119");
                   $jsondata = json_decode($jsonfile, true);

                   $response = "END The 8 day forecast for " . ucfirst($userCity) . " is: \n";
                   //$response .= "\n";
                   foreach ($jsondata['daily'] as $day => $value) {
                       //print_r($day);
                       $desc     = ucfirst($value['weather'][0]['description']);
                       $max_temp = round($value['temp']['max'] - 273.15);
                       $min_temp = round($value['temp']['min'] - 273.15);
                       $pressure = $value['pressure'];
                       $response .= "-" . $desc . ": " . $min_temp . "-" . $max_temp . "C \n";
                   }
                       header('Content-type: text/plain');
                       echo $response;
                   } // End of jsondata loop
                 //} // End of if (userResponse == 1) {

                else {
                  $userCityL2 = strtolower($userResponse);
                  $sqlmap2    = "SELECT * FROM coordinates WHERE LOWER(city) LIKE '%" . $userCityL2 . "%' LIMIT 1";
                  $mapQuery2  = $db->query($sqlmap2);
                  $mapAvailable2 = $mapQuery2->fetch_assoc();
                  $lon2       = $mapAvailable2['lon'];
                  $lat2       = $mapAvailable2['lat'];
                  $jsonfile = file_get_contents("https://api.openweathermap.org/data/2.5/onecall?lat=" . $lat2 . "&lon=" . $lon2 . "&exclude=minutely,hourly&appid=4af2b0a91ec1e9a97c54df34b2d3a119");
                  $jsondata = json_decode($jsonfile, true);

                  $response = "END The 8 day forecast for " . ucfirst($userResponse) . " is: \n";
                  //$response .= "\n";
                  foreach ($jsondata['daily'] as $day => $value) {
                      //print_r($day);
                      $desc     = ucfirst($value['weather'][0]['description']);
                      $max_temp = round($value['temp']['max'] - 273.15);
                      $min_temp = round($value['temp']['min'] - 273.15);
                      $pressure = $value['pressure'];
                      $response .= "-" . $desc . ": " . $min_temp . "-" . $max_temp . "C \n";
                  }
                      header('Content-type: text/plain');
                      echo $response;
                  } // End of jsondata loop
                } // End of elseif textarrray =3

              break;


            case "3":

              // VIEW BALANCES
              if ($userResponse == 1) {

                // ReFetch circleSelect data
                $sqlcircle2 = "SELECT * FROM circleMembers WHERE phonenumber LIKE '%" . $phoneNumber . "%' AND circleIndex LIKE '%" . $userResponse . "'";
                $circleQuery2  = $db->query($sqlcircle2);
                $circlesAvailable = $circleQuery2->fetch_assoc();
                $circleSelect = $circlesAvailable['circleID'];

                $response = "END Here is the member balances for " . $circleSelect . ": \n";

                // Fetch members of the circle
                $sqlcircleMembers = "SELECT phonenumber FROM circleMembers WHERE circleID LIKE '%" . $circleSelect . "%' ";
                $resultCircleMembers = mysqli_query($db, $sqlcircleMembers);
                $circleMembers = "";
                $membersName = "";

                //Loop through circleMembers
                while($circleMembers = mysqli_fetch_assoc($resultCircleMembers)) {
                  $circleMembers = $circleMembers['phonenumber'];

                  $sqlmembersName = "SELECT username FROM users WHERE phonenumber LIKE '%" . $circleMembers . "%' ";
                  $resultMembersName = mysqli_query($db, $sqlmembersName);
                  $membersNameAvailable = mysqli_fetch_assoc($resultMembersName);
                  $membersName = $membersNameAvailable['username'];

                  $response .= "" . $r . ". " . $membersName . "\n";
                  //$response .= "" . $r . ". " . $circleMembers . "\n";
                  $r++;
                }


                //CON with user balances, 0 to go back
                header('Content-type: text/plain');
                echo $response;
              }

              // PAY-IN FUNDS
              elseif ($userResponse == 2) {
                //CON enter amount, confirm & pin, receipt
                $response = "END Please enter an amount to pay-in (KWACHA): \n";
                header('Content-type: text/plain');
                echo $response;
              }

              // REQUEST FUNDS
              elseif ($userResponse == 3) {
                //CON enter amount, confirm & pin, receipt
                $response = "END Please enter an amount to request (KWACHA): \n";
                header('Content-type: text/plain');
                echo $response;
              }

              // LEAVE CIRCLE
              elseif ($userResponse == 4) {
                //CON tbh idk what to do
                $response = "END Are you sure you want to leave [circlename]? \n";
                header('Content-type: text/plain');
                echo $response;
              }

              // GO BACK
              elseif ($userResponse == 5) {
                //demote user to level 1
                $sqlLevelDemote = "UPDATE `session_levels` SET `level`=1 where `session_id`='" . $sessionId . "'";
                $db->query($sqlLevelDemote);
              }

          // default:
            //$response = "CON You have to choose a service. \n";
            //header('Content-type: text/plain');
            //echo $response;

            break;


      } // End of Switch (LEVEL)

    //} // End of For Loop



} //end of if ($userAvailable && $userAvailable['city'])



else{
  //10. Register the user
  if($userResponse==""){
    //10a. On receiving a Blank. Advise user to input correctly based on level
    switch ($level) {
        case 0:
          //10b. Graduate the user to the next level, so you dont serve them the same menu
           $sql10b = "INSERT INTO `session_levels`(`session_id`, `phoneNumber`,`level`) VALUES('".$sessionId."','".$phoneNumber."', 1)";
           $db->query($sql10b);

           //10c. Insert the phoneNumber, since it comes with the first POST
           $sql10c = "INSERT INTO `users`(`phonenumber`) VALUES ('".$phoneNumber."')";
           $db->query($sql10c);

           //10d. Serve the menu request for name
           $response = "CON Welcome to Pollen! Please enter your name.";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            break;

        case 1:
          //10e. Request again for name
            $response = "CON Your name is not supposed to be empty. Please enter your name.\n";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            break;

        case 2:
          //10f. Request fir city again
        $response = "CON City not supposed to be empty. Please reply with your city \n";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            break;

        default:
          //10g. Request for city again
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
        case 0:
           //11a. Serve the menu request for name
           $response = "END This level should not be seen...";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            break;

        case 1:
          //11b. Update Name, Request for city
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

        case 2:
          //11d. Update city
            $sql11d = "UPDATE `users` SET `city`='".$userResponse."' WHERE `phonenumber` LIKE '%". $phoneNumber ."%'";
            $db->query($sql11d);

          //11e. Change level to 0
            $sql11e = "INSERT INTO `session_levels`(`session_id`,`phoneNumber`,`level`) VALUES('".$sessionId."','".$phoneNumber."',1)";
            //$db->query($sql11e);
            $resultUserLvle = mysqli_query($db, $sql11e);

          //11f. Serve services menu...
          $response = "END Congrats " . ucfirst($userAvailable['username'])  . ", you have registered with Pollen! Dial back at *384*313233# to view services.\n";
          //$response .= "1. My Circles \n";
          //$response .= "2. Join a Circle \n";
          //$response .= "3. " . ucfirst($userResponse) . " Weather \n";
          //$response .= "4. Start a Circle \n";
          //$response .= "5. Cash-in/out \n";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            break;

        default:
          //11g. Request for city again
        $response = "END Apologies, something went wrong... \n";

          // Print the response onto the page so that our gateway can read it
          header('Content-type: text/plain');
          echo $response;
            break;
    }
  }}}

?>
