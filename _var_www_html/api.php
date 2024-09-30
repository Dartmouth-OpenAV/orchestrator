<?php

// ASCII titles from: https://patorjk.com/software/taag/#p=display&f=Standard&t=Routing


// config variables
$verbose = true ;


// prevent information disclosures
if( php_sapi_name()!=="cli" ) {
	error_reporting( 0 ) ;
	ini_set( "display_errors", 0 ) ;
}


if( php_sapi_name()!="cli" ) {
	// CORS
	header( "Access-Control-Allow-Origin: *" ) ;
	header( "Access-Control-Allow-Credentials: true" ) ;
	header( "Access-Control-Allow-Methods: GET,HEAD,OPTIONS,POST,PUT,PATCH,DELETE" ) ;
	header( "Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Authorization" ) ;

	if( $_SERVER['REQUEST_METHOD']=="OPTIONS" ) {
		http_response_code( 200 ) ;
		exit( 0 ) ;
	}
}




//  ___            _           _           
// |_ _|_ __   ___| |_   _  __| | ___  ___ 
//  | || '_ \ / __| | | | |/ _` |/ _ \/ __|
//  | || | | | (__| | |_| | (_| |  __/\__ \
// |___|_| |_|\___|_|\__,_|\__,_|\___||___/
//

require_once( "include/error.php" ) ;
require_once( "include/github.php" ) ;
require_once( "include/memcached.php" ) ;




//  _____            _                                      _   
// | ____|_ ____   _(_)_ __ ___  _ __  _ __ ___   ___ _ __ | |_ 
// |  _| | '_ \ \ / / | '__/ _ \| '_ \| '_ ` _ \ / _ \ '_ \| __|
// | |___| | | \ V /| | | | (_) | | | | | | | | |  __/ | | | |_ 
// |_____|_| |_|\_/ |_|_|  \___/|_| |_|_| |_| |_|\___|_| |_|\__|
//

$required_environment_variables = [
	'SYSTEM_CONFIGURATIONS_GITHUB_REPOSITORY',
	'SYSTEM_CONFIGURATIONS_GITHUB_REPOSITORY_OWNER',
	'SYSTEM_CONFIGURATIONS_GITHUB_REPOSITORY_PATH',
] ;
// Github authentication can either be via token or app so required environment variables may vary
if( isset(getenv()['SYSTEM_CONFIGURATIONS_GITHUB_TOKEN']) ) {
	$required_environment_variables[] = 'SYSTEM_CONFIGURATIONS_GITHUB_TOKEN' ; // silly I know, just a bit clearer code
} else {
	$required_environment_variables[] = 'SYSTEM_CONFIGURATIONS_GITHUB_APP_INSTALLATION_ID' ;
	$required_environment_variables[] = 'SYSTEM_CONFIGURATIONS_GITHUB_APP_CLIENT_ID' ;
	$required_environment_variables[] = 'SYSTEM_CONFIGURATIONS_GITHUB_APP_PEM' ;
}
foreach( $required_environment_variables as $required_environment_variable ) {
	if( !isset(getenv()[$required_environment_variable]) ) {

		(new error_())->add( "Missing environment variable: {$required_environment_variable}",
		                     "cF30D09PLe8Q",
				             1,
				             "backend" ) ;
		if( php_sapi_name()==="cli" ) {
			echo "server misconfiguration" ;
			exit( 1 ) ;
		} else {
			close_with_500( "server misconfiguration" ) ;
			exit( 1 ) ; // for good measure
		}
		
	}
	define( $required_environment_variable, getenv()[$required_environment_variable] ) ;
}




//   ____ _     ___ 
//  / ___| |   |_ _|
// | |   | |    | | 
// | |___| |___ | | 
//  \____|_____|___|
        

if( php_sapi_name()==="cli" ) {
	$function_name = false ;
	if( isset($argv[1]) ) {
		$function_name = $argv[1] ;
	} else {
		"error: no function_name argument given\n" ;
		exit( 1 ) ;
	}
	if( !function_exists($function_name) ) {
		echo "function doesn't exist" ;
		exit( 1 ) ;
	}

	$arguments = [] ;
	for( $i=2 ; $i<count($argv) ; $i++ ) {
		$arguments[] = $argv[$i] ;
	}

	$return_value = null ;
	if( count($arguments)>0 ) {
		$return_value = call_user_func_array( $function_name, $arguments ) ;
	} else {
		$return_value = call_user_func( $function_name ) ;
	}
	if( $return_value!==true ) {
		echo "error: !true return value:\n", var_export( $return_value, true ), "\n" ;
		exit( 1 ) ;
	} else {
		echo "success\n" ;
		exit( 0 ) ;
	}

	// we shouldn't read this point
	echo "error: unreachable point 4g3Wz8p8s2P6\n" ;
	exit( 1 ) ;
}


function run_cli_function( $function_name, $parameters=[] ) {
	if( !function_exists($function_name) ) {
		return false ;
	}
	if( !is_array($parameters) ) {
		return false ;
	}

	$command = "/usr/bin/nohup /usr/bin/php {$_SERVER['SCRIPT_FILENAME']} \"{$function_name}\"" ;
	if( count($parameters)>0 ) {
		$command .= ' "' . implode( '" "', $parameters ) . '"' ;
	}
	$command .= " 2>&1 &" ;

	shell_exec( $command ) ;

	return true ;
}




//  ____             _   _             
// |  _ \ ___  _   _| |_(_)_ __   __ _ 
// | |_) / _ \| | | | __| | '_ \ / _` |
// |  _ < (_) | |_| | |_| | | | | (_| |
// |_| \_\___/ \__,_|\__|_|_| |_|\__, |
//                               |___/ 

$method = $_SERVER['REQUEST_METHOD'] ;
$request_uri = $_SERVER['REQUEST_URI'] ;
$request_uri = explode( "/", $request_uri ) ;
$path = implode( "/", array_slice($request_uri, 2) ) ;
$path = explode( "?", $path ) ;
$path = $path[0] ;

// echo "method: {$method}, path: {$path}" ;
// exit( 0 ) ;

if( $method=="GET" &&
	preg_match('/^systems\/[0-9a-zA-Z\-\_]{1,}\/status$/', $path) ) {
	route_function_if_allowed( "get_system_status" ) ;
}
if( $method=="PUT" &&
	preg_match('/^systems\/[0-9a-zA-Z\-\_]{1,}\/status$/', $path) ) {
	route_function_if_allowed( "update_system_status" ) ;
}
if( $method=="GET" &&
	preg_match('/^version$/', $path) ) {
	route_function_if_allowed( "get_version" ) ;
}
if( $method=="POST" &&
	preg_match('/^clients\/errors$/', $path) ) {
	route_function_if_allowed( "create_client_error" ) ;
}

