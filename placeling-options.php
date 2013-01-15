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
    <style type="text/css">
      .wrap {
        font-family: "HelveticaNeue-Light","Helvetica Neue Light","Helvetica Neue",sans-serif;
        color: #464646;
        padding: 0px;
        margin: 0px;

      }

      table {
        border-collapse: collapse;
      }

      th {
        padding: 10px 0px;
      }

      td {
        border: 1px solid #464646;
      }

      .noborder {
        border: none;
      }

      ul {
        list-style-position:inside;
        margin: 0px 0px 0px 15px;
        padding: 0px;
      }

      li {
        padding: 0px;
        margin: 0px;
      }

      .center {
        text-align: center;
      }

      .embiggen {
        font-size: 24px;
      }

      .title {
        width: 120px;
      }

      .feature {
        width: 180px;
        padding: 5px;
      }

      .upgrade {
        padding-top: 5px;
      }

      .upgrade a {
        font-size: 18px;
        font-weight: 800;
      }
    </style>

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
<?php } ?>



<br>
<h2>Plugin Upgrade Options</h2>
    <table>
          <tr>
            <th class="noborder"></td>
            <th class="center embiggen title">Basic</td>
            <th class="center embiggen title">Simple</td>
            <th class="center embiggen title">Premiere</td>
          </tr>
          <tr>
            <td class="noborder feature">Tag any post or page with a place</td>
            <td class="center">&#10003;</td>
            <td class="center">&#10003;</td>
            <td class="center">&#10003;</td>
          </tr>
          <tr>
            <td class="noborder feature">Mobile optimized</td>
            <td class="center">&#10003;</td>
            <td class="center">&#10003;</td>
            <td class="center">&#10003;</td>
          </tr>
          <tr>
            <td class="noborder feature">View nearby places as</td>
            <td></td>
            <td></td>
            <td></td>
          </tr>
          <tr>
            <td class="noborder feature"><ul><li>Map</li></ul></td>
            <td class="center">&#10003;</td>
            <td class="center">&#10003;</td>
            <td class="center">&#10003;</td>
          </tr>
          <tr>
            <td class="noborder feature"><ul><li>List</li></ul></td>
            <td></td>
            <td class="center">&#10003;</td>
            <td class="center">&#10003;</td>
          </tr>
          <tr>
            <td class="noborder feature"><ul><li>Photos</ul></li></td>
            <td></td>
            <td></td>
            <td class="center">&#10003;</td>
          </tr>
          <tr>
            <td class="noborder feature">Daily nearby places views</td>
            <td class="center">1,000</td>
            <td class="center">Unlimited<sup>1</sup></td>
            <td class="center">Unlimited<sup>1</sup></td>
          </tr>
          <tr>
            <td class="noborder feature">Full CSS control</td>
            <td></td>
            <td></td>
            <td class="center">&#10003;</td>
          </tr>
          <tr>
            <td class="noborder feature">Custom map pins</td>
            <td></td>
            <td></td>
            <td class="center">&#10003;</td>
          </tr>
          <tr>
            <td class="noborder feature">Price</td>
            <td class="center">Free</td>
            <td class="center">$10/month</td>
            <td class="center">$20/month</td>
          </tr>
          <tr>
            <td class="noborder"></td>
            <td class="noborder"></td>
            <td class="noborder center upgrade"><a href="https://www.placeling.com/bloggers/plans?click=10" target="_blank">Upgrade</a></td>
            <td class="noborder center upgrade"><a href="https://www.placeling.com/bloggers/plans?click=20" target="_blank">Upgrade</a></td>
          </tr>
        </table>
        <p>1. Congrats on having such a popular site. We'll help you get your own Google Maps key for unlimited maps views</p>
   </div>
<?php
}
?>