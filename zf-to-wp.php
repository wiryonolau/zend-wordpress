<?php
/**
 * @package ZF to Wordpress
 * @version 3.0
 */
/*
Plugin Name: ZF to Wordpress
Plugin URI: http://zendmaniacs.com/products/zf-to-wp-plugin
Description: This plugin give possiblity to extend Wordpress featured direct from Zend Framework 3 Application
Author: ZendManiacs
Version: 3.0
License: GPLv2 or later
Author URI: http://www.zendmaniacs.com
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2015-2017 Zendmaniacs.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'ZF_VERSION', '3.0.0' );
define( 'ZF__MINIMUM_WP_VERSION', '3.0' );
define( 'ZF__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ZF__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


register_activation_hook( __FILE__, array( 'ZF', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'ZF', 'plugin_deactivation' ) );

require_once( ZF__PLUGIN_DIR . 'ZfPlugin.php' );
ZfPlugin::initApplication();
add_action( 'init', array( 'ZfPlugin', 'init' ) );
add_filter( 'posts_results', array( 'ZfPlugin', 'posts' ) );
add_action( 'template_redirect', array('ZfPlugin','templateRedirect'));
add_action( 'widgets_init', array('ZfPlugin','registerWidgets'));
add_action( 'admin_menu', array('ZfPlugin','registerAdminNavigation') );
