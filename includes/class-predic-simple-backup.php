<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Predic_Simple_Backup {
    
    /**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	public $plugin_name;
    
    /**
	 * The plugin public name.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $plugin_name    The name for plugin, not the identifier but just a name.
	 */
	public $plugin_public_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $version    The current version of the plugin.
	 */
	public $version;
    
    /**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
    public function __construct() {
        $this->plugin_name = 'predic-simple-backup';
		$this->version = '1.0.0';
    }
    
    /**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Predic_Simple_Backup_Admin. Defines all hooks and functionality for the admin area.
	 * - Predic_Simple_Backup_i18n. Defines internationalization functionality.
	 *
	 * Include all classes needed for plugin
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
        
        /**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __FILE__ ) . 'class-predic-simple-backup-i18n.php';
        
        /**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-predic-simple-backup-admin.php';
        
    }
    
    /**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
        
        $plugin_admin = new Predic_Simple_Backup_Admin( $this->plugin_name, $this->version );
        
        // Add menu page
        add_action( 'admin_menu', array( $plugin_admin, 'add_menu_page' ) );
        
        // Add backup process action
        add_action( 'admin_post_start_predic_simple_backup', array( $plugin_admin, 'make_site_backup' ) );
    }
    
    /**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Predic_Simple_Backup_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Predic_Simple_Backup_i18n();
		$plugin_i18n->set_domain( $this->plugin_name );

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );

	}
    
    /**
	 * Load dependencies and execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
	}
    
}