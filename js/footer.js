

function resizeFooter(){
    if ( jQuery(document).width() <= 360 ){
        jQuery("#placeling_footer_wrapper").hide();
        jQuery("#placeling_mobile_footer").show();
    } else {
        jQuery("#placeling_footer_wrapper").show();
        jQuery("#placeling_mobile_footer").hide();

        if ( jQuery("#placeling_footer_wrapper").parent().width() < 468 ){
            jQuery("#placeling_right_footer").hide();
            jQuery("#placeling_footer").css("margin-right", "0");
            jQuery("#placeling_footer_wrapper").css("min-width", "378px");
        } else {
            jQuery("#placeling_right_footer").show();
            jQuery("#placeling_footer").css("margin-right", "90px");
            jQuery("#placeling_footer_wrapper").css("min-width", "468px");
        }
    }
}

jQuery(document).ready(function(){

    jQuery("#placeling_footer_wrapper").mouseenter(function() {
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

