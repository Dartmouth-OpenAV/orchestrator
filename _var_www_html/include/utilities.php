<?php


// file_get_contents with locking
function safe_file_get_contents( $filename ) {
    $content = false ;

    $handle = fopen( $filename, "r" ) ;
    if( $handle ) {
        if( flock($handle, LOCK_SH) ) {
            $content = stream_get_contents( $handle ) ;
            flock( $handle, LOCK_UN ) ;
        } else {
            return false ;
        }
        fclose( $handle ) ;
    } else {
        return false ;
    }

    return $content ;
}


// file_put_contents with locking, could also be achieved with file_put_contents( $filename, $data, LOCK_EX ) but I like the consistency of having explicit & symmetrical functions
function safe_file_put_contents( $filename, $data ) {
    $bytes_written = false ;

    $handle = fopen( $filename, "c" ) ;
    if( $handle ) {
        if( flock($handle, LOCK_EX) ) {
            ftruncate( $handle, 0 ) ;
            rewind( $handle ) ;
            $bytes_written = fwrite( $handle, $data ) ;
            fflush( $handle ) ;
            flock( $handle, LOCK_UN ) ;
        } else {
            return false ;
        }
        fclose( $handle ) ;
    } else {
        return false ;
    }

    return $bytes_written;
}

?>