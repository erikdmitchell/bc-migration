<?php
/**
 * Subprocessors CLI class
 *
 * @package erikdmitchell\bcmigration\cli
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use WP_CLI;

/**
 * Subprocessors CLI class.
 */
class Subprocessors extends CLICommands {

    /**
     * Construct
     */
    public function __construct() {}

    /**
     * Register the commands.
     *
     * @return void
     */
    public static function register_commands() {
        $parent             = 'boomi migrate subprocessors';
        $supported_commands = array( 'subscribe-data' );

        foreach ( $supported_commands as $command ) {
            $synopsis = array();

            if ( 'subscribe-data' === $command ) {
                $shortdesc = 'Migrate subprocessors subscribe data';
                $longdesc  = 'Migrate subprocessors subscribe data';
                $method    = 'migrate';
            }

            WP_CLI::add_command(
                "{$parent} {$command}",
                array( __NAMESPACE__ . '\Subprocessors', $method ),
                array(
                    'shortdesc' => $shortdesc,
                    'longdesc'  => $longdesc,
                    'synopsis'  => $synopsis,
                )
            );
        }
    }

    /**
     * Migrate subprocessors subscribe data.
     */
    public function migrate( $args, $assoc_args ) {
        WP_CLI::log( 'Migrating subprocessors subscribe data...' );

        $message = '';

        WP_CLI::log( 'Migration complete.' );
    }

    private function handle_shortcode_search() {
        $shortcode = 'subprocessors-subscribe';
        $post_types = get_post_types( array( 'public' => true ), 'names' );
        unset( $post_types['attachment'] ); // we don't want to search for attachments.
        

        foreach ( $post_types as $post_type ) {
                array(
                    'post_type' => $post_type,
                    'shortcode' => $shortcode,
                );
        }

        // $this->process_all->save()->dispatch();
    }
}
