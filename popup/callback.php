<?php
	include ('../OAuthSimple.php');
	//require 'oauth.php';
	//check if logged in
	
	$oauthObject = new OAuthSimple();
	$signatures = array( 'consumer_key'     => 'IR3hVvWRYBp1ah3PJUiPirgFzKlMHTeujbORNzAK',
                     'shared_secret'    => 'PqsYkO2smE7gkz9txhzN0bHoPMtDLfp73kIc3RSY');
                     
	    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    // Step 3: Exchange the Authorized Request Token for a Long-Term
    //         Access Token.
    //
    // We just returned from the user authorization process on Google's site.
    // The token returned is the same request token we got in step 1.  To 
    // sign this exchange request, we also need the request token secret that
    // we baked into a cookie earlier. 
    //

    // Fetch the cookie and amend our signature array with the request
    // token and secret.
    $signatures['oauth_secret'] = $_COOKIE['oauth_token_secret'];
    $signatures['oauth_token'] = $_GET['oauth_token'];
    
    
    
    echo "<br><br>\n\n";
    var_dump($signatures);
    
    // Build the request-URL...
    $result = $oauthObject->sign(array(
        'path'      => 'http://localhost:3000/oauth/access_token',
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
    
    echo "STAGE 3 RETURN DUMP:"; 
    var_dump($r);
      
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
    /* $result = $oauthObject->sign(array(
       'path'      =>'http://www.google.com/calendar/feeds/default/allcalendars/full',
        'parameters'=> array('orderby' => 'starttime'),
        'signatures'=> $signatures));
	*/
    // Instead of going to the list, I will just print the link along with the 
    // access token and secret, so we can play with it in the sandbox:
    // http://googlecodesamples.com/oauth_playground/
    //
    //curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
    $output = "<p>Access Token: $access_token<BR>
                  Token Secret: $access_token_secret</p>
               <p><a href='$result[signed_url]'>List of Calendars</a></p>";
    curl_close($ch);
    echo $output;

	/*
	$accessToken = get_option("placeling_access_token");
	$secretToken = get_option("placeling_access_secret");
	

	update_option("placeling_access_token", $accessToken);
	update_option("placeling_access_secret", $secretToken);
	
	header( 'Location: /popup/index.php' ) ;	*/

?>
GOT OAUTH