<?php
/**
 * Map block (FR-05).
 *
 * Renders nothing at all when the map is disabled for this context or has
 * no address and no coordinates, so a half-configured site shows no grey
 * placeholder and produces no console error (FR-05.5).
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mk_map_context = isset( $args['context'] ) ? (string) $args['context'] : 'contact';
$mk_map         = mk_map_settings();

if ( ! mk_map_is_visible( $mk_map_context ) ) {
	return;
}

$mk_map_src        = mk_map_embed_url();
$mk_map_directions = ! empty( $mk_map['show_directions'] ) ? mk_map_directions_url() : '';

if ( '' === $mk_map_src ) {
	return;
}

$mk_map_label = '' !== trim( (string) $mk_map['marker'] )
	? (string) $mk_map['marker']
	: mk_setting( 'studio_name', get_bloginfo( 'name' ) );
?>
<div
	class="mk-map mk-map--style-<?php echo esc_attr( (string) $mk_map['style'] ); ?>"
	style="--mk-map-h-desktop: <?php echo esc_attr( (string) (int) $mk_map['h_desktop'] ); ?>px; --mk-map-h-mobile: <?php echo esc_attr( (string) (int) $mk_map['h_mobile'] ); ?>px;"
>
	<?php
	// Lazy-loaded so the embed never competes with first paint. The title
	// is the frame's accessible name — without it a screen reader
	// announces only "frame".
	?>
	<iframe
		class="mk-map__frame"
		src="<?php echo esc_url( $mk_map_src ); ?>"
		title="<?php echo esc_attr( sprintf( /* translators: %s: studio or location name. */ __( 'Map showing %s', 'maapkathi' ), $mk_map_label ) ); ?>"
		loading="lazy"
		referrerpolicy="no-referrer-when-downgrade"
		allowfullscreen
	></iframe>

	<?php if ( $mk_map_directions ) : ?>
		<p class="mk-map__actions">
			<a class="mk-btn mk-btn--ghost" href="<?php echo esc_url( $mk_map_directions ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Open in Google Maps', 'maapkathi' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
