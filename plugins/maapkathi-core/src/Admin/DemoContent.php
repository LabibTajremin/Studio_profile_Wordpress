<?php
/**
 * "Demo content" buttons on the Maapkathi Dashboard — the no-terminal
 * way to run the same seeder as `wp maapkathi seed`.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Admin;

use Maapkathi\Core\Cli\SeedCommand;
use Maapkathi\Core\Roles\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Dashboard card and handles its form posts.
 */
final class DemoContent {

	public const ACTION = 'mk_seed_demo';

	/**
	 * Wires up the admin-post handler.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Prints the "Demo content" card. Only shown to users who manage settings.
	 *
	 * @return void
	 */
	public static function render_card(): void {
		if ( ! current_user_can( Roles::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		$seeded = (bool) get_option( 'mk_seed_installed' );
		$result = isset( $_GET['mk_demo'] ) ? sanitize_key( wp_unslash( $_GET['mk_demo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

		echo '<div class="mk-card"><h2>' . esc_html__( 'Demo content', 'maapkathi' ) . '</h2>';

		if ( 'seeded' === $result ) {
			echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Demo content added. Open the public site to see it.', 'maapkathi' ) . '</p></div>';
		} elseif ( 'already_seeded' === $result ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Demo content is already installed.', 'maapkathi' ) . '</p></div>';
		}

		$warning = get_transient( 'mk_demo_warning_' . get_current_user_id() );
		if ( $warning ) {
			delete_transient( 'mk_demo_warning_' . get_current_user_id() );
			echo '<div class="notice notice-warning inline"><p>' . esc_html( (string) $warning ) . '</p></div>';
		}

		if ( $seeded ) {
			echo '<p>' . esc_html__( 'Demo content is installed. "Reset" deletes the demo items and images and adds them again. Content you created yourself is not touched.', 'maapkathi' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'Fill the site with sample projects, services, team members, testimonials, FAQs, images and pages, so you can see how everything looks. You can edit or delete any of it afterwards.', 'maapkathi' ) . '</p>';
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '" />';
		wp_nonce_field( self::ACTION, 'mk_demo_nonce' );

		if ( $seeded ) {
			$confirm = esc_js( __( 'Delete the demo content and add it again? Your own content is kept.', 'maapkathi' ) );
			echo '<input type="hidden" name="fresh" value="1" />';
			echo '<button type="submit" class="button" onclick="return confirm(\'' . $confirm . '\');">' . esc_html__( 'Reset demo content', 'maapkathi' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $confirm escaped with esc_js above.
		} else {
			echo '<button type="submit" class="button button-primary">' . esc_html__( 'Load demo content', 'maapkathi' ) . '</button>';
		}

		echo ' <span class="description">' . esc_html__( 'This can take up to a minute — please wait on the page.', 'maapkathi' ) . '</span>';
		echo '</form></div>';
	}

	/**
	 * Runs the seeder, then redirects back to the Dashboard.
	 *
	 * @return void
	 */
	public function handle(): void {
		if ( ! current_user_can( Roles::CAP_MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'maapkathi' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION, 'mk_demo_nonce' );

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- may be disabled on shared hosting.
		}

		$seeder = new SeedCommand();
		$result = $seeder->run( ! empty( $_POST['fresh'] ) );

		$warnings = array_column(
			array_filter(
				$seeder->messages(),
				static fn( array $m ): bool => 'warning' === $m['level']
			),
			'text'
		);
		if ( $warnings ) {
			set_transient( 'mk_demo_warning_' . get_current_user_id(), implode( ' ', $warnings ), 5 * MINUTE_IN_SECONDS );
		}

		wp_safe_redirect( add_query_arg( 'mk_demo', $result, admin_url( 'admin.php?page=maapkathi' ) ) );
		exit;
	}
}