close_with_400( "Unknown combination of method: {$method}, and path: {$path}" ) ;
exit( 1 ) ;


function route_function_if_allowed( $function_name ) {
	// TODO actual permissions
	$function_name() ;
}




//  ____            _                     
// / ___| _   _ ___| |_ ___ _ __ ___  ___ 
// \___ \| | | / __| __/ _ \ '_ ` _ \/ __|
//  ___) | |_| \__ \ ||  __/ | | | | \__ \
// |____/ \__, |___/\__\___|_| |_| |_|___/
//        |___/            


function get_system_status() {
	global $path ;

	$system = explode( "/", $path ) ;
	$system = $system[1] ;

	if( !is_valid_system_name($system) ) { // this is also enforced at the routing level
		close_with_400( "invalid system name: {$system}" ) ;
	}

	// we haven't heard from this system yet
	if( !file_exists("/data/{$system}.json") ) {
		touch( "/data/{$system}.json.lock" ) ;
		run_cli_function( "cli_refresh_system_config", [$system] ) ;
		close_with_204( "initializing" ) ;
	}
	// we've heard from it but let's keep the config fresh
	if( (time()-filemtime("/data/{$system}.json"))>900 ) { // 15 minutes
		$refresh = false ;
		if( file_exists("/data/{$system}.json.lock") ) {
			if( (time()-filemtime("/data/{$system}.json.lock"))>600 ) { // 10 minutes
				(new error_())->add( "system config refresh lock file older than 10 minutes for system: {$system}",
				                     "Y74Z5Dym1V2L",
						             2,
						             "backend" ) ;
				$refresh = true ;
			}
		} else {
			$refresh = true ;
		}
	    
	    if( $refresh ) {
			touch( "/data/{$system}.json.lock" ) ;
			run_cli_function( "cli_refresh_system_config", [$system] ) ;
		}
	}

	// we haven't refreshed this system's status yet
	if( !file_exists("/data/{$system}.status.json") ) {
		touch( "/data/{$system}.status.json.lock" ) ;
		run_cli_function( "cli_refresh_system_status", [$system] ) ;
		close_with_204( "initializing" ) ;
	}
	// we've refreshed it and we need to keep it fresh
	if( (time()-filemtime("/data/{$system}.status.json"))>60 ) { // 1 minute
		$refresh = false ;
		if( file_exists("/data/{$system}.status.json.lock") ) {
			if( (time()-filemtime("/data/{$system}.status.json.lock"))>600 ) { // 10 minutes
				(new error_())->add( "system status refresh lock file older than 10 minutes for system: {$system}",
				                     "M2spq9100P3P",
						             2,
						             "backend" ) ;
				$refresh = true ;
			}
		} else {
			$refresh = true ;
		}
	    
	    if( $refresh ) {
			touch( "/data/{$system}.status.json.lock" ) ;
			run_cli_function( "cli_refresh_system_status", [$system] ) ;
		}
	}

	$system_status = json_decode( file_get_contents("/data/{$system}.status.json"), true ) ;

	close_with_200( $system_status ) ;
}


function update_system_status() {
	global $path ;

	$system = explode( "/", $path ) ;
	$system = $system[1] ;

	if( !is_valid_system_name($system) ) { // this is also enforced at the routing level
		close_with_400( "invalid system name: {$system}" ) ;
	}

	$update = get_request_body() ;
	if( $update===null ) {
		close_with_400( "provided update is invalid JSON" ) ;
	}

	// we haven't heard from this system yet
	if( !file_exists("/data/{$system}.json") ) {
		touch( "/data/{$system}.json.lock" ) ;
		run_cli_function( "cli_refresh_system_config", [$system] ) ;
		close_with_204( "initializing" ) ;
	}
	// we've heard from it but let's keep the config fresh
	if( (time()-filemtime("/data/{$system}.json"))>900 ) { // 15 minutes
		$refresh = false ;
		if( file_exists("/data/{$system}.json.lock") ) {
			if( (time()-filemtime("/data/{$system}.json.lock"))>600 ) { // 10 minutes
				(new error_())->add( "system config refresh lock file older than 10 minutes for system: {$system}",
				                     "f5GKacBQt07y",
						             2,
						             "backend" ) ;
				$refresh = true ;
			}
		} else {
			$refresh = true ;
		}
	    
	    if( $refresh ) {
			touch( "/data/{$system}.json.lock" ) ;
			run_cli_function( "cli_refresh_system_config", [$system] ) ;
		}
	}

	// we haven't refreshed this system's status yet
	if( !file_exists("/data/{$system}.status.json") ) {
		touch( "/data/{$system}.status.json.lock" ) ;
		run_cli_function( "cli_refresh_system_status", [$system] ) ;
		close_with_204( "initializing" ) ;
	}
	// we've refreshed it and we need to keep it fresh
	if( (time()-filemtime("/data/{$system}.status.json"))>60 ) { // 1 minute
		$refresh = false ;
		if( file_exists("/data/{$system}.status.json.lock") ) {
			if( (time()-filemtime("/data/{$system}.status.json.lock"))>600 ) { // 10 minutes
				(new error_())->add( "system status refresh lock file older than 10 minutes for system: {$system}",
				                     "6RYeBYfVjW42",
						             2,
						             "backend" ) ;
				$refresh = true ;
			}
		} else {
			$refresh = true ;
		}
	    
	    if( $refresh ) {
			touch( "/data/{$system}.status.json.lock" ) ;
			run_cli_function( "cli_refresh_system_status", [$system] ) ;
		}
	}

	$system_config = json_decode( file_get_contents("/data/{$system}.json"), true ) ;
	$system_status = json_decode( file_get_contents("/data/{$system}.status.json"), true ) ;
	// we merge it with the desired update
	$accumulated_microservice_sequences = [] ;
	$error = null ;
	merge_current_status_with_update( $system_config, $system_status, $update, $accumulated_microservice_sequences, $error ) ;

	if( $error!==null ) {
		close_with_400( $error ) ;
	}

	file_put_contents( "/data/{$system}.status.json", json_encode($system_status), LOCK_EX ) ;
	@unlink( "/data/{$system}.status.json.lock" ) ;

	$microservice_sequences_filename = "/data/{$system}.status.microservice_sequences." . md5( microtime() ) . ".json" ;
	file_put_contents( $microservice_sequences_filename, json_encode($accumulated_microservice_sequences) ) ;
	run_cli_function( "cli_run_microservice_sequences", [$system, $microservice_sequences_filename] ) ;

	close_with_200( $system_status ) ;
}


