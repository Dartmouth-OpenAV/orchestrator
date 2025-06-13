<?php

$env = getenv() ;
if( isset($env['TZ']) ) {
    date_default_timezone_set( $env['TZ'] ) ;
} else {
    ; // happen what may
}

?>