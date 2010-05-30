<?php
/*
Plugin Name: Simple Fields
Plugin URI: http://eskapism.se/code-playground/simple-fields/
Description: Add groups of textareas, input-fields, dropdowns, radiobuttons, checkboxes and files to your edit post screen.
Version: 0.2.1
Author: Pär Thernström
Author URI: http://eskapism.se/
License: GPL2
*/

/*  Copyright 2010  Pär Thernström (email: par.thernstrom@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// if called directly, load wordpress
if (isset($_GET["wp_abspath"])) {
	define( 'WP_USE_THEMES', false );
	require( $_GET["wp_abspath"] . './wp-blog-header.php' );
}


define( "EASY_FIELDS_URL", WP_PLUGIN_URL . '/simple-fields/');
define( "EASY_FIELDS_NAME", "Simple Fields"); 
define( "EASY_FIELDS_VERSION", "0.2");
define( "EASY_FIELDS_FILE", "options-general.php?page=simple-fields-options"); // this still feels nasty...

// on admin init: add styles and scripts
add_action( 'admin_init', 'simple_fields_admin_init' );
add_action( 'admin_menu', "simple_fields_admin_menu" );
add_action( 'admin_head', 'simple_fields_admin_head' );
#add_action( 'wp_dashboard_setup', "cms_tpv_wp_dashboard_setup" );
#add_action( 'admin_head', "cms_tpv_admin_head" );


add_action( 'dbx_post_advanced', 'xxx' ); // denna verkar finnas om man kör cmspress och även wp3?
function xxx() {
	#global $wp_actions; d($wp_actions);
	#echo "xxxy";
	#global $post; d($post);
}

// Ajax hooks
#add_action('wp_ajax_cms_tpv_get_childs', 'cms_tpv_get_childs');
#add_action('wp_ajax_cms_tpv_move_page', 'cms_tpv_move_page');
#add_action('wp_ajax_cms_tpv_add_page', 'cms_tpv_add_page');
add_action('wp_ajax_simple_fields_field_group_add_field', 'simple_fields_field_group_add_field');

require("functions_admin.php");
require("functions_post.php");

