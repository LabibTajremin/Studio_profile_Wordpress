<?php
declare( strict_types = 1 );

namespace Maapkathi\Core;

use Maapkathi\Core\Config\Config;
use Maapkathi\Core\PostTypes\PostTypes;
use Maapkathi\Core\PostTypes\Taxonomies;
use Maapkathi\Core\Support\Database;
use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Theme\ThemeSettings;
use Maapkathi\Core\Admin\Menu;
use Maapkathi\Core\Inquiries\Inquiries;
use Maapkathi\Core\Rest\UploadController;
use Maapkathi\Core\Approval\ApprovalService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin bootstrap. Wires every subsystem together. Kept intentionally thin —
 * each subsystem owns its own hooks.
 */
final class Plugin {

	public static function activate(): void {
		Roles::register_roles();
		Database::install();
		Roles::bootstrap_admin_user();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	public static function boot(): void {
		load_plugin_textdomain( 'maapkathi', false, dirname( plugin_basename( MK_PLUGIN_FILE ) ) . '/languages' );

		$config = Config::instance();
		$config->validate_or_notice();

		Database::maybe_upgrade();

		( new Taxonomies() )->register();
		( new PostTypes() )->register();
		( new Roles() )->register_hooks();
		( new ThemeSettings() )->register_hooks();
		( new Inquiries() )->register_hooks();
		( new ApprovalService() )->register_hooks();
		( new UploadController() )->register_hooks();

		if ( is_admin() ) {
			( new Menu() )->register_hooks();
		}
	}
}
