<?php
/**
 * Example of completing OAuth flow by resolving access token.
 */


//  Configure your OAuth settings from your application at https://dev.twitter.com/apps

define('YOUR_CONSUMER_KEY', 'your app key here');
define('YOUR_CONSUMER_SECRET', 'your app secret here');


// Configure authentication credentials from request token you got in step 1

define('THAT_REQUEST_KEY', 'the request key you stored in step 1');
define('THAT_REQUEST_SECRET', 'the requst secret you stored in step 1'); 

// Finally, you'll need the verifier which was either in redirect URL from Twitter or an on-screen code

define('SOME_VERIFIER', 'code obtained via user action');


// Require client library and request an access token with all the stuff above

require __DIR__.'/../twitter-client.php';
$Client = new TwitterApiClient;
$Client->set_oauth ( YOUR_CONSUMER_KEY, YOUR_CONSUMER_SECRET, THAT_REQUEST_KEY, THAT_REQUEST_SECRET );


// Ask twitter for a request token and specify a callback parameter (for dsktop app we use "oob" to get a pin number)

try { 
    $Token = $Client->get_oauth_access_token( SOME_VERIFIER );
}
catch( TwitterApiException $Ex ){
    echo 'Status ', $Ex->getStatus(), '. Error '.$Ex->getCode(), ' - ',$Ex->getMessage(),"\n";
    exit();
}


// Do storage of values and that's it

echo "\n",
     "Store these values somewhere, securely of course:\n",
     ' > access key:    ',$Token->key, "\n",
     ' > access secret: ',$Token->secret, "\n",
     "\n";
