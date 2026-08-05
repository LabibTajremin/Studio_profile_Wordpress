<?php
declare( strict_types = 1 );

namespace Maapkathi\Core;

use Maapkathi\Core\Config\Config;
use Maapkathi\Core\PostTypes\PostTypes;
use Maapkathi\Core\PostTypes\Taxonomies;
use Maapkathi\Core\Support\Database;
use Maapkathi\Core\Support\Security;
use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Theme\ThemeSettings;
use Maapkathi\Core\Admin\Menu;
use Maapkathi\Core\Inquiries\Inquiries;
use Maapkathi\Core\Rest\UploadController;
use Maapkathi\Core\Rest\HealthController;
use Maapkathi\Core\Rest\PlaceholderController;
use Maapkathi\Core\Approval\ApprovalService;
use Maapkathi\Core\Fields\MetaBoxes;
use Maapkathi\Core\Seo\Seo;
use Maapkathi\Core\Cli\SeedCommand;

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

		// Register post types before flushing, or the CPT rewrite rules
		// (/work, /services/{slug}) are absent from the freshly-written
		// rules and every detail page 404s until the next manual flush.
		( new Taxonomies() )->register_taxonomies();
		( new PostTypes() )->register_post_types();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	public static function boot(): void {
		load_plugin_textdomain( 'maapkathi', false, dirname( plugin_basename( MK_PLUGIN_FILE ) ) . '/languages' );

		require_once MK_PLUGIN_DIR . 'src/Support/template-functions.php';

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
		( new HealthController() )->register_hooks();
		( new PlaceholderController() )->register_hooks();
		( new MetaBoxes() )->register_hooks();
		( new Seo() )->register_hooks();
		( new Security() )->register_hooks();

		if ( is_admin() ) {
			( new Menu() )->register_hooks();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( '\WP_CLI' ) ) {
			\WP_CLI::add_command( 'maapkathi seed', SeedCommand::class );
		}
	}
}
