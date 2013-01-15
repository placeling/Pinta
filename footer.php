<?php

include_once('pinta-config.php');

function placelingFooterHtml( $place ){
	global $PLACELING_WEB_HOSTNAME;

	if ( isset( $place ) ){
		$lat = $place->lat;
		$lng = $place->lng;
		
		$url = $place->map_url;
		$map_url = str_replace( "size=100x100", "size=550x150", $url );
		$thirdparty_url = $place->google_url;
		$place_url = $PLACELING_WEB_HOSTNAME."/places/$place->slug";
		$name = $place->name;

		$page_id = get_option( 'placeling_linking_page',0 );
		$action_url = get_page_link($page_id)."?placelinglat=".$lat."&placelinglng=".$lng;
	} else {
		$lat = 0;
		$lng = 0;
		$map_url = "";
		$thirdparty_url = "#";
		$place_url ="#";
		$name = "";
		$action_url="#";
	}

	return "
        <div id='placeling_footer' class='placeling_shadow'>
            <div id='placeling_top_footer'>
                <div id='placeling_map'>
                    <a class='placeling_action_link' href='$action_url'><img id='placeling_map_image' src='$map_url'></a>
                </div>
                <div id='placeling_logo'>
                    <a id='placeling_link' target='_blank' href='https://www.placeling.com'><img id='placeling_add_image' src='https://www.placeling.com/images/blogFooterLogo.png' /></a>
                </div>
            </div>
            <div id='placeling_bottom_footer'>
                <div id='placeling_left_footer'>

                </div>
                <div id='placeling_right_footer'>
                    <div id='placeling_place_title'>
                        <a href='$place_url' target='_blank'><span id='placeling_place_name'>$name</span></a>
                    </div>
                    <div id='placeling_contact_info'>
                        <a href='$thirdparty_url'  target='_blank'>hours, directions, and contact info</a>
                    </div>
                </div>
            </div>
        </div>
	";
}

?>