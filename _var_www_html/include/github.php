<?php


//  ___            _           _           
// |_ _|_ __   ___| |_   _  __| | ___  ___ 
//  | || '_ \ / __| | | | |/ _` |/ _ \/ __|
//  | || | | | (__| | |_| | (_| |  __/\__ \
// |___|_| |_|\___|_|\__,_|\__,_|\___||___/
//


require_once( "error.php" ) ;
require_once( "utilities.php" ) ;




class github_ {

    // variable declaration
    private $repository_owner ;
    private $repository ;
    private $authentication_type ; // app or token
    private $token ;
    private $app_installation_id ;
    private $app_client_id ;
    private $app_pem ;


    // constructor
    function __construct( $repository_owner, $repository, $credentials ) {
    	$this->repository_owner = $repository_owner ;
    	$this->repository = $repository ;

    	if( !is_array($credentials) ) {
    		return false ;
    	}
    	if( array_key_exists('token', $credentials) &&
    		is_string($credentials['token']) ) {
    		$this->authentication_type = "token" ;
    		$this->token = $credentials['token'] ;
    	} else if( is_array($credentials) &&
    		       isset($credentials['installation_id']) &&
    		       isset($credentials['client_id']) &&
    		       isset($credentials['pem']) ) {
    		$this->authentication_type = "app" ;
    		$this->app_installation_id = $credentials['installation_id'] ;
			$this->app_client_id       = $credentials['client_id'] ;    	
			$this->app_pem             = str_replace( '\\n', "\n", $credentials['pem'] ) ;	
    	} else {
    		// maybe it's public?
    		$this->authentication_type = "none" ;
    	}
	}


