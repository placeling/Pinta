

function resizeFooter(){
    if ( jQuery("#placeling_right_footer").width() + jQuery("#placeling_left_footer").width() > jQuery("#placeling_footer").parent().width() -10 ){
        jQuery("#placeling_left_footer").hide();
        jQuery("#placeling_user_link_secondary").show();
        jQuery("#placeling_place_title").css("margin", "3px 0 0 0")
    } else {
        jQuery("#placeling_left_footer").show();
        jQuery("#placeling_user_link_secondary").hide();
        jQuery("#placeling_place_title").css("margin", "16px 0 0 0")
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
        resizeFooter();
    })

    resizeFooter();

});

