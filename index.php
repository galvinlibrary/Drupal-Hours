<?php

function format_iit_time ($time){ 
	$time = str_replace(':00','',$time);
	$time = str_replace('am','a.m.',$time);
	$time = str_replace('pm','p.m.',$time);
	return $time;
}

//echo getcwd() . "<br/>";
$is24=false;
date_default_timezone_set("America/Chicago");
$dateFormat="Y-m-d";
$timeFormat="g:ia";
$debug=true;

$two_days_ago = date("Y-m-d", time()-172800); 
$yesterday=date("Y-m-d", time()-86400); 
$today = date("l, F j");
$APIformat="Y-m-d";

$timeMin = date($APIformat) . 'T00:00:00.000Z';
$timeMax = date($APIformat) . 'T23:59:00.000Z';


$key = file_get_contents('GoogleAPIkey.txt'); 
// not included in github account for security. Uses digitalservices API key
if(($key== NULL)||($key=="")){
  $error=1;
}        
    
$calendar="iit.edu_8l0d8qd4qtfn7skmgkiu55uv58%40group.calendar.google.com";
$url='https://www.googleapis.com/calendar/v3/calendars/' . $calendar . '/events?singleEvents=true&orderby=startTime&timeMin=' . $timeMin . '&timeMax=' . $timeMax . '&maxResults=1&key=' . $key;
//this works more reliably than only getting one event

//echo $url;

    $json_file = file_get_contents($url);
    // convert the string to a json object
    $jsonObj = json_decode($json_file);
    $items = $jsonObj->items;

foreach ($items as $item) {
  $title = $item->summary;
  // Google Calendar API v3 uses dateTime field if event is less than 24 hours, or date field if it is
  if (isset($item->start->dateTime)){
    $startTime = date('Hi',strtotime(substr($item->start->dateTime, 0, 19)));
    if ( ($startTime == '0000') ||($startTime == '2400'))
      $is24=true;
    //$startTime = substr($item->start->dateTime, 0, 19)));
    $endTime = format_iit_time(date($timeFormat,strtotime(substr($item->end->dateTime, 0,19))));
    $eventDate = date($dateFormat,strtotime($item->start->dateTime));
  }
  else {
    $startTime='0000';
    $endTime = 2400;
    $eventDate = date($dateFormat,strtotime($item->start->date));
  }

  $dow = date('l',strtotime($eventDate));

  if ($debug) {
    echo "<p>Hours for $today</p> ";
    if ($is24)
      echo "Galvin is open until $endTime";
    else
      echo "Galvin is open from $startTime until $endTime";
  }
}


?>

