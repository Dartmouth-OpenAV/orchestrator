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


	function list( $code=null, $severity=null, $tags=null, $source=null, $system=null  ) {
		$this->remove_obsolete() ;

		$query = "" ;
		$query_params = [] ;
		if( $code!==null || $severity!==null || $tags!==null || $source!==null || $system!==null ) {
			$query .= " WHERE" ;
		}
		$need_and = false ;
		if( $code!==null ) {
			if( $need_and ) {
				$query .= " AND" ;
			}
			$query .= " code=:code" ;
			$query_params[':code'] = $code ;
			$need_and = true ;
		}
		if( $severity!==null ) {
			if( $need_and ) {
				$query .= " AND" ;
			}
			$query .= " severity=:severity" ;
			$query_params[':severity'] = $severity ;
			$need_and = true ;
		}
		if( $tags!==null ) {
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
		if( $source!==null ) {
			if( $need_and ) {
				$query .= " AND" ;
			}
			$query .= " source=:source" ;
			$query_params[':source'] = $source ;
			$need_and = true ;
		}
		if( $system!==null ) {
			if( $need_and ) {
				$query .= " AND" ;
			}
			$query .= " system=:system" ;
			$query_params[':system'] = $system ;
			$need_and = true ;
		}

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


	function add( $message, $code, $severity, $tags=[], $source=null, $system=null, $tolerance=0 ) { // tolerance is count over last hour
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
		
		if( $tolerance>0 ) {
			$error_hash = md5( $code . $severity . implode("|", $tags) . $source . $system ) ; // the message isn't included as it might contain fluctuating details
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

	    // $new_error = [
	    // 	'message'=>$message,
	    // 	'code'=>$code,
	    // 	'trace'=>$trace,
	    // 	'severity'=>$severity,
	    // 	'tags'=>$tags,
	    // 	'source'=>$source,
	    // 	'system'=>$system
	    // ] ;

		sqlite_query( "/dev/shm/errors.db",
					  "INSERT INTO data (message,
					  					 code,
					  					 severity,
					  					 tags,
					  					 source,
					  					 system) VALUES (:message,
					  					 				 :code,
					  					 				 :severity,
					  					 				 :tags,
					  					 				 :source,
					  					 				 :system)", [':message'=>$message,
											  					     ':code'=>$code,
											  					     ':severity'=>$severity,
											  					     ':tags'=>$tags,
											  					     ':source'=>$source,
											  					     ':system'=>$system] ) ;

		$this->remove_obsolete() ;
	}


	function remove_obsolete() {
		sqlite_query( "/dev/shm/errors.db",
					  "DELETE FROM data WHERE time_stamp<datetime('now', '-{$this->error_retention_hours} hours')", [] ) ;
	}
}

?>