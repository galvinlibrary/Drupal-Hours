<?php
date_default_timezone_set('America/Chicago');


function get_and_format_todays_date_time(){
  $dateFormat="l, F j";
  $timeFormat="g:ia";
  $today = date($dateFormat).", " . format_iit_time(date($timeFormat));
  return $today;
}

//Communications & Marketing format for times
function format_iit_time ($time){ 
	$time = str_replace(':00','',$time);
	$time = str_replace('am','a.m.',$time);
	$time = str_replace('pm','p.m.',$time);
	return $time;
}
//MK - no longer needed. environment variable is set directly below.
//load developer key
//function get_googleAPI_key(){
// $file = getenv('GOOGLE_API');
//  $drupalDir='sites/all/modules/custom/display_hours/';
//  if (file_exists($drupalDir) == true){
//    $file = $drupalDir . $file; 
//  }
//  $key = file_get_contents($file); 
//  if(($key== NULL)||($key=="")){
//    trigger_error('Google API key not found', E_USER_NOTICE);
//  }
//  else {
//    return $key;
//  }
//}


//retrieve JSON data from a Google Calendar (public)
function get_calendar_data($calendar, $dateToGet=0){
  $debug=true;
//  $key = get_googleAPI_key();
  $key = getenv('GOOGLE_API');
  $APIformat="Y-m-d";
  $timeMin = date($APIformat,time()+$dateToGet) . 'T00:00:00.000Z';
  $timeMax = date($APIformat,time()+$dateToGet) . 'T23:59:00.000Z';
  $url='https://www.googleapis.com/calendar/v3/calendars/' . $calendar . '/events?singleEvents=true&orderby=startTime&timeMin=' . 
      $timeMin . '&timeMax=' . $timeMax . '&maxResults=1&key=' . $key;
    //this works more reliably than only getting one event
  
  $jsonFile = file_get_contents($url);
  if (!$jsonFile) {
      trigger_error('NO DATA returned from url.', E_USER_NOTICE);
  }
  else {
    // convert the string to a json object
    $jsonObj = json_decode($jsonFile);
    $dateData = $jsonObj->items;
    return $dateData;
  }
}


function check_if_open($item){   
  
  $now=time();
  
  if (isset($item->start->dateTime)){ // non 24-hour event
      $unixStart=strtotime(substr($item->start->dateTime, 0,16));
      $unixEnd=strtotime(substr($item->end->dateTime, 0,16));
  }

  else{ // all day event
    $unixStart=strtotime(substr($item->start->date, 0,16));
    $unixEnd=strtotime(substr($item->end->date, 0,16));
  }

  if ( ($now < $unixStart) || ($now > $unixEnd) ){
    $isOpen = 0;
  }
  else {
    $isOpen = 1;
  }      
  
  return $isOpen;
}

function format_open_msg($isOpen){
  if ($isOpen<=0){
    $openMsg="<span class=\"closed\">CLOSED</span>";   
  }          
  else {
    $openMsg="<span class=\"open\">OPEN</span>";
  }
  return $openMsg;
}


function format_hours_message($startTime,$endTime){
  
  if ($endTime=="12a.m."){ // don't use 12am time to avoid confusion
    if ($startTime=="12a.m."){ // eg: Tuesday 12am-12am
      $msg="Open 24 hours";
    }
    else{
      $msg="Open from $startTime - overnight"; // eg: Sunday 12pm-12am
    }
  }
  else { // normal 
    $msg="Today's hours: $startTime - $endTime"; // eg: Saturday 8:30am-5pm
  }
  
  return $msg;
  
}

function format_hours_data($dateData){// default is to use Galvin and today's Unix date
  $msg="no data available";
  $timeFormat="g:ia";
  
// error gracefully if no data
    if (count($dateData)<=0){
      return $msg;
    }
    else{
      $item = $dateData[0]; // no need to loop. just get first object
    }     
    $title = $item->summary;

    if (stripos($title,"closed")===false) { // library open (verify identical FALSE to avoid "false false")

        // Google Calendar API v3 uses the date field if event is a full day long, or the dateTime field if it is less than 24 hours  
      if (isset($item->start->dateTime)){ // non 24-hour event
          $tmpStart=strtotime(substr($item->start->dateTime, 0,16));
          $tmpEnd=strtotime(substr($item->end->dateTime, 0,16));
      }

      else{ // all day event
        $tmpStart=strtotime(substr($item->start->date, 0,16));
        $tmpEnd=strtotime(substr($item->end->date, 0,16));
      }
      
      $startTime = format_iit_time(date($timeFormat,$tmpStart));
      $endTime = format_iit_time(date($timeFormat,$tmpEnd));
      
      $msg=format_hours_message($startTime, $endTime);

      return $msg; // return hours info
    } // end library open

    // library is closed
    else {
      return $title;
    }
        
}// end function

function galvin_hours_block($calendar){
  $dataObj=get_calendar_data($calendar);

  if (count($dataObj)>0){
    $hours = format_hours_data($dataObj);
    $isOpen = check_if_open($dataObj[0]);
    $openMsg = format_open_msg($isOpen);
    $message = "Currently: $openMsg</p><p>$hours</p>";
  }
  else{
    $message = "<p>Library hours cannot be displayed at this time.</p>";
  }
  return $message;
}
