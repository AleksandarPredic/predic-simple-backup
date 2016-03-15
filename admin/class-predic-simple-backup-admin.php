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
        $this->plugin_admin_page = $this->plugin_name . '-page';

	}
    
    public function add_menu_page() {
        add_menu_page( 
            $this->plugin_public_name . esc_html__( 'Options', 'predic-simple-backup' ), //  The text to be displayed in the title tags of the page when the menu is selected
            $this->plugin_public_name, // The text to be used for the menu
            'manage_options', // The capability required for this menu to be displayed to the user
            $this->plugin_admin_page, //  The slug name to refer to this menu by (should be unique for this menu)
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
               <p><?php echo esc_html__( 'This plugin is for small sites that do not need fancy WP plugins for backup jobs. It zip all files from Your WP directory and add database dump into zip.', 'predic-simple-backup' ) ?></p>

           </div>

            <div id="psb-admin-page-form">
                <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                    <input type="hidden" name="action" value="start_predic_simple_backup">
                    <input type="submit" value="<?php echo esc_html__( 'Backup now', 'predic-simple-backup' ); ?>">
                </form>
            </div>
        </div>

        <?php
        // List all backed files
        $this->list_backup_files();
    }
    
    /*
     * dir or url
     * 
     * return path without trailing slash
     */
    private function get_backup_directory( $parth_or_url = 'dir' ) {
        
        $dir_name = 'predic-simple-backup';
        
        // Define files and folders
        $upload_dir = wp_upload_dir();
        $uploads_basedir = $upload_dir['basedir']; // Uploads basedir without slash
        $uploads_basedurl = $upload_dir['baseurl']; // Uploads basedir without slash
        
        // Make directory in uploads folder to store backups, if don't exist
        $backup_files_dir = $uploads_basedir . '/' . $dir_name;
        
        if ( ! file_exists(realpath($backup_files_dir) ) ) {
            if ( ! mkdir($backup_files_dir, 0755) ) {
                wp_die( esc_html__( 'Can not create parent directory to store backup files' ) );
            }
        }
        
        if ( $parth_or_url === 'dir' ) {
            return $backup_files_dir;
        } else {
            return $uploads_basedurl . '/' . $dir_name;
        }
        
        
    }
    
    public function make_site_backup() {

        /*
        * PHP: Recursively Backup Files & Folders to ZIP-File
        */
        
        // Define files and folders
        $backup_files_dir = $this->get_backup_directory();
        if ( ! $backup_files_dir ) {
            wp_die( esc_html__( 'Directory to store backup files does not exist' ) );
        }

        // Folder to backup and zip name and path
        $zip_name = strtolower( sanitize_file_name( get_bloginfo( 'name' ) ) . date("Y-m-d-h-i-sa") ) .".zip";
        $destination = $backup_files_dir . '/' . $zip_name; // Destination dir and filename
        $directory = ABSPATH; // The folder which you archivate
        
        
        // Make sure the script can handle large folders/files
        $this->bypass_server_limit();

        // Start the backup!
        if ( $this->zipData($directory, $destination) ) {

            // redirect to plugin page
            $admin_page_url = admin_url( 'admin.php?page=' . $this->plugin_admin_page );
            $this->redirect( $admin_page_url );

        } else {
            
            wp_die( esc_html__( 'Something went wront while archiving the site.' ) );
            
        }
        
    }
    
    // Here the magic happens :)
    private function zipData($directory, $destination) {

        $upload_dir = wp_upload_dir();
        // Uploads basedir without slash
        $uploads_basedir = $upload_dir['basedir'];
        // Set database name
        $database_add_to_root = sanitize_file_name( 'database-' . DB_NAME . date("Y-m-d") ) . '.sql';
        $database_filename = $uploads_basedir . '/' . $database_add_to_root;

        if (extension_loaded('zip')) {
            
            if (file_exists($directory)) {
                
                $zip = new ZipArchive();

                if ($zip->open($destination, ZIPARCHIVE::CREATE)) {

                        $directory = realpath($directory);
                        
                        if (is_dir($directory)) {
                            
                            $iterator = new RecursiveDirectoryIterator($directory);
                            // skip dot files while iterating 
                            $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
                            $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
                            
                            foreach ($files as $file) {
                                $file = realpath($file);
                                if (is_dir($file)) {
                                        $zip->addEmptyDir(str_replace($directory . '/', '', $file . '/'));
                                } else if (is_file($file)) {
                                        $zip->addFromString(str_replace($directory . '/', '', $file), file_get_contents($file));
                                }
                            }
                            
                        } else if (is_file($directory)) {
                                $zip->addFromString(basename($directory), file_get_contents($directory));
                        }

                        // Try to export database and add it to the zip
                        try {
                            exec('mysqldump --add-drop-table --user=' . DB_USER . ' --password=' . DB_PASSWORD . ' --host=' . DB_HOST . ' ' . DB_NAME . ' > ' . $database_filename);
                            $zip->addFromString(str_replace($directory . '/', '', $database_add_to_root), file_get_contents($database_filename));
                            unlink($database_filename);
                            
                           

                           // echo "<span style='color:green;'>database export file is added to zip file.<br /></span> " ;

                        } catch(Exception $e) {
                            
                            wp_die( esc_html__( 'Error message (there was something wrong while exporting database): ', 'predic-simple-backup' ) .$e->getMessage() );

                        }

                }

                if ( $zip->close() ) {
                    // Set mode for newly created archive file
                    chmod($destination, 0644);
                    return true;
                } else {
                    return false;
                }
                
            }
            
        }
        
        return false;
        
    }
    
    private function list_backup_files() {
        
        $backup_dir = $this->get_backup_directory();
        $backup_dir_url = $this->get_backup_directory( 'url' );
        
        $files = scandir( $backup_dir );
        
        echo '<h3>' . esc_html__( 'List of backups', 'predic-simple-backup' ) . '</h3>';
        
        echo '<ul>';
        
        foreach ( $files as $file ) {
            
            if ( $file === '.' || $file === '..' ) {
                continue;
            }
            
            echo '<li><a href="' . esc_url( $backup_dir_url . '/' . $file ) . '">' . $file . '</a></li>';
        }
        
        echo '</ul>';
        
    }
    
    private function redirect( $destination ) {
        wp_redirect( $destination ); 
        die();
    }
    
    /**
    * Bypass limit server if possible
    * @since 1.0.0
    */
   private function bypass_server_limit() {
       @ini_set('memory_limit','1024M');
       @ini_set('max_execution_time','0');
   }
    
}