	function get_token( $force_refresh=false ) {
		$token_file = "/data/github_access_token" ;

		if( $force_refresh===false &&
			file_exists($token_file) ) {
			$data = safe_file_get_contents( $token_file ) ;
			$data = json_decode( $data, true ) ;
			// print_r( $data['expires_at'] ) ;
			// print_r( (strtotime($data['expires_at'])-time()) ) ;
			if( is_array($data) &&
				isset($data['expires_at']) &&
				(strtotime($data['expires_at'])-time())>0 && // hasn't expired yet
				isset($data['token']) ) {
				return $data['token'] ;
			}
		}

		// JWT
	    $payload = ['iat'=>time(),
	                'exp'=>time() + 600, // 10 minutes JWT expiration time, Github will actually respond with a token that is set 1 hour ahead
	        	    'iss'=>$this->app_client_id] ;
	    $header = ['alg'=>'RS256',
        		   'typ'=>'JWT'] ;

    	$encoded_header  = $this->base64_url_encode( json_encode($header) ) ;
    	$encoded_payload = $this->base64_url_encode( json_encode($payload) ) ;

    	$signature ;
    	openssl_sign( "{$encoded_header}.{$encoded_payload}", $signature, $this->app_pem, OPENSSL_ALGO_SHA256 ) ;

    	$encoded_signature = $this->base64_url_encode( $signature ) ;

    	$jwt = "{$encoded_header}.{$encoded_payload}.{$encoded_signature}" ;

		$token_url = "https://api.github.com/app/installations/{$this->app_installation_id}/access_tokens" ;
		$ch = curl_init() ;
		curl_setopt( $ch, CURLOPT_URL, $token_url ) ;
		curl_setopt( $ch, CURLOPT_POST, true ) ;
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ) ;
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 ) ;
		curl_setopt( $ch, CURLOPT_TIMEOUT, 5 ) ;
		curl_setopt( $ch, CURLOPT_HTTPHEADER, ["Accept: application/vnd.github+json",
											   "Authorization: Bearer {$jwt}",
											   "User-Agent: OpenAV-App"] ) ;
		$response      = curl_exec( $ch ) ;
		$response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE ) ;
		$curl_errno    = curl_errno( $ch ) ;
		curl_close( $ch ) ;
		
		if( $response_code==201 ||
			$response_code==200 ) { // Github responds with 201, but just in case
			$response = json_decode( $response, true ) ;
			if( isset($response['token']) ) {
				safe_file_put_contents( $token_file, json_encode($response) ) ;
				return $response['token'] ;
			}
		}

		(new error_())->add( "Unable to refresh access token with Github with curl_errno: {$curl_errno}, response_code: {$response_code}, and response:\n{$response}",
			                 "Opf7E2euKe13",
					         3,
					         ["backend","github"],
					         "orchestrator",
					         null,
					         1 ) ;

		return false ;
	}


	function base64_url_encode( $data ) {
	    return rtrim( strtr(base64_encode($data), '+/', '-_'), '=' ) ;
	}


	function get_file( $path ) {
		$url = "https://api.github.com/repos/{$this->repository_owner}/{$this->repository}/contents{$path}" ;

		$file = $this->make_api_call( "GET", "/repos/{$this->repository_owner}/{$this->repository}/contents{$path}" ) ;

		if( $file===null ) {
			return null ;
		}

		if( !isset($file['name']) ||
			!isset($file['sha']) ||
			!isset($file['content']) ||
			!isset($file['encoding']) ) {
			return false ;
		}

		if( $file['encoding']!="base64" ) {
			return false ;
		}

		$content = base64_decode( $file['content'] ) ;

		// hash check
		if( hash("sha1", "blob " . strlen($content) . "\0{$content}")!=$file['sha'] ) {
			return false ;
		}

		return $content ;
	}


	function make_api_call( $method,
		                    $path,
		                    $get_variables=null,
		                    $body=null,
		                    $json_encode_body=true,
		                    $json_decode_response=true,
		                    $return_null_if_404=true,
		                    $retry_count=2 ) {

		$url = "https://api.github.com{$path}" ;
		if( $get_variables!==null &&
			is_array($get_variables) &&
			count($get_variables)>0 ) {
		 	$url .= "?" ;
		 	$first = true ;
			foreach( $get_variables as $var_name=>$var_value ) {
				if( !$first ) {
					$url .= "&" ;
				}
				$url .= "{$var_name}=" . urlencode($var_value) ;
				$first = false ;
			}
		}

		$headers = ["User-Agent: Orchestrator PHP cURL"] ;
		if( $this->authentication_type=="token" ) {
			$headers[] = "Authorization: token {$this->token}" ;
		} else if( $this->authentication_type=="app" ) {
			$headers[] = "Authorization: bearer " . $this->get_token() ;
		}

		$ch = curl_init() ;
		curl_setopt( $ch, CURLOPT_URL, $url ) ;
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ) ;
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 ) ;
		curl_setopt( $ch, CURLOPT_TIMEOUT, 5 ) ;
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers ) ;
		if( $body!==null ) {
			if( $json_encode_body ) {
				$body = json_encode( $body ) ;
			}
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $body ) ;
		}
		$response      = curl_exec( $ch ) ;
		$response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE ) ;
		$curl_errno    = curl_errno( $ch ) ;
		curl_close( $ch ) ;

		if( !($response_code>=200 && $response_code<=299) ) {
			if( $response_code==404 &&
				$return_null_if_404 ) {
				return null ;
			}

			if( $response_code==401 &&
				$this->authentication_type=="app" ) {
				// the access token has expired
				//   force refresh
				$this->get_token( true ) ;
			} else {
				// unknown case so we report as an error
				(new error_())->add( "Unable to make API call with Github with curl_errno: {$curl_errno}, response_code: {$response_code}, and response:\n{$response}",
					                 "aEf8D6b3wnQB",
							         2,
							         ["backend","github"],
						             "orchestrator",
						             null,
						             1 ) ;
				if( $retry_count>0 ) {
					sleep( 1 ) ;
				}
			}
			if( $retry_count>0 ) {
				return $this->make_api_call( $method,
					                         $path,
					                         $get_variables,
					                         $body,
					                         $json_encode_body,
					                         $json_decode_response,
					                         $return_null_if_404,
					                         $retry_count-1 ) ;
			}

			return false ;
		}

		if( $json_decode_response ) {
			$response = json_decode( $response, true ) ;
		}
		
		return $response ;
	}
}

?>