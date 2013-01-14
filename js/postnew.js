
window.attach_placeling_place = function(json) {
    jQuery('#placeling_place_json').val( encodeURI( json ) );
    renderPlaceAdmin( );
};

window.get_placeling_json = function(json) {
    return jQuery('#placeling_place_json').val();
};


jQuery(document).ready(function(){
    jQuery("#placeling_js_warning").remove();

    renderPlaceAdmin( );

    jQuery("a#placeling_remove_place").live('click', function(){
        jQuery("#placeling_place_json").val("");
        renderPlaceAdmin( );		
        return false;
    });
	
});


function renderPlaceAdmin( ){

	if ( jQuery("#placeling_place_json").val() != ""){		
	    raw_json = unescape( jQuery("#placeling_place_json").val() );
	    
	    place =  JSON.parse( raw_json );
	    
	    var raw_place = jQuery('#placeling_place_json').val();
	    var json_place = unescape( raw_place );
	    var place = JSON.parse( json_place );
	    
	    if ( !place.street_address ){
		    place.street_address = "";
	    }
	    
	    if ( !place.city_data ){
            place.city_data = "";
	    }

        jQuery("#placeling_place_name").html( place.name );
        jQuery("#placeling_place_address").html( place.street_address );
        jQuery("#placeling_place_city").html( place.city_data );
        jQuery("#placeling_place_map").attr("src", place.map_url);

	    jQuery("#placeling_add_place_metabox .placeling_place_name").html( place.name );
	    jQuery("#placeling_add_place_metabox #placeling_tagged").show();
	    jQuery("#placeling_add_place_metabox #placeling_untagged").hide();
	    
	    jQuery("#placeling_empty_place").hide();
	    jQuery("#placeling_tagged_place").show();
	    
	} else {
	    jQuery("#placeling_add_place_metabox .placeling_place_name").html( "" );
	    jQuery("#placeling_add_place_metabox #placeling_tagged").hide();
	    jQuery("#placeling_add_place_metabox #placeling_untagged").show();
	    jQuery("#placeling_empty_place").show();
	    jQuery("#placeling_tagged_place").hide();
	}						
}

