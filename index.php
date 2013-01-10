<?php
/*
Plugin Name: Placeling
Plugin URI: https://www.placeling.com
Description: Placeling turns your blog into an iPhone- and map-based guide to the world. Simply use this plugin to tag your posts with a location and we'll convert each post into a point on a map at placeling.com. Your readers can use their iPhone to see nearby places you've recommended (and they'll be driven to your blog to read your post) or explore a web-based map of all your posts.
Version: 2.0.0
Author: Placeling (Internet Services) Inc.
Author URI: https://www.placeling.com
*/

include_once('OAuthSimple.php');
include_once('simple_html_dom.php');
include_once('pinta-config.php');

if (!class_exists("Placeling")) {
	class Placeling {

	    var $page_slug = 'placeling-map';

        var $page_title = 'Map';

        var $ping_status = 'open';

		function Placeling() {
			// Add Options Page
			add_action( 'admin_menu',  array(&$this, 'admin_menu') );
			add_action( 'save_post', array( &$this, 'save_post') );
			add_action( 'publish_post', array( &$this, 'postToPlaceling') );
			add_action( 'publish_page', array( &$this, 'postToPlaceling') );
			add_filter( 'media_buttons_context', array(&$this, 'placeling_media_button') );
			add_filter( 'the_content', array(&$this, 'addPlacelingFooter') );

            add_filter( 'the_posts',array(&$this,'detectPost'));
            add_filter( 'admin_init', array(&$this, 'flush_rewrite_rules'));
            add_action( 'init', array(&$this, 'add_rewrites_init' ) );
		}
		
		function install() {
			//there isn't anything we need to do, this just is to prevent an error on activation
		}

        function flush_rewrite_rules(){
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
        }

        function getContent()
        {
            global $SERVICE_HOSTNAME;
            $username = get_site_option( '_placeling_username', false, true);

            if ( isset( $username )  && isset( $SERVICE_HOSTNAME ) ) {
                return "<iframe src=\"$SERVICE_HOSTNAME/users/$username/pinta?lat=49.268991905470884&amp;lng=-123.13450887298586\" frameborder=\"0\"  height=\"300\" width=\"100%\">You need iframes enabled to view the map</iframe>";
            } else {
                return "<p>Placeling has not yet been setup, please contact the site's administrator</p>";
            }

        }

        function add_rewrites_init(){
            add_rewrite_rule(
                'placeling/map?$',
                'index.php?pagename=placeling-map',
                'top' );
        }

        function detectPost($posts){
                global $wp;
                global $wp_query;

                /**
                 * Check if the requested page matches our target
                 */
                if ( isset( $wp->query_vars ) && array_key_exists('pagename', $wp->query_vars) && $wp->query_vars['pagename'] == $this->page_slug ){
                    //Add the fake post
                    $posts=NULL;
                    $posts[]=$this->createPost();

                    /**
                     * Trick wp_query into thinking this is a page (necessary for wp_title() at least)
                     * Not sure if it's cheating or not to modify global variables in a filter
                     * but it appears to work and the codex doesn't directly say not to.
                     */
                    $wp_query->is_page = true;
                    //Not sure if this one is necessary but might as well set it like a true page
                    $wp_query->is_singular = true;
                    $wp_query->is_home = false;
                    $wp_query->is_archive = false;
                    $wp_query->is_category = false;
                    //Longer permalink structures may not match the fake post slug and cause a 404 error so we catch the error here
                    unset($wp_query->query["error"]);
                    $wp_query->query_vars["error"]="";
                    $wp_query->is_404=false;

                }
                return $posts;
            }

        function createPost(){

            /**
             * Create a fake post.
             */
            $post = new stdClass;

            /**
             * The author ID for the post.  Usually 1 is the sys admin.  Your
             * plugin can find out the real author ID without any trouble.
             */
            $post->post_author = 1;

            /**
             * The safe name for the post.  This is the post slug.
             */
            $post->post_name = $this->page_slug;

            /**
             * Not sure if this is even important.  But gonna fill it up anyway.
             */
            $post->guid = get_bloginfo('wpurl') . '/' . $this->page_slug;


            /**
             * The title of the page.
             */
            $post->post_title = $this->page_title;
            $post->post_type = "page";
            $post->post_parent = null;

            /**
             * This is the content of the post.  This is where the output of
             * your plugin should go.  Just store the output from all your
             * plugin function calls, and put the output into this var.
             */
            $post->post_content = $this->getContent();

            /**
             * Fake post ID to prevent WP from trying to show comments for
             * a post that doesn't really exist.
             */
            $post->ID = -134;

            /**
             * Static means a page, not a post.
             */
            $post->post_status = 'static';

            /**
             * Turning off comments for the post.
             */
            $post->comment_status = 'closed';

            /**
             * Let people ping the post?  Probably doesn't matter since
             * comments are turned off, so not sure if WP would even
             * show the pings.
             */
            $post->ping_status = $this->ping_status;

            $post->comment_count = 0;

            /**
             * You can pretty much fill these up with anything you want.  The
             * current date is fine.  It's a fake post right?  Maybe the date
             * the plugin was activated?
             */
            $post->post_date = current_time('mysql');
            $post->post_date_gmt = current_time('mysql', 1);

            return($post);
        }

        function dev4press_debug_rewrite_rules() {
          global $wp_rewrite;
          echo '<div>';
          if (!empty($wp_rewrite->rules)) {
            echo '<h5>Rewrite Rules</h5>';
            echo '<table><thead><tr>';
            echo '<td>Rule</td><td>Rewrite</td>';
            echo '</tr></thead><tbody>';
            foreach ($wp_rewrite->rules as $name => $value) {
              echo '<tr><td>'.$name.'</td><td>'.$value.'</td></tr>';
            }
            echo '</tbody></table>';
          } else {
            echo 'No rules defined.';
          }
          echo '</div>';
        }


		function admin_menu() {
			add_meta_box( 'WPPlaceling', 'Placeling', array(&$this,'draw_placeling'), 'post', 'normal', 'high' );
			add_meta_box( 'WPPlaceling', 'Placeling', array(&$this,'draw_placeling'), 'page', 'normal', 'high' );
		}

		function update_place( $post_ID ){
			global $SIGNATURES;
			global $SERVICE_HOSTNAME;
			
			$oauthObject = new OAuthSimple();
			
			$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);
			
			$postObj = get_post( $post_ID ); 
			$author_id = $postObj->post_author;
			$username = get_site_option( '_placeling_username', false, true);
			
			$place_json = rawurldecode( $meta_value );
			$place_json = preg_replace('/\\\\\'/', '\'', $place_json);
			$place = json_decode( $place_json );
			
			$url = $SERVICE_HOSTNAME.'/v1/places/'.$place->id;
			
			$result = $oauthObject->sign(array(
				'path'      => $url,
                'parameters'=> array(
                    'rf' => $username ),
				'signatures'=> $SIGNATURES));
			
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
			global $RELOAD_INTERVAL;

			if ( !is_singular() ){
				//we only want to show on single views, for now,  so as not to crowd
				return $content;
			}
			
		  	$post_ID = $GLOBALS['post']->ID;
			
			$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);
			
			if ( strlen( $meta_value ) > 0 ){
				
				$timestamp = get_post_meta($post_ID, '_placeling_place_json_timestamp', true);
			
				if ( $timestamp =="" || $timestamp < time() - $RELOAD_INTERVAL ){
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

				wp_enqueue_style( 'footer', plugins_url( 'css/footer.css', __FILE__ ),false, "1.2" );
				wp_enqueue_script( 'footer', plugins_url( 'js/footer.js', __FILE__ ), array('jquery'), "1.2" );
				
				include("footer.php");
		  		$content = $content .placelingFooterHtml( $place );
		  	}
		  	
		  	return $content;
		}
		
		function draw_placeling(){
			global $post_ID;

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-validate',  plugins_url( 'js/jquery.validate.min.js' , __FILE__ ), array('jquery') );
			wp_enqueue_script( 'postnew', plugins_url( 'js/postnew.js', __FILE__ ), array('jquery'), "1.2" );

			wp_enqueue_style( 'pinta', plugins_url( 'css/pinta.css' , __FILE__ ), false, "1.2" );

			$empty_marker_button = plugins_url( 'img/EmptyMarker.png' , __FILE__ );
			
			$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);
			$placemarker_memo = get_post_meta( $post_ID, '_placeling_placemark_memo', true );
			
			?>
				<input id="placeling_place_json" name="placeling_place_json" type="hidden" value="<?php echo $meta_value ; ?>" />
                <input id="placeling_placemarker_initial_memo" name="placeling_placemarker_initial_memo" type="hidden" value="<?php echo htmlentities( $placemarker_memo );?>" />
				
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
                            <div id='placeling_memo_label'><label for='placeling_placemark_memo'>Add a brief summary. This will appear in Placeling</label></div>
                            <textarea id='placeling_placemark_memo' rows='5' cols='50' name='placeling_placemark_memo'></textarea>
                            <div id='placeling_photo_label'><label for='placeling_placemark_photos'><input name='placeling_placemark_photos' type='checkbox' id='placeling_placemark_photos' checked='checked'>Copy blog post photos to Placeling?</label></div>
                        </fieldset>
                    </div>
				</div>
	
			<?php
		}
		
		function postToPlaceling( $post_ID ){
			global $SERVICE_HOSTNAME;
			global $SIGNATURES;

			if ( !array_key_exists( 'placeling_placemark_memo', $_POST ) ){
				return; //no placeling memo to post
			}

			$placemark_memo = stripslashes( $_POST['placeling_placemark_memo'] );
			
			$permalink = get_permalink( $post_ID );
			$current_user = wp_get_current_user();
			
			$accessToken = get_site_option('_placeling_access_token', false, true);
			$secretToken = get_site_option('_placeling_access_secret', false, true);
	
			$oauthObject = new OAuthSimple();
			$oauthObject->setAction("POST");
			
			$placemarker_json = rawurldecode( $_POST['placeling_place_json'] );
			$placemarker_json = preg_replace('/\\\\\'/', '\'', $placemarker_json);
			$placemarker = json_decode( $placemarker_json );

            update_post_meta( $post_ID, '_placeling_placemark_memo', $placemark_memo );
			
			if ( empty($accessToken) || empty($secretToken) || $accessToken == "" || $secretToken == "" ) {
				//this is a weird state that probably shouldn't happen, but I don't want it to break their post
			} else {
				$SIGNATURES['oauth_token'] = $accessToken;
				$SIGNATURES['oauth_secret'] = $secretToken;
				
				$url = $SERVICE_HOSTNAME.'/v1/places/'.$placemarker->id.'/perspectives';
				
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
					'signatures'=> $SIGNATURES));
				
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
	
	$Placeling = new Placeling();
	if (isset($Placeling)) {
		register_activation_hook( __FILE__, array(&$Placeling, 'install') );
	}
endif;

?>
