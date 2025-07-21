<?php

function value_is_the_one_variable( $value ) {
	$variables = array() ;

	$variables['value'] = $value ;

	return $variables ;
}


function only_one_result( $values ) {
	if( count($values)==0 ) {
		return null ;
	}
	if( count($values)!=1 ) {
		// error_out( "it actually does not look like we only have 1 result...", false, false ) ;
		return $values ;
	}

	return $values[0] ;
}


function only_one_result_json_decode( $values ) {
	if( count($values)==0 ) {
		return null ;
	}
	if( count($values)!=1 ) {
		// error_out( "it actually does not look like we only have 1 result...", false, false ) ;
		return $values ;
	}

	return json_decode( $values[0], true ) ;
}


function array_path( $values, $path="" ) {
	$values_original = $values ;
	$path_original = $path ;

	if( $path=="" ) {
		return $values ;
	}

	$path = explode( ".", $path ) ;
	foreach( $path as $path_item ) {
		if( preg_match('/^[0-9]+$/', $path_item) ) {
			$path_item = intval( $path_item ) ;
		}

		if( is_array($values) &&
			array_key_exists($path_item, $values) ) {
			$values = $values[$path_item] ;
		} else {
			// error_out( "unable to find path through array. path is: {$path_original}, array is:\n" . var_export($values_original, true), false, false ) ;
			return $values_original ;
		}
	}

	return $values ;
}


function process_zoom_room_scheduled_meetings( $values ) {
	// Will suggest the meeting 10 minutes before it starts
	$bufferBeforeMeeting = 10 * 60 ;

	// First, we make sure there are meetings scheduled
	if( !isset($values[0][0]) ) {
		return false ;
	}
	$meetingList = $values[0] ;

	// Finding the meeting to suggest
	foreach ($meetingList as $meeting) {
		// Getting the current unix timestamp in GMT
		// Zoom Rooms use UTC which is the same as GMT
		$timeNowUnix = time() ;

		// Making sure there is a start and end time for the meeting
		if (!isset($meeting['startTime'])){
			// error_out( "zoom room - no start time found for meeting: " . var_export($values, true), false, false, 20, 2 ) ;
			return false ;
		}
		if (!isset($meeting['endTime'])){
			// error_out( "zoom room - no end time found for meeting: " . var_export($values, true), false, false, 20, 2 ) ;
			return false ;
		}
		// Converting the date strings to a unix timestamp
		$startTime = $meeting['startTime'] ;
		$endTime = $meeting['endTime'] ;
		$startTimeUnix = strtotime($startTime) ; 
		$endTimeUnix = strtotime($endTime) ; 

		$timeUntilStart = $startTimeUnix - $timeNowUnix;
		$timeUntilEnd = $endTimeUnix - $timeNowUnix ;

		// If a meeting is coming up or ongoing, it will suggest it until it ends
		// If another meeting starts right after and we are within the time buffer, the next meeting will be suggested
		if ($timeUntilStart < $bufferBeforeMeeting && $timeUntilEnd > 0) {
			$chosenMeeting = $meeting ;
		}
	}

	// Validating that a meeting is coming up soon
	if (!isset($chosenMeeting)){
		return false ;
	}
	if (!isset($chosenMeeting['meetingNumber'])){
		// error_out( "zoom room - chosen meeting does not have a meeting number: " . var_export($values, true), false, false, 20, 2 ) ;
		return false ;
	}
	$meetingID = $chosenMeeting['meetingNumber'] ;

	// Happens when a calendar meeting is booked, but there is no zoom meeting attached
	if ($meetingID == "0"){
		return false;
	}

	if (isset($chosenMeeting['meetingName'])){
		$meetingName = $chosenMeeting['meetingName'] ;
	}

	// If the name of the meeting is private or blank, set the name to "creator's meeting"
	if ($chosenMeeting['isPrivate'] == true or $meetingName == "" ){
		if (isset($chosenMeeting['creatorName']) and $chosenMeeting['creatorName'] != ""){
			$meetingName = $chosenMeeting['creatorName'] . "'s Meeting" ;
		} else {
			$meetingName = "Upcoming Meeting" ;
		}
	}

	// Joining a meeting scheduled with a Zoom Room from the Zoom Room does not require a password
	$finalZoomData = array( "id" => $meetingID,
							"password" => "",
							"name" => $meetingName ) ;

	// We made it!
	return $finalZoomData ;
}


