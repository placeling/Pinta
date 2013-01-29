<?php
/*
Plugin Name: Placeling
Plugin URI: https://www.placeling.com
Description: Placeling turns your blog into an iPhone- and map-based guide to the world. Simply use this plugin to tag your posts with a location and we'll convert each post into a point on a map at placeling.com. Your readers can use their iPhone to see nearby places you've recommended (and they'll be driven to your blog to read your post) or explore a web-based map of all your posts.
Version: 2.1.2
Author: Placeling (Internet Services) Inc.
Author URI: https://www.placeling.com
*/

include_once('OAuthSimple.php');
include_once('simple_html_dom.php');
include_once('pinta-config.php');
include_once('placeling-options.php');

if (!class_exists("Placeling")) {
	class Placeling {

		function Placeling() {
			add_action( 'admin_menu',  array(&$this, 'admin_menu') );
			add_action( 'save_post', array( &$this, 'save_post') );
			add_action( 'publish_post', array( &$this, 'postToPlaceling') );
			add_action( 'publish_page', array( &$this, 'postToPlaceling') );
            add_action( 'admin_init', array( &$this, 'upgrade_check' ) );

			add_filter( 'media_buttons_context', array(&$this, 'placeling_media_button') );
			add_filter( 'the_content', array(&$this, 'addPlacelingFooter') );

            add_shortcode( 'placeling_map', array(&$this, 'placeling_map_widget' ) );
		}
		
		function install() {

            if( !function_exists('curl_exec') ){
                // Deactivate the plugin
                deactivate_plugins(__FILE__);

                // Show the error page, Maybe this shouldn't happen?
                die("You must enable cURL support in your PHP installation to use Placeling. Please contact your hosting provider, or get more <a target='_blank' href='http://stackoverflow.com/questions/1347146/how-to-enable-curl-in-php-xampp'>help here</a>");
            }

			//there isn't anything we need to do, this just is to prevent an error on activation
			//don't generate output
			$page_slug = 'placeling-map';
			update_site_option( '_placeling_version', "2.0.0");

            foreach (get_pages( array() ) as $page ){
                if ( $page->post_name == $page_slug ){
                    update_option( 'placeling_linking_page',$page->ID);
                    return;
                }
            }

            global $user_ID;
            $new_post = array(
            'post_title' => 'Map',
            'post_content' => '[placeling_map height="400px" mobile_auto_scroll=true]',
            'post_status' => 'publish',
            'post_name' => $page_slug,
            'post_author' => $user_ID,
            'comment_status' => "closed",
            'ping_status' => "closed",
            'post_type' => 'page'
            );
            $post_id = wp_insert_post($new_post);
            update_option( 'placeling_linking_page',$post_id );

            // Ping Placeling with notification of activation
            $url = $GLOBALS['PLACELING_WEB_HOSTNAME'].'/vanity/pinta';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                    'blog_name' => get_bloginfo( 'name' ),
                    'blog_url' => get_bloginfo( 'url' ),
                    'blog_description' => get_bloginfo( 'description' ),
                    'blog_email' => get_bloginfo( 'admin_email' ),
                    'blog_version' => get_bloginfo('version')
            ) );
            $r = curl_exec($ch);

		}

        function upgrade_check(){
            $version = get_site_option( '_placeling_version', false, true);

            if ( !$version ){
                $this->install();
            }

        }

        function is_mobile() {
            static $is_mobile;

            if ( isset($is_mobile) )
                return $is_mobile;

            if ( empty($_SERVER['HTTP_USER_AGENT']) ) {
                $is_mobile = false;
            } elseif ( strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false // many mobile devices (all iPhone, iPad, etc.)
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
                || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false ) {
                    $is_mobile = true;
            } else {
                $is_mobile = false;
            }

            return $is_mobile;
        }

        function getContent($height, $width, $autoscroll)
        {
            global $PLACELING_SERVICE_HOSTNAME;
            $username = get_site_option( '_placeling_username', false, true);

            if ( self::is_mobile() && $autoscroll){
                wp_enqueue_script( 'jquery' );
                $scrollhtml= "
                    <script type=\"text/javascript\">
                    jQuery(document).ready(function() {
                         jQuery('html, body').animate({
                             scrollTop: jQuery('#placeling_iframe').offset().top-10
                         }, 1000);
                     });
                     </script>
                 ";
             } else {
               $scrollhtml = "";
             }
            $zoom = get_option('placeling_default_zoom', 14);
            if ( isset( $username ) && $username != ""  && isset( $PLACELING_SERVICE_HOSTNAME ) ) {
                if ( isset( $_GET["placelinglat"]) && isset( $_GET["placelinglng"] ) ){
                    $lat = $_GET["placelinglat"];
                    $lng = $_GET["placelinglng"];

                    return $scrollhtml."<iframe id=\"placeling_iframe\" src=\"$PLACELING_SERVICE_HOSTNAME/users/$username/pinta?lat=$lat&amp;lng=$lng&amp;zoom=$zoom&amp;newwin=".(1 == get_option( 'placeling_marker_new_window', 1 ) )."\" frameborder=\"0\"  height=\"$height\" width=\"$width\">You need iframes enabled to view the map</iframe>";
                } else {
                    return $scrollhtml."<iframe id=\"placeling_iframe\" src=\"$PLACELING_SERVICE_HOSTNAME/users/$username/pinta?zoom=$zoom&amp;newwin=".( 1 == get_option( 'placeling_marker_new_window', 1 ) )."\" frameborder=\"0\"  height=\"$height\" width=\"$width\">You need iframes enabled to view the map</iframe>";
                }
            } else {
                return "<p>Placeling has not yet been setup, please contact the site's administrator to see the map</p>";
            }

        }

        // [placeling_map height="height" width="width" mobile_auto_scroll=true]
        function placeling_map_widget( $atts ){
            extract( shortcode_atts( array(
            		'height' => '400px',
            		'width' => '100%',
            		'mobile_auto_scroll' => true )
            	, $atts ) );

            return $this->getContent($height, $width, $mobile_auto_scroll);
        }

		function admin_menu() {
			add_meta_box( 'WPPlaceling', 'Placeling', array(&$this,'draw_placeling'), 'post', 'normal', 'high' );
			add_meta_box( 'WPPlaceling', 'Placeling', array(&$this,'draw_placeling'), 'page', 'normal', 'high' );
		}

		function update_place( $post_ID ){
			global $PLACELING_SIGNATURES;
			global $PLACELING_SERVICE_HOSTNAME;
			
			$oauthObject = new OAuthSimple();
			
			$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);
			
			$postObj = get_post( $post_ID ); 
			$author_id = $postObj->post_author;
			$username = get_site_option( '_placeling_username', false, true);
			
			$place_json = rawurldecode( $meta_value );
			$place_json = preg_replace('/\\\\\'/', '\'', $place_json);
			$place = json_decode( $place_json );
			
			$url = $PLACELING_SERVICE_HOSTNAME.'/v1/places/'.$place->id;
			
			$result = $oauthObject->sign(array(
				'path'      => $url,
                'parameters'=> array(
                    'rf' => $username ),
				'signatures'=> $PLACELING_SIGNATURES));
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $result['signed_url'] );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$r = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);
			
			if ( $info['http_code'] == 200 ){
				$place = json_decode( $r );
				if ( isset( $place->id ) ) {
					$place_json = rawurlencode( $r );
					update_post_meta( $post_ID, '_placeling_place_json', $place_json );
				} 
			} 
			
		}
		
		function addPlacelingFooter( $content ){
			global $PLACELING_RELOAD_INTERVAL;

			if ( !is_singular() ){
				//we only want to show on single views, for now,  so as not to crowd
				return $content;
			}
			
		  	$post_ID = $GLOBALS['post']->ID;
			
			$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);
			
			if ( strlen( $meta_value ) > 0 ){
				
				$timestamp = get_post_meta($post_ID, '_placeling_place_json_timestamp', true);
			
				if ( $timestamp =="" || $timestamp < time() - $PLACELING_RELOAD_INTERVAL ){
					update_post_meta( $post_ID, '_placeling_place_json_timestamp', time() );
					try{
						$this->update_place( $post_ID );	
					} catch (Exception $e){
						//we just never want this to prevent rendering of a page
					}
					$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);
				}
				
				$place_json = urldecode( $meta_value );
				$place = json_decode( $place_json );

				wp_enqueue_style( 'footer', plugins_url( 'css/footer.css', __FILE__ ),false, "2.0.0" );
				wp_enqueue_script( 'footer', plugins_url( 'js/footer.js', __FILE__ ), array('jquery'), "2.0.0" );
				
				include_once("footer.php");
		  		$content = $content .placelingFooterHtml( $place );
		  	}
		  	
		  	return $content;
		}
		
		function draw_placeling(){
			global $post_ID;

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'postnew', plugins_url( 'js/postnew.js', __FILE__ ), array('jquery'), "2.0.0" );

			wp_enqueue_style( 'pinta', plugins_url( 'css/pinta.css' , __FILE__ ), false, "2.0.0" );

			$empty_marker_button = plugins_url( 'img/EmptyMarker.png' , __FILE__ );
			
			$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);
			
			?>
				<input id="placeling_place_json" name="placeling_place_json" type="hidden" value="<?php echo $meta_value ; ?>" />
				
				<div id="placeling_dialog_form" title="Post to Placeling">
			
				</div>

				<div id="placeling_js_warning">
					<div>If you see this, then the Placeling javascript didn't get to run. Please check your browser's javascript console.</div>
				</div>

				<div id="placeling_empty_place" style="display:none;">
					<a id='placeling_add_place' href='<?php echo plugins_url( 'popup/index.php' , __FILE__ ); ?>?TB_iframe=true&height=500&width=700' class='thickbox' alt='foo' title='Tag Place'><img id='placeling_empty_icon' src='<?php echo $empty_marker_button; ?>' /><div id='placeling_add_label'>Add Place</div></a>
				</div>
				
				<div id="placeling_tagged_place" style="display:none;">
                    <div id="place_data">
                        <div class="placeling_map_image">
                            <img id="placeling_place_map" src="#"/>
                        </div>
                        <div class='placeling_place_details'>
                            <div id='placeling_place_name'></div>
                            <div id='placeling_place_address'></div>
                            <div id='placeling_place_city'></div>
                        </div>
                        <div class='placeling_place_remove'>
                            <div><a href='#' id='placeling_remove_place'>remove place</a></div>
                        </div>
                    </div>
                    <hr>
                    <div id='placeling_placemark'>
                        <fieldset>
                            <div id='placeling_photo_label'><label for='placeling_placemark_photos'><input name='placeling_placemark_photos' type='checkbox' id='placeling_placemark_photos' checked='checked'>Copy blog post photos to Placeling?</label></div>
                        </fieldset>
                    </div>
				</div>
	
			<?php
		}
		
		function postToPlaceling( $post_ID ){
			global $PLACELING_SERVICE_HOSTNAME;
			global $PLACELING_SIGNATURES;

            $postObj = get_post( $post_ID );

			if ( !array_key_exists( 'placeling_place_json', $_POST ) ||  $_POST['placeling_place_json'] == "" ){
				return; //no placeling data to post
			}

            $placemark_memo = "";
            if ( strlen( $postObj->post_excerpt ) >0 ){
                $placemark_memo = $postObj->post_excerpt."\n\n";
            }

            $tags = wp_get_post_tags( $post_ID );
            $memotags = array();

            foreach( $tags as $tag ){
                $memotags[] = "#".$tag->slug;
            }

            $placemark_memo .= join(", ", $memotags);
			
			$permalink = get_permalink( $post_ID );
			$current_user = wp_get_current_user();
			
			$accessToken = get_site_option('_placeling_access_token', false, true);
			$secretToken = get_site_option('_placeling_access_secret', false, true);
	
			$oauthObject = new OAuthSimple();
			$oauthObject->setAction("POST");
			
			$placemarker_json = rawurldecode( $_POST['placeling_place_json'] );
			$placemarker_json = preg_replace('/\\\\\'/', '\'', $placemarker_json);
			$placemarker = json_decode( $placemarker_json );

			if ( empty($accessToken) || empty($secretToken) || $accessToken == "" || $secretToken == "" ) {
				//this is a weird state that probably shouldn't happen, but I don't want it to break their post
			} else {
				$PLACELING_SIGNATURES['oauth_token'] = $accessToken;
				$PLACELING_SIGNATURES['oauth_secret'] = $secretToken;
				
				$url = $PLACELING_SERVICE_HOSTNAME.'/v1/places/'.$placemarker->id.'/perspectives';
				
				if ( array_key_exists( 'placeling_placemark_photos', $_POST) && $_POST['placeling_placemark_photos'] =="on" ){
					$content = $_POST['content'];
					
					$html = str_get_html( $content );
					$images = array();
					if ($html){
                        foreach($html->find('img') as $element){
                            $images[] = trim( $element->src, "\\\"" );
                        }
					}
	
					$image_urls = join( ',', $images);
					
					$data = array(
						'memo' => $placemark_memo,
						'url'  => $permalink,
						'photo_urls' => $image_urls);
					
				} else {
					$data = array( 'memo' => $placemark_memo,
						'url'  => $permalink );
				}
				
				$result = $oauthObject->sign(array(
					'path'      => $url,
					'parameters'=> $data,
					'signatures'=> $PLACELING_SIGNATURES));
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $result['parameters']);
				$r = curl_exec($ch);
				// has to be "fire and forget"
			}
		}
		
		function save_post( $post_ID ){
			if ( array_key_exists( 'placeling_place_json', $_POST ) ){
				$place_json = $_POST['placeling_place_json'];
                update_post_meta( $post_ID, '_placeling_place_json', 0 ); //always want to refresh
				if ( strlen( $place_json ) > 0 ){
					update_post_meta( $post_ID, '_placeling_place_json', $place_json );
				} else {
					delete_post_meta( $post_ID, '_placeling_place_json' );
				}
			}
		}
		
		
		function placeling_media_button($context) {
			global $post_ID;
		  	$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);
		  	
		  	$empty_button_image = plugins_url( 'img/EmptyMarker.png', __FILE__ );
		  	$placed_button_image = plugins_url( 'img/MyMarker.png', __FILE__ );
		  	if ( strlen($meta_value) > 0 ){
		  		$place_json = rawurldecode( $meta_value );
				$place = json_decode( $place_json );
		  		$name = $place->name;
		  	} else {
	        	$name = "";
	        }
	        $placesApi_media_button = ' %s' . "<a id='placeling_add_place_metabox' href='".plugins_url( 'popup/index.php', __FILE__ )."?TB_iframe=true&height=500&width=700' class='thickbox' alt='foo' title='Add Place'><img id='placeling_untagged' style='display:none;' height=16 width=16 src='" . $empty_button_image . "' /><img id='placeling_tagged' height=16 width=16 style='display:none;' src='" . $placed_button_image . "' /><span class='placeling_place_name'>".$name."</span></a>";
	        return sprintf($context, $placesApi_media_button);
	    }

	}
}

if ( class_exists('Placeling') ) :
	
	$placeling = new Placeling();
	if (isset($placeling)) {
		register_activation_hook( __FILE__, array(&$placeling, 'install') );
	}
endif;

?>
