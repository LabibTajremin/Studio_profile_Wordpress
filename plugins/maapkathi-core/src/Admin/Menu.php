<?php
/**
 * Custom top-level "Maapkathi" admin menu and its top-level screen renderers.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Admin;

use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Theme\HexColor;
use Maapkathi\Core\Theme\ThemeSettings;
use Maapkathi\Core\Storage\Adapters\LocalStorageAdapter;
use Maapkathi\Core\Admin\Screens\HeroScreen;
use Maapkathi\Core\Admin\Screens\ApprovalsScreen;
use Maapkathi\Core\Admin\Screens\UsersScreen;
use Maapkathi\Core\Admin\Screens\SiteTextScreen;
use Maapkathi\Core\Admin\Screens\FooterScreen;
use Maapkathi\Core\Admin\Screens\SectionBuilderScreen;
use Maapkathi\Core\Admin\Screens\SectionsScreen;
use Maapkathi\Core\Admin\Screens\SettingsScreen;
use Maapkathi\Core\Admin\Screens\InquiriesScreen;

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

	/**
	 * Wires up the admin_menu and admin_enqueue_scripts hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Registers the "Maapkathi" top-level menu and its 17 §3.3 submenu pages.
	 *
	 * @return void
	 */
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

		// Enquiries inbox — carries an unread count bubble, the same
		// affordance WordPress uses for pending comments, so a new
		// enquiry is impossible to miss.
		$unread      = InquiriesScreen::unread_count();
		$inbox_label = __( 'Enquiries', 'maapkathi' );
		if ( $unread > 0 ) {
			$inbox_label .= sprintf(
				' <span class="awaiting-mod"><span class="pending-count">%d</span></span>',
				$unread
			);
		}
		add_submenu_page( 'maapkathi', __( 'Enquiries', 'maapkathi' ), $inbox_label, $cap, 'maapkathi-inquiries', array( $this, 'render_inquiries' ) );

		// #12 Approvals.
		add_submenu_page( 'maapkathi', __( 'Approvals', 'maapkathi' ), __( 'Approvals', 'maapkathi' ), Roles::CAP_APPROVE_REVISIONS, 'maapkathi-approvals', array( $this, 'render_approvals' ) );

		// #13 Users.
		add_submenu_page( 'maapkathi', __( 'Users', 'maapkathi' ), __( 'Users', 'maapkathi' ), Roles::CAP_MANAGE_USERS, 'maapkathi-users', array( $this, 'render_users' ) );

		// #14 Appearance.
		add_submenu_page( 'maapkathi', __( 'Appearance', 'maapkathi' ), __( 'Appearance', 'maapkathi' ), Roles::CAP_MANAGE_APPEARANCE, 'maapkathi-appearance', array( $this, 'render_appearance' ) );

		// #15 Site Text.
		add_submenu_page( 'maapkathi', __( 'Section Builder', 'maapkathi' ), __( 'Section Builder', 'maapkathi' ), Roles::CAP_MANAGE_SETTINGS, 'maapkathi-section-builder', array( $this, 'render_section_builder' ) );

		add_submenu_page( 'maapkathi', __( 'Sections', 'maapkathi' ), __( 'Sections', 'maapkathi' ), Roles::CAP_MANAGE_SETTINGS, 'maapkathi-sections', array( $this, 'render_sections' ) );

		add_submenu_page( 'maapkathi', __( 'Footer', 'maapkathi' ), __( 'Footer', 'maapkathi' ), Roles::CAP_MANAGE_APPEARANCE, 'maapkathi-footer', array( $this, 'render_footer' ) );

		add_submenu_page( 'maapkathi', __( 'Site Text', 'maapkathi' ), __( 'Site Text', 'maapkathi' ), Roles::CAP_MANAGE_SETTINGS, 'maapkathi-site-text', array( $this, 'render_site_text' ) );

		// #16 Settings.
		add_submenu_page( 'maapkathi', __( 'Settings', 'maapkathi' ), __( 'Settings', 'maapkathi' ), Roles::CAP_MANAGE_SETTINGS, 'maapkathi-settings', array( $this, 'render_settings' ) );

		// #17 Account — WordPress already owns this at profile.php; alias it
		// in so it's reachable from the Maapkathi menu without duplicating
		// WP's own password/profile security logic.
		add_submenu_page( 'maapkathi', __( 'Account', 'maapkathi' ), __( 'Account', 'maapkathi' ), 'read', 'profile.php' );
	}

	/**
	 * Enqueues the admin CSS/JS bundle on Maapkathi screens only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'maapkathi' ) ) {
			return;
		}

		// The Settings and Hero screens pick images from the Media
		// Library, which needs core's media modal scripts loaded.
		wp_enqueue_media();

		wp_enqueue_style( 'maapkathi-admin', MK_PLUGIN_URL . 'assets/admin/admin.css', array(), MK_DB_VERSION );
		wp_enqueue_script( 'maapkathi-admin', MK_PLUGIN_URL . 'assets/admin/admin.js', array( 'jquery' ), MK_DB_VERSION, true );
		wp_localize_script(
			'maapkathi-admin',
			'mkAdmin',
			array(
				'chooseImage' => __( 'Choose image', 'maapkathi' ),
				'useImage'    => __( 'Use this image', 'maapkathi' ),
			)
		);

		// The builder is the only screen that needs a sortable, so its
		// script and jQuery UI stay off every other Maapkathi page.
		if ( str_contains( $hook, 'maapkathi-section-builder' ) ) {
			wp_enqueue_script( 'maapkathi-section-builder', MK_PLUGIN_URL . 'assets/admin/section-builder.js', array( 'jquery-ui-sortable' ), MK_DB_VERSION, true );
			wp_localize_script(
				'maapkathi-section-builder',
				'mkBuilder',
				array(
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'mk_save_layout' ),
					'saving'        => __( 'Saving…', 'maapkathi' ),
					'failed'        => __( 'Could not save. Your changes are still here — try again.', 'maapkathi' ),
					'unsaved'       => __( 'Unsaved changes', 'maapkathi' ),
					'duplicate'     => __( 'Duplicate', 'maapkathi' ),
					'remove'        => __( 'Delete', 'maapkathi' ),
					'copyBadge'     => __( 'copy', 'maapkathi' ),
					'newBadge'      => __( 'new', 'maapkathi' ),
					/* translators: %s: the section's name. */
					'confirmDelete' => __( 'Remove the "%s" section from your homepage?', 'maapkathi' ),
				)
			);
		}
	}

	/**
	 * Renders the Maapkathi dashboard: storage usage and pending-approval count.
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( Roles::CAP_EDIT_CONTENT ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$usage   = ( new LocalStorageAdapter() )->usage();
		$disk_gb = round( $usage['bytes'] / ( 1024 ** 3 ), 2 );
		$percent = round( ( $usage['bytes'] / ( 20 * 1024 ** 3 ) ) * 100, 1 );

		global $wpdb;
		$pending_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE status = 'pending'",
				\Maapkathi\Core\Support\Database::revisions_table()
			)
		);

		echo '<div class="wrap mk-admin"><h1>' . esc_html__( 'Maapkathi Dashboard', 'maapkathi' ) . '</h1>';
		echo '<p class="mk-admin-intro">' . esc_html__( 'An overview of your site: how much file storage is in use, and anything waiting for your approval. Use the menu on the left to edit content — Hero for the homepage slideshow, Site Text for wording, Settings for studio details, Appearance for colours and fonts.', 'maapkathi' ) . '</p>';
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

	/**
	 * Renders the Hero carousel screen.
	 *
	 * @return void
	 */
	public function render_hero(): void {
		( new HeroScreen() )->render();
	}

	/**
	 * Renders the Enquiries inbox screen.
	 *
	 * @return void
	 */
	public function render_inquiries(): void {
		( new InquiriesScreen() )->render();
	}

	/**
	 * Renders the Approvals queue screen.
	 *
	 * @return void
	 */
	public function render_approvals(): void {
		( new ApprovalsScreen() )->render();
	}

	/**
	 * Renders the Users screen.
	 *
	 * @return void
	 */
	public function render_users(): void {
		( new UsersScreen() )->render();
	}

	/**
	 * Renders the Site Text screen.
	 *
	 * @return void
	 */
	public function render_site_text(): void {
		( new SiteTextScreen() )->render();
	}

	/**
	 * Renders and saves the Section Builder screen (FR-03).
	 *
	 * @return void
	 */
	public function render_section_builder(): void {
		( new SectionBuilderScreen() )->render();
	}

	/**
	 * Renders and saves the Sections screen (FR-02).
	 *
	 * @return void
	 */
	public function render_sections(): void {
		( new SectionsScreen() )->render();
	}

	/**
	 * Renders and saves the Footer screen (FR-08, FR-09).
	 *
	 * @return void
	 */
	public function render_footer(): void {
		( new FooterScreen() )->render();
	}

	/**
	 * Renders the Settings screen.
	 *
	 * @return void
	 */
	public function render_settings(): void {
		( new SettingsScreen() )->render();
	}

	/**
	 * Renders and saves the Appearance (theme + motion) screen.
	 *
	 * @return void
	 */
	public function render_appearance(): void {
		if ( ! current_user_can( Roles::CAP_MANAGE_APPEARANCE ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$field_errors = array();

		if ( isset( $_POST['mk_appearance_nonce'] ) && check_admin_referer( 'mk_save_appearance', 'mk_appearance_nonce' ) ) {
			$raw = wp_unslash( $_POST['mk_theme_settings'] ?? array() );
			$raw = is_array( $raw ) ? $raw : array();

			// FR-01.6: a hex that will not parse is reported inline and the
			// previously saved value is put back, so a typo cannot quietly
			// blank the header colour. An empty field is not an error — it
			// means "no custom hex", which hands the header to the swatch.
			$posted_hex = isset( $raw['header_hex'] ) ? trim( (string) $raw['header_hex'] ) : '';
			if ( '' !== $posted_hex && ! HexColor::is_valid( $posted_hex ) ) {
				$current                    = ThemeSettings::get();
				$raw['header_hex']          = $current['header_hex'];
				$field_errors['header_hex'] = sprintf(
					/* translators: %s: the rejected value the admin typed. */
					__( '"%s" is not a colour. Use #rgb or #rrggbb — the previous value has been kept.', 'maapkathi' ),
					$posted_hex
				);
			}

			$sanitized = ThemeSettings::sanitize( $raw );
			update_option( ThemeSettings::OPTION, $sanitized );

			if ( $field_errors ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Saved, but one field was rejected — see the error below.', 'maapkathi' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'maapkathi' ) . ' <a href="' . esc_url( home_url( '/' ) ) . '" target="_blank">' . esc_html__( 'View site ↗', 'maapkathi' ) . '</a></p></div>';
			}
		}

		$settings = ThemeSettings::get();

		require MK_PLUGIN_DIR . 'src/Admin/views/appearance.php';
	}
}
