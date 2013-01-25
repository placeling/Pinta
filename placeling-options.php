<?php

require_once('pinta-config.php');
include_once('OAuthSimple.php');

add_action( 'admin_menu', 'placeling_plugin_menu' );

function placeling_plugin_menu(){
    add_options_page( "Placeling Options", "Placeling", "manage_options",'placeling_options', 'placeling_settings_page' );
    add_action( 'admin_init', 'register_placelingsettings' );
}

function register_placelingsettings() {
	//register our settings
	register_setting( 'placeling-settings-group', 'placeling_linking_page' );
	register_setting( 'placeling-settings-group', 'placeling_default_zoom' );
	register_setting( 'placeling-settings-group', 'placeling_marker_new_window' );
}

function plugin_options_validate($input) {
    $options = get_option('plugin_options');
    $options['text_string'] = trim($input['text_string']);
    if(!preg_match('/^[a-z0-9]{32}$/i', $options['text_string'])) {
        $options['text_string'] = '';
    }
    return $options;
}


function placeling_settings_page() {
    $username = get_site_option( '_placeling_username', false, true);
	$accessToken = get_site_option('_placeling_access_token', false, true);
	$secretToken = get_site_option('_placeling_access_secret', false, true);

	if ( empty($accessToken) || empty($secretToken) || $accessToken == "" || $secretToken == "" ) {
     ?>
        <p>You haven't connected a Placeling account, <a href='<?php echo plugins_url( 'popup/index.php?placelingsrc=admin' , __FILE__ ); ?>' class='thickbox' alt='foo' title='Tag Place'> connect now.</a></p>

<?php
    } else {
        $accessToken = get_site_option('_placeling_access_token', false, true);
        $secretToken = get_site_option('_placeling_access_secret', false, true);
        $GLOBALS['PLACELING_SIGNATURES']['oauth_token'] = $accessToken;
        $GLOBALS['PLACELING_SIGNATURES']['oauth_secret'] = $secretToken;
        $oauthObject = new OAuthSimple();

        $result = $oauthObject->sign(array(
            'path'      => $GLOBALS['PLACELING_SERVICE_HOSTNAME'].'/v1/users/me.json',
            'signatures'=> $GLOBALS['PLACELING_SIGNATURES']));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
        $r = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

		if ( $info['http_code'] == 401 ){
			delete_site_option('_placeling_access_token');
			delete_site_option('_placeling_access_secret');
			//die("no good access_key");
		} else if ( $info['http_code'] != 200 ){
			die("can't connect to Placeling server");
		}

        $user = json_decode( $r );

        $lat = $user->lat;
        $lng = $user->lng;

        wp_enqueue_script( 'jquery' );
 ?>

 <script type="text/javascript">

    jQuery(document).ready(function() {
        jQuery("#placeling_default_zoom").change( function(){
            var url = jQuery("#placeling_map").attr("src");

            url = url.replace(/zoom=.*/, "zoom=" + jQuery("#placeling_default_zoom").val() );

            jQuery("#placeling_map").attr("src", url);

        });
    });

 </script>

<div class="wrap">
<?php screen_icon(); ?>
<h2>Placeling Plugin Options</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'placeling-settings-group' ); ?>

    <table class="form-table">
        <tr valign="top">
        <th scope="row">Placeling Footers Link to Page:</th>
        <td style="border:none;">

        <select type="text" name="placeling_linking_page"  />
            <?php
                $page_id = get_option('placeling_linking_page');
                foreach (get_pages( array() ) as $page ){
                    if ( $page->ID == $page_id ){
                        echo "<option value='$page->ID' SELECTED>$page->post_title</option>";
                    }else {
                        echo "<option value='$page->ID'>$page->post_title</option>";
                    }
                }
            ?>
        </select>

        </td>
        </tr>
        <tr valign="top">
            <th scope="row">Open Map Marker Clicks in New Window:</th>
            <td style="border:none;">
                <input type="checkbox" name="placeling_marker_new_window" value="1"<?php checked( 1 == get_option( 'placeling_marker_new_window', 1 ) ); ?> />
            </td>
        </tr>
        <tr>
            <th scope="row">
                Default Map Zoom
            </th>
            <td>
                <select type="text" name="placeling_default_zoom" id="placeling_default_zoom" />
                    <?php
                        $zoom = get_option('placeling_default_zoom', 14);
                        for ($i=0; $i<=20; $i++){
                            if ( $zoom == $i ){
                                echo "<option value='$i' SELECTED>$i</option>";
                            }else {
                                echo "<option value='$i'>$i</option>";
                            }
                        }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan="2"><img id='placeling_map' src="http://maps.googleapis.com/maps/api/staticmap?center=<?php echo $lat ?>,<?php echo $lng ?>&sensor=false&size=500x300&zoom=<?php echo get_option( 'placeling_default_zoom', 14 ); ?>"><br>
                <a target="_blank" href="<?php echo $GLOBALS['PLACELING_WEB_HOSTNAME'] ?>/users/<?php echo $user->username?>/location">Update home location</a> <span style="color:grey;font-size: 0.8em">You will need to refresh this page to see your updated location</span>
            </td>
        </tr>
    </table>

    <?php submit_button(); ?>

</form>
<br>
<h2>Account Options</h2>


    <form method="post" action="<?php echo plugins_url( 'clear_credentials.php' , __FILE__ ) ?>">
        <table class="form-table">
            <tr>
                <th scope="row">
                    Login
                </th>
                <td>
                <img style="max-width: 64px" src="<?php echo $user->picture->thumb_url ?>">
                <b><?php echo $user->username; ?></b>
                    <?php submit_button("Logout", "secondary"); ?>
                </td>
            </tr>
        </table>
    </form>
<?php } ?>



<br>
<h2>Plugin Upgrade Options</h2>
   <iframe src="<?php echo $GLOBALS['PLACELING_WEB_HOSTNAME'] ?>/bloggers/matrix" width="800px" height="600px">You need iFrames enabled to see this</iframe>
<?php
}
?>