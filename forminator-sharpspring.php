<?php

/*
Plugin Name: SharpSpring Addon for Forminator
Plugin URI: https://www.iqnection.com
Description: Create leads in SharpSpring with Forminator submissions
Version: 1.0.0
Author: Mike Eckert
*/

define( 'FORMINATOR_ADDON_SHARPSPRING_VERSION', '1.0' );
define( 'SHARPSPRING_TRACKING_COOKIE_NAME', '__ss_tk' );


if (is_admin()) {
    add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'sharpspring_manage_links', 10, 1);
    add_action( 'admin_init', 'sharpspring_has_forminator_plugin' );
}

function sharpspring_manage_links($actions) {
    $actions['configure'] = '<a href="' . admin_url( 'admin.php?page=forminator-integrations' ) . '">Configure</a>';
	return $actions;
}

function sharpspring_has_forminator_plugin() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'forminator/forminator.php' ) ) {
        add_action( 'admin_notices', 'forminator_missing_notice' );

        deactivate_plugins( plugin_basename( __FILE__ ) );

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}


function forminator_missing_notice(){
	?><div class="error"><p>Sorry, but the SharpSpring plugin for Forminator requires Forminator to be installed and activated.</p></div><?php
}

if ( ! function_exists( 'forminator_plugin_sharpspring_addon_url' ) ) {
	function forminator_plugin_sharpspring_addon_url() {
		return trailingslashit( plugin_dir_url( __FILE__ ) );
	}
}

function sharpspring_user_tracking_id() {
    if ( (isset($_COOKIE[SHARPSPRING_TRACKING_COOKIE_NAME])) && (!empty($_COOKIE[SHARPSPRING_TRACKING_COOKIE_NAME])) ) {
        return preg_replace('/\|/','_', $_COOKIE[SHARPSPRING_TRACKING_COOKIE_NAME]);
    }
}

function forminator_addon_sharpspring_url() {
	return trailingslashit( forminator_plugin_sharpspring_addon_url() . 'addon' );
}

function forminator_addon_sharpspring_dir() {
	return trailingslashit( dirname( __FILE__ ) );
}

function forminator_addon_sharpspring_assets_url() {
	return trailingslashit( forminator_plugin_sharpspring_addon_url() . 'assets' );
}

require_once $_SERVER['DOCUMENT_ROOT'].'/wp-admin/includes/plugin.php';

require_once realpath(__DIR__.'/../forminator/forminator.php');
require_once forminator_plugin_dir() . 'library/class-addon-loader.php';
require_once dirname( __FILE__ ) . '/class-forminator-addon-sharpspring-exception.php';
require_once dirname( __FILE__ ) . '/class-forminator-addon-sharpspring-form-settings.php';

require_once dirname( __FILE__ ) . '/class-forminator-addon-sharpspring-form-settings.php';
require_once dirname( __FILE__ ) . '/class-forminator-addon-sharpspring-form-hooks.php';


add_action( 'forminator_addons_loaded', 'load_forminator_addon_sharpspring' );
function load_forminator_addon_sharpspring() {
	require_once dirname( __FILE__ ) . '/class-forminator-addon-sharpspring.php';
	if ( class_exists( 'Forminator_Addon_Loader' ) ) {
		Forminator_Addon_Loader::get_instance()->register( 'Forminator_Addon_SharpSpring' );
	}
}

//Direct Load
//Forminator_Addon_Loader::get_instance()->register( 'Forminator_Addon_SharpSpring' );
