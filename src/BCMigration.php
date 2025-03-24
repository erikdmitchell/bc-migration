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

echo "BCMigration";

/**
 * BC Migration class.
 */
class BCMigration {

    /**
     * The single instance of the class.
     *
     * @var boolean
     */
    private static $instance = false;

    /**
     * Constructor.
     * 
     * @return void
     */
    private function __construct() {
        add_action( 'init', array( $this, 'includes' ) );
echo "foo";        
    }

    /**
     * Gets the single instance of the class.
     *
     * @return BCMigration Single instance of the class.
     */
    public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();

            if (defined('WP_CLI') && WP_CLI) {
                new CLI();
            }
		}

		return self::$instance;
    }

    public function includes() {
        if (is_admin()) {
            new Admin();
        }
    }

}