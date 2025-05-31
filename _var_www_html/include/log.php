<?php


require_once( "error.php" ) ;
require_once( "web_calls.php" ) ;


class log_ {

    // variable declaration

    function __construct() {
    }

    function add_entry( $system, $type, $data ) {
        // data is currently string, need to make array ? and add room and type?
        if( isset(getenv()['LOG_TO_SPLUNK']) &&
            getenv()['LOG_TO_SPLUNK']=="true" ) {
            if( isset(getenv()['LOG_TO_SPLUNK_URL']) &&
                isset(getenv()['LOG_TO_SPLUNK_KEY']) &&
                isset(getenv()['LOG_TO_SPLUNK_INDEX']) ) {
                $splunk_data = ["time"=>time(),
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
                                     "backend" ) ;
            }
        }
    }
}

?>