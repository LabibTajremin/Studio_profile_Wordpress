<?php
/**
 * Elementor integration.
 *
 * The homepage this theme ships renders from its own section layout, not
 * from post content, so Elementor cannot reach it: Elementor edits the
 * content of a post or page. Rather than move the whole site into the
 * builder, every section is also published as an Elementor widget. An
 * admin who wants to build a page by hand drags the same sections onto a
 * canvas, in any order, as many times as they like, and the theme keeps
 * rendering them exactly as it does on the homepage.
 *
 * All of this works on Elementor's free version. Theme Builder — editing
 * the header, the footer or an archive — is a Pro feature and nothing here
 * can change that; the locations below are registered so that a site which
 * later adds Pro finds them already wired.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Elementor;

use Maapkathi\Core\Sections\SectionRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the theme's sections into Elementor.
 */
final class Support {

	/**
	 * Slug of the widget panel category the sections appear under.
	 */
	public const CATEGORY = 'maapkathi';

	/**
	 * Whether Elementor is active in a version that has the APIs used here.
	 *
	 * `register()` on the widgets manager replaced `register_widget_type()`
	 * in Elementor 3.5. Guarding on the class alone would fatal on an older
	 * install, which is the one failure mode an admin cannot debug.
	 */
	public static function is_available(): bool {
		return did_action( 'elementor/loaded' ) && defined( 'ELEMENTOR_VERSION' )
			&& version_compare( (string) constant( 'ELEMENTOR_VERSION' ), '3.5.0', '>=' );
	}

	/**
	 * Registers every hook. Safe to call when Elementor is absent.
	 *
	 * @return void
	 */
	public static function boot(): void {
		// Theme support is declared whether or not Elementor is installed:
		// it costs nothing and means the editor behaves on first activation.
		add_action( 'after_setup_theme', array( self::class, 'theme_support' ) );

		if ( ! self::is_available() ) {
			add_action( 'admin_notices', array( self::class, 'version_notice' ) );
			return;
		}

		add_action( 'elementor/elements/categories_registered', array( self::class, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( self::class, 'register_widgets' ) );
		add_action( 'elementor/theme/register_locations', array( self::class, 'register_locations' ) );

		// The theme's own stylesheet must be present inside the editor
		// iframe, or every section previews unstyled and the admin cannot
		// tell what they are arranging.
		add_action( 'elementor/editor/after_enqueue_styles', array( self::class, 'enqueue_editor_styles' ) );
		add_action( 'elementor/preview/enqueue_styles', array( self::class, 'enqueue_editor_styles' ) );
	}

	/**
	 * Theme-level declarations Elementor reads.
	 *
	 * @return void
	 */
	public static function theme_support(): void {
		add_theme_support( 'align-wide' );
		add_theme_support( 'responsive-embeds' );

		// Elementor reads $content_width to size its default container and
		// to cap uploaded images. Matching the theme's own container keeps
		// builder pages aligned with hand-built ones.
		if ( ! isset( $GLOBALS['content_width'] ) ) {
			$GLOBALS['content_width'] = 1200;
		}
	}

	/**
	 * Warns only where it is actionable, and only when Elementor is present
	 * but too old — never when it simply is not installed.
	 *
	 * @return void
	 */
	public static function version_notice(): void {
		if ( ! did_action( 'elementor/loaded' ) || self::is_available() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'Maapkathi: Elementor 3.5 or newer is needed for the theme sections to appear in the builder. Update Elementor to use them.', 'maapkathi' );
		echo '</p></div>';
	}

	/**
	 * Adds the panel category the section widgets live in.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor's manager.
	 * @return void
	 */
	public static function register_category( $elements_manager ): void {
		$elements_manager->add_category(
			self::CATEGORY,
			array(
				'title' => __( 'Maapkathi Sections', 'maapkathi' ),
				'icon'  => 'eicon-theme-builder',
			)
		);
	}

	/**
	 * The section types offered as widgets.
	 *
	 * Three filters, each for its own reason:
	 *
	 * - Only homepage sections. The registry also carries the page-heading
	 *   entries (work archive, about, contact and so on), which are titles
	 *   for existing templates rather than blocks anything can render.
	 * - Not the hero. It is full-viewport by contract and renders above the
	 *   content region, so dropping one mid-page would break the layout.
	 * - Only types with a partial at parts/sections/{type}.php. Partners
	 *   renders from footer.php and the map from front-page.php, so neither
	 *   has one; offering a widget that renders nothing would be worse than
	 *   not offering it.
	 *
	 * @return array<string,string> type => label
	 */
	public static function widget_types(): array {
		$out = array();

		foreach ( SectionRegistry::all() as $type => $section ) {
			if ( 'hero' === $type || 'home' !== $section['page'] ) {
				continue;
			}

			if ( ! function_exists( 'get_theme_file_path' ) ) {
				continue;
			}

			if ( ! file_exists( get_theme_file_path( 'parts/sections/' . $type . '.php' ) ) ) {
				continue;
			}

			$out[ $type ] = (string) $section['label'];
		}

		return $out;
	}

	/**
	 * Registers one widget per section type.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor's manager.
	 * @return void
	 */
	public static function register_widgets( $widgets_manager ): void {
		foreach ( array_keys( self::widget_types() ) as $type ) {
			$class = __NAMESPACE__ . '\\Widgets\\' . self::class_suffix( $type ) . 'Widget';

			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	}

	/**
	 * Declares header/footer/content locations for Elementor Theme Builder.
	 *
	 * Theme Builder is Pro-only, so on a free install this never fires. It
	 * is registered anyway so that adding Pro later needs no code change.
	 *
	 * @param \ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $manager Locations manager.
	 * @return void
	 */
	public static function register_locations( $manager ): void {
		if ( ! is_object( $manager ) || ! method_exists( $manager, 'register_all_core_location' ) ) {
			return;
		}

		$manager->register_all_core_location();
	}

	/**
	 * Loads the public stylesheets inside the editor and its preview.
	 *
	 * @return void
	 */
	public static function enqueue_editor_styles(): void {
		if ( ! function_exists( 'mk_asset_url' ) || ! function_exists( 'mk_asset_version' ) ) {
			return;
		}

		foreach ( array( 'tokens', 'base', 'sections', 'motion' ) as $handle ) {
			$relative = '/assets/css/' . $handle . '.css';

			wp_enqueue_style(
				'maapkathi-' . $handle,
				mk_asset_url( $relative ),
				array(),
				mk_asset_version( $relative )
			);
		}
	}

	/**
	 * Turns a section type into the PascalCase half of its widget class.
	 *
	 * @param string $type Section type, e.g. "projects".
	 * @return string e.g. "Projects".
	 */
	public static function class_suffix( string $type ): string {
		return str_replace( array( '-', '_' ), '', ucwords( $type, '-_' ) );
	}
}
