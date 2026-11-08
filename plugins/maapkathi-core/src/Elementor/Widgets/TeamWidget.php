<?php
/**
 * Elementor widget: the team section.
 *
 * One thin subclass per section type. Elementor resolves a placed widget
 * back to a class by name, so each type needs its own concrete class
 * rather than one class serving many names.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders parts/sections/team.php.
 */
final class TeamWidget extends SectionWidget {

	/**
	 * The section partial this widget renders.
	 *
	 * @return string
	 */
	protected function section_type(): string {
		return 'team';
	}
}
