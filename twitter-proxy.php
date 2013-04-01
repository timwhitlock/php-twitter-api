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
        $type = TW_CONTENT_TYPE;

        // Authenticate Twitter client from creds in config.php
        $Client = new TwitterApiClient;
        $Client->set_oauth( TW_CONSUMER_KEY, TW_CONSUMER_SEC, TW_ACCESS_KEY, TW_ACCESS_SEC );
    
        // Twitter API params supported in GET and POST only
        $method = strtoupper( $_SERVER['REQUEST_METHOD'] );
        if( false === strpos(TW_ALLOW_METHODS,$method) ){
            proxy_die( 405, 'Proxy forbids '.$method );
        }
        if( 'POST' === $method ){
            $args = $_POST;
            $cache = false;
        }
        else {
            $args = $_GET;
            $cache = TW_CACHE_ENGINE;
        }
        
        // Twitter doesn't complain about unecessary parameters, but removing junk and "cache-busters" will improve caching
        // @todo proper array intersect of all suppoerted Twitter API args across methods
        unset( $args['_'] );
        
        // We want to ensure that the cache is hit for requests even when the JSONP callback is different
        unset( $args['callback'] );
                
        // Fetch from cache if engine specified. Currently only APC supported
        // @todo use a faster method than md5 for key hash?
        if( $cache ){
            ksort( $args );
            $key  = TW_CACHE_PREFIX.'_'.md5( serialize($args) );
            $data = apc_fetch($key) or $data = null;
        }

        if( isset($data) ){
            header('X-Cache: Flamingo HIT' );
            // reduce TTL to life of cached data
            $age = time() - $data['t'];
            $ttl-= $age;
        }
        else {
            header('X-Cache: Flamingo MISS' );
            $data = $Client->raw( $path, $args, $method );

            // extend TTL if rate limit has been reached for this request
            if( $ttl ){
                $meta = $Client->last_rate_limit_data();
                if( $meta['limit'] && ! $meta['remaining'] ){
                    $ttl = max( $ttl, $meta['reset'] - time() );
                }

                // Cache response
                if( $cache ){
                    $data['t'] = time();
                    apc_store( $key, $data, $ttl );
                }
            }
        }
        
        // success, respond to client.
        proxy_exit( $data['body'], $data['headers']['content-type'], $data['status'], $ttl );

    }
    catch( Exception $Ex ){
        proxy_die( 500, $Ex->getMessage() );
    }
    
}



/**
 * Respond with proxied data and exit
 * @internal
 */
function proxy_exit( $body, $type, $status = 200, $ttl = 0 ){
    
    // currently only supporting json
    // @todo support XML formats
    $isJSON = 0 === strpos( $type, 'application/json' );
    
    // wrap JSONP callback function as long as response is JSON
    if( ! empty($_REQUEST['callback']) && $isJSON ){
        $type = 'text/javascript; charset=utf-8';
        $body = $_REQUEST['callback'].'('.$body.');';
    }
    
    // handle HTTP status and expiry header
    if( 200 === $status ){
        if( $ttl ){
            $exp = gmdate('D, d M Y H:i:s', $ttl + time() ).' GMT';
            header('Pragma: cache', true );
            header('Cache-Control: public, max-age='.$ttl, true );
            header('Expires: '.$exp, true );
        }
    }
    else {
        header('HTTP/1.1 '.$status.' '._twitter_api_http_status_text($status), true, $status );
    }
    
    header('Content-Type: '.$type, true );
    header('Content-Length: '.strlen($body), true );
    echo $body;
    exit(0);    
}




/**
 * Fatal exit for proxy in similar format to Twitter API
 * @internal
 */
function proxy_die( $status, $message = '' ){
    if( ! $message ){
        $message = _twitter_api_http_status_text( $status );
    }
    $errors[]= array (
        'code'    => -1, 
        'message' => $message,
    );
    $body = json_encode( compact('errors') );
    proxy_exit( $body, TW_CONTENT_TYPE, $status );
}




/**
 * Check user_id and screen_name params for security purposes
 */
function proxy_user_restrict( array $args ){
    if( TW_LOCK_USER_NAME && isset($args['screen_name']) && strcasecmp(TW_LOCK_USER_NAME, $args['screen_name']) ){
        proxy_die( 403, 'Proxy locked to screen_name '.TW_LOCK_USER_NAME );
    }
    if( TW_LOCK_USER_ID && isset($args['user_id']) && TW_LOCK_USER_ID !== $args['user_id'] ){
        proxy_die( 403, 'Proxy locked to user_id '.TW_LOCK_USER_ID );
    }
    return true;
}




