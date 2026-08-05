<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Admin;

use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Theme\ThemeSettings;
use Maapkathi\Core\Storage\Adapters\LocalStorageAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom top-level "Maapkathi" admin menu (§9) with the 17 screens from
 * §3.3. Each screen checks capability on both render and any write action —
 * never relies on the menu item being hidden.
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

		$screens = array(
			'maapkathi'                => array( __( 'Dashboard', 'maapkathi' ), $cap, array( $this, 'render_dashboard' ) ),
			'maapkathi-projects'       => array( __( 'Projects', 'maapkathi' ), $cap, array( $this, 'render_placeholder' ) ),
			'maapkathi-services'       => array( __( 'Services', 'maapkathi' ), $cap, array( $this, 'render_placeholder' ) ),
			'maapkathi-content'        => array( __( 'Content', 'maapkathi' ), $cap, array( $this, 'render_placeholder' ) ),
			'maapkathi-blog'           => array( __( 'Blog', 'maapkathi' ), $cap, array( $this, 'render_placeholder' ) ),
			'maapkathi-team'           => array( __( 'Team', 'maapkathi' ), $cap, array( $this, 'render_placeholder' ) ),
			'maapkathi-hero'           => array( __( 'Hero', 'maapkathi' ), $cap, array( $this, 'render_placeholder' ) ),
			'maapkathi-approvals'      => array( __( 'Approvals', 'maapkathi' ), Roles::CAP_APPROVE_REVISIONS, array( $this, 'render_placeholder' ) ),
			'maapkathi-users'          => array( __( 'Users', 'maapkathi' ), Roles::CAP_MANAGE_USERS, array( $this, 'render_placeholder' ) ),
			'maapkathi-appearance'     => array( __( 'Appearance', 'maapkathi' ), Roles::CAP_MANAGE_APPEARANCE, array( $this, 'render_appearance' ) ),
			'maapkathi-site-text'      => array( __( 'Site Text', 'maapkathi' ), Roles::CAP_MANAGE_SETTINGS, array( $this, 'render_placeholder' ) ),
			'maapkathi-settings'       => array( __( 'Settings', 'maapkathi' ), Roles::CAP_MANAGE_SETTINGS, array( $this, 'render_placeholder' ) ),
			'maapkathi-account'        => array( __( 'Account', 'maapkathi' ), $cap, array( $this, 'render_placeholder' ) ),
		);

		foreach ( $screens as $slug => [ $label, $screen_cap, $callback ] ) {
			add_submenu_page( 'maapkathi', $label, $label, $screen_cap, $slug, $callback );
		}
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
		echo '</div></div>';
	}

	public function render_placeholder(): void {
		if ( ! current_user_can( Roles::CAP_EDIT_CONTENT ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}
		echo '<div class="wrap mk-admin"><h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		echo '<p>' . esc_html__( 'This screen is scaffolded and pending full implementation.', 'maapkathi' ) . '</p></div>';
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
