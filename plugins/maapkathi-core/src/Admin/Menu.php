<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Admin;

use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Theme\ThemeSettings;
use Maapkathi\Core\Storage\Adapters\LocalStorageAdapter;
use Maapkathi\Core\Admin\Screens\HeroScreen;
use Maapkathi\Core\Admin\Screens\ApprovalsScreen;
use Maapkathi\Core\Admin\Screens\UsersScreen;
use Maapkathi\Core\Admin\Screens\SiteTextScreen;
use Maapkathi\Core\Admin\Screens\SettingsScreen;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom top-level "Maapkathi" admin menu (§9) with the 17 screens from
 * §3.3. Projects/Services/Team/Testimonials/Clients/Awards/FAQs/Values/
 * Stats/Process-Steps are native WordPress post-type screens nested here
 * via PostTypes::show_in_menu('maapkathi') — sortable/filterable list
 * tables and full create/edit/delete come from WordPress core, with the
 * extra typed fields added by Fields\MetaBoxes. Every screen here still
 * checks capability on both render and any write action.
 */
final class Menu {

	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menu(): void {
		$cap = Roles::CAP_EDIT_CONTENT;

		add_menu_page(
			__( 'Maapkathi', 'maapkathi' ),
			__( 'Maapkathi', 'maapkathi' ),
			$cap,
			'maapkathi',
			array( $this, 'render_dashboard' ),
			'dashicons-admin-multisite',
			3
		);

		// #1 Dashboard (the top-level page itself).
		add_submenu_page( 'maapkathi', __( 'Dashboard', 'maapkathi' ), __( 'Dashboard', 'maapkathi' ), $cap, 'maapkathi', array( $this, 'render_dashboard' ) );

		// #8 Blog (list) — core 'post' type menu can't take show_in_menu, so
		// alias it in here explicitly; #9 Blog editor is WP's native
		// post-new.php/post.php, reached from that list as usual.
		add_submenu_page( 'maapkathi', __( 'Blog', 'maapkathi' ), __( 'Blog', 'maapkathi' ), $cap, 'edit.php' );

		// #11 Hero.
		add_submenu_page( 'maapkathi', __( 'Hero', 'maapkathi' ), __( 'Hero', 'maapkathi' ), $cap, 'maapkathi-hero', array( $this, 'render_hero' ) );

		// #12 Approvals.
		add_submenu_page( 'maapkathi', __( 'Approvals', 'maapkathi' ), __( 'Approvals', 'maapkathi' ), Roles::CAP_APPROVE_REVISIONS, 'maapkathi-approvals', array( $this, 'render_approvals' ) );

		// #13 Users.
		add_submenu_page( 'maapkathi', __( 'Users', 'maapkathi' ), __( 'Users', 'maapkathi' ), Roles::CAP_MANAGE_USERS, 'maapkathi-users', array( $this, 'render_users' ) );

		// #14 Appearance.
		add_submenu_page( 'maapkathi', __( 'Appearance', 'maapkathi' ), __( 'Appearance', 'maapkathi' ), Roles::CAP_MANAGE_APPEARANCE, 'maapkathi-appearance', array( $this, 'render_appearance' ) );

		// #15 Site Text.
		add_submenu_page( 'maapkathi', __( 'Site Text', 'maapkathi' ), __( 'Site Text', 'maapkathi' ), Roles::CAP_MANAGE_SETTINGS, 'maapkathi-site-text', array( $this, 'render_site_text' ) );

		// #16 Settings.
		add_submenu_page( 'maapkathi', __( 'Settings', 'maapkathi' ), __( 'Settings', 'maapkathi' ), Roles::CAP_MANAGE_SETTINGS, 'maapkathi-settings', array( $this, 'render_settings' ) );

		// #17 Account — WordPress already owns this at profile.php; alias it
		// in so it's reachable from the Maapkathi menu without duplicating
		// WP's own password/profile security logic.
		add_submenu_page( 'maapkathi', __( 'Account', 'maapkathi' ), __( 'Account', 'maapkathi' ), 'read', 'profile.php' );
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'maapkathi' ) ) {
			return;
		}
		wp_enqueue_style( 'maapkathi-admin', MK_PLUGIN_URL . 'assets/admin/admin.css', array(), MK_DB_VERSION );
		wp_enqueue_script( 'maapkathi-admin', MK_PLUGIN_URL . 'assets/admin/admin.js', array(), MK_DB_VERSION, true );
	}

	public function render_dashboard(): void {
		if ( ! current_user_can( Roles::CAP_EDIT_CONTENT ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$usage    = ( new LocalStorageAdapter() )->usage();
		$disk_gb  = round( $usage['bytes'] / ( 1024 ** 3 ), 2 );
		$percent  = round( ( $usage['bytes'] / ( 20 * 1024 ** 3 ) ) * 100, 1 );

		global $wpdb;
		$pending_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . \Maapkathi\Core\Support\Database::revisions_table() . " WHERE status = 'pending'" ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.PreparedSQLPlaceholders

		echo '<div class="wrap mk-admin"><h1>' . esc_html__( 'Maapkathi Dashboard', 'maapkathi' ) . '</h1>';
		echo '<p><a href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View public site ↗', 'maapkathi' ) . '</a></p>';

		echo '<div class="mk-card"><h2>' . esc_html__( 'Storage', 'maapkathi' ) . '</h2>';
		printf(
			'<p>%s / 20 GB (%s%%) &middot; %s %s</p>',
			esc_html( (string) $disk_gb ),
			esc_html( (string) $percent ),
			esc_html( number_format_i18n( $usage['files'] ) ),
			esc_html__( 'files', 'maapkathi' )
		);
		if ( $percent > 80 ) {
			echo '<p class="mk-warning">' . esc_html__( 'Disk usage is above 80%. Consider clearing unused media.', 'maapkathi' ) . '</p>';
		}
		echo '</div>';

		echo '<div class="mk-card"><h2>' . esc_html__( 'Pending approvals', 'maapkathi' ) . '</h2>';
		printf( '<p>%d</p>', esc_html( (string) $pending_count ) );
		if ( $pending_count > 0 ) {
			echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=maapkathi-approvals' ) ) . '">' . esc_html__( 'Review', 'maapkathi' ) . '</a></p>';
		}
		echo '</div></div>';
	}

	public function render_hero(): void {
		( new HeroScreen() )->render();
	}

	public function render_approvals(): void {
		( new ApprovalsScreen() )->render();
	}

	public function render_users(): void {
		( new UsersScreen() )->render();
	}

	public function render_site_text(): void {
		( new SiteTextScreen() )->render();
	}

	public function render_settings(): void {
		( new SettingsScreen() )->render();
	}

	public function render_appearance(): void {
		if ( ! current_user_can( Roles::CAP_MANAGE_APPEARANCE ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		if ( isset( $_POST['mk_appearance_nonce'] ) && check_admin_referer( 'mk_save_appearance', 'mk_appearance_nonce' ) ) {
			$raw       = wp_unslash( $_POST['mk_theme_settings'] ?? array() );
			$sanitized = ThemeSettings::sanitize( is_array( $raw ) ? $raw : array() );
			update_option( ThemeSettings::OPTION, $sanitized );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'maapkathi' ) . ' <a href="' . esc_url( home_url( '/' ) ) . '" target="_blank">' . esc_html__( 'View site ↗', 'maapkathi' ) . '</a></p></div>';
		}

		$settings = ThemeSettings::get();

		require MK_PLUGIN_DIR . 'src/Admin/views/appearance.php';
	}
}
