<?php

function format_iit_time ($time){ 
	$time = str_replace(':00','',$time);
	$time = str_replace('am','a.m.',$time);
	$time = str_replace('pm','p.m.',$time);
	return $time;
}

function get_calendar_data($url){
  $jsonFile = file_get_contents($url);
  // convert the string to a json object
  $jsonObj = json_decode($jsonFile);
  $items = $jsonObj->items;
  return $items;
}

//echo getcwd() . "<br/>";
$is24=false;
date_default_timezone_set("America/Chicago");
$dateFormat="l, F j";
$timeFormat="g:ia";
$debug=true;
$hours24=86400;
$two_days_ago = date("Y-m-d", time()-172800); 
$yesterday=date("Y-m-d", time()-86400); 
$today = date("l, F j");
$APIformat="Y-m-d";


$getDate=$hours24*3;
$timeMin = date($APIformat,time()+$getDate) . 'T00:00:00.000Z';
$timeMax = date($APIformat,time()+$getDate) . 'T23:59:00.000Z';


$key = file_get_contents('GoogleAPIkey.txt'); 
// not included in github account for security. Uses digitalservices API key
if(($key== NULL)||($key=="")){
  $error=1;
}        
    
$calendar="iit.edu_8l0d8qd4qtfn7skmgkiu55uv58%40group.calendar.google.com";
$url='https://www.googleapis.com/calendar/v3/calendars/' . $calendar . '/events?singleEvents=true&orderby=startTime&timeMin=' . $timeMin . '&timeMax=' . $timeMax . '&maxResults=1&key=' . $key;
//this works more reliably than only getting one event

//echo $url;

$items = get_calendar_data($url);

foreach ($items as $item) {
  $is24=false;
  $title = $item->summary;
  // Google Calendar API v3 uses dateTime field if event is less than 24 hours, or date field if it is
  if (isset($item->start->dateTime)){
    $startTime = format_iit_time(date($timeFormat,strtotime(substr($item->start->dateTime, 0,19))));
    $endTime = format_iit_time(date($timeFormat,strtotime(substr($item->end->dateTime, 0,19))));
    $eventDate = date($dateFormat,strtotime($item->start->dateTime));
  }
  else {
    $is24=true;
    $startTime = 0;
    $endTime = format_iit_time(date($timeFormat,strtotime(substr($item->end->date, 0,19))));
    $eventDate = date($dateFormat,strtotime($item->start->date));
  }

  $dow = date('l',strtotime($eventDate));

  if ($debug) {
    echo "<p>Hours for $eventDate:</p> ";
    if ($is24)
      echo "Galvin is open from $startTime until $endTime"; 
    else
      echo "Galvin is open from $startTime until $endTime";
  }
}


?>