function cli_refresh_system_config( $system ) {
	// $github_credentials = ['token'=>SYSTEM_CONFIGURATIONS_GITHUB_TOKEN] ;
	$github_credentials = ['installation_id'=>SYSTEM_CONFIGURATIONS_GITHUB_APP_INSTALLATION_ID,
						   'client_id'=>SYSTEM_CONFIGURATIONS_GITHUB_APP_CLIENT_ID,
						   'pem'=>SYSTEM_CONFIGURATIONS_GITHUB_APP_PEM] ;
	$github = new github_( SYSTEM_CONFIGURATIONS_GITHUB_REPOSITORY_OWNER, SYSTEM_CONFIGURATIONS_GITHUB_REPOSITORY, $github_credentials ) ;

	$content = $github->get_file( SYSTEM_CONFIGURATIONS_GITHUB_REPOSITORY_PATH . "/{$system}.json" ) ;

	if( $content===false ) {
		// something went wrong
		if( file_exists("/data/{$system}.json") ) {
			(new error_())->add( "unable to refresh config for system: {$system}, I have a previous copy at least",
				                 "Ie5N0P4PZ9kr",
						         3,
						         "backend" ) ;
		} else {
			(new error_())->add( "unable to refresh config for system: {$system}",
				                 "V73KUz85ep0C",
						         1,
						         "backend" ) ;
		}
		return false ;
	}

	if( !is_valid_system_config($content) ) {
		if( file_exists("/data/{$system}.json") ) {
			(new error_())->add( "config for system: {$system} is invalid, I have a previous copy at least",
				                 "T3o5i84TVGV8",
						         3,
						         "backend" ) ;
		} else {
			(new error_())->add( "config for system: {$system} is invalid",
				                 "6C0x23n3hVkS",
						         1,
						         "backend" ) ;
		}
		return false ;
	}

	file_put_contents( "/data/{$system}.json", $content, LOCK_EX ) ;
	@unlink( "/data/{$system}.json.lock" ) ;

	return true ;
}


function cli_refresh_system_status( $system ) {
	$system_config = [] ;

	if( !file_exists("/data/{$system}.json") ) {
		(new error_())->add( "config for system: {$system} doesn't exist at the time of status refresh",
			                 "B9X9cwA7ls4f",
					         1,
					         "backend" ) ;
		@unlink( "/data/{$system}.status.json.lock" ) ;
		return false ;
	}
	$system_config = file_get_contents( "/data/{$system}.json" ) ;
	$system_config = json_decode( $system_config, true ) ;
	if( $system_config===null ) {
		(new error_())->add( "config for system: {$system} doesn't parse at the time of status refresh",
			                 "4Ikj18m28kPf",
					         1,
					         "backend" ) ;
		@unlink( "/data/{$system}.status.json.lock" ) ;
		return false ;
	}

	// making sure we have all the microservices we'll need
	$microservices = [] ;
    $microservices_mapping = [] ;
    if( !file_exists("/microservices.json") ) {
        (new error_())->add( "missing known microservices file",
                             "1J6cDOU8FpXy",
                             1,
                             "backend" ) ;
        @unlink( "/data/{$system}.status.json.lock" ) ;
        return false ;
    }
    $microservices_mapping = json_decode( file_get_contents("/microservices.json"), true ) ;
    if( $microservices_mapping===null ||
        !is_associative_array($microservices_mapping) ) {
        (new error_())->add( "invalid known microservices file",
                             "8IvN0L65xZGm",
                             1,
                             "backend" ) ;
        @unlink( "/data/{$system}.status.json.lock" ) ;
        return false ;
    }
	$microservices_missing = [] ;
	compile_system_microservice_list( $system_config, $microservices ) ;
	foreach( $microservices as $microservice ) {
		if( !array_key_exists($microservice, $microservices_mapping) ) {
			$microservices_missing[] = $microservice ;
		}
	}
	if( count($microservices_missing)>0 ) {
		(new error_())->add( "microservice(s): " . implode(", ", $microservices_missing) . " are not defined on orchestrator",
		                 	 "86G3OsE55Qr4",
				         	 1,
				         	 "backend" ) ;
		@unlink( "/data/{$system}.status.json.lock" ) ;
		return false ;
	}

	interpret_config_as_current_status( $system_config, $microservices_mapping ) ;
	$system_status = $system_config ; // really just to disambiguate that it was transformed

	$system_status = json_encode( $system_status ) ;

	if( !file_exists("/data/{$system}.status.json") ||
		!file_exists("/data/{$system}.status.json.lock") ||
		(file_exists("/data/{$system}.status.json") &&
		 file_exists("/data/{$system}.status.json.lock") &&
		 filemtime("/data/{$system}.status.json")<=filemtime("/data/{$system}.status.json.lock")) ) {
		file_put_contents( "/data/{$system}.status.json", $system_status, LOCK_EX ) ;
	} else {
		// likely, a status update arrived between the time we started our status update and now
		//   the update takes precedence so we abandon our efforts
	}
	@unlink( "/data/{$system}.status.json.lock" ) ;

	return true ;
}


function cli_run_microservice_sequences( $system, $microservice_sequences_filename ) {
	if( !file_exists($microservice_sequences_filename) ) {
		(new error_())->add( "status update file: {$microservice_sequences_filename} doesn't exist",
			                 "fN8P05A6F8St",
					         1,
					         "backend" ) ;
		return false ;
	}
	$microservice_sequences = file_get_contents( $microservice_sequences_filename ) ;
	$microservice_sequences = json_decode( $microservice_sequences, true ) ;
	if( $microservice_sequences_filename===null ) {
		(new error_())->add( "microservice sequences in file: {$microservice_sequences_filename} doesn't parse",
			                 "Ej941SabB3rD",
					         1,
					         "backend" ) ;
		return false ;
	}

	$microservices_mapping = [] ;
    if( !file_exists("/microservices.json") ) {
        (new error_())->add( "missing known microservices file",
                             "kBsS5n2Zj40F",
                             2,
                             "backend" ) ;
        return false ;
    }
    $microservices_mapping = json_decode( file_get_contents("/microservices.json"), true ) ;
    if( $microservices_mapping===null ||
        !is_associative_array($microservices_mapping) ) {
        (new error_())->add( "invalid known microservices file",
                             "8Q5z1MDfD94s",
                             2,
                             "backend" ) ;
        return false ;
    }

	foreach( $microservice_sequences as $microservice_sequence ) {
		run_microservice_sequence( $microservice_sequence, $microservices_mapping, true ) ;
	}

	return true ;
}


// function cli_apply_system_update( $system, $update_filename ) {
// 	$system_config = [] ;

