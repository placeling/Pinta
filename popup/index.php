<?php
	include_once('../../../../wp-config.php');
	include_once('../OAuthSimple.php');
	include_once('../pinta-config.php');
	//check if logged in
	
	if (!current_user_can('edit_pages') && !current_user_can('edit_posts'))
    	wp_die(__("You are not allowed to be here"));
    	
	$current_user = wp_get_current_user();
    
	$accessToken = get_site_option('_placeling_access_token', false, true);
	$secretToken = get_site_option('_placeling_access_secret', false, true);
	
	$oauthObject = new OAuthSimple();
	
	if ( empty($accessToken) || empty($secretToken) || $accessToken == "" || $secretToken == "" ) {	

	    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	    // Step 1: Get a Request Token
	    //
	    $callback_url = plugins_url( 'popup/callback.php' , dirname(__FILE__) );
	    $result = $oauthObject->sign(array(
	        'path'      =>$PLACELING_SERVICE_HOSTNAME . '/oauth/request_token',
	        'parameters'=> array(
	            'oauth_callback'=> $callback_url),
	        'signatures'=> $PLACELING_SIGNATURES));
	
	    // The above object generates a simple URL that includes a signature, the 
	    // needed parameters, and the web page that will handle our request.  I now
	    // "load" that web page into a string variable.
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
	    $r = curl_exec($ch);
	    $info = curl_getinfo($ch);
	    curl_close($ch);
	    
	    if ( $info['http_code'] != 200 ){
		    die("can't connect to Placeling server");
	    }
	
	    // We parse the string for the request token and the matching token
	    // secret. Again, I'm not handling any errors and just plough ahead 
	    // assuming everything is hunky dory.
	    parse_str($r, $returned_items);
	    $request_token = $returned_items['oauth_token'];
	    $request_token_secret = $returned_items['oauth_token_secret'];
	
	    // We will need the request token and secret after the authorization.
	    // Google will forward the request token, but not the secret.
	    // Set a cookie, so the secret will be available once we return to this page.

	    delete_user_meta( $current_user->ID, '_oauth_token_secret' );
        delete_user_meta( $current_user->ID, '_oauth_token_secret_timeout' );
        delete_user_meta($current_user->ID, '_oauth_token_dest');
        update_user_meta( $current_user->ID, '_oauth_token_secret', $request_token_secret);
	    update_user_meta( $current_user->ID, '_oauth_token_secret_timeout', time()+60*30);
	    if ( isset( $_GET['placelingsrc'] ) ){
	        update_user_meta($current_user->ID, '_oauth_token_dest', $_GET['placelingsrc']);
	    }
	    //
	    //////////////////////////////////////////////////////////////////////
	    
	    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	    // Step 2: Authorize the Request Token
	    //
	    // Generate a URL for an authorization request, then redirect to that URL
	    // so the user can authorize our access request.  The user could also deny
	    // the request, so don't forget to add something to handle that case.
	    $result = $oauthObject->sign(array(
	        'path'      => $PLACELING_SERVICE_HOSTNAME . '/oauth/authorize',
	        'parameters'=> array(
	            'oauth_token' => $request_token),
	        'signatures'=> $PLACELING_SIGNATURES));
	
	    // See you in a sec in step 3.
	    header("Location:$result[signed_url]");
	    exit;			
	} else {
		$PLACELING_SIGNATURES['oauth_token'] = $accessToken;
		$PLACELING_SIGNATURES['oauth_secret'] = $secretToken;
    	
		$result = $oauthObject->sign(array(
			'path'      => $PLACELING_SERVICE_HOSTNAME.'/v1/users/me.json',
			'signatures'=> $PLACELING_SIGNATURES));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $result['signed_url']);	    
		$r = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		
		if ( $info['http_code'] == 401 ){
			delete_site_option('_placeling_access_token');
			delete_site_option('_placeling_access_secret');
			//die("no good access_key");
		} else if ( $info['http_code'] != 200 ){
			die("can't connect to Placeling server");
		}
		
		// We parse the string for the request token and the matching token
		// secret. Again, I'm not handling any errors and just plough ahead 
		$user = json_decode( $r );
		    
		$lat = $user->lat;
		$lng = $user->lng;
		
		$username = $user->username;
		update_site_option( '_placeling_username', $username );
		
		$recent_perspectives = $user->perspectives;
		$recent_places = array();

		if ( substr($username, -1) == 's' ){
            $displayusername = $username . "'";
        } else {
            $displayusername = $username . "'s";
        }

        $recent_perspectives = array_slice( $recent_perspectives, 0, 8);
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
		<link rel='stylesheet' href='../css/footer.css' type='text/css' />
		<link rel='stylesheet' href='../css/popup.css' type='text/css' />
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<!-- for IE compatibility -->
		<script type="text/javascript" src="../js/json2.js"></script>
		<script type="text/javascript" src="../js/footer.js"></script>
		<script type="text/javascript" src="../js/jsrender.js"></script>

		<script type="text/javascript">
			
			var places_json = '<?php echo addslashes( json_encode( $recent_places ) ); ?>';
			var lat = "<?php echo $lat;?>";
			var lng = "<?php echo $lng;?>";
			var username = "<?php echo $username; ?>";
			var hostname = "<?php echo $PLACELING_SERVICE_HOSTNAME;?>";
			var places_dictionary;
			var autocomplete;
			varxhrPool = [];
			
			function showRecentlyBookmarked() {
                $( "#recent_places" ).html(
                    $( "#placeTemplate" ).render( places_dictionary )
                );
			}

			$.views.converters({
                // Tag to reverse-sort an array
                placeAddress: function( array ){
                    var address=[];

                    for (i=1;i<array.length;i++) {
                        address.push( array[i].value );
                    }

                    return address.join(", ");
                }
            });

			function drawPreview( place ){
				$("#selected_place_json").val( JSON.stringify( place ) );
				
				if ( place ){
					$("#placeling_footer").show();
					$("#placeling_map_image").attr( "src", place.map_url );
					$("#placeling_username").html( "<?php echo $displayusername; ?>" );
					$("#placeling_place_name").html( place.name );
				} else {
					$("#placeling_footer").hide();
				}
				resizePlacelingMap();
				$("html, body").animate({ scrollTop: $(document).height() }, "slow");
			}
			
			var $recent_places;
			$(document).ready(function(){
				$("#placeling_footer").hide();
				places_dictionary = JSON.parse(places_json);
				
				showRecentlyBookmarked();
				
				$.xhrPool = new Array();
				$.xhrPool.abortAll = function() {
				    $(this).each(function(idx, jqXHR) {
					    jqXHR.abort();
				    });
				    $.xhrPool = [];
				};


				$.ajaxSetup({
				    beforeSend: function(jqXHR) {
					    $.xhrPool.push(jqXHR);
				    },
				    complete: function(jqXHR) {
					    var index = jQuery.inArray( jqXHR, $.xhrPool );
					    if (index > -1) {
					        $.xhrPool.splice(index, 1);
					    }
				    }
				});
				
				
				$( "#searchTextField" ).keyup(function( e ) {
					var text = $( "#searchTextField" ).val();
					$('#add_place_click').attr("href", "https://www.placeling.com/places/new?name=" + $( "#searchTextField" ).val() );
                    $('#placeling_search_name').html( $( "#searchTextField" ).val() );
					code= (e.keyCode ? e.keyCode : e.which);
					if (code != 13) {
						if ( text.length >= 1 ){
							$.ajax({
								url: "autocomplete.php",
								dataType: "json",
								data: {
									input: text,
									location: lat + "," + lng
								},
								success: function( data ) {
									$("ul#recent_places li").remove();
									if (data.predictions){
										$.each(data.predictions, function(i, item){
										    if ( $.inArray("route", item.types) < 0 && i < 8){
											    $("ul#recent_places").append( $( "#googlePlaceTemplate" ).render( item ) );
										    }
										});
									}
								}
							});
						} else {					
							showRecentlyBookmarked();
						}
					} else {
						$("ul#recent_places li").remove();
						$("#addplace").show();
						$.xhrPool.abortAll
						$("ul#recent_places").append( "<li class='waitload'><img height='91px' src='../img/spinner.gif'/></li>" );
						$.ajax({
							url: hostname + "/v1/places/search.json",
							dataType: "jsonp",
							data: {
								key: "<?php echo $PLACELING_SIGNATURES['consumer_key']; ?>",
								query: text,
								lat: lat,
								lng: lng
							},
							error: function( jqXHR ){
								$("ul#recent_places li").remove();	
							},
							success: function( data ) {
							    data.places.splice(7);
								$( "#recent_places" ).html(
                                	$( "#placeTemplate" ).render( data.places )
                                );
							}
						});
					}
				});	
				
				$("li.place_option").live('click', function(){
					$(this).find("a").click();
				});
				
				$("li.place_option a").live('click', function(){
					var place_id = $(this).attr('data-id');
					var data_ref = $(this).attr('data-ref');
					$( "#spinwait" ).show();
					$( "#placeling_footer" ).hide();	
					$.ajax({
						url: hostname + "/v1/places/" + place_id + ".json",
						dataType: "jsonp",
						data: {
							google_ref:data_ref, 
							key: "<?php echo $PLACELING_SIGNATURES['consumer_key']; ?>",
							rf: username
						},
						success: function( data ) {	
							$( "#spinwait" ).hide();					
							drawPreview( data );															            
						},
						error: function( jqXHR, textStatus, errorThrown ){
							$( "#spinwait" ).hide();	
						}
					});
					return false;
				});

			    $("a:not(#add_place_click)").live('click', function(){
			        //none of the links should actually perform a non-javascript function
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
            <script id="placeTemplate" type="text/x-jsrender">
                <li class="place_option">
                    <div class="place_image">
                        <img src="{{>thumb_url}}" class="place_icon" />
                    </div>
                    <div class="place_data">
                        <a class="place_link" data-id='{{>id}}' href="#">{{>name}}</a><br>
                        <span class="place_address">{{>street_address}}</span>
                    </div>
                </li>
            </script>

            <script id="googlePlaceTemplate" type="text/x-jsrender">
                <li class="place_option">
                    <div class="place_image">
                        <img class="place_icon" src="https://www.placeling.com/assets/quickpicks/EverythingPick.png"/>
                    </div>
                    <div class="place_data">
                        <a class="place_link" href="#" data-id="{{>id}}" data-ref="{{>reference}}">{{>terms[0].value}}</a><br>
                        <span class="place_address">{{placeAddress:terms}}</span>
                    </div>
                </li>
            </script>

            <div class="place_pick" style="display:block;width:600px">
                <div id="search_top">
                    <input id="searchTextField" type="text" class="search_box" placeholder="Start typing the name of the place you want to attach"  autofocus>
                </div>
                <div id="search_results">
                    <ul id="recent_places">
                    </ul>
                </div>
            </div>

            <div id="addplace" style="display:none;"><a id="add_place_click" target="_blank" href="https://www.placeling.com/places/new">Can't find <span id="placeling_search_name">your place</span>? Add it as a new place.</a></div>
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

                include_once("../footer.php");
                echo placelingFooterHtml( null, "" );
            ?>
        </div>
        <div id="actions" style="padding-top:10px">
            <input class="button-primary" type="button" name="Save" value="Save" id="submitbutton" style="float:right;" />
        </div>

    </body>
</html>