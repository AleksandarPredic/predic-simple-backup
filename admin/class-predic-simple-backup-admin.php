<?php

class Predic_Simple_Backup_Admin {
    
    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->plugin_public_name = esc_html_x( 'Very Simple Backup', 'plugin public name and admin menu page name', 'predic-simple-backup' );

	}
    
    public function add_menu_page() {
        add_menu_page( 
            $this->plugin_public_name . esc_html__( 'Options', 'predic-simple-backup' ), //  The text to be displayed in the title tags of the page when the menu is selected
            $this->plugin_public_name, // The text to be used for the menu
            'manage_options', // The capability required for this menu to be displayed to the user
            $this->plugin_name . '-page', //  The slug name to refer to this menu by (should be unique for this menu)
            array( $this, 'render_admin_page' ), // The function to be called to output the content for this page
            'dashicons-index-card', // Menu icon - https://developer.wordpress.org/resource/dashicons/
            79 // Position
        );
    }
    
    public function render_admin_page() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        
        /*
         * DODATI OVDE NOUNCE ZA SVAKI SLUCAJ
         * 
         * napraviti da se dole listaju svi backup zip arhive sa velicinama fajla i datumom
         */
        ?>

        <div class="psb-admin-page wrap">
            <h1><?php echo sprintf( esc_html__( '%s Settings', 'predic-simple-backup' ), $this->plugin_public_name ); ?></h1>

           <div class="psb-admin-page-content">
               <p>SOME TEXT TO BE TRANSLATED</p>

           </div>

            <div id="psb-admin-page-form">
                <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                    <input type="hidden" name="action" value="start_predic_simple_backup">
                    <input type="submit" value="<?php echo esc_html__( 'Backup now', 'predic-simple-backup' ); ?>">
                </form>
            </div>
        </div>

        

        <?php
    }
    
    public function make_site_backup() {
        
        /*
        * PHP: Recursively Backup Files & Folders to ZIP-File
        * plugin for very simple backup (database dump and whole wordpress zip)
        */
        
        // Define files and folders
        $upload_dir = wp_upload_dir();
        $uploads_basedir = $upload_dir['basedir']; // Uploads basedir without slash
        $uploads_basedurl = $upload_dir['baseurl']; // Uploads basedir without slash
        $directory = ABSPATH; // With end trailing slash

        // Folder to backup and zip name and path
        $zip_name = strtolower( sanitize_file_name( get_bloginfo( 'name' ) ) . date("Y-m-d-h-i-sa") ) .".zip";
        $destination = $uploads_basedir . '/' . $zip_name; // Destination dir and filename
        $destination_download_link = $uploads_basedurl . '/' . $zip_name; // Destination url and filename
        $source = ABSPATH; // the folder which you archivate


        // Make sure the script can handle large folders/files
        $this->bypass_server_limit();

        // Start the backup!
        if ( $this->zipData($source, $destination) ) {
            echo 'Finished.';
            echo '<a href="' . $destination_download_link . '" target="_blank">Click here</a> to download your backup zip archive.';
        } else {
            echo 'Operation failed';
        }
        
    }
    
    // Here the magic happens :)
    private function zipData($source, $destination) {

        $upload_dir = wp_upload_dir();
        // Uploads basedir without slash
        $uploads_basedir = $upload_dir['basedir'];

        if (extension_loaded('zip')) {
                if (file_exists($source)) {
                    $zip = new ZipArchive();
                    if ($zip->open($destination, ZIPARCHIVE::CREATE)) {
                            $source = realpath($source);
                            if (is_dir($source)) {
                                    $iterator = new RecursiveDirectoryIterator($source);
                                    // skip dot files while iterating 
                                    $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
                                    $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
                                    foreach ($files as $file) {
                                            $file = realpath($file);
                                            if (is_dir($file)) {
                                                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                                            } else if (is_file($file)) {
                                                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                                            }
                                    }
                            } else if (is_file($source)) {
                                    $zip->addFromString(basename($source), file_get_contents($source));
                            }

                            // Try to export database and add it to the zip
                            try {
                                $database_add_to_root = sanitize_file_name( 'database-' . DB_NAME . date("Y-m-d") ) . '.sql';
                                $database_filename = $uploads_basedir . '/' . $database_add_to_root;
                                exec('mysqldump --add-drop-table --user=' . DB_USER . ' --password=' . DB_PASSWORD . ' --host=' . DB_HOST . ' ' . DB_NAME . ' > ' . $database_filename);
                                $zip->addFromString(str_replace($source . '/', '', $database_add_to_root), file_get_contents($database_filename));
                                unlink($database_filename);

                                echo "<span style='color:green;'>database export file is added to zip file.<br /></span> " ;

                            } catch(Exception $e) {
                              echo 'Error message (there was something wrong while exporting database): ' .$e->getMessage();
                            }

                    }
                    return $zip->close();
                }
        }
        return false;
    }
    
    /**
    * Bypass limit server if possible
    * @since 1.0.0
    */
   public static function bypass_server_limit() {
       @ini_set('memory_limit','1024M');
       @ini_set('max_execution_time','0');
   }
    
}
