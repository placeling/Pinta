

jQuery(document).ready(function(){

    jQuery("#placeling_footer_wrapper").mouseenter(function() {
        jQuery(this).addClass("placeling_highlight_shadow");
        jQuery(this).removeClass("placeling_shadow");
    }).mouseleave(function() {
        jQuery(this).removeClass("placeling_highlight_shadow");
        jQuery(this).addClass("placeling_shadow");
    });

});