function zoom_room_meeting_set_meeting_id_and_password( $value ) {
	$variables = array() ;

	$ok = true ;

	if( !isset($value['id']) ||
		(!is_numeric($value['id']) && substr_count( $value['id'], "@" )==0)) {
		$ok = false ;
		// error_out( "zoom_room_meeting_set_meeting_id_and_password 'id' is supposed to be set and numeric or have an @ for SIP. Received " . var_export($value, true) . " instead.", false, false ) ;
	}

	if( !isset($value['password']) ) {
		$ok = false ;
		// error_out( "zoom_room_meeting_set_meeting_id_and_password 'password' is supposed to be set. Received " . var_export($value, true) . " instead.", false, false ) ;
	}

	if( $ok ) {
		$variables['meeting_id'] = $value['id'] ;
		$variables['password'] = $value['password'] ;
	}

	return $variables ;
}


function zoom_room_controller_error_if_not_online( $values ) {
	// first we make sure we find the controller device
	if( !isset($values[0]['devices']) ) {
		// error_out( "zoom room - no devices found in data: " . var_export($values, true), false, false, 20, 2 ) ;
		return false ;
	}
	$devices = $values[0]['devices'] ;
	$controller_device = false ;
	foreach( $devices as $device ) {
		if( strtolower($device['device_type'])=="controller" ) {
			$controller_device = $device ;
			break ;
		}
	}

	if( $controller_device===false ) {
		// error_out( "zoom room - no controller device found in data: " . var_export($values, true), false, false, 20, 2 ) ;
		return false ;
	}

	// then we try to see if it's online
	if( !isset($controller_device['status']) ) {
		// error_out( "zoom room - no controller device status found in data: " . var_export($values, true), false, false, 20, 2 ) ;
		return false ;
	}

	if( strtolower($controller_device['status'])!="online" ) {
		// error_out( "zoom room - controller device is not online with status: {$controller_device['status']}", false, false, 20, 2 ) ;
		return false ;
	}

	// we made it!
	return true ;
}


function set_process_volume( $value, $min_volume=0, $max_volume=100 ) {
	$variables = array() ;

	$ok = true ;

	if( !is_numeric($value) ||
		$value<0 ||
		$value>100 ) {
		$ok = false ;
		// error_out( "set_process_volume 'volume' is supposed to be an int between [0,100]. Received {$value} instead.", false, false ) ;
	}

	if( $ok ) {
		$unrounded = (($max_volume-$min_volume)/100)*$value + $min_volume ;
		$rounded   = round( $unrounded ) ;
		$variables['volume'] = $rounded ;
	}

	return $variables ;
}


function get_process_volume( $results, $min_volume=0, $max_volume=100 ) {
	$results = json_decode( $results, true ) ;
	if( !is_array($results) ) {
		// error_out( "get_process_volume $results is not an array", false, false ) ;
		return null ;
	}
	$result = json_decode( $results[0], true ) ;

	if( is_array($result) && array_key_exists('volume', $result) ) {
		$result = $result['volume'] ;
	}
	if( is_array($result) && array_key_exists('audio_volume', $result) ) {
		$result = $result['audio_volume'] ;
	}
	if( is_array($result) && !array_key_exists('audio_volume', $result) && array_key_exists('power_status', $result) && $result['power_status']==0 ) {
		$result = 0 ;
	}

	if( !is_numeric($result) ) {
		// error_out( "get_process_volume 'volume' is not numeric with: " . var_export($result, true), false, false ) ;
		return null ;
	}

	$result = (100/($max_volume-$min_volume))*($result - $min_volume) ;
	$result = round( $result ) ;

	if( !is_numeric($result) ) {
		// error_out( "get_process_volume 'volume' is not an int with {$result} instead", false, false ) ;
		return null ;
	}
	if( $result<0 ) {
		// Ben - 2022-04-08 - in a lot of cases, devices respond with 0 when they're off which normalizes bellow 0 resulting in too many false errors.
		// // error_out( "get_process_volume 'volume' is less than 0 with {$result}", false, false ) ;
		$result = 0 ;
	}
	if( $result>100 ) {
		// error_out( "get_process_volume 'volume' is greater than 100 with {$result}", false, false ) ;
		$result = 100 ;
	}

	return $result ;
}

?>
