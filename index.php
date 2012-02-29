
<?php
/*
Plugin Name: Placeling
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
			add_action( 'save_post', array( &$this, 'save_post') );
			add_filter('media_buttons_context', array(&$this, 'placeling_media_button'));
			
		}
		
			// Hook the options mage
		function admin_menu() {
			add_meta_box( 'WPPlaceling', 'Placeling', array(&$this,'draw_placeling'), 'post', 'normal', 'high' );
		} 
		
		
		function draw_placeling(){
			global $post_ID;
						
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script('postnew', $this->path.'/js/postnew.js', array('jquery'));
						
			$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);
			
			if ( !isset( $meta_values ) ){
			 	$path = $this->path;
				$placesApi_media_button_image = $this->path . 'img/EmptyMarker.png';
				
				?>
					<div class="empty_place">
						<input id="placeling_place_json" name="placeling_place_json" type="hidden"/>
						<a id='add_place' href='<?php echo $path; ?>/popup/index.php?TB_iframe=true&height=500&width=660' class='thickbox' alt='foo' title='Tag Place'><imgsrc='<?php echo $placesApi_media_button_image; ?>' />Attach a place</a>
					</div>
				<?php
			
			} else {
				$user = json_decode( $meta_value );
				var_dump( $user );
				?>
					<input id="placeling_place_json" name="placeling_place_json" type="hidden" value="<?php echo url_encode( $meta_value ); ?>" />
					<img id="placeling_map_image"/>
				<?php

			}
		}
		
		
		function scripts_action() {	
			//wp_enqueue_script('jquery');		 		
		}
		
		
		function save_post( $post_ID ){
			if ( array_key_exists( 'placeling_place_json', $_POST ) ){
				$place_json = $_POST['placeling_place_json'];
				if ( strlen( $place_json ) > 0 ){
					update_post_meta( $post_ID, '_placeling_place_json', $place_json, true );
				}
			}
		}
		
		function placeling_media_button($context) {
	        $path = $this->path;
	        
	        $placesApi_media_button_image = $this->path . 'img/EmptyMarker.png';
	        $placesApi_media_button = ' %s' . "<a id='add_place' href='{$path}/popup/index.php?TB_iframe=true&height=500&width=660' class='thickbox' alt='foo' title='Tag Place'><img height=16 width=16 src='" . $placesApi_media_button_image . "' /></a>";
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