// 	if( !file_exists("/data/{$system}.json") ) {
// 		(new error_())->add( "config for system: {$system} doesn't exist at the time of status update",
// 			                 "A8c3oAoF8qHv",
// 					         1,
// 					         "backend" ) ;
// 		return false ;
// 	}
// 	$system_config = file_get_contents( "/data/{$system}.json" ) ;
// 	$system_config = json_decode( $system_config, true ) ;
// 	if( $system_config===null ) {
// 		(new error_())->add( "config for system: {$system} doesn't parse at the time of status update",
// 			                 "J4t9rc6Xry1n",
// 					         1,
// 					         "backend" ) ;
// 		return false ;
// 	}

// 	if( !file_exists($update_filename) ) {
// 		(new error_())->add( "status update file: {$update_filename} doesn't exist",
// 			                 "fN8P05A6F8St",
// 					         1,
// 					         "backend" ) ;
// 		return false ;
// 	}
// 	$update = file_get_contents( $update_filename ) ;
// 	$update = json_decode( $update, true ) ;
// 	if( $update_filename===null ) {
// 		(new error_())->add( "status update in file: {$update_filename} doesn't parse",
// 			                 "Ej941SabB3rD",
// 					         1,
// 					         "backend" ) ;
// 		return false ;
// 	}

// 	// making sure we have all the microservices we'll need
// 	$microservices = [] ;
//     $microservices_mapping = [] ;
//     if( !file_exists("/microservices.json") ) {
//         (new error_())->add( "missing known microservices file",
//                              "ST3R0Y5pF9wj",
//                              1,
//                              "backend" ) ;
//         return false ;
//     }
//     $microservices_mapping = json_decode( file_get_contents("/microservices.json"), true ) ;
//     if( $microservices_mapping===null ||
//         !is_associative_array($microservices_mapping) ) {
//         (new error_())->add( "invalid known microservices file",
//                              "N63lL55jWhnK",
//                              1,
//                              "backend" ) ;
//         return false ;
//     }
// 	$microservices_missing = [] ;
// 	compile_system_microservice_list( $system_config, $microservices ) ;
// 	foreach( $microservices as $microservice ) {
// 		if( !array_key_exists($microservice, $microservices_mapping) ) {
// 			$microservices_missing[] = $microservice ;
// 		}
// 	}
// 	if( count($microservices_missing)>0 ) {
// 		(new error_())->add( "microservice(s): " . implode(", ", $microservices_missing) . " are not defined on orchestrator",
// 		                 	 "5u2Q6ytgWmNi",
// 				         	 1,
// 				         	 "backend" ) ;
// 		@unlink( "/data/{$system}.status.json.lock" ) ;
// 		return false ;
// 	}

// 	apply_update_against_config( $system_config, $microservices_mapping ) ;

// 	return true ;
// }


function compile_system_microservice_list( $system_config, &$microservice_list ) {
	if( is_array($system_config) && is_associative_array($system_config) ) {
		foreach( $system_config as $key=>$value ) {
			if( ($key=='set' || $key=='get') && is_array($value) ) {
				for( $i=0 ; $i<count($value) ; $i++ ) {
					$microservice_call = $value[$i] ;
					if( gettype($microservice_call)=="string" &&
						preg_match('/^[A-Za-z\-\_0-9\/]+\:[A-Za-z\-\_0-9]+\/.+$/', $microservice_call) ) {
						$microservice_name = explode( ":", $microservice_call ) ;
						$microservice_name = $microservice_name[0] ;
						$microservice_tag  = explode( ":", $microservice_call ) ;
						$microservice_tag  = $microservice_tag[1] ;
						$microservice_tag  = explode( "/", $microservice_tag ) ;
						$microservice_tag  = $microservice_tag[0] ;
                        if( $microservice_tag=="current" ) {
                            $microservice_tag = getenv()['VERSION'] ;
                        }
						$microservice_key  = "{$microservice_name}:{$microservice_tag}" ;
						if( !in_array($microservice_key, $microservice_list) ) {
							$microservice_list[] = $microservice_key ;
						}
					} elseif( gettype($microservice_call)=="array" &&
							  isset($microservice_call['microservice']) &&
							  preg_match('/^[A-Za-z\-\_0-9\/]+\:[A-Za-z\-\_0-9]+\/.+$/', $microservice_call['microservice']) ) {
						$microservice_name = explode( ":", $microservice_call['microservice'] ) ;
						$microservice_name = $microservice_name[0] ;
						$microservice_tag  = explode( ":", $microservice_call['microservice'] ) ;
						$microservice_tag  = $microservice_tag[1] ;
						$microservice_tag  = explode( "/", $microservice_tag ) ;
						$microservice_tag  = $microservice_tag[0] ;
                        if( $microservice_tag=="current" ) {
                            $microservice_tag = getenv()['VERSION'] ;
                        }
						$microservice_key  = "{$microservice_name}:{$microservice_tag}" ;
						if( !in_array($microservice_key, $microservice_list) ) {
							$microservice_list[] = $microservice_key ;
						}
					}
				}
			} else {
				compile_system_microservice_list( $value, $microservice_list ) ;
			}
		}
	}
}


