<?php

function sqlite_query( $file, $query, $params=[], $one_result=false ) {
    $db = new PDO( "sqlite:{$file}" ) ;
    if( php_sapi_name()!=="cli" ) {
        $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING ) ;
    }

    $query_statement = $db->prepare( $query ) ;
    $ok = $query_statement->execute( $params ) ;

    if( !$ok && is_cli() ) {
        [$sql_state, $driver_code, $message] = $query_statement->errorInfo() ;
        echo "SQLite error: ($sql_state/$driver_code): $message\nQuery: {$query}\n\n" ;
    }

    $result = $query_statement->fetchAll( PDO::FETCH_ASSOC ) ;
    if( $result===null ) {
        return $result ;
    } else if( $one_result ) {
        if( count($result)==0 ) {
            return null ;
        }
        return $result[0][array_keys($result[0])[0]] ;
    } else {
        return $result ;
    }
}

?>