<?php
// If uninstall not called from WordPress exit
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
exit ();

// Delete option from options table
delete_option( 'cdp_article_settings' );
delete_option( 'cdp_behaviour_settings' );
delete_option( 'cdp_category_settings' );
delete_option( 'cdp_domaineid' );
delete_option( 'cdp_general_settings' );
delete_option( 'cdp_personal_settings' );
delete_option( 'cdp_theme_settings' );
delete_option( 'cdp_version_settings' );
delete_option( 'cdp_widget_contextuelle_settings' );
delete_option( 'cdp_widget_settings' );
delete_option( 'cdp_widget_settings_aftercontent' );
delete_option( 'cdp_blogid' );
//remove any additional options and custom tables
?>