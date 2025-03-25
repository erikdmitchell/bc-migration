<?php
/**
 * BC Migration class
 *
 * @package erikdmitchell\bcmigration\cli
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration;

use erikdmitchell\bcmigration\cli\CLI;

/**
 * BC Migration class.
 */
class BCMigration {

    /**
     * The single instance of the class.
     *
     * @var BCMigration|null
     */
    protected static ?BCMigration $instance = null;

    /**
     * Constructor.
     *
     * @return void
     */
    private function __construct() {}

    /**
     * Gets the single instance of the class.
     *
     * @return BCMigration Single instance of the class.
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new self();

            if ( defined( 'WP_CLI' ) && WP_CLI ) {
                new CLI();
            }
        }

        return self::$instance;
    }
}
