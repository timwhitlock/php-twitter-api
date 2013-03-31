<?php
/**
 * Proxy a Twitter API call as authenticated user.
 * @param string e.g. account/verify_credentials
 * @param int Expiry in seconds
 * @return void
 */
function proxy_user_request( $path, $ttl = 60 ){

    try {
        
        // default content type in case of failure
        $type = 'application/json; charset=utf-8';

        // Authenticate Twitter client from creds in config.php
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
        
        // Lock screen_name and user_id params if specified in config
        // This is a security measure to stop other people using your proxy
        //
        if( TW_LOCK_USER_NAME && isset($args['screen_name']) && strcasecmp(TW_LOCK_USER_NAME, $args['screen_name']) ){
            throw new Exception( 'Proxy locked to screen_name '.TW_LOCK_USER_NAME, 403 );
        }
        if( TW_LOCK_USER_ID && isset($args['user_id']) && TW_LOCK_USER_ID !== $args['user_id'] ){
            throw new Exception( 'Proxy locked to user_id '.TW_LOCK_USER_ID, 403 );
        }
        
        // JSONP callback may be in request
        if( ! empty($_REQUEST['callback']) ){
            $callback = $_REQUEST['callback'];
            unset( $args['callback'] );
        }
        
        // execute raw api call with no caching
        $http = $Client->raw( $path, $args, $method );
        extract( $http );
        $type = $headers['content-type'];

    }
    catch( Exception $Ex ){
        $status = $Ex->getCode();
        $error  = array( 'message' => $Ex->getMessage() );
        $body   = json_encode( array('errors' => array($error) ) );
    }

    
    // wrap JSONP callback function as long as response is JSON
    if( isset($callback) && 0 === strpos( $type, 'application/json' ) ){
        $type = 'text/javascript; charset=utf-8';
        $body = $callback.'('.$body.');';
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
