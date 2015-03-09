<?php

  function load_calendar_data($daysToLoad, $calendar, $table){
    require_once("../mysqli_connect_update.php");
    $error=0;// check for errors and send email if problem. 
    $two_days_ago = date("Y-m-d", time()-172800);   
    $timeMin = $two_days_ago . 'T00:00:00.000Z';
    $timeMax = date("Y-m-d", time()+(($daysToLoad*86400)+86400)) . 'T23:59:00.000Z';
    $debug=false;
    $query="";
    $sql="";
    $dateFormat="Y-m-d";
    $timeFormat="Hi";
      
    $key = file_get_contents('api_key.txt'); 
    // not included in github account for security. Uses digitalservices API key
    if(($key== NULL)||($key=="")){
      $error=1;
    }    

    $url='https://www.googleapis.com/calendar/v3/calendars/' . $calendar . '/events?singleEvents=true&orderby=startTime&timeMin=' . $timeMin . '&timeMax=' . $timeMax . '&key=' . $key;
    if ($debug)
      echo "$url<br/>";
    
    $json_file = file_get_contents($url);
    // convert the string to a json object
    $jsonObj = json_decode($json_file);
    $items = $jsonObj->items;

    foreach ($items as $item) {
        $title = $item->summary;
        if (preg_match("/closed/i", $title)) {$closed = 1; } else {$closed = 0;}// search for closed in title
        if (preg_match("/24/i", $title)) {$is24 = 1; } else {$is24 = 0;} // search for 24 in title in case entry does not have the correct date

        // Google Calendar API v3 uses dateTime field if event is less than 24 hours, or date field if it is
        if (isset($item->start->dateTime)){
          $startTime = date($timeFormat, strtotime(substr($item->start->dateTime, 0, 19)));
          $endTime = date($timeFormat,strtotime(substr($item->end->dateTime, 0,19)));
          $eventDate = date($dateFormat,strtotime($item->start->dateTime));
        }
        else {
          $startTime='0000';
          $endTime = 2400;
          $eventDate = date($dateFormat,strtotime($item->start->date));
        }

        $dow = date('l',strtotime($eventDate));
        $sql .=  "('$title', '$eventDate', '$dow', '$startTime', '$endTime', '$closed', '$is24'), ";

        if ($debug) {
          echo "$eventDate | $startTime - $endTime |Open 24 hours? $is24 | Closed? $closed | $dow | $title <br/>";
        }
    }

    // remove that final ", " from the string
    $sql = substr($sql, 0, -2);
    
    if(($sql== NULL)||($sql=="")){
      $error=1;
    }
    else if ($error != 1){
      $query = "INSERT INTO " . $table . " (event_title, ymd, dow, opening, closing, is_closed, is_24) VALUES " . $sql . ";";
      if ($debug){
        echo "<p>$query</p>";
      }
      $sql = "TRUNCATE " . $table . ";";
      $r1 = mysqli_query($dbc, $sql);
      //insert the new entries
      $r2 = mysqli_query($dbc, $query);
    }

    if ($error == 1){
      $body="An error occurred while processing Google calendar data for $table. Please investigate.";
      echo $body;
      $body = wordwrap($body, 40);
      mail('digitalservices@iit.edu', 'Batch process failure', $body, "From: cmcclur1@iit.edu");
    }
  }// end function

?>