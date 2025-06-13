<?php

//  ___            _           _           
// |_ _|_ __   ___| |_   _  __| | ___  ___ 
//  | || '_ \ / __| | | | |/ _` |/ _ \/ __|
//  | || | | | (__| | |_| | (_| |  __/\__ \
// |___|_| |_|\___|_|\__,_|\__,_|\___||___/
//


require_once( "sqlite.php" ) ;
require_once( "utilities.php" ) ;



class error_ {


    // variable declaration
    private $error_tolerance_cache_dir ;
    private $error_retention_hours ;


    // constructor
    function __construct() {
		$this->error_tolerance_cache_dir = "/data" ;
		$this->error_retention_hours = 24 ;
	}


	function list( $code=null, $severity=null, $tags=null, $source=null, $system=null ) {
		if( $code===null ) { $code = "" ; } // string Vs null queries are built different so we're making our lives easier here
		if( $severity===null ) { $severity = "" ; }
		if( $tags===null ) { $tags = "" ; }
		if( $source===null ) { $source = "" ; }
		if( $system===null ) { $system = "" ; }
		$this->remove_obsolete() ;

		$query = "" ;
		$query_params = [] ;
		$query .= " WHERE" ;
		$need_and = false ;
		if( $code!=="" ) {
			if( $need_and ) {
				$query .= " AND" ;
			}
			$query .= " code=:code" ;
			$query_params[':code'] = $code ;
			$need_and = true ;
		}
		if( $severity!=="" ) {
			if( $need_and ) {
				$query .= " AND" ;
			}
			$query .= " severity=:severity" ;
			$query_params[':severity'] = $severity ;
			$need_and = true ;
		}
		if( $tags!=="" ) {
			if( $need_and ) {
				$query .= " AND" ;
			}
			// tags are a little more involved
			if( is_string($tags) ) {
				$tags = [$tags] ;
			}
			$query .= "(" ;
			if( is_array($tags) ) {
				$need_or = false ;
				foreach( $tags as $tag ) {
					if( $need_or ) {
						$query .= " OR" ;
					}

					$prepared_variable_name = md5( $tag ) . "1" ;
					if( !in_array(":{$prepared_variable_name}", $query_params) ) {
						$query .= " tags=:{$prepared_variable_name}" ;
						$query_params[":{$prepared_variable_name}"] = $tag ;

						$query .= " OR" ;

						$prepared_variable_name = md5( $tag ) . "2" ;
						$query .= " tags LIKE :{$prepared_variable_name}" ;
						$query_params[":{$prepared_variable_name}"] = "$tag|%" ;

						$query .= " OR" ;

						$prepared_variable_name = md5( $tag ) . "3" ;
						$query .= " tags LIKE :{$prepared_variable_name}" ;
						$query_params[":{$prepared_variable_name}"] = "%|$tag" ;

						$query .= " OR" ;

						$prepared_variable_name = md5( $tag ) . "4" ;
						$query .= " tags LIKE :{$prepared_variable_name}" ;
						$query_params[":{$prepared_variable_name}"] = "%|$tag|%" ;
					}
					
					$need_or = true ;
				}
			}
			$query .= ")" ;
			$need_and = true ;
		}
		if( $source!=="" ) {
			if( $need_and ) {
				$query .= " AND" ;
			}
			$query .= " source=:source" ;
			$query_params[':source'] = $source ;
			$need_and = true ;
		}
		if( $system!=="" ) {
			if( $need_and ) {
				$query .= " AND" ;
			}
			$query .= " system=:system" ;
			$query_params[':system'] = $system ;
			$need_and = true ;
		}
		if( $need_and ) {
			$query .= " AND" ;
		}
		$query .= " is_reported=1" ;

		$errors = sqlite_query( "/dev/shm/errors.db",
                                "SELECT message,
                      		            code,
                      		            severity,
                      		            tags,
                      		            source,
                      		            system,
                      		            time_stamp FROM data{$query}", $query_params ) ;

		return $errors ;
	}


	function add( $message,
				  $code,
				  $severity, // 1 is considered most severe
				  $tags=[],
				  $source=null,
				  $system=null,
				  $tolerance_per_hour=0, // tolerance means "won't bark unless I get more than x per hour"
				  $limit_per_hour=60, // limit means "won't bark beyond x per hour"
				  $time_stamp_override=null ) {
		if( $source===null ) { $source = "" ; } // string Vs null queries are built different so we're making our lives easier here
		if( $system===null ) { $system = "" ; }

		if( !is_array($tags) ) {
			// trying to be nice
			if( is_string($tags) ) {
				$tags = [$tags] ;
			} else {
				$tags = [] ;
			}
		} else {
			sort( $tags ) ;
		}
		$tags = implode( "|", $tags ) ;

		// trace
	    $e = new Exception() ;
	    $trace = explode( "\n", $e->getTraceAsString() ) ;
	    // reverse array to make steps line up chronologically
	    $trace = array_reverse( $trace ) ;
	    array_shift( $trace ) ; // remove main

	    $time_stamp = date( "Y-m-d H:i:s" ) ;

	    if( $time_stamp_override!==null ) {
	    	if( !preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $time_stamp_override) &&
			    !preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{1,9}/', $time_stamp_override) ) {
	    		$time_stamp_override = null ;
	    	}
	    }

	    $query = "INSERT INTO data (message,
					  			    code,
					  			    severity,
					  			    tags,
					  			    source,
					  			    system" ;
		if( $time_stamp_override!==null ) {
			$query .= ",time_stamp" ;
		}
		$query .= ") VALUES (:message,
					  		 :code,
					  		 :severity,
					  		 :tags,
					  		 :source,
					  		 :system" ;
		if( $time_stamp_override!==null ) {
			$query .= ",:time_stamp" ;
		}
		$query .= ")" ;

		$query_params = [':message'=>$message,
						 ':code'=>$code,
						 ':severity'=>$severity,
						 ':tags'=>$tags,
						 ':source'=>$source,
						 ':system'=>$system] ;
		if( $time_stamp_override!==null ) {
			$query_params[':time_stamp'] = $time_stamp_override ;
		}

		$insert_id = sqlite_query( "/dev/shm/errors.db",
					  			   $query,
					 			   $query_params,
					 			   false,
					 			   true ) ;

		// now's the time to decide if this one will actually percolate through the reporting layers
		$report_based_on_tolerance = true ;
		if( $tolerance_per_hour>0 ) {
			$recent_error_count = sqlite_query( "/dev/shm/errors.db",
											    "SELECT COUNT(1) FROM data WHERE code=:code AND
											    								 severity=:severity AND
											    								 source=:source AND
											    								 system=:system AND
											    								 time_stamp<datetime('now', '-1 hour')",
											    [':code'=>$code,
						 						 ':severity'=>$severity,
						 						 ':source'=>$source,
						 						 ':system'=>$system], true ) ;
			if( $recent_error_count<$tolerance_per_hour ) {
				$report_based_on_tolerance = false ;
			}
		}
		$report_based_on_limit = false ;
		if( $report_based_on_tolerance &&
			$limit_per_hour>0 ) {
			$recent_reported_error_count = sqlite_query( "/dev/shm/errors.db",
											             "SELECT COUNT(1) FROM data WHERE code=:code AND
											    								          severity=:severity AND
											    								          source=:source AND
											    								          system=:system AND
											    								          is_reported=1 AND
											    								          time_stamp<datetime('now', '-1 hour')",
											             [':code'=>$code,
						 						          ':severity'=>$severity,
						 						          ':source'=>$source,
						 						          ':system'=>$system], true ) ;
			if( $recent_reported_error_count<$limit_per_hour ) {
				$report_based_on_limit = true ;
			}
		}

		if( $report_based_on_tolerance &&
			$report_based_on_limit ) {
			if( preg_match('/^\d{1,}$/', $insert_id) ) {
				sqlite_query( "/dev/shm/errors.db",
							  "UPDATE data SET is_reported=1 WHERE id=:id",
											             [':id'=>$insert_id] ) ;
			}
			if( isset(getenv()['LOG_ERRORS']) &&
	            getenv()['LOG_ERRORS']=="true" ) {
				(new log_())->add_entry( $system, "error", ['message'=>$message,
															'code'=>$code,
															'severity'=>$severity,
															'tags'=>explode( "|", $tags),
															'source'=>$source,
															'system'=>$system], $time_stamp_override ) ;
			}
		}

		$this->remove_obsolete() ;
	}


	function remove_obsolete() {
		sqlite_query( "/dev/shm/errors.db",
					  "DELETE FROM data WHERE time_stamp<datetime('now', '-{$this->error_retention_hours} hours')", [] ) ;
	}
}

?>