function interpret_config_as_current_status( &$system_config, $microservices_mapping ) {
	if( is_array($system_config) ) {
		if( array_key_exists('get', $system_config) ) {
			$results = run_microservice_sequence( $system_config['get'], $microservices_mapping, false ) ;
			if( !array_key_exists('get_process', $system_config) ) {
                foreach( $results as &$result ) {
                        $potential_result = json_decode( $result, true ) ;
	                if( is_array($potential_result) ) {
	                        $result = $potential_result ;
	                }
                }
                unset( $result ) ;
                $system_config = $results ;
			} else if( gettype($system_config['get_process'])=="array" ) {
				if( isset($system_config['get_process']['function_name']) ) {
					require_once( "system_status_process_functions.php" ) ;
		    		if( !function_exists($system_config['get_process']['function_name']) ) {
		    			(new error_())->add( "get_process function: {$system_config['get_process']['function_name']} is not defined",
				                             "ORji83l6j6Xt",
				                             2,
				                             "backend" ) ;
		    			// to backend information from percolating up to the client
		    			$system_config = null ;
					} else {
						$arguments = array( 'results'=>json_encode($results) ) ;
						if( isset($system_config['get_process']['function_arguments']) ) {
							foreach( $system_config['get_process']['function_arguments'] as $argument_name=>$argument_value ) {
								$arguments[$argument_name] = $argument_value ;
							}
						}
						// $config_location_key = md5( json_encode($system_config['set']) . "|" . json_encode($system_config['get']) ) ;
						// $arguments['config_location_key'] = $config_location_key ;
						$system_config = call_user_func_array( $system_config['get_process']['function_name'], $arguments ) ;
					}
		    	} else {
			    	// simple key based variable substitution
					foreach( $results as &$result ) {
						$result = json_decode( $result, true ) ;
					}
					unset( $result ) ;
					$system_config = true_if_exact_match( $results, $system_config['get_process'] ) ;
				}
			} else if( gettype($system_config['get_process'])=="string" ) {
				require_once( "system_status_process_functions.php" ) ;

				$get_process_function_name = $system_config['get_process'] ;
				$get_process_function_name = explode( "(", $get_process_function_name ) ;
				$get_process_function_name = $get_process_function_name[0] ;
				$get_process_function_name = trim( $get_process_function_name ) ;

				if( $get_process_function_name!="" && !function_exists($get_process_function_name) ) {
					error_out( "get_process function {$get_process_function_name} is NOT defined, it needs to be", false, false ) ;
					$system_config = json_encode( $results ) ;
				} else {
					// ok we're good
					foreach( $results as &$result ) {
						$result = json_decode( $result, true ) ;
					}
					unset( $result ) ;
					if( substr_count($system_config['get_process'], "(")==1 &&
						substr_count($system_config['get_process'], ")")==1 ) {
						// looks like we have arguments to worry about
						$args = explode( "(", $system_config['get_process'] ) ;
						$args = implode( "(", array_slice($args, 1) ) ;
						$args = explode( ")", $args ) ;
						$args = implode( ")", array_slice($args, 0, count($args)-1) ) ;
						// $args = str_replace( " ", "", $args ) ;
						$args = trim( $args ) ;
						// $args = explode( ",", $args ) ;
						// if( count($args)==1 ) {
						// 	$args = $args[0] ;
						// }
						$args = json_decode( $args, true ) ;
						// echo "<pre>" ;print_r( $args ) ;
						$system_config = call_user_func( $get_process_function_name, $results, $args ) ;
					} else {
						$system_config = call_user_func( $get_process_function_name, $results ) ;
					}
				}
			} else {
				// error_out( "get_process in system_config at " . var_export( $system_config, true ) . " is set to an unknown type, it needs to either be an array or a string.", false, false ) ;
				(new error_())->add( "get_process is set to an unknown type, it needs to either be an array or a string",
		                             "D3n657jcS8k4",
		                             2,
		                             "backend" ) ;
    			// to backend information from percolating up to the client
    			$system_config = null ;
			}
		} else {
			$keys = array_keys( $system_config ) ;
			foreach( $keys as $key ) {
				interpret_config_as_current_status( $system_config[$key], $microservices_mapping ) ;
			}
		}
	} else {
		// TODO error here?
	}
}


function merge_current_status_with_update( $system_config, &$system_status, $update, &$accumulated_microservice_sequences, &$error ) {
	if( is_array($update) ) {
		if( is_array($system_config) &&
			is_array($system_status) ) {
			foreach( $update as $update_key=>$update_value ) {
				if( isset($system_config[$update_key]) &&
					isset($system_status[$update_key]) ) {
					return merge_current_status_with_update( $system_config[$update_key], $system_status[$update_key], $update[$update_key], $accumulated_microservice_sequences, $error ) ;
				} else {
					(new error_())->add( "requested update:\n" . json_encode($update, JSON_PRETTY_PRINT) . "\n\ndoesn't line up in structure with system config:\n" . json_encode($system_config, JSON_PRETTY_PRINT) . "\n\nor system status:\n" . json_encode($system_status, JSON_PRETTY_PRINT),
					                 	 "7ND4dL6XCmus",
							         	 2,
							         	 "backend" ) ;
					$error = "invalid update" ;
				}
			}
		} else {
			(new error_())->add( "requested update:\n" . json_encode($update, JSON_PRETTY_PRINT) . "\n\ndoesn't line up in structure with system config:\n" . json_encode($system_config, JSON_PRETTY_PRINT) . "\n\nor system status:\n" . json_encode($system_status, JSON_PRETTY_PRINT),
			                 	 "xetOz4m4J0t2",
					         	 2,
					         	 "backend" ) ;
			$error = "invalid update" ;
		}
	} else {
		if( isset($system_config['set']) ) {

			$variables = [] ;
		    if( isset($system_config['set_process']) ) {
		    	require_once( "system_status_process_functions.php" ) ;
		    	if( gettype($system_config['set_process'])=="string" ) {
		    		if( !function_exists($system_config['set_process']) ) {
		    			(new error_())->add( "unknown set_process function: {$system_config['set_process']}",
					                 	     "w1ZqaCb014Th",
							         	     2,
							         	     "backend" ) ;
		    		} else {
				    	$variables = call_user_func( $system_config['set_process'], $update ) ;
				    }
			    } else if( gettype($system_config['set_process'])=="array" ) {
			    	if( isset($system_config['set_process']['function_name']) ) {
			    		if( !function_exists($system_config['set_process']['function_name']) ) {
			    			(new error_())->add( "unknown set_process function: {$system_config['set_process']}",
						                 	     "2cz9L2ZhTOzP",
								         	     2,
								         	     "backend" ) ;
						} else {
							$arguments = ['value'=>$update] ;
							if( isset($system_config['set_process']['function_arguments']) ) {
								foreach( $system_config['set_process']['function_arguments'] as $argument_name=>$argument_value ) {
									$arguments[$argument_name] = $argument_value ;
								}
							}
							$variables = call_user_func_array( $system_config['set_process']['function_name'], $arguments ) ;
						}
			    	} else {
				    	// simple key based variable substitution
				    	$value_key = $update ;
				    	// JSON doesn't support booleans as indices
				    	if( $value_key===true ) {
				    		$value_key = "true" ;
				    	} elseif( $value_key===false ) {
				    		$value_key = "false" ;
				    	}
				    	if( gettype($value_key)!="string" ) {
				    		(new error_())->add( "unknown type for set_process key",
						                 	     "aU8BoySsf80A",
								         	     2,
								         	     "backend" ) ;
				    	} else if( !array_key_exists($value_key, $system_config['set_process']) ) {
				    		(new error_())->add( "unhandled value key for set_process",
						                 	     "oUNna7FYk98j",
								         	     2,
								         	     "backend" ) ;
				    	} else {
				    		foreach( $system_config['set_process'][$value_key] as $variable_name=>$variable_value ) {
				    			$variables[$variable_name] = $variable_value ;
				    		}
						}
					}
			    } else {
			    	(new error_())->add( "unknown type for set_process",
				                 	     "uxDQ8Rp9L224",
						         	     2,
						         	     "backend" ) ;
			    }
		    }
		    if( count($variables)>0 ) {
				$matchess = array() ;

				$set_as_string = json_encode( $system_config['set'] ) ;
				preg_match_all( '/\$[a-zA-Z]{1,}[a-zA-Z0-9-_]{0,}/', $set_as_string, $matchess ) ;
				// print_r( $matches ) ;
				foreach( $matchess as $matches ) {
					foreach( $matches as $match ) {
						$match_no_dollar = str_replace( '$', "", $match ) ;
						if( array_key_exists($match_no_dollar, $variables) ) {
							$set_as_string = str_replace( $match, $variables[$match_no_dollar], $set_as_string ) ;
						} else {
							(new error_())->add( "variable: {$match} not found in computed variables:\n" . var_export( $variables, true ),
						                 	     "16W5p2y3pI2R",
								         	     3,
								         	     "backend" ) ;
						}
					}
				}
				$system_config['set'] = json_decode( $set_as_string, true ) ;
		    }

		    if( isset($system_config['set_process']) ) {
			    unset( $system_config['set_process'] ) ;
			}
			if( isset($system_config['get']) ) {
			    unset( $system_config['get'] ) ;
			}
			if( isset($system_config['get_process']) ) {
			    unset( $system_config['get_process'] ) ;
			}

			$accumulated_microservice_sequences[] = $system_config['set'] ;
			$system_status = $update ;
		} else {
			(new error_())->add( "requested update:\n" . json_encode($update, JSON_PRETTY_PRINT) . "\n\nis trying to set a variable that is not settable in system config:\n" . json_encode($system_config, JSON_PRETTY_PRINT),
			                 	 "I5h05S2P7yQX",
					         	 2,
					         	 "backend" ) ;
			$error = "invalid update" ;
		}
	}
}


