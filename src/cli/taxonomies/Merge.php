<?php
/**
 * Merge Taxonomies CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
 * @version 0.2.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\TaxonomyCLICommands;

/**
 * Merge Class
 */
class Merge extends TaxonomyCLICommands {

	/**
	 * Merge terms within a taxonomy.
	 *
	 * ## OPTIONS
	 *
	 * [<taxonomy>]
	 * : The taxonomy name.
	 *
	 * [<from_terms>]
	 * : Pipe-separated list of terms to merge.
	 *
	 * [<to_term>]
	 * : Term to merge into.
	 *
	 * [--post-type=<post_type>]
	 * : Post type to migrate.
	 *
	 * [--file=<file>]
	 * : Path to CSV file for batch merge.
	 *
	 * [--delete-old]
	 * : Delete the old terms after merging.
	 *
	 * [--dry-run]
	 * : Simulate actions without making changes.
	 *
	 * [--log=<logfile>]
	 * : Path to a log file for results.
	 *
	 * ## EXAMPLES
	 *
	 *     wp boomi taxonomies merge products "B2B Integration|CRM Integration" "Integration" --delete-old
	 *     wp boomi taxonomies merge --file=merge-terms.csv --dry-run --log=merge.log
	 *
	 * @param string[]             $args       CLI positional arguments.
	 * @param array<string, mixed> $assoc_args CLI associative arguments.
	 *
	 * @return void
	 */
	public function merge_terms( $args, $assoc_args ) {
		$dry_run    = isset( $assoc_args['dry-run'] );
		$delete_old = isset( $assoc_args['delete-old'] );
		$post_type  = $assoc_args['post-type'] ?? 'any';
		$log_name   = $assoc_args['log'] ?? null;

		if ( $log_name ) {
			$this->set_log_name( $log_name );
		}

		// Batch merge.
		if ( isset( $assoc_args['file'] ) ) {
			if ( is_valid_file( $assoc_args['file'] ) ) {
				$this->process_csv( $assoc_args['file'], $delete_old, $dry_run, $post_type );
			}

			$this->display_notices();

			return;
		}

		// Single command.
		$this->process_single_term( $args, $dry_run, $delete_old, $post_type );

		$this->display_notices();
	}

	/**
	 * Processes a CSV file containing taxonomies, from terms, and destination terms to merge.
	 *
	 * @param string $file        Path to the CSV file.
	 * @param bool   $delete_old  If set, delete the old terms after merging.
	 * @param bool   $dry_run     If set, simulate actions without making changes.
	 * @param string $post_type   Post type to migrate.
	 *
	 * @return void
	 */
	private function process_csv( string $file, bool $delete_old = false, bool $dry_run = false, string $post_type = 'any' ) {
		$rows    = array_map(
			function ( $line ) {
				return str_getcsv( $line, ',', '"', '\\' );
			},
			file( $file )
		);
		$headers = array_map( 'trim', array_shift( $rows ) );

		if ( ! $this->validate_headers( $headers, array( 'taxonomy', 'from_terms', 'to_term' ) ) ) {
			return;
		}

		foreach ( $rows as $i => $row ) {
			$row_num  = $i + 2;
			$data     = array_combine( $headers, $row );
			$data     = array_map( 'trim', $data );
			$taxonomy = $data['taxonomy'];

			// skip empty lines.
			if ( count( $row ) === 1 && empty( trim( $row[0] ) ) ) {
				continue;
			}

			// Check required fields.
			if ( ! $this->has_required_fields( $data, array( 'taxonomy', 'from_terms', 'to_term' ), $row_num ) ) {
				continue;
			}

			$taxonomy = $this->validate_taxonomy( $taxonomy );

			if ( is_wp_error( $taxonomy ) ) {
				$this->invalid_taxonomy( $taxonomy, $row_num );

				continue;
			}

			$from_terms = explode( '|', $data['from_terms'] );
			$to_term    = $data['to_term'];

			if ( $dry_run ) {
				$this->dry_run_result( $taxonomy, $from_terms, $to_term, $row_num );

				continue;
			}

			$result = $this->merge( $taxonomy, $from_terms, $to_term, $delete_old, $post_type, $row_num );

			if ( is_wp_error( $result ) ) {
				$this->add_notice( "Row $row_num: Error - " . $result->get_error_message(), 'warning' );
			}
		}

		$this->add_notice( $dry_run ? 'Dry run complete.' : 'Batch merge complete.', 'success' );
	}

