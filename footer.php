<?php

include_once('pinta-config.php');

function placelingFooterHtml( $place, $username ){
	global $WEB_HOSTNAME;

	if ( isset( $place ) ){
		$lat = $place->lat;
		$lng = $place->lng;
		$pid = $place->id;
		
		$url = $place->map_url;
		$map_url = str_replace( "size=100x100", "size=550x150", $url );
		$thirdparty_url = $place->google_url;
		$place_url = $WEB_HOSTNAME."/places/$pid";
		$name = $place->name;
        
		if ( isset($place->referring_perspectives) ){
			$found = false;
			$user_perspective;
			foreach ($place->referring_perspectives as $perspective){
				if ( $perspective->user->username == $username ){
					$user_perspective = $perspective;
					$found = true;
				} 	
			}
			
			if ( $found ){
				$pid = $user_perspective->id;
				$action_url = $WEB_HOSTNAME."/users/$username?pid=$pid&src=pinta12";
			} else {
				$action_url = $WEB_HOSTNAME."/users/$username?src=pinta12&lat=$lat&lng=$lng";
			}
		} else {
            $action_url = $WEB_HOSTNAME."/users/$username?src=pinta12&lat=$lat&lng=$lng";
        }
	} else {
		$lat = 0;
		$lng = 0;
		$pid = "";		
		$map_url = "";
		$thirdparty_url = "#";
		$place_url ="#";
		$name = "";
		$action_url="#";
	}

    if ( substr($username, -1) == 's' ){
        $displayusername = $username . "'";
    } else {
        $displayusername = $username . "'s";
    }
                
	return "
        <div id='placeling_footer' class='placeling_shadow'>
            <div id='placeling_top_footer'>
                <div id='placeling_map'>
                    <a class='action_link' href='$action_url' target='_blank'><img id='placeling_map_image' src='$map_url'></a>
                </div>
                <div id='placeling_logo'>
                    <a id='placeling_link' target='_blank' href='http://www.placeling.com'><img id='placeling_add_image' src=". plugins_url( 'img/logoBanner.png', __FILE__ ) . " /></a>
                </div>
            </div>
            <div id='placeling_bottom_footer'>
                <div id='placeling_left_footer'>
                    <div id='placeling_user_link'>
                        <a class='action_link' href='$action_url' target='_blank'>See <span id='placeling_username'>$displayusername</span> places</a>
                    </div>
                </div>
                <div id='placeling_right_footer'>
                    <div id='placeling_place_title'>
                        <a href='$place_url' target='_blank'><span id='placeling_place_name'>$name</span></a>
                    </div>
                    <div id='placeling_contact_info'>
                        <a href='$thirdparty_url'  target='_blank'>hours, directions, and contact info</a>
                    </div>
                    <div id='placeling_user_link_secondary' style='font-size: 14px;display:none;margin-top:4px;'>
                        <a class='action_link' href='$action_url' target='_blank'>See <span id='placeling_username'>$username's</span> places</a>
                    </div>
                </div>
            </div>
        </div>
	";
}

?>