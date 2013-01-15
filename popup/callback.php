<?php
	include('../../../../wp-config.php');
	include_once ('../OAuthSimple.php');
	include_once('../pinta-config.php');
	
	//require 'oauth.php';
	//check if logged in
	$current_user = wp_get_current_user();
	
	$oauthObject = new OAuthSimple();

	$requestTokenSecret = get_user_meta($current_user->ID, '_oauth_token_secret', true);
	$requestTokenSecretTimeout = get_user_meta($current_user->ID, '_oauth_token_secret_timeout', true);
	$requestTokenDest = get_user_meta($current_user->ID, '_oauth_token_dest', true);

	$oauthObject = new OAuthSimple();

	if ( empty($requestTokenSecret) || empty($requestTokenSecretTimeout) || $requestTokenSecret == "" || $requestTokenSecretTimeout < time() ) {
	    //token we set there maxs out at an hour, should check
	    header("Location:index.php");
	    exit;
	}
	$PLACELING_SIGNATURES['oauth_secret'] = $requestTokenSecret;
	$PLACELING_SIGNATURES['oauth_token'] = $_GET['oauth_token'];
	
	// Build the request-URL...
	$result = $oauthObject->sign(array(
	    'path'      => $PLACELING_SERVICE_HOSTNAME.'/oauth/access_token_new',
	    'parameters'=> array(
		'oauth_verifier' => $_GET['oauth_verifier'],
		'oauth_token'    => $_GET['oauth_token']),
	    'signatures'=> $PLACELING_SIGNATURES));
    
	// ... and grab the resulting string again. 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
	$r = curl_exec($ch);
    
	// Voila, we've got a long-term access token.

	$returned_items = json_decode( $r );

	$access_token = $returned_items->token->token;
	$access_token_secret = $returned_items->token->secret;
	$username = $returned_items->user->username;

	// We can use this long-term access token to request Google API data,
	// for example, a list of calendars. 
	// All Google API data requests will have to be signed just as before,
	// but we can now bypass the authorization process and use the long-term
	// access token you hopefully stored somewhere permanently.
	$PLACELING_SIGNATURES['oauth_token'] = $access_token;
	$PLACELING_SIGNATURES['oauth_secret'] = $access_token_secret;
	//////////////////////////////////////////////////////////////////////
	
	// Example Google API Access:
	// This will build a link to an RSS feed of the users calendars.
	$oauthObject->reset();
	update_site_option( '_placeling_access_token', $access_token);
	update_site_option( '_placeling_access_secret', $access_token_secret);
	update_site_option( '_placeling_username', $username);

	if ( isset( $requestTokenDest ) && $requestTokenDest == "admin" ){
	    $placeling_url = admin_url("options-general.php?page=placeling_options");
        header( "Location:$placeling_url" ) ;
	} else {
	    header( 'Location:index.php' ) ;
	}
?>