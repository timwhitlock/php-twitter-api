<?php
/**
 * Simple example shows API call with a pre-authenticated client.
 */


//  Configure your OAuth settings from your application at https://dev.twitter.com/apps

define('YOUR_CONSUMER_KEY', 'your app key here');
define('YOUR_CONSUMER_SECRET', 'your app secret here');


// Configure authentication credentials.
// you can generate your own access key from the link above

define('SOME_ACCESS_KEY', 'some access key here');
define('SOME_ACCESS_SECRET', 'some access secret key'); 
  
 
// Require client library and authorize an instance with your creds

require __DIR__.'/../twitter-client.php';
$Client = new TwitterApiClient;
$Client->set_oauth ( YOUR_CONSUMER_KEY, YOUR_CONSUMER_SECRET, SOME_ACCESS_KEY, SOME_ACCESS_SECRET );


// Now you're ready to make authorized API calls

try {
    $path = 'account/verify_credentials';
    $args = array ( 'skip_status' => true );
    $data = $Client->call( $path, $args, 'GET' );
    echo 'Authenticated as @',$data['screen_name'],' #',$data['id_str'],"\n";
}
catch( TwitterApiException $Ex ){
    echo 'Status ', $Ex->getStatus(), '. Error '.$Ex->getCode(), ' - ',$Ex->getMessage(),"\n";
}

