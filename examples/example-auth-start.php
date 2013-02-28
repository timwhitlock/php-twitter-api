<?php
/**
 * Example of starting OAuth flow by fetch a request token.
 */


//  Configure your OAuth settings from your application at https://dev.twitter.com/apps

define('YOUR_CONSUMER_KEY', 'your app key here');
define('YOUR_CONSUMER_SECRET', 'your app secret here');


// Require client library and authorize an instance with just application details

require __DIR__.'/../twitter-client.php';
$Client = new TwitterApiClient;
$Client->set_oauth ( YOUR_CONSUMER_KEY, YOUR_CONSUMER_SECRET );


// Ask twitter for a request token and specify a callback parameter (for desktop we use "oob" to get a PIN)

try {
    $Token = $Client->get_oauth_request_token('oob');
    $redirect = $Token->get_authorization_url();
}
catch( TwitterApiException $Ex ){
    echo 'Status ', $Ex->getStatus(), '. Error '.$Ex->getCode(), ' - ',$Ex->getMessage(),"\n";
    exit();
}


// Do storage of values and redirect your authenticating user

echo "\n",
     "Store these values somewhere, you're going to need them in a bit:\n",
     ' > request key:    ',$Token->key, "\n",
     ' > request secret: ',$Token->secret, "\n",
     "\n",
     "Then send your user here to authorize the token:\n",
     ' > ',$redirect,"\n",
     "\n",
     "Then run example-auth-complete.php\n",
     "\n";