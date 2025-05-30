<?php


require_once( "error.php" ) ;
require_once( "sqlite.php" ) ;


if( php_sapi_name()==="cli" &&
    basename($argv[0])=="web_calls.php" ) {
    $last_cleanup = time() ;
    while( true ) {
        $to_process = sqlite_query( "/dev/shm/web_calls.db",
                                    "SELECT id,
                                            request_url,
                                            request_method,
                                            request_headers,
                                            request_body FROM data WHERE keep_refreshed='true' AND
                                                                         (last_refresh IS NULL OR last_refresh<=datetime('now',printf('-%d minutes', refresh_every_x_minutes)))" ) ;
        if( count($to_process)>0 ) {
            echo "\n" ;
            foreach( $to_process as $web_call ) {
                echo "> " . date( "Y-m-d H:i:s" ) . " running: {$web_call['request_url']}, {$web_call['request_method']}, {$web_call['request_headers']}, {$web_call['request_body']}\n" ;
                $response = (new web_calls_())->synchronous_web_call( $web_call['request_url'], $web_call['request_method'], json_decode($web_call['request_headers'], true), $web_call['request_body'], true ) ;
                echo "> " . date( "Y-m-d H:i:s" ) . "   response code:{$response['code']}\n" ;
                sqlite_query( "/dev/shm/web_calls.db",
                              "UPDATE data SET response_code=:response_code,
                                               response_body=:response_body,
                                               last_refresh=CURRENT_TIMESTAMP WHERE id=:id", [':response_code'=>$response['code'],
                                                                                              ':response_body'=>$response['body'],
                                                                                              ':id'=>$web_call['id']] ) ;
            }
        } else {
            echo "." ;
            sleep( 1 ) ;
        }
        if( time()-$last_cleanup>60 ) {
            echo "> " . date( "Y-m-d H:i:s" ) . " cleaning up non inquired\n" ;
            sqlite_query( "/dev/shm/web_calls.db",
                          "DELETE FROM data WHERE last_inquiry>=datetime('now', '-5 minutes')" ) ;
            $last_cleanup = time() ;
        }
    }
    exit( 0 ) ;
}


class web_calls_ {

    // variable declaration

    function __construct() {
    }


    function get_decoupled_data( $request_url, $request_method="GET", $request_headers=[], $request_body="" ) {
        $data = sqlite_query( "/dev/shm/web_calls.db",
                              "SELECT response_code,
                                      response_body FROM data WHERE request_url=:request_url AND
                                                                    request_method=:request_method AND
                                                                    request_headers=:request_headers AND
                                                                    request_body=:request_body", [':request_url'=>$request_url,
                                                                                                  ':request_method'=>$request_method,
                                                                                                  ':request_headers'=>json_encode($request_headers),
                                                                                                  ':request_body'=>$request_body] ) ;
        if( is_array($data) &&
            count($data)==0 ) {
            $data = null ;
        }
        if( $data===null ) {
            sqlite_query( "/dev/shm/web_calls.db",
                          "INSERT INTO data (request_url,
                                             request_method,
                                             request_headers,
                                             request_body,
                                             keep_refreshed) VALUES (:request_url,
                                                                     :request_method,
                                                                     :request_headers,
                                                                     :request_body,
                                                                     'true')
                                  ON CONFLICT (request_url,
                                               request_method,
                                               request_headers,
                                               request_body) DO UPDATE SET last_inquiry=CURRENT_TIMESTAMP", [':request_url'=>$request_url,
                                                                                                             ':request_method'=>$request_method,
                                                                                                             ':request_headers'=>json_encode($request_headers),
                                                                                                             ':request_body'=>$request_body] ) ;
        } else if( is_array($data) &&
                   count($data)==1 &&
                   is_array($data[0]) &&
                   array_key_exists('response_body', $data[0]) ) {
            $data = $data[0]['response_body'] ;
        } else {
            (new error_())->add( "invalid web call data gotten from database call: request_url={$request_url}, request_method={$request_method}, request_headers=" . json_encode($request_headers) . ", request_body={$request_body}, which yielded data: " . json_encode($data),
                                 "1g40KdgZZP9j",
                                 2,
                                 "backend" ) ;
            // failsafe
            $data = null ;
        }
        return $data ;
    }


    function synchronous_web_call( $request_url, $request_method="GET", $request_headers=[], $request_body="", $return_details=false ) {
        $ch = curl_init() ;
        curl_setopt( $ch, CURLOPT_URL, $request_url ) ;
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
        $response_body = curl_exec( $ch ) ;
        $response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE ) ;
        $curl_errno = curl_errno( $ch ) ;
        curl_close( $ch ) ;

        // if( $verbose ) {
        //     echo ">     response_code: {$response_code}\n" ;
        //     echo ">     response:\n" ;
        //     print_r( $response ) ;
        //     echo "\n" ;
        // }

        if( $return_details ) {
            return ['code'=>$response_code,
                    'body'=>$response_body] ;
        } else {
            return $response_body ;
        }
    }
}

?>