function extract_microservice_call_parts( $microservice_call ) {

    $parts = explode( "/", $microservice_call ) ;

    $device_index = false ;
    for( $i=0 ; $i<count($parts) ; $i++ ) {
        if( $i>0 && substr_count($parts[$i], ".")>0 ) {
            $device_index = $i ;
            break ;
        }
    }

    $registry_path = "" ;
    $device_username = "" ;
    $device_password = "" ;
    $device_fqdn = "" ;
    $microservice_path = "" ;

    if( $device_index!==false ) {
        if( substr_count($parts[$device_index], "@")==1 ) {
            $parts[$device_index] = explode( "@", $parts[$device_index] ) ;
            $credentials = $parts[$device_index][0] ;
            $parts[$device_index] = $parts[$device_index][1] ;
            $credentials = explode( ":", $credentials ) ;
            $device_username = $credentials[0] ;
            if( count($credentials)==2 ) {
                $device_password = $credentials[1] ;
            }
        }
        $device_fqdn = $parts[$device_index] ;
        $device_fqdn = resolve_dns( $device_fqdn ) ;
        $registry_path = "/" . implode( "/", array_slice($parts, 0, $device_index) ) ;
        $microservice_path = "/" . implode( "/", array_slice($parts, $device_index+1) ) ;

        return ['registry_path'=>$registry_path,
                'device_username'=>$device_username,
                'device_password'=>$device_password,
                'device_fqdn'=>resolve_dns($device_fqdn),
                'microservice_path'=>$microservice_path] ;

    } else {
        return false ;
    }
}


