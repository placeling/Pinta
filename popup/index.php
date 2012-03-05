<?php
	include('../../../../wp-config.php');
	include('../OAuthSimple.php');
	//check if logged in
	
	if (!current_user_can('edit_pages') && !current_user_can('edit_posts'))
    	wp_die(__("You are not allowed to be here"));
    	
	$current_user = wp_get_current_user();
    
	$accessToken = get_user_meta($current_user->ID, 'placeling_access_token', true);
	$secretToken = get_user_meta($current_user->ID, 'placeling_access_secret', true);
	$hostname = "http://localhost:3000";
	
	$oauthObject = new OAuthSimple();
	
	$signatures = array( 'consumer_key'     => 'IR3hVvWRYBp1ah3PJUiPirgFzKlMHTeujbORNzAK',
                     'shared_secret'    => 'PqsYkO2smE7gkz9txhzN0bHoPMtDLfp73kIc3RSY');
	
	if ( empty($accessToken) || empty($secretToken) || $accessToken == "" || $secretToken == "" ) {	

	    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	    // Step 1: Get a Request Token
	    //
	    $result = $oauthObject->sign(array(
	        'path'      =>$hostname . '/oauth/request_token',
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
	        'path'      => $hostname . '/oauth/authorize',
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
			'path'      => $hostname.'/users/me.json',
			'signatures'=> $signatures));

		$ch = curl_init();	        
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $result['signed_url']);	    
		$r = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
	
		if ( $info['http_code'] == 401 ){
			delete_user_meta($current_user->ID, 'placeling_access_token');
			delete_user_meta($current_user->ID, 'placeling_access_secret');
			//die("no good access_key");	
			header("Location:index.php");
		} else if ( $info['http_code'] != 200 ){
			die("can't connect to Placeling server");
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
	
		if ( array_key_exists( 'post_id', $_GET ) ){
			$meta_values = get_post_meta( $_GET['post_id'], '_placeling_place_json', true );
		}   
	}
	

?>
<html>
	<head>
		<link rel='stylesheet' id='colors-css'  href='../css/style.css' type='text/css' media='all' />
		<link rel='stylesheet' href='../css/footer.css' type='text/css' />
		<link rel='stylesheet' href='../css/popup.css' type='text/css' />
		<link rel="stylesheet" href="../css/jquery-ui-1.8.18.custom.css" type="text/css"/>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
		
		<style type="text/css">
			.ui-progressbar-value { background-image: url(../css/images/pbar-ani.gif); }
		</style>
		<script type="text/javascript">
			
			var places_json = '<?php echo addslashes( json_encode( $recent_places ) ); ?>';
			var lat = "<?php echo $lat;?>";
			var lng = "<?php echo $lng;?>";
			var hostname = "<?php echo $hostname;?>";
			var path= hostname +"/users/me.json";
			var places_dictionary;
			var autocomplete;			
			
			function showRecentlyBookmarked(){
			
				$("ul#recent_places li").remove();
				for (var i=0; i < places_dictionary.length; i++){
					var place = places_dictionary[i];
					$("ul#recent_places").append('<li class="place_option"><a data-id=' + place.google_id + ' href="#">' + place.name + ", " + place.city_data + '</a></li>');			
				}
				
				$('#searchTextField').focus();
			}
			
			
			function drawPreview( place ){
				$("#selected_place_json").val( JSON.stringify( place ) );
				
				if ( place ){
					$("#placeling_footer").show();
					$("#placeling_map_image").attr( "src", place.map_url );
					$("#placeling_place_name").html( place.name );
				} else {
					$("#placeling_footer").hide();
				}
			}
			
			var $recent_places;
			$(document).ready(function(){
				$("#placeling_footer").hide();
				places_dictionary = JSON.parse(places_json);
				
				showRecentlyBookmarked();
				
				$( "#searchTextField" ).keyup(function() {
					var text = $( "#searchTextField" ).val();
					
					if ( text.length >= 1 ){
						$.ajax({
							url: "autocomplete.php",
							dataType: "json",
							data: {
								input: text,
								location: lat + "," + lng,
								types: "establishment"
							},
							success: function( data ) {
								$("ul#recent_places li").remove();
								$.each(data.predictions, function(i, item){
								$("ul#recent_places").append('<li class="place_option"><a href="#" data-id="'+ item.id + '" data-ref="'+ item.reference + '" >'+ item.description + '</a></li>');  
						        });													            
							}
						});
					} else {					
						showRecentlyBookmarked();
					}
				});	
				
				
				$("li.place_option a").live('click', function(){
					var place_id = $(this).attr('data-id');
					var data_ref = $(this).attr('data-ref');
					$( "#spinwait" ).show();
					$( "#placeling_footer" ).hide();	
					$.ajax({
						url: hostname + "/places/" + place_id + ".json",
						dataType: "jsonp",
						data: {
							id: place_id,
							google_ref:data_ref, 
							key: "<?php echo $signatures['consumer_key']; ?>"
						},
						success: function( data ) {	
							$( "#spinwait" ).hide();					
							drawPreview( data );															            
						}
					});
					return false;
				});
			    
			
				$('#submitbutton').click( function(){
					var win = window.dialogArguments || opener || parent || top;
				win.attach_placeling_place( $("#selected_place_json").val() );
			
				parent.tb_remove();
				return;				
				} );
				
				place_json = $("#selected_place_json").val();
				
				var win = window.dialogArguments || opener || parent || top;
				$("#selected_place_json").val( win.get_placeling_json( ) );
				place_json =  unescape( $("#selected_place_json").val() );
				
				if ( place_json != "" ){
					place = JSON.parse(place_json);
					drawPreview( place );
				} else {
					$("#placeling_footer").hide();
				}
				
			});

		</script>
    </head>
    <body>
    
    <div id='placeling_popup_main' class="wrap mapEnabled">
        <h2>Attach Place to post</h2>
        <div class="place_pick" style="display:block;width:600px">
        		<div class="search_top">
        			<input id="searchTextField" type="text" class="search_box ui-autocomplete-input" style="width:100%;">
        		</div>
        		<div id="search_results">
				<ul id="recent_places">
        			</ul>   
        		</div>
        	</div>
        
        	<div id="spinwait"><img height='91px' src="../img/spinner.gif"/></div>
        	<?php
        		if ( isset( $meta_values ) ){
        	?>
        		<input type="hidden" id="selected_place_json" name="selected_place_json" value="<?php echo $meta_values; ?>"/>
        	<?php 
        		} else{
        	?>
        		<input type="hidden" id="selected_place_json" name="selected_place_json"/>
        	<?php 
        		}
        	
        		include("../footer.php");
		  		echo footerHtml( null, '../img/addPlace.png' );
		  	?>
	        
	        <div id="actions">
	        	<input class="button-primary" type="submit" name="Save" value="Save" id="submitbutton" style="float:right;" />   
	        </div>
        </div>
        
    </div>

</body>
</html>