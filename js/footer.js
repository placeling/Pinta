
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
        placeling_t = setTimeout("resizePlacelingMap()",1000);
    })

    resizePlacelingMap()

});

