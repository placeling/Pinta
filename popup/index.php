<?php
	include('../../../../wp-config.php');
	include('../OAuthSimple.php');
	//check if logged in
	
	$accessToken = get_option("placeling_access_token");
	$secretToken = get_option("placeling_access_secret");
	
	$oauthObject = new OAuthSimple();
	
	$signatures = array( 'consumer_key'     => 'IR3hVvWRYBp1ah3PJUiPirgFzKlMHTeujbORNzAK',
                     'shared_secret'    => 'PqsYkO2smE7gkz9txhzN0bHoPMtDLfp73kIc3RSY');
	
	if ( empty($accessToken) || empty($secretToken) ) {	
	
		if (!isset($_GET['oauth_verifier'])) {
		    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		    // Step 1: Get a Request Token
		    //
		    // Get a temporary request token to facilitate the user authorization 
		    // in step 2. We make a request to the OAuthGetRequestToken endpoint,
		    // submitting the scope of the access we need (in this case, all the 
		    // user's calendars) and also tell Google where to go once the token
		    // authorization on their side is finished.
		    //
		    $result = $oauthObject->sign(array(
		        'path'      =>'http://localhost:3000/oauth/request_token',
		        'parameters'=> array(
		            'oauth_callback'=> 'http://localhost/~imack/wp-content/plugins/Pinta/popup/callback.php'),
		        'signatures'=> $signatures));
		
		    // The above object generates a simple URL that includes a signature, the 
		    // needed parameters, and the web page that will handle our request.  I now
		    // "load" that web page into a string variable.
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
		    $r = curl_exec($ch);
		    curl_close($ch);
		
		    // We parse the string for the request token and the matching token
		    // secret. Again, I'm not handling any errors and just plough ahead 
		    // assuming everything is hunky dory.
		    parse_str($r, $returned_items);
		    $request_token = $returned_items['oauth_token'];
		    $request_token_secret = $returned_items['oauth_token_secret'];
		
		    // We will need the request token and secret after the authorization.
		    // Google will forward the request token, but not the secret.
		    // Set a cookie, so the secret will be available once we return to this page.
		    setcookie("oauth_token_secret", $request_token_secret, time()+3600);
		    //
		    //////////////////////////////////////////////////////////////////////
		    
		    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		    // Step 2: Authorize the Request Token
		    //
		    // Generate a URL for an authorization request, then redirect to that URL
		    // so the user can authorize our access request.  The user could also deny
		    // the request, so don't forget to add something to handle that case.
		    $result = $oauthObject->sign(array(
		        'path'      =>'http://localhost:3000/oauth/authorize',
		        'parameters'=> array(
		            'oauth_token' => $request_token),
		        'signatures'=> $signatures));
		
		    // See you in a sec in step 3.
		    header("Location:$result[signed_url]");
		    exit;
		    //////////////////////////////////////////////////////////////////////
		}		
		
		//header("Location: http://localhost:3000/oauth/request_token"); 	
	} else {
		echo "TEST";
	}
	
	
	//if (!current_user_can('edit_pages') && !current_user_can('edit_posts')){
    	//wp_die(__("You are not allowed to be here"));
    	//echo "BLAHBLAH";
    //}

?>
<html>
    <head>

    </head>
    <body>
    <style>

    </style>
    
    <div id="wrapper" class="mapEnabled">

        <div id="headerStep1">
            <h1>Search for the place or address</h1>
            <div id="searchBox"></div>
        </div>
        <div id="headerStep2" class="hidden">
            <h1>Customize your map</h1>
            <a id="changePlace">Change Place / Address</a>
        </div>
        
        <input class="insert_place" id="insertPlace" type="button" Value="Insert">
        <div id="placeContainer">
            
            
            <div id="placeList" class="hidden"></div>
            <div id="map"></div>
            <div id="placeWidgetContainer" class="hidden">
                <div id="placeWidget"></div>
                
            <div class="settings">

                <div id="layoutTab" class="tab">
                    <h5>Layout</h5>
                    <div class="contentLeft">
                        <ul id="layoutOptions">
                            <li id="layoutCompact" rel="nokia.blue.compact"></li>
                            <li id="layoutMap" rel="nokia.blue.map"></li>
                            <li id="layoutBasic" class="active" rel="nokia.blue.place"></li>
                            <li id="layoutAdvanced" rel="nokia.blue.extended"></li>
<!--
                            <li id="layoutFull" rel="nokia.blue.full"></li>
-->
                        </ul>
                        
                    </div>
                    <div class="contentRight">
<!--
                        <input type="radio" name="theme" checked="1" value="dark"> Dark 
                        <input type="radio" name="theme" value="bright"> Bright 
-->
                    </div>
                </div>
                <div class="tab">
                    <h5>Display</h5>
                    <div class="contentLeft checkboxContainer">
                        <div rel="actions"><input type="checkbox" name="elements" value="actions"> Actions</div>
                        <div rel="contact"><input type="checkbox" name="elements" value="contact"> Contact info</div>
                        <div rel="description"><input type="checkbox" name="elements" value="description"> Description</div>
                        <div rel="reviews"><input type="checkbox" name="elements" value="reviews"> Reviews</div>
                        <div rel="thumbnail"><input type="checkbox" name="elements" value="thumbnail"> Photo</div>
                        <div rel="thumbnailList"><input type="checkbox" name="elements" value="thumbnailList"> Thumbnail list</div>
                        <div rel="controls"><input type="checkbox" name="elements" value="controls"> Map controls</div>
                    </div>
                    <div class="contentRight">
<!--
                        <input type="radio" name="theme" value="map"> Map 
                        <input type="radio" name="theme" value="image"> Image
-->
                    </div>
                </div>
                <div class="tab">
                    <h5>Size</h5>
                    <div class="contentLeft  fixedSizes" id="fixedSizes">
                    </div>
                    <div class="contentRight sizes">
                        <input id="customSizeWidth" type="text" name="width" value="width" class="labelText"> x <input id="customSizeHeight" type="text" name="height" value="height" class="labelText"> pixels 
                    </div>
                </div>

            </div>
                
            </div>
            
            <a type="button" class="button" id="cancelAction">Cancel</a>
            <a type="button" class="button-primary" id="insertAction">Finish</a>
            
        </div>
    </div>

</body>
</html>