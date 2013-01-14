<?php

add_action( 'admin_menu', 'placeling_plugin_menu' );

function placeling_plugin_menu(){
    add_options_page( "Placeling Options", "Placeling", "manage_options",'placeling_options', 'placeling_settings_page' );
    add_action( 'admin_init', 'register_placelingsettings' );
}

function register_placelingsettings() {
	//register our settings
	register_setting( 'placeling-settings-group', 'placeling_linking_page' );
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
?>

<div class="wrap">
<?php screen_icon(); ?>
<h2>Placeling Plugin Options</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'placeling-settings-group' ); ?>

    <table class="form-table">
        <tr valign="top">
        <th scope="row">Placeling Footers Link to Page:</th>
        <td>

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
    </table>

    <?php submit_button(); ?>

</form>
<br>
<?php
    $username = get_site_option( '_placeling_username', false, true);
	$accessToken = get_site_option('_placeling_access_token', false, true);
	$secretToken = get_site_option('_placeling_access_secret', false, true);

	if ( empty($accessToken) || empty($secretToken) || $accessToken == "" || $secretToken == "" ) {
     ?>
        <p>You haven't connected a placeling account, <a href='<?php echo plugins_url( 'popup/index.php?placelingsrc=admin' , __FILE__ ); ?>' class='thickbox' alt='foo' title='Tag Place'> connect now.</a></p>

<?php
    } else {
 ?>

    <form method="post" action="<?php echo plugins_url( 'clear_credentials.php' , __FILE__ ) ?>">
        <h3>Logged in as <?php echo $username; ?></h3>
        <?php submit_button("Logout", "secondary"); ?>
    </form>
<?php }
    echo "</div>";
}
?>