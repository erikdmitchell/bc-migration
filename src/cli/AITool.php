<?php
/**
 * AI Tool CLI class
 *
 * @package erikdmitchell\bcmigration\cli
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use erikdmitchell\bcmigration\aitool\MigrateLikes;
use erikdmitchell\bcmigration\aitool\MigrateReports;
use WP_CLI;

/**
 * AITool CLI class.
 */
class AITool extends CLICommands {

    /**
     * Construct
     */
    public function __construct() {


        // add_action( 'bc_legacy_tool_likes_process_complete_aitool', array( $this, 'remove_ai_tool_uploads_folder' ) );

        
    }

    public function migrate( $args, $assoc_args ) {
        list ( $action ) = $args;

        if (empty( $action ) ) {
            WP_CLI::error( 'Invalid arguments. Requires action and post_id' );
        }

        switch ( $action ) {
            case 'reports':
                $this->migrate_reports();
                break;
            case 'likes':
                $this->migrate_likes();
                break;
            default:
                WP_CLI::error( 'Invalid action.' );
                break;
        }
    }

    /**
     * Migrates AI Tool reports to the database.
     *
     * This method will log messages to the user about the migration process and
     * report the number of migrated records.
     */
    private function migrate_reports() {
        WP_CLI::log( 'Migrating AI Tool reports...' );

        $migrated_data = MigrateReports::init()->migrate_data();

        if ( empty( $migrated_data ) ) {
            WP_CLI::log( 'No data to migrate.' );

            return;
        }

        WP_CLI::success( count( $migrated_data ) . ' AI Tool reports migrated successfully.' );
    }

    private function migrate_likes() {
        WP_CLI::log( 'Migrating AI Tool likes...' );

        $migrated_data = MigrateLikes::init()->migrate_data();

        if ( empty( $migrated_data ) ) {
            WP_CLI::log( 'No data to migrate.' );

            return;
        }

        WP_CLI::success( count( $migrated_data ) . ' AI Tool likes migrated successfully.' );        
    }

 
    
    public function remove_ai_tool_uploads_folder() {
        error_log( 'bc_update_521_remove_ai_tool_uploads_folder - false' );
        return false;
        $upload_dir = wp_upload_dir( null, false );
        $bc_logger  = boomi_get_logger();

        $deleted = BC_Filesystem::getInstance()->delete( $upload_dir['basedir'] . '/bc-ai-tool-data' );

        if ( $deleted ) {
            $bc_logger->info(
                sprintf(
                    __( 'AI Tool uploads folder deleted.', 'boomi-cms' )
                ),
                array(
                    'source' => 'boomi-ai-tool-uploads-folder-update',
                )
            );
        } else {
            $bc_logger->error(
                sprintf(
                    __( 'AI Tool uploads folder deletion failed.', 'boomi-cms' )
                ),
                array(
                    'source' => 'boomi-ai-tool-uploads-folder-update',
                )
            );
        }

        delete_option( '_bc_ai_tool_page_id' );
    }
    

    

    


}