	/**
	 * Process a single taxonomy term merge command.
	 *
	 * @param string[] $args     CLI arguments.
	 * @param bool     $dry_run  If set, simulate actions without making changes.
	 * @param bool     $delete_old  If set, delete the old terms after merging.
	 * @param string   $post_type  Post type to migrate.
	 *
	 * @return void
	 */
	private function process_single_term( array $args, $dry_run, $delete_old, $post_type ) {
		$taxonomy = $this->validate_taxonomy( $args[0] );

		if ( is_wp_error( $taxonomy ) ) {
			$this->add_notice( $taxonomy->get_error_message(), 'error' );

			return;
		}

		if ( ! $this->validate_command_args( $args, 3, 3 ) ) {
			$this->add_notice( 'Invalid arguments. Usage: wp taxonomy merge_terms <taxonomy> <from_terms> <to_term>', 'error' );

			return;
		}

		$from_terms = explode( '|', $args[1] );
		$to_term    = $args[2];

		if ( $dry_run ) {
			$message = '[DRY RUN] Would merge ' . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)";

			$this->log( $message );

			return;
		}

		$result = $this->merge( $taxonomy, $from_terms, $to_term, $delete_old, $post_type );

		if ( is_wp_error( $result ) ) {
			$this->add_notice( 'Error - ' . $result->get_error_message(), 'warning' );
		} else {
			$this->log( 'Merged ' . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)" );
			$this->add_notice( 'Merged ' . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)", 'success' );
		}

		$this->display_notices();
	}

	/**
	 * Merges multiple terms into a single term.
	 *
	 * @param string   $taxonomy     The taxonomy to merge within.
	 * @param string[] $from_terms   Array of term names to merge.
	 * @param string   $to_term_name The term to merge into.
	 * @param bool     $delete_old   If true, delete the old terms after merging.
	 * @param string   $post_type    Post type to migrate.
	 * @param int      $row_num      The row number (for logging purposes).
	 *
	 * @return bool If the merge was successful.
	 */
	protected function merge( $taxonomy, $from_terms, $to_term_name, $delete_old, $post_type, $row_num = null ) {
		$to_term = get_term_by( 'name', $to_term_name, $taxonomy );

		if ( ! $to_term || is_wp_error( $to_term ) ) {
			if ( $row_num ) {
				$message = "Row {$row_num}: Target term '{$to_term_name}' does not exist in taxonomy '{$taxonomy}'. Skipping.";
			} else {
				$message = "Target term '{$to_term_name}' does not exist in taxonomy '{$taxonomy}'. Skipping.";
			}

			$this->add_notice( $message, 'warning' );

			$this->log( "[SKIPPED] $message" );

			return false;
		}

		foreach ( $from_terms as $from_name ) {
			$from_term = get_term_by( 'name', trim( $from_name ), $taxonomy );

			if ( ! $from_term || is_wp_error( $from_term ) ) {
				$message = ( $row_num ? "Row $row_num: " : '' ) . "From term '{$from_name}' does not exist in taxonomy '{$taxonomy}'. Skipping.";

				$this->add_notice( $message, 'warning' );

				$this->log( "[SKIPPED] $message" );

				continue;
			}

			// Get all posts with this term.
			$posts = get_posts(
				array(
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'post_type'      => $post_type,
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => $taxonomy,
							'field'    => 'term_id',
							'terms'    => $from_term->term_id,
						),
					),
					'fields'         => 'ids',
				)
			);

			if ( empty( $posts ) ) {
				$message = ( $row_num ? "Row $row_num: " : '' ) . "No posts found for '$from_name' in '$taxonomy'";

				$this->log( $message );

				$this->add_notice( $message, 'warning' );
			} else {
				foreach ( $posts as $post_id ) {
					$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );

					if ( ! in_array( $to_term->term_id, $terms, true ) ) {
						$terms[] = $to_term->term_id;

						wp_set_post_terms( $post_id, array( $to_term->term_id ), $taxonomy, true );
					}
				}
			}

			if ( $delete_old ) { // TODO: we have a delete CLI class, maybe use that instead?
				if ( ! is_wp_error( wp_delete_term( $from_term->term_id, $taxonomy ) ) ) {
					$message = ( $row_num ? "Row $row_num: " : '' ) . "Deleted term '$from_name'";

					$this->log( $message );

					$this->add_notice( $message, 'success' );
				} else {
					$message = ( $row_num ? "Row $row_num: " : '' ) . "Failed to delete term '$from_name'";

					$this->log( $message );

					$this->add_notice( $message, 'warning' );
				}
			} else {
				$message = ( $row_num ? "Row $row_num: " : '' ) . "Merged term '$from_name'";

				$this->log( $message );

				$this->add_notice( $message, 'success' );
			}
		}

		return true;
	}
}
