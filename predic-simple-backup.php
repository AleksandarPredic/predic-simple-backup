<?php

/**
 * Plugin Name:       Very very simple backup for WordPress
 * Plugin URI:        
 * Description:       I am making this plugin for my needs and for small sites that don't need fancy plugins for backup jobs. This plugin zip whole WordPress and add database dump into zip. 
 * Version:           1.0.0
 * Author:            Aleksandar Predic
 * Author URI:        http://acapredic.com/
 * License:           
 * License URI:       
 * 
 * Requires at least: 3.9
 * Tested up to: 4.4.2
 * 
 * Text Domain:       predic-simple-backup
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks if any.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-predic-simple-backup.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_predic_simple_backup() {

    $plugin = new Predic_Simple_Backup();
	$plugin->run();

}
run_predic_simple_backup();
?>
