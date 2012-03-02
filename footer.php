<?php

function footerHtml( $place, $add_url ){
	$lat = $place->lat;
	$lng = $place->lng;
	$pid = $place->id;
	
	$url = $place->map_url;
	$thirdparty_url = $place->google_url;
	$place_url = "http://www.placeling.com/places/$pid?src=plugin";
	$name = $place->name;				
				
	return "
	<div id='placeling_footer'>
		<div id='placeling_left_footer'>
			<div id='placeling_add_text'>
				<a id='placeling_add_action' href='#'><img id='placeling_add_map' src='$add_url'/>Add to My Map</a>
			</div>
		</div>
		<div id='placeling_middle_footer'>
			<div id='placeling_place_title'>
				<a href='$place_url'>$name</a>
			</div>
			<div id='placeling_contact_info'>
				<a href='$thirdparty_url'>hours, directions, and contact</a>
			</div>
		</div>
		<div id='placeling_right_footer'>
			<a href='$thirdparty_url'><img id='placeling_map_image' src='$url'></a>
		</div>
	</div>
	";
}

?>