<?php

$debug=true;
$dateFormat="l, F j";
$timeFormat="g:ia";
$now=time();
date_default_timezone_set('America/Chicago');

//Communications & Marketing format for times
function format_iit_time ($time){ 
	$time = str_replace(':00','',$time);
	$time = str_replace('am','a.m.',$time);
	$time = str_replace('pm','p.m.',$time);
	return $time;
}

//load developer key
function get_googleAPI_key(){
  $localDebug=true;
  $file='GoogleAPIkey.txt';
  
  if ($localDebug==true)
    $key = file_get_contents ($file);
  else
    //working dir in Drupal is /var/www/drupal/
    $key = file_get_contents('sites/all/modules/custom/hours/' . $file); 
  // not included in github account for security. Uses digitalservices API key
  if(($key== NULL)||($key==""))
    trigger_error('Google API key not found', E_USER_NOTICE);
  else
    return $key;
}

//retrieve JSON data from a Google Calendar (public)
function get_calendar_data($calendar, $libraryDisplayName='Galvin', $dateToGet=0){
  global $debug;
  $key = get_googleAPI_key();
  $APIformat="Y-m-d";
  $timeMin = date($APIformat,time()+$dateToGet) . 'T00:00:00.000Z';
  $timeMax = date($APIformat,time()+$dateToGet) . 'T23:59:00.000Z';
  $url='https://www.googleapis.com/calendar/v3/calendars/' . $calendar . '/events?singleEvents=true&orderby=startTime&timeMin=' . 
      $timeMin . '&timeMax=' . $timeMax . '&maxResults=1&key=' . $key;
    //this works more reliably than only getting one event
  if ($debug)
    echo $url . "<br/>";
  
  $jsonFile = file_get_contents($url);
  if (!$jsonFile) {
      trigger_error('NO DATA returned from url.', E_USER_NOTICE);
  }
  else {
    // convert the string to a json object
    $jsonObj = json_decode($jsonFile);
    $dateData = $jsonObj->items;
    $msg=format_calendar_data($dateData, $libraryDisplayName);
    return $msg;
  }
}

function format_calendar_data($dateData, $libraryDisplayName){// default is to use Galvin and today's Unix date
  global $debug, $now, $dateFormat, $timeFormat, $isOpen;
  $isOpen=0;
  //start with defaults to fail gracefully
  $now=time();
  $startTime=0;
  $endTime=0;
  $msg="no data available";
    foreach ($dateData as $item) {
      // check for closed first
        $title = $item->summary;
        if ($debug)
          echo "TITLE $title <br/>";
        
        if (stripos($title,"closed")>=0) {
          $msg=$title;
          $isOpen=-1;
          return $msg;
        } 
        
      // Google Calendar API v3 uses the date field if event is a full day long, or the dateTime field if it is less than 24 hours
      if (isset($item->start->dateTime)){
          $tmpStart=strtotime(substr($item->start->dateTime, 0,16));
          $tmpEnd=strtotime(substr($item->end->dateTime, 0,16));
        }
      elseif (isset($item->start->date)){
        $tmpStart=strtotime(substr($item->start->date, 0,16));
        $tmpEnd=strtotime(substr($item->end->date, 0,16));
      }
      else{
        return $msg;
      }
      $startTime = format_iit_time(date($timeFormat,$tmpStart));
      $endTime = format_iit_time(date($timeFormat,$tmpEnd));
      
      if ($debug){
        echo "<p>$tmpStart start  $startTime " . "</p>";
        echo "<p>$tmpEnd end $endTime </p>";
        echo "<p>$now now</p>";
      }      
      
      if ($endTime=="12a.m."){
        if ($startTime=="12a.m.")
          $msg="Open 24 hours";
        else
          $msg="Open from $startTime - overnight";
      }
      else {
        $msg="Today's hours: $startTime - $endTime"; 
      }
      
      
      if ( ($now < $tmpStart) || ($now > $tmpEnd)|| ($isOpen == -1) ){
        $isOpen = 0;
      }
      else {
        $isOpen = 1;
      }    
      
    }// end foreach
    
    return $msg;
}


function display_todays_hours_info($calendar, $libraryDisplayName='Galvin'){
  global $dateFormat, $timeFormat, $isOpen;
  $openMsg="";
  $msg=get_calendar_data($calendar, $libraryDisplayName);
  if ($isOpen<=0){
    $openMsg="<span=\"closed\">closed</span>";   
  }          
  else {
    $openMsg="<span=\"open\">open</span>";
  }
  echo "<p class=today>".date($dateFormat).", " . format_iit_time(date($timeFormat))."</p>";
  echo "<p>Currently: $openMsg</p>";
  echo "<p>$msg</p>";
}

?>

