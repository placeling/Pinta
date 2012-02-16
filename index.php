
<?php
/*
Plugin Name: Placeling-2
Plugin URI: https://www.placeling.com
Description: Tag your posts with location data from Placeling!
Version: 0.1
Author: Placeling (Internet Services) Inc.
Author URI: https://www.placeling.com
*/



if (!class_exists("Placeling")) {
	class Placeling {
		var $path = '';
		function Placeling() { 
			$this->path = WP_PLUGIN_URL . '/Pinta/';
			// Add Options Page
			add_action('admin_menu',  array(&$this, 'admin_menu'));
			add_filter('media_buttons_context', array(&$this, 'placeling_media_button'));
		}
		
			// Hook the options mage
		function admin_menu() {
		
			//add_options_page('Placeling Options', 'Placeling', 8, basename(__FILE__), array(&$this, 'handle_options'));
			//add_meta_box( 'WPPlaceling', 'Placeling', array(&$this,'draw_placeling'), 'post', 'normal', 'high' );
	    			
		} 
		
		
		function scripts_action() {	
			//wp_enqueue_script('jquery');		 		
		}
		
		
		function placeling_media_button($context) {
	        $path = $this->path;
	        
	        $placesApi_media_button_image = $this->path . 'img/MyMarker.png';
	        $placesApi_media_button = ' %s' . "<a id='add_place' href='{$path}/page/index.php?TB_iframe=true&height=500&width=660' class='thickbox' alt='foo' title='Tag Place'><img src='" . $placesApi_media_button_image . "' /></a>";
	        return sprintf($context, $placesApi_media_button);
	    }

	}
}

if ( class_exists('Placeling') ) :
	
	$Placeling = new Placeling();
	if (isset($Placeling)) {
		register_activation_hook( __FILE__, array(&$Placeling, 'install') );
	}
endif;

?>







