<?php
ini_set( 'display_errors', 0 );
require( '../../../../wp-load.php' );

define('DONOTCACHEPAGE',1);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

child_validate_settings_form($_POST['action'], $_POST['form_data']);
?>