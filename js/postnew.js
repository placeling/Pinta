
window.attach_placeling_place = function(json) {
    jQuery('#placeling_place_json').val( encodeURI( json ) );
    renderPlaceAdmin( );
};


jQuery(document).ready(function(){

	jQuery('form[name=post]').submit(function(){ 
		alert("TEST");
            //the validateForm function lives in wp-admin/js/common.js
            /*
            if ( !validateForm( jQuery(this)) ) { 
                    alert('Please enter a value'); 
                    return false; //stop submitting the form
            } else {
                    //alert('all required fields have some input');
            }*/
    });


	renderPlaceAdmin( );

	jQuery("a#placeling_remove_place").click( function(){
		jQuery("#placeling_place_json").val("");
		renderPlaceAdmin( );		
		return false;
	});
});


function renderPlaceAdmin( ){

	if ( jQuery("#placeling_place_json").val() != ""){
		
		raw_json = unescape( jQuery("#placeling_place_json").val() );
		
		place =  JSON.parse( raw_json );
		
		lat = place.lat;
		lng = place.lng;
		name = place.name;
		
		map_url = "http://maps.google.com/maps/api/staticmap?center="+lat+","+lng+"&zoom=14&size=100x100&&markers=color:red%%7C"+lat+","+lng+"&sensor=false";
		
		template = getPlacelingAdminTemplate();
		//console.debug( place );
		
		jQuery("#placeling_tagged_place").html( template( place ) );
		
		jQuery("#add_place .placeling_place_name").html( name );
		jQuery("#add_place #placeling_tagged").show();
		jQuery("#add_place #placeling_untagged").hide();
		
		jQuery("#empty_place").hide();
		jQuery("#placeling_tagged_place").show();
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
	return _.template("																\
			<div class='placeling_map_image'>										\
				<img src='<%= map_url %>'/>												\
			</div>																	\
			<div class='placeling_place_details'>									\
				<div style='placeling_place_name'><%= name %></div>				\
				<div style='placeling_place_address'><%= street_address %></div>	\
				<div style='placeling_place_city'><%= city_data %></div>			\
			</div>																	\
			<div class='placeling_place_remove'>									\
				<div><a href='#' id='placeling_remove_place'>remove place</a></div>	\
			</div>");

}

