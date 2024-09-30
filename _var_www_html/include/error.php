<?php


class error_ {


    // variable declaration
    private $error_log_file ;
    private $error_retention_hours ;


    // constructor
    function __construct() {
		$this->error_log_file = "/data/errors.json" ;
		$this->error_retention_hours = 24 ;
	}


	function add( $message, $code, $severity, $channel, $system=null ) {
		// trace
	    $e = new Exception() ;
	    $trace = explode( "\n", $e->getTraceAsString() ) ;
	    // reverse array to make steps line up chronologically
	    $trace = array_reverse( $trace ) ;
	    array_shift( $trace ) ; // remove main

	    $time_stamp = date( "Y-m-d H:i:s" ) ;

	    $new_datum = [
	    	'time_stamp'=>$time_stamp,
	    	'message'=>$message,
	    	'code'=>$code,
	    	'trace'=>$trace,
	    	'severity'=>$severity,
	    	'channel'=>$channel,
	    	'system'=>$system
	    ] ;

	    // we're going to want to do a lot better than this eventually, especially for busy systems
	    $safety_counter = 10 ; // sleep 0.1 so 1 second max
	    $logged = false ;
	    while( $safety_counter>0 &&
	    	   $logged!==true ) {
	    	if( !file_exists("{$this->error_log_file}.lock") ||
	    		(time()-filemtime("{$this->error_log_file}.lock"))>300 ) { // if the lock file is older than 5 minutes, we bulldoze
	    		touch( "{$this->error_log_file}.lock" ) ;

	    		$data = [] ;
	    		if( file_exists($this->error_log_file) ) {
	    			$possible_new_data = json_decode( file_get_contents($this->error_log_file), true ) ;
	    			if( is_array($possible_new_data) ) {
	    				$data = $possible_new_data ;
	    			}
	    		}
	    		$data[] = $new_datum ;

	    		// obsoletion
	    		$indices_to_remove = [] ;
	    		$now = time() ;
	    		for( $i=0 ; $i<count($data) ; $i++ ) {
	    			if( !(isset($data[$i]['time_stamp']) &&
	    				  ($now-strtotime($data[$i]['time_stamp']))<($this->error_retention_hours*24*60*60)) ) {
	    				$indices_to_remove[] = $i ;
	    			}
	    		}
	    		foreach( $indices_to_remove as $index_to_remove ) {
	    			unset( $data[$index_to_remove] ) ;
	    		}
	    		$data = array_values( $data ) ; // pack to fill index gaps
	    		file_put_contents( $this->error_log_file, json_encode($data) ) ;

	    		$logged = true ;

	    		unlink( "{$this->error_log_file}.lock" ) ;
	    	} else {
	    		sleep( 0.1 ) ; // safety counter 10 so 1 second max
	    		$safety_counter-- ;
	    	}
	    }
	}
}

?>