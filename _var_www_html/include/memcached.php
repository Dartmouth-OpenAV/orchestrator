<?php


//  ___            _           _           
// |_ _|_ __   ___| |_   _  __| | ___  ___ 
//  | || '_ \ / __| | | | |/ _` |/ _ \/ __|
//  | || | | | (__| | |_| | (_| |  __/\__ \
// |___|_| |_|\___|_|\__,_|\__,_|\___||___/
//

require_once( "error.php" ) ;




class memcached_ {

    // variable declaration
    private $m ;

    // constructor
    function __construct() {
        $this->m = new Memcached() ;
        $this->m->addServer( 'localhost', 11211 ) ;
    }


    function delete( $key, $no_md5=false ) {
        $new_key = $key ;
        if( !$no_md5 ) {
            $new_key = md5( $new_key ) ;
        }

        $temp = $this->m->delete( $new_key ) ;
        $result_code = $this->m->getResultCode() ;
        if( $temp===true || $result_code==Memcached::RES_NOTFOUND ) {
            return true ;
        } else {
            (new error_())->add( "unable to delete entry with key: {$key} from memcached",
                                 "nI5539j0ANyN",
                                 3,
                                 "backend" ) ;
        }

        return false ;
    }


    function retrieve( $key, $no_md5=false ) {
        $new_key = $key ;
        if( !$no_md5 ) {
            $new_key = md5( $new_key ) ;
        }
        
        $temp = $this->m->get( $new_key ) ;
        $result_code = $this->m->getResultCode() ;
        if( $result_code==Memcached::RES_SUCCESS ) {
            return unserialize( $temp ) ;
        } else if( $result_code==Memcached::RES_NOTFOUND ) {
            ; // ok, but maybe we want to keep track of hit/miss ratio
        } else {
            (new error_())->add( "unable to retrieve memcached key {$key} with result_code {$result_code}",
                                 "pEfVfVT4Iy06",
                                 3,
                                 "backend" ) ;
        }

        return null ;
    }



    function store( $key, $data, $timeout=null, $no_md5=false ) {
        $new_key = $key ;
        if( !$no_md5 ) {
            $new_key = md5( $new_key ) ;
        }

        // a little heavy handed but we use null to represent that nothing was found in the cache so we can't have this be the data, we had that conflict hence this response.
        if( $data===null ) {
            $data = false ;
        }

        $this->m->set( $new_key, serialize($data), $timeout ) ;
        $result_code = $this->m->getResultCode() ;
        if( $result_code!==Memcached::RES_SUCCESS ) {
            (new error_())->add( "unable to store memcached key {$key} with result_code {$result_code}",
                                 "pEfVfVT4Iy06",
                                 3,
                                 "backend" ) ;
            return false ;
        }

        return true ;
    }


    function flush() {
        $this->m->flush() ;
        $result_code = $m->getResultCode() ;
        if( $result_code!==Memcached::RES_SUCCESS ) {
            (new error_())->add( "unable to flush memcached",
                                 "E6v45KtxfeMY",
                                 3,
                                 "backend" ) ;
            return false ;
        }

        return true ;
    }
}


?>