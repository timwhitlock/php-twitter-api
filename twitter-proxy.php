<?php
/**
 * Proxy a Twitter API call as authenticated user.
 * @param string e.g. account/verify_credentials
 * @param int Expiry in seconds
 * @return void
 */
function proxy_user_request( $path, $ttl = 60 ){
    
    // Authenticate
    $Client = new TwitterApiClient;
    $Client->set_oauth( TW_CONSUMER_KEY, TW_CONSUMER_SEC, TW_ACCESS_KEY, TW_ACCESS_SEC );

    // Twitter API params supported in GET and POST only
    $method = strtoupper( $_SERVER['REQUEST_METHOD'] );
    if( 'POST' === $method ){
        $args = $_POST;
    }
    else {
        $args = $_GET;
    }
    
    // JSONP callback may be in request
    if( ! empty($_REQUEST['callback']) ){
        $callback = $_REQUEST['callback'];
        unset( $args['callback'] );
    }
    
    // execute raw api call with no caching
    $http = $Client->raw( $path, $args, $method );
    extract( $http );

    if( $body && '{' === $body{0} ){
        if( isset($callback) ){
            $type = 'text/javascript; charset=utf-8';
            $body = $callback.'('.$body.');';
        }
        else {
            $type = 'application/json; charset=utf-8';
        }
    }
    
    if( 200 === $status ){
        if( $ttl ){
            $exp = gmdate('D, d M Y H:i:s', $ttl + time() ).' GMT';
            header('Pragma: ', true );
            header('Cache-Control: private, max-age='.$ttl, true );
            header('Expires: '.$exp, true );
        }
    }
    else {
        header('HTTP/1.0 '.$status.' '._twitter_api_http_status_text($status), true, $status );
    }
    
    
    header('Content-Type: '.$type, true );
    header('Content-Length: '.strlen($body), true );

    echo $body;
    exit(0);
    
}