function run_microservice_sequence( $microservice_sequence, $microservices_mapping, $is_a_set=true /* as opposed to a get, set invalidates the cache, it's safer to default to no cache if not specified */ ) {
	global $verbose ;

	$results = [] ;

    $memcached = new memcached_() ;

	if( !is_array($microservice_sequence) ) {
		(new error_())->add( "invalid microservice sequence: " . var_export($microservice_sequence, true),
                             "i61Mn7v74J9P",
                             2,
                             "config" ) ;
        return $results ; // which should only be [] at this point
	}
	foreach( $microservice_sequence as $microservice_call ) {
        $request_method = "GET" ;
        $request_headers = [] ;
        $request_body = "" ;
        $repo_owner = false ;
        $repo_name = false ;
        $tag = false ;
        $device_username = false ;
        $device_password = false ;
        $device_fqdn = "" ;
        $microservice_path_and_get_variables = "" ;
        $microservice_error_to_return = false ;
        $no_cache = false ;

        if( gettype($microservice_call)==="array" ) {
            if( isset($microservice_call['method']) &&
                is_string($microservice_call['method']) ) {
                $request_method = $microservice_call['method'] ;
            }
            if( isset($microservice_call['headers']) &&
                is_array($microservice_call['headers']) ) {
                $request_headers = $microservice_call['headers'] ;
            }
            if( isset($microservice_call['body']) ) {
                $request_body = $microservice_call['body'] ;
            }
            if( isset($microservice_call['error_return']) ) {
                $microservice_error_to_return = $microservice_call['error_return'] ;
            }
            if( isset($microservice_call['no_cache']) &&
                $microservice_call['no_cache']===true ) {
                $no_cache = true ;
            }

            if( isset($microservice_call['microservice']) &&
                is_string($microservice_call['microservice']) ) {
                $microservice_call = $microservice_call['microservice'] ;
            } else if( isset($microservice_call['driver']) && // legacy name
                       is_string($microservice_call['driver']) ) {
                $microservice_call = $microservice_call['driver'] ;
            }
        }

        if( gettype($microservice_call)==="string" ) {
            $repo_owner = explode( "/", $microservice_call )[0] ;
            $repo_name = explode( ":", explode("/", $microservice_call)[1] )[0] ;
            $tag = explode( ":", explode("/", $microservice_call)[1] )[1] ;
            $device_fqdn = explode( "/", $microservice_call )[2] ;
            if( preg_match('/^.*\:.*\@.*$/', $device_fqdn) ) { // simple auth
                $device_username = explode( ":", explode("@", $device_fqdn)[0] )[0] ;
                $device_password = explode( ":", explode("@", $device_fqdn)[0] )[1] ;
                $device_fqdn = explode( "@", $device_fqdn )[1] ;
            }
            $microservice_path_and_get_variables = "/" . implode( "/", array_slice(explode("/", $microservice_call), 3) ) ;
        }

        if( $verbose ) {
	        echo "> microservice call: {$microservice_call}\n" ;
	        echo ">   parameters:\n" ;
	        echo ">     request_method: {$request_method}\n" ;
	        echo ">     request_headers:\n" ; print_r( $request_headers ) ;
	        echo ">     request_body: {$request_body}\n" ;
	        echo ">     repo_owner: {$repo_owner}\n" ;
	        echo ">     repo_name: {$repo_name}\n" ;
	        echo ">     tag: {$tag}\n" ;
	        echo ">     device_username: {$device_username}\n" ;
	        echo ">     device_password: {$device_password}\n" ;
	        echo ">     device_fqdn: {$device_fqdn}\n" ;
	        echo ">     microservice_path_and_get_variables: {$microservice_path_and_get_variables}\n" ;
	    }

	    $proceed_with_call = true ;

        if( $repo_owner===false || $repo_name===false || $tag==="false" ) {
            (new error_())->add( "invalid microservice call: {$microservice_call}",
                                 "EQr87gl3YCKm",
                                 2,
                                 "config" ) ;
            echo ">   invalid microservice call: {$microservice_call}\n" ;
            $proceed_with_call = false ;
            $results[] = null ;
        }

        if( $tag=="current" ) {
            $tag = getenv()['VERSION'] ;
        }

        if( !isset($microservices_mapping["{$repo_owner}/{$repo_name}:{$tag}"]) ) {
            (new error_())->add( "missing microservice mapping for: {$repo_owner}/{$repo_name}:{$tag}",
                                 "i61Mn7v74J9P",
                                 2,
                                 "backend" ) ;
            echo ">   missing microservice mapping for: {$repo_owner}/{$repo_name}:{$tag}\n" ;
            $proceed_with_call = false ;
            $results[] = null ;
        }

        
        if( $proceed_with_call ) {
	        $url = $microservices_mapping["{$repo_owner}/{$repo_name}:{$tag}"] . "/" . $device_fqdn . $microservice_path_and_get_variables ;
	        if( $verbose ) {
	        	echo ">   url: {$url}\n" ;
	        }
			$cache_data = null ;
			$cache_keys = null ;
	        // microservice
			$cache_keys = $memcached->retrieve( $device_fqdn ) ;
			if( $is_a_set && $cache_keys!==null ) {
				if( $verbose ) {
		        	echo ">   wiping cache for device: {$device_fqdn}\n" ;
		        }
				// we're setting on the device, we want to wipe its cache for subsequent gets to get new data
	            //   that's because some endpoints might be interdependent and changing one might affect another
	            //   for example, changing the volume might unmute
				foreach( $cache_keys as $cache_key ) {
					$memcached->delete( $cache_key ) ;
				}
				$memcached->delete( $device_fqdn ) ;
			} else {
				// we're getting, maybe we can hit the cache
				$cache_data = $memcached->retrieve( $url ) ;
				if( $verbose ) {
		        	echo ">   checking cache for data\n" ;
		        }
			}

			if( !$is_a_set &&
				$cache_data!==null ) {
				if( $verbose ) {
		        	echo ">     cache hit\n" ;
		        }
				$results[] = $cache_data ;
			} else {
				if( $verbose ) {
		        	echo ">   proceeding with call\n" ;
		        }
				$ch = curl_init() ;
				curl_setopt( $ch, CURLOPT_URL, $url ) ;
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false ) ;
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_method ) ;
				if( $request_body!=="" ) {
					if( gettype($request_body)=="string" ) {
	        			curl_setopt( $ch, CURLOPT_POSTFIELDS, $request_body ) ;
	        		} else {
	        			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($request_body) ) ;
	        		}
	    		}
	    		if( count($request_headers)>0 ) {
					curl_setopt( $ch, CURLOPT_HTTPHEADER, $request_headers ) ;
	    		}
				curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 1 ) ;
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ) ;
				curl_setopt( $ch, CURLOPT_TIMEOUT, 5 ) ;
				$response = curl_exec( $ch ) ;
				$response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE ) ;
				$curl_errno = curl_errno( $ch ) ;
				curl_close( $ch ) ;

				if( $verbose ) {
					echo ">     response_code: {$response_code}\n" ;
					echo ">     response:\n" ;
					print_r( $response ) ;
				}

				if( $response_code==200 ) {
					$results[] = $response ;
					if( !$is_a_set ) {
						if( $no_cache!==true ) {
							echo ">  storing in cache\n" ;
							if( $cache_keys===null || !is_array($cache_keys) ) {
								$cache_keys = [] ;
							}
							if( !in_array($url, $cache_keys) ) {
								$cache_keys[] = $url ;
							}
							$memcached->store( $device_fqdn, $cache_keys, 0 ) ;
							$memcached->store( $url, $response, 60 ) ;
						}
					}
				} else { // $response_code!=200
					$only_a_204_on_a_fresh_microservice = false ;
					if( $response_code==204 && is_fresh_device($device_fqdn) ) {
						$only_a_204_on_a_fresh_microservice = true ;
					}

					if( !$only_a_204_on_a_fresh_microservice ) {
						$timeout_potentially = "" ;
						if( $curl_errno==28 ) {
							$timeout_potentially = " (which is a timeout)" ;
						}

						if( $microservice_error_to_return!==false ) {
							$results[] = $microservice_error_to_return ;
						} else {
							$results[] = null ;
						}
						(new error_())->add( "microservice call failed:\n\nrequest:\n  method: {$request_method}\n  url: {$url}  body: {$request_body}\n  headers: " . implode( "\n    ", $request_headers ) . "\n\nresponse:\n  response_code: {$response_code}\n  response: {$response}\n  curl_errno: {$curl_errno}{$timeout_potentially}",
	                 	 "qv23K8hX8Y0R",
			         	 1,
			         	 "backend" ) ;
					}
				}
			}
		}
    }

    if( $verbose ) {
    	echo "> all calls results:\n" ;
    	print_r( $results ) ;
    }
	return $results ;
}


function is_valid_system_config( $config ) {
	if( json_decode($config)===null ) {
		return false ;
	}

	// TODO more checks
	return true ;
}


function is_valid_system_name( $system ) {
	return preg_match( '/^[0-9a-zA-Z\-\_]{1,}$/', $system ) ;
}


function is_fresh_device( $fqdn ) {
    // TODO at some point we'll actually want to keep track of new devices so we can handle 204s properly
    return true ;
}




//   ____ _ _            _       
//  / ___| (_) ___ _ __ | |_ ___ 
// | |   | | |/ _ \ '_ \| __/ __|
// | |___| | |  __/ | | | |_\__ \
//  \____|_|_|\___|_| |_|\__|___/


function create_client_error() {
	$data = get_request_body() ;

	if( !is_string($data) ||
		strlen(trim($data))==0 ) {
		close_with_400( "Client error message needed as a string in the request body" ) ;
	}

	$client_ip = $_SERVER['REMOTE_ADDR'] ;
	if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
	    $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ;
	}
	$client_dns = resolve_dns( $client_ip ) ;
	$client_user_agent = $_SERVER['HTTP_USER_AGENT'] ;

	(new error_())->add( "Client error reported from: {$client_ip} / {$client_dns} / {$client_user_agent}\n\nMessage: {$data}",
		                 "WQ2r1U4gSX8A",
        				 3,
				         "client" ) ;

	close_with_200( "ok" ) ;
}




