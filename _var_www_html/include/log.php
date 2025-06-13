<?php


require_once( "error.php" ) ;
require_once( "include/time.php" ) ;
require_once( "web_calls.php" ) ;

class log_ {

    // variable declaration

    function __construct() {
    }

    function add_entry( $system, $type, $data, $time_stamp_override=null ) {
        file_put_contents( "/tmp/meow", var_export($system, true).var_export($state, true).var_export($data, true)) ;
        if( isset(getenv()['LOG_TO_SPLUNK']) &&
            getenv()['LOG_TO_SPLUNK']=="true" ) {
            if( isset(getenv()['LOG_TO_SPLUNK_URL']) &&
                isset(getenv()['LOG_TO_SPLUNK_KEY']) &&
                isset(getenv()['LOG_TO_SPLUNK_INDEX']) ) {
                $splunk_time_stamp = ($time_stamp_override===null)?time():strtotime($time_stamp_override) ;
                $splunk_data = ["time"=>$splunk_time_stamp,
                                "host"=>gethostname().".".$system,
                                "sourcetype"=>$type,
                                "index"=>getenv()['LOG_TO_SPLUNK_INDEX'],
                                "event"=>$data] ;
                (new web_calls_())->asynchronous_one_time_web_call( getenv()['LOG_TO_SPLUNK_URL'], "POST", ["authorization: Splunk " . getenv()['LOG_TO_SPLUNK_KEY'],
                                                                                                            "content-type: application/json"], json_encode($splunk_data) ) ;
            } else {
                (new error_())->add( "I have LOG_TO_SPLUNK environment variable set to \"true\" but something is wrong with the subsequently needed LOG_TO_SPLUNK_* variables",
                                     "52Tf7ejz9O0t",
                                     2,
                                     ["backend"],
                                     "orchestrator",
                                     null,
                                     0,
                                     1 ) ;
            }
        }
    }
}

?>