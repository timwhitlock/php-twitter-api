<?php
/**
* This command line wizard walks through the app authorization via the 
* command line and returns the access keys needed to start interacting with the API.
* 
* To execute, run "php authorize.php" form the command line.
**/
require __DIR__.'/twitter-client.php';

$Client = new TwitterApiClient;
$config = array();

echo "\nThis command line wizard walks through the app authorization via the command line and returns the access keys needed to start interacting with the API.\n\n";

echo "Enter your consumer key: ";
$config['consumer_key'] = trim(fgets(STDIN));

echo "\n\nEnter your consumer secret: ";
$config['consumer_secret'] = trim(fgets(STDIN));

// Require client library and authorize an instance with just application details


$Client->set_oauth ( $config['consumer_key'], $config['consumer_secret']);


// Ask twitter for a request token and specify a callback parameter (for desktop we use "oob" to get a PIN)

try {
    $Token = $Client->get_oauth_request_token('oob');
    $redirect = $Token->get_authorization_url();
}
catch( TwitterApiException $Ex ){
    echo 'Status ', $Ex->getStatus(), '. Error '.$Ex->getCode(), ' - ',$Ex->getMessage(),"\n";
    exit();
}

// Redirect your authenticating user to get a verifier

echo "\n\n",
     "Authorize the token:\n",
     ' > ',$redirect,"\n",
     "\n",
     "Then enter your verifier: ";

$some_verifier = trim(fgets(STDIN));

echo "\n";


$Client->set_oauth ( $config['consumer_key'], $config['consumer_secret'], $Token->key, $Token->secret );


// Ask twitter for a request token and specify a callback parameter (for desktop app we use "oob" to get a pin number)

try { 
    $Token = $Client->get_oauth_access_token( $some_verifier );
}
catch( TwitterApiException $Ex ){
    echo 'Status ', $Ex->getStatus(), '. Error '.$Ex->getCode(), ' - ',$Ex->getMessage(),"\n";
    exit();
}


$Client->set_oauth ( $config['consumer_key'], $config['consumer_secret'], $Token->key, $Token->secret );

$config['access_key'] = $Token->key;
$config['access_secret'] = $Token->secret;

// Now you're ready to make authorized API calls

try {
	
    $path = 'account/verify_credentials';
    $args = array ( 'skip_status' => true );
    $data = $Client->call( $path, $args, 'GET' );
    echo 'Authenticated as @',$data['screen_name'],' #',$data['id_str'],"\n\n";
	
}
catch( TwitterApiException $Ex ){
    echo 'Status ', $Ex->getStatus(), '. Error '.$Ex->getCode(), ' - ',$Ex->getMessage(),"\n";
	exit();
}

echo '<?php
$config = ', var_export($config,1),';
';
