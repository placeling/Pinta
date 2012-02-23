<?php
	include('../../../../wp-config.php');
	include('../OAuthSimple.php');
	//check if logged in
	
	
	$accessToken = get_option('placeling_access_token');
	$secretToken = get_option('placeling_access_secret');
	
	$oauthObject = new OAuthSimple();
	
	$signatures = array( 'consumer_key'     => 'IR3hVvWRYBp1ah3PJUiPirgFzKlMHTeujbORNzAK',
                     'shared_secret'    => 'PqsYkO2smE7gkz9txhzN0bHoPMtDLfp73kIc3RSY');
	
	if ( empty($accessToken) || empty($secretToken) || $accessToken == "" || $secretToken == "" ) {	

	    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	    // Step 1: Get a Request Token
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
	} else {
		$signatures['oauth_token'] = $accessToken;
    	$signatures['oauth_secret'] = $secretToken;
    	
    	$result = $oauthObject->sign(array(
	        'path'      =>'http://localhost:3000/users/me.json',
	        'signatures'=> $signatures));

		$ch = curl_init();	        
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);	    
	    $r = curl_exec($ch);
	    $info = curl_getinfo($ch);
	    curl_close($ch);
	
		if ( $info['http_code'] == 401 ){
			delete_option('placeling_access_token');
			delete_option('placeling_access_secret');
			header("Location:index.php");	
		}
		
	    // We parse the string for the request token and the matching token
	    // secret. Again, I'm not handling any errors and just plough ahead 
	    $user = json_decode( $r );
		
	    $lat = $user->location[0];
	    $lng = $user->location[1];
	    
	    $recent_perspectives = $user->perspectives;
	    $recent_places = array();
	    
	    foreach ( $recent_perspectives as $perspective){
	    	$recent_places[] = $perspective->place;
	    }   
	}
	

?>
<html>
    <head>
		<script src="../js/OAuthSimple.js" ></script>
        <script>

        // To get results in all their magnificent glory (rather than some error)
        // fill these out with your own magical Netflix API keys.
        // Don't have a magical Netflix API key yet?
        // http://developer.netflix.com
        //
            // Some easy defines
            var places_json = '<?php echo json_encode( $recent_places ); ?>';
            var apiKey = "<?php echo $signatures['consumer_key']; ?>";
            var sharedSecret = "<?php echo $signatures['shared_secret']; ?>";
            var accessToken =  "<?php echo $signatures['oauth_token']; ?>";
            var tokenSecret =  "<?php echo $signatures['oauth_secret']; ?>";
            var path="http://localhost:3000/users/me.json";
            
         </script>
		<link rel='stylesheet' id='colors-css'  href='../css/style.css' type='text/css' media='all' />
		<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?libraries=places&sensor=false"></script>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		
		<script type="text/javascript">
			
			def drawPreview( place ){
				
				
				var lat = place->location[0];
				var lng = place->location[1];
				var url = "http://maps.google.com/maps/api/staticmap?center=" + lat + "," + lng + "&zoom=15&size=40x40&&markers=color:red%%7C"+lat+"," +lng+"&sensor=false";
				$("#static_map").attr("src", url);
			
			}
			
			
			var $recent_places;
			$(document).ready(function(){
				var defaultBounds = new google.maps.LatLngBounds(
				new google.maps.LatLng(<?php echo $lat;?>, <?php echo $lng;?>),
				new google.maps.LatLng(<?php echo $lat;?>, <?php echo $lng;?>));
				
				var input = document.getElementById('searchTextField');
				var options = {
				  bounds: defaultBounds,
				  types: ['establishment']
				};
				
				var recent_places = JSON.parse(places_json);
				for(var i=0; i<recent_places.length; i++) {
					var place = recent_places[i];
					$("ul#recent_places").append('<li>'+ place.name +'</li>');
				}

				
				autocomplete = new google.maps.places.Autocomplete(input, options);
			
				google.maps.event.addListener(autocomplete, 'place_changed', function() {
				  var place = autocomplete.getPlace();
				  if (place.geometry.viewport) {
				    map.fitBounds(place.geometry.viewport);
				  } else {
				    map.setCenter(place.geometry.location);
				    map.setZoom(17);
				  }
				  var image = new google.maps.MarkerImage(
				      place.icon, new google.maps.Size(71, 71),
				      new google.maps.Point(0, 0), new google.maps.Point(17, 34),
				      new google.maps.Size(35, 35));
				  marker.setIcon(image);
				  marker.setPosition(place.geometry.location);
				  
				  infowindow.setContent(place.name);
				  infowindow.open(map, marker);
				});
			
			});

		</script>
    </head>
    <body>
    
    <div class="wrap mapEnabled" style="width:660px;height:500px; background-color:yellow;padding: 10 10 10 10;">
        <h2>Attach Place to post</h2>
        <div class="place_pick" style="display:block;width:600px">
        		<div class="search_top">
        			<input id="searchTextField" type="text" class="search_box ui-autocomplete-input" style="width:100%;">
        		</div>
        		<div class="search_results">
					<ul id="recent_places">
        			</ul>   
        		</div>
        	</div>
        	     
        </div>
        
        <div id="display">
        	<img id="static_map"/>
        
        </div>
        
        <div id="actions">
        	<input class="button-primary" type="submit" name="Save" value="Save" id="submitbutton" style="float:right;" />   
        </div>
        
    </div>

</body>
</html>