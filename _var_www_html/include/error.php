<?php

//  ___            _           _           
// |_ _|_ __   ___| |_   _  __| | ___  ___ 
//  | || '_ \ / __| | | | |/ _` |/ _ \/ __|
//  | || | | | (__| | |_| | (_| |  __/\__ \
// |___|_| |_|\___|_|\__,_|\__,_|\___||___/
//


require_once( "utilities.php" ) ;




class error_ {


    // variable declaration
    private $error_log_file ;
    private $error_tolerance_cache_dir ;
    private $error_retention_hours ;


    // constructor
    function __construct() {
		$this->error_log_file = "/data/errors.json" ;
		$this->error_tolerance_cache_dir = "/data" ;
		$this->error_retention_hours = 24 ;
	}


	function list( $system=null, $code=null, $severity=null, $channel=null ) {
		$errors = [] ;
		if( file_exists($this->error_log_file) ) {
			$possible_new_errors = json_decode( safe_file_get_contents($this->error_log_file), true ) ;
			if( is_array($possible_new_errors) ) {
				$errors = $possible_new_errors ;
			}
		}

		$this->remove_obsolete( $errors ) ;

		$errors_kept = [] ;
		foreach( $errors as $error ) {
			if( ($system===null || ($system!==null && $system==$error['system'])) &&
				($code===null || ($code!==null && $code==$error['code'])) &&
				($severity===null || ($severity!==null && $severity==$error['severity'])) &&
				($channel===null || ($channel!==null && $channel==$error['channel'])) ) {
				$errors_kept[] = $error ;
			}
		}

		return $errors_kept ;
	}


	function add( $message, $code, $severity, $channel, $tolerance=0, $system=null ) { // tolerance is for the last hour
		
		if( $tolerance>0 ) {
			$error_hash = md5( $code . $severity . $channel . $system ) ; // the message isn't included as it might contain fluctuating details
			$tolerance_filename = "{$this->error_tolerance_cache_dir}/error_tolerance.{$error_hash}.json" ;
			$tolerance_data = [] ;
			if( file_exists($tolerance_filename) ) {
				$tolerance_data = json_decode( safe_file_get_contents($tolerance_filename), true ) ;
				if( !is_array($tolerance_data) ) {
					$tolerance_data = [] ;
				}
			}

			// cleaning up obsolete entries
			$now = time() ;
			$indices_to_remove = [] ;
			for( $i=0 ; $i<count($tolerance_data) ; $i++ ) {
				if( ($now-$tolerance_data[$i])>3600 ) {
					$indices_to_remove[] = $i ;
				}
			}
			foreach( $indices_to_remove as $index_to_remove ) {
				unset( $tolerance_data[$index_to_remove] ) ;
			}
			$tolerance_data = array_values( $tolerance_data ) ;

			// adding new one
			$tolerance_data[] = $now ;
			safe_file_put_contents( $tolerance_filename, json_encode($tolerance_data) ) ;

			if( count($tolerance_data)<=$tolerance ) {
				// occurred within tolerance, no need to actually do anything
				return null ;
			}
		}

		// trace
	    $e = new Exception() ;
	    $trace = explode( "\n", $e->getTraceAsString() ) ;
	    // reverse array to make steps line up chronologically
	    $trace = array_reverse( $trace ) ;
	    array_shift( $trace ) ; // remove main

	    $time_stamp = date( "Y-m-d H:i:s" ) ;

	    $new_error = [
	    	'time_stamp'=>$time_stamp,
	    	'message'=>$message,
	    	'code'=>$code,
	    	'trace'=>$trace,
	    	'severity'=>$severity,
	    	'channel'=>$channel,
	    	'system'=>$system
	    ] ;

		$errors = [] ;
		if( file_exists($this->error_log_file) ) {
			$possible_new_errors = json_decode( safe_file_get_contents($this->error_log_file), true ) ;
			if( is_array($possible_new_errors) ) {
				$errors = $possible_new_errors ;
			}
		}
		$errors[] = $new_error ;

		$this->remove_obsolete( $errors ) ;
		
		safe_file_put_contents( $this->error_log_file, json_encode($errors) ) ;
	}


	function remove_obsolete( &$errors ) {
		// obsoletion
		$indices_to_remove = [] ;
		$now = time() ;
		for( $i=0 ; $i<count($errors) ; $i++ ) {
			if( !(isset($errors[$i]['time_stamp']) &&
				  ($now-strtotime($errors[$i]['time_stamp']))<($this->error_retention_hours*24*60*60)) ) {
				$indices_to_remove[] = $i ;
			}
		}
		if( count($indices_to_remove)>0 ) {
			foreach( $indices_to_remove as $index_to_remove ) {
				unset( $errors[$index_to_remove] ) ;
			}
			$errors = array_values( $errors ) ; // pack to fill index gaps
		}

		// no need to return, passed by reference
	}
}

?>