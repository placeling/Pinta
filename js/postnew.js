
window.attach_placeling_place = function(json) {
    jQuery('#placeling_place_json').val( encodeURI( json ) );
    renderPlaceAdmin( );
};

window.get_placeling_json = function(json) {
    return jQuery('#placeling_place_json').val();
};


jQuery(document).ready(function(){

    jQuery("form[name=post]").validate();
    
    jQuery('form[name=post]').submit(function(){
        jQuery('form[name=post]').validate();
        
        if ( !jQuery('form[name=post]').valid() ) {
            jQuery("textarea[name=placeling_placemark_memo]").focus();
            return false;
        }
        return true; 
    });

    renderPlaceAdmin( );

    jQuery("a#placeling_remove_place").live('click', function(){
        jQuery("#placeling_place_json").val("");
        jQuery("#placeling_placemarker_initial_memo").val("")
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
	    
	    lat = place.lat;
	    lng = place.lng;
	    name = place.name;
	    map_url = place.map_url;
	    
	    template = getPlacelingAdminTemplate();
	    
	    jQuery("#placeling_tagged_place").html( template( place ) );
	    jQuery("#add_place .placeling_place_name").html( name );
	    jQuery("#add_place #placeling_tagged").show();
	    jQuery("#add_place #placeling_untagged").hide();
	    
        if ( jQuery("#placeling_placemarker_initial_memo").val() != ""){
            jQuery("textarea[name=placeling_placemark_memo]").val( jQuery("#placeling_placemarker_initial_memo").val() );
	    } else if ( place.perspectives[0] && place.perspectives[0].mine){
            jQuery("textarea[name=placeling_placemark_memo]").val( place.perspectives[0].memo );
	    }
        
	    
	    jQuery("#empty_place").hide();
	    jQuery("#placeling_tagged_place").show();
	    
	    jQuery("#placeling_placemark_memo").rules("add", {
		required: true,
		minlength: 20,
		messages: {
		    required: "Required input",
		    minlength: jQuery.format("At least {0} characters are necessary")
		}
	    });
	    
	} else {
	    jQuery("#placeling_tagged_place").html("");
	    jQuery("#add_place .placeling_place_name").html( "" );
	    jQuery("#add_place #placeling_tagged").hide();
	    jQuery("#add_place #placeling_untagged").show();
	    jQuery("#empty_place").show();
	    jQuery("#placeling_tagged_place").hide();
	}						
}

function getPlacelingAdminTemplate(){
	return _.template("<div id='place_data'>		\
			    <div class='placeling_map_image'>						\
				    <img src='<%= map_url %>'/>							\
			    </div>										\
			    <div class='placeling_place_details'>						\
				    <div id='placeling_place_name'><%= name %></div>			\
				    <div id='placeling_place_address'><%= street_address %></div>	\
				    <div id='placeling_place_city'><%= city_data %></div>			\
			    </div>										\
			    <div class='placeling_place_remove'>						\
				    <div><a href='#' id='placeling_remove_place'>remove place</a></div>	\
			    </div>										\
			</div>		\
			<hr> 			\
			<div id='placeling_placemark'>					\
			    <fieldset>									\
				    <div id='placeling_memo_label'><label for='placeling_placemark_memo'>Placemark Note</label></div>				\
				    <textarea id='placeling_placemark_memo' rows='5' cols='50' name='placeling_placemark_memo'></textarea>\
				    <div id='placeling_photo_label'><label for='placeling_placemark_photos'><input name='placeling_placemark_photos' type='checkbox' id='placeling_placemark_photos' checked='checked'>Attach Photos?</label></div> \
			    </fieldset>									\
			</div>		\
	");

}

