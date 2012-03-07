<?php
	include('../../../../wp-config.php');
	include_once ('../OAuthSimple.php');
	include_once('../pinta-config.php');
	
	//require 'oauth.php';
	//check if logged in
	$current_user = wp_get_current_user();
	
	$oauthObject = new OAuthSimple();
	$signatures = array( 'consumer_key'     => 'IR3hVvWRYBp1ah3PJUiPirgFzKlMHTeujbORNzAK',
                     'shared_secret'    => 'PqsYkO2smE7gkz9txhzN0bHoPMtDLfp73kIc3RSY');
    
    if ( !isset( $_COOKIE['oauth_token_secret'] )){
    	//cookie we set there maxs out at an hour, should check
    	header("Location:index.php");
    	exit;
    }
    $signatures['oauth_secret'] = $_COOKIE['oauth_token_secret'];
    $signatures['oauth_token'] = $_GET['oauth_token'];
    
    // Build the request-URL...
    $result = $oauthObject->sign(array(
        'path'      => $SERVICE_HOSTNAME.'/oauth/access_token',
        'parameters'=> array(
            'oauth_verifier' => $_GET['oauth_verifier'],
            'oauth_token'    => $_GET['oauth_token']),
        'signatures'=> $signatures));

    // ... and grab the resulting string again. 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
    $r = curl_exec($ch);

    // Voila, we've got a long-term access token.
    parse_str($r, $returned_items);     
      
    $access_token = $returned_items['oauth_token'];
    $access_token_secret = $returned_items['oauth_token_secret'];
    
    // We can use this long-term access token to request Google API data,
    // for example, a list of calendars. 
    // All Google API data requests will have to be signed just as before,
    // but we can now bypass the authorization process and use the long-term
    // access token you hopefully stored somewhere permanently.
    $signatures['oauth_token'] = $access_token;
    $signatures['oauth_secret'] = $access_token_secret;
    //////////////////////////////////////////////////////////////////////
    
    // Example Google API Access:
    // This will build a link to an RSS feed of the users calendars.
    $oauthObject->reset();
	echo $current_user->ID."\n<br>";
	echo $access_token;
	update_user_meta( $current_user->ID, 'placeling_access_token', $access_token);
	update_user_meta( $current_user->ID, 'placeling_access_secret', $access_token_secret);
	
	header( 'Location:index.php' ) ;	
?>