//  __  __             _ _             _             
// |  \/  | ___  _ __ (_) |_ ___  _ __(_)_ __   __ _ 
// | |\/| |/ _ \| '_ \| | __/ _ \| '__| | '_ \ / _` |
// | |  | | (_) | | | | | || (_) | |  | | | | | (_| |
// |_|  |_|\___/|_| |_|_|\__\___/|_|  |_|_| |_|\__, |
//                                             |___/ 

function get_version() {
	$version = "unknown" ;
	if( file_exists("/var/version") ) {
		$version = file_get_contents( "/var/version" ) ;
	}

	close_with_200( $version ) ;
}




//  _   _ _____ _____ ____  
// | | | |_   _|_   _|  _ \ 
// | |_| | | |   | | | |_) |
// |  _  | | |   | | |  __/ 
// |_| |_| |_|   |_| |_|    
                          
function get_request_body( $decode_json=true ) {
	$body = file_get_contents( "php://input" ) ;
	
	if( $decode_json ) {
		$body = json_decode( $body, true ) ;
	}

	return $body ;
}


function close_with_200( $data ) {

	http_response_code( 200 ) ;

	header( "Content-Type: application/json" ) ;

	echo json_encode( $data ) ;
	exit( 0 ) ;
}


function close_with_204( $message ) {

	http_response_code( 204 ) ;

	header( "Content-Type: application/json" ) ;

	echo json_encode( $message ) ;
	exit( 0 ) ;
}


function close_with_400( $message ) {

	http_response_code( 400 ) ;

	header( "Content-Type: application/json" ) ;

	echo json_encode( $message ) ;
	exit( 1 ) ;
}


function close_with_404( $message ) {

	http_response_code( 404 ) ;

	header( "Content-Type: application/json" ) ;

	echo json_encode( $message ) ;
	exit( 1 ) ;
}


function close_with_500( $message ) {

	http_response_code( 500 ) ;

	header( "Content-Type: application/json" ) ;

	echo json_encode( $message ) ;
	exit( 1 ) ;
}




 //  _   _ _   _ _ _ _   _           
 // | | | | |_(_) (_) |_(_) ___  ___ 
 // | | | | __| | | | __| |/ _ \/ __|
 // | |_| | |_| | | | |_| |  __/\__ \
 //  \___/ \__|_|_|_|\__|_|\___||___/


function resolve_dns( $fqdn ) {
	if( filter_var($fqdn, FILTER_VALIDATE_IP) ) {
		// it's already an IP
		return $fqdn ;
	} else {
		$dns_result = gethostbyname( $fqdn ) ;

		return $dns_result ;
		// // anything already cached?
		// $dns_filename = "/data/dns_cache_" . md5( $fqdn ) ;
		// $result = $memcached->retrieve( "dns_cache_{$fqdn}" ) ;
		// if( $result===null ) {
		// 	// let's try persistent storage
		// 	if( file_exists($dns_filename) ) {
		// 		$record = json_decode( file_get_contents($dns_filename) ) ;
		// 		if( is_string($record) ) {
		// 			// ok!
		// 			$result = $record ;
		// 			// we can commit this to the memory cache now for next time
		// 			$memcached->store( "dns_cache_{$fqdn}", $result, 0 ) ;
		// 		}
		// 	}
		// }
		// if( $result!==null ) {
		// 	return $result ;
		// } else {
		// 	$dns_result = gethostbyname( $fqdn ) ;
		// 	$memcached->store( "dns_cache_{$fqdn}", $dns_result, 0 ) ;
		// 	// and persistent cache to survive reboots
		// 	file_put_contents( $dns_filename, json_encode($dns_result) ) ;
		// 	return $dns_result ;
		// }
	}
}


function true_if_exact_match( $data, $expected ) {
	$diff = array_diff_assoc_recursive( $expected, $data ) ;
	if( count($diff)===0 ) {
		// all the values in $expected were found in $results
		return true ;
	} else {
		return false ;
	}
}


function true_if_at_least_one_match( $data, $expected ) {
	foreach( $expected as $expected_key=>$expected_value ) {
		if( $data[$expected_key]==$expected_value ) {
			return true ;
		}
	}
	return false ;
}


# taken from https://stackoverflow.com/a/173479
function is_associative_array(array $arr)
{
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}


// gotten from https://www.php.net/manual/en/function.array-diff-assoc.php
//   2023-11-22 - Ben - modification to support range comparison
function array_diff_assoc_recursive ( )
{
    $args = func_get_args ( );
    $diff = array ( );
    foreach ( array_shift ( $args ) as $key => $val )
    {
        for ( $i = 0, $j = 0, $tmp = array ( $val ) , $count = count ( $args ); $i < $count; $i++ )
            if ( is_array ( $val ) )
                if ( !isset ( $args[$i][$key] ) || !is_array ( $args[$i][$key] ) || empty( $args[$i][$key] ) )
                    $j++;
                else
                    $tmp[] = $args[$i][$key];
            // elseif ( ! array_key_exists ( $key, $args[$i] ) || $args[$i][$key] !== $val )
            elseif ( ! array_key_exists ( $key, $args[$i] ) || !compare_function($args[$i][$key], $val) )
                $j++;
        if ( is_array ( $val ) )
        {
            $tmp = call_user_func_array ( __FUNCTION__, $tmp );
            if ( ! empty ( $tmp ) ) $diff[$key] = $tmp;
            elseif ( $j == $count ) $diff[$key] = $val;
        }
        elseif ( $j == $count && $count ) $diff[$key] = $val;
    }

    return $diff;
}


function compare_function( $val1, $val2 ) {
    if( $val1===$val2 ||
        range_comparison($val1, $val2) ||
        range_comparison($val2, $val1) ) {
        return true ;
    }

    return false ;
}


function range_comparison( $val, $range ) {
    if( gettype($range)=="string" &&
        $range[0]=="[" &&
        $range[strlen($range)-1]=="]" &&
        substr_count($range, ",")>0 &&
        (gettype($val)=="integer" || gettype($val)=="double") ) {
        $range = substr( $range, 1, strlen($range)-2 ) ;
        $range = explode( ",", $range ) ;
        $range[0] = floatval( trim($range[0]) ) ;
        $range[1] = floatval( trim($range[1]) ) ;

        if( $range[0]>$range[1] ) {
            return false ;
        }
        if( $range[0]<=$val && $val<=$range[1] ) {
            return true ;
        }
    }

    return false ;
}


?>