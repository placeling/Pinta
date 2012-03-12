<?php

include_once('pinta-config.php');


function truncateName( $placename ){

    $shortname = "";
    
    $parts = preg_split( "/[\s]+/", $placename );
    
    $i = 0;
    foreach ($parts as $part){
        $shortname .= $part . " ";
        $i += strlen( $part ) + 1;
        
        if ( $i > 20 ){
            break;
        }
    }
    
    if ( strlen( $shortname ) > 27){
        $shortname = substr( $shortname, 0, 25 )."...";
    }
    
    return $shortname;

}


function footerHtml( $place, $username ){
	global $WEB_HOSTNAME;
	
	$add_url = plugins_url( 'img/addPlace.png', __FILE__ );
	if ( isset( $place ) ){
		$lat = $place->lat;
		$lng = $place->lng;
		$pid = $place->id;
		
		$url = $place->map_url;
		$thirdparty_url = $place->google_url;
		$place_url = $WEB_HOSTNAME."/places/$pid?src=plugin";
		$name = truncateName( $place->name );
        
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
				$add_action_url = $WEB_HOSTNAME."/perspectives/$pid?src=plugin";
			} else {
				$add_action_url = $place_url;
			}
		} else {
            $add_action_url = $place_url;
        }
	} else {
		$lat = 0;
		$lng = 0;
		$pid = "";		
		$url = "";
		$thirdparty_url = "#";
		$place_url ="#";
		$name = "";
		$add_action_url="#";
	}			
                
	return "
	<div id='placeling_footer'>
		<div id='placeling_left_footer'>
			<div id='placeling_add_map'>
				<a id='placeling_add_action'  target='_blank' href='$add_action_url'><img id='placeling_add_image' src='$add_url'/><div id='placeling_add_text'>add to my places</div></a>
			</div>
		</div>
		<div id='placeling_middle_footer'>
			<div id='placeling_place_title'>
				<a href='$place_url' target='_blank'><span id='placeling_place_name'>$name</span></a>
			</div>
			<div id='placeling_contact_info'>
				<a href='$thirdparty_url'  target='_blank'>hours, directions, and contact</a>
			</div>
		</div>
		<div id='placeling_right_footer'>
			<a href='$thirdparty_url'  target='_blank'><img id='placeling_map_image' src='$url'></a>
		</div>
	</div>
	";
}

?>