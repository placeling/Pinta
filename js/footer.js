
var placeling_t;

function getPlacelingParameterByName(url, name){
  name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
  var regexS = "[\\?&]" + name + "=([^&#]*)";
  var regex = new RegExp(regexS);
  var results = regex.exec( url );
  if(results == null)
    return "";
  else
    return decodeURIComponent(results[1].replace(/\+/g, " "));
}

function resizePlacelingMap(){
    map_url = jQuery("#placeling_map_image").attr("src");
    size_param = getPlacelingParameterByName( map_url, "size");
    newwidth = jQuery("#placeling_top_footer").width();
    if (newwidth + "x150" != size_param){
        map_url = map_url.replace( "size="+ size_param, "size=" + newwidth + "x150" )
        jQuery("#placeling_map_image").attr("src", map_url);
    }
}

function resizePlacelingFooter(){
    if ( jQuery("#placeling_footer").width() < 400 ){
        jQuery("#placeling_left_footer").hide();
        jQuery("#placeling_user_link_secondary").show();
        jQuery("#placeling_place_title").css("margin", "2px 0 0 0");
        jQuery("#placeling_right_footer").css("max-width", "100%");

    } else {
        jQuery("#placeling_left_footer").show();
        jQuery("#placeling_user_link_secondary").hide();
        jQuery("#placeling_place_title").css("margin", "16px 0 0 0");
        totalspace =  jQuery("#placeling_footer").width() - jQuery("#placeling_left_footer").width() -15;
        jQuery("#placeling_right_footer").css("max-width", totalspace + "px");
    }
}

jQuery(document).ready(function(){

    jQuery("#placeling_footer").mouseenter(function() {
        jQuery(this).addClass("placeling_highlight_shadow");
        jQuery(this).removeClass("placeling_shadow");
    }).mouseleave(function() {
        jQuery(this).removeClass("placeling_highlight_shadow");
        jQuery(this).addClass("placeling_shadow");
    });

    jQuery(window).resize( function(){
        clearTimeout( placeling_t );
        resizePlacelingFooter();
        placeling_t = setTimeout("resizePlacelingMap()",500);
    })

    resizePlacelingFooter();
    resizePlacelingMap()

});

