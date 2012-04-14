
window.attach_placeling_place = function(json) {
    jQuery('#placeling_place_json').val( encodeURI( json ) );
    renderPlaceAdmin( );
};

window.get_placeling_json = function(json) {
    return jQuery('#placeling_place_json').val();
};


jQuery(document).ready(function(){
    jQuery("#placeling_js_warning").remove();

     jQuery("form[name=post]").validate({
         wrapper: "div",
         onsubmit: false
     });


    jQuery("input#publish").click(function(event){
        if ( !jQuery("form[name=post]").valid() ){
            event.preventDefault();
            jQuery("#placeling_placemark_memo").focus();
            alert("We need a brief summary (20 character minimum) to be displayed on your placeling map");
            return false;
        }
    });

    renderPlaceAdmin( );

    jQuery("a#placeling_remove_place").live('click', function(){
        jQuery("#placeling_place_json").val("");
        jQuery("#placeling_placemarker_initial_memo").val("")
        jQuery("textarea[name=placeling_placemark_memo]").val("");
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
	    
        if ( jQuery("#placeling_placemarker_initial_memo").val() != ""){
            jQuery("textarea[name=placeling_placemark_memo]").val( jQuery("#placeling_placemarker_initial_memo").val() );
	    } else if ( undefined != place.referring_perspectives && place.referring_perspectives.length > 0){
            jQuery("textarea[name=placeling_placemark_memo]").val( place.referring_perspectives[0].memo );
	    }
        
	    
	    jQuery("#placeling_empty_place").hide();
	    jQuery("#placeling_tagged_place").show();

	    jQuery("#placeling_placemark_memo").rules("add", {
            required: true,
            minlength: 20,
            messages: {
                required: jQuery.format("You need to add a summary that's at least 20 characters long"),
                minlength: jQuery.format("You need to add a summary that's at least 20 characters long")
            }
	    });
	    
	} else {
	    jQuery("#placeling_add_place_metabox .placeling_place_name").html( "" );
	    jQuery("#placeling_add_place_metabox #placeling_tagged").hide();
	    jQuery("#placeling_add_place_metabox #placeling_untagged").show();
	    jQuery("#placeling_empty_place").show();
	    jQuery("#placeling_tagged_place").hide();
	}						
}

