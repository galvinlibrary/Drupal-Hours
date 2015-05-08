<?php

$debug=true;
date_default_timezone_set('America/Chicago');

//Communications & Marketing format for times
function format_iit_time ($time){ 
	$time = str_replace(':00','',$time);
	$time = str_replace('am','a.m.',$time);
	$time = str_replace('pm','p.m.',$time);
	return $time;
}

//retrieve JSON data from a Google Calendar (public)
function get_calendar_data($url){
  $jsonFile = file_get_contents($url);
  // convert the string to a json object
  $jsonObj = json_decode($jsonFile);
  $items = $jsonObj->items;
  return $items;
}

//load developer key
function get_googleAPI_key(){
  global $debug;
  $file='GoogleAPIkey.txt';
  
  if ($debug==true)
    $key = file_get_contents ($file);
  else
    //working dir in Drupal is /var/www/drupal/
    $key = file_get_contents('sites/all/modules/custom/hours/' . $file); 
  // not included in github account for security. Uses digitalservices API key
  if(($key== NULL)||($key==""))
    return -1;
  else
    return $key;
}

// Using Google Calendar API v3. Parses JSON data from a public calendar.
function display_google_calendar_hours($calendar,$days){
  $hours24=0;
  $dateFormat="l, F j";
  $timeFormat="g:ia";
  $key = get_googleAPI_key();
  if ($key==-1){
    echo "error getting API key";
    exit;
  }
  for ($i=1; $i<=$days; $i++){

    $APIformat="Y-m-d";
    $timeMin = date($APIformat,time()+$hours24) . 'T00:00:00.000Z';
    $timeMax = date($APIformat,time()+$hours24) . 'T23:59:00.000Z';

    $url='https://www.googleapis.com/calendar/v3/calendars/' . $calendar . '/events?singleEvents=true&orderby=startTime&timeMin=' . 
      $timeMin . '&timeMax=' . $timeMax . '&maxResults=1&key=' . $key;
    //this works more reliably than only getting one event

    $items = get_calendar_data($url);
    if(($items== NULL)||($items=="")){
      echo "error getting calendar data";
      exit;      
    }
    
    //start with defaults to fail gracefully
    $eventDate=date($dateFormat);
    $startTime=0;
    $endTime=0;
    
    foreach ($items as $item) {
      $is24=false;
      // Google Calendar API v3 uses dateTime field if event is less than 24 hours, or date field if it is
      if (isset($item->start->dateTime)){
        $startTime = substr($item->start->dateTime, 11,5);
        $endTime = format_iit_time(date($timeFormat,strtotime(substr($item->end->dateTime, 0,19))));
        $eventDate = date($dateFormat,strtotime($item->start->dateTime));
      }
      else {
        $is24=true;
        $eventDate = date($dateFormat,strtotime($item->start->date));
      }

      echo "$eventDate:<br/>";
      if ($is24){
        echo "Galvin is open 24 hours"; 
      }
      elseif ($startTime=="00:00"){
        echo "Galvin is open overnight until $endTime";
      }
      else {
        echo "Galvin is open from $startTime until $endTime";
      }
    }
    $hours24 += 86400;
  }//end for loop
}//end function

?>

