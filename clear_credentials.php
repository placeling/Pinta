<?php
    include_once('../../../wp-config.php');

	delete_site_option( '_placeling_access_token' );
	delete_site_option( '_placeling_access_secret' );
	delete_site_option( '_placeling_username' );

    $placeling_url = admin_url("options-general.php?page=placeling_options");

    header( "Location:$placeling_url" ) ;
?>