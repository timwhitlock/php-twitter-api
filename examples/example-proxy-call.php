<?php
/**
 * Example proxy call.
 * append &callback=<function> for JSONP response
 */
 
require '../twitter-client.php';
require '../twitter-proxy.php';

// Twitter application key and secret
// See: https://dev.twitter.com/apps 
define('TW_CONSUMER_KEY', 'your app key');
define('TW_CONSUMER_SEC', 'your app secret');

// Authenticated user access token, obtained from your own user flow
// See: https://dev.twitter.com/docs/auth/obtaining-access-tokens
define('TW_ACCESS_KEY', 'your access key');
define('TW_ACCESS_SEC', 'your access secret');


proxy_user_request( 'account/verify_credentials' );
