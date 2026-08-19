<?php
/**
 * Sections screen markup (FR-02).
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View variables provided by SectionsScreen::render().
 *
 * @var array<string,array{label:string,anchor:string,text_key:string,page:string,core:bool}> $sections
 * @var array<string,array{subtitle:string,show_title:bool,anchor:string}>                    $state
 * @var array<string,string>                                                                  $text
 * @var string                                                                                $notice
 * @var array<string,string>                                                                  $field_errors
 */
$mk_pages = array(
	'home'     => __( 'Homepage', 'maapkathi' ),
	'work'     => __( 'Work archive', 'maapkathi' ),
	'services' => __( 'Services page', 'maapkathi' ),
	'about'    => __( 'About page', 'maapkathi' ),
	'team'     => __( 'Team page', 'maapkathi' ),
	'contact'  => __( 'Contact page', 'maapkathi' ),
);
?>
<div class="wrap mk-admin">
	<h1><?php esc_html_e( 'Sections', 'maapkathi' ); ?></h1>
	<p class="mk-admin-intro"><?php esc_html_e( 'The heading above each part of your site, and whether that heading is shown at all. Rename anything here — calling "Trusted by" something else changes only the words visitors read; menu links and page links pointing at that section keep working exactly as before.', 'maapkathi' ); ?></p>

	<?php if ( $notice ) : ?>
		<div class="notice <?php echo $field_errors ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'mk_save_sections', 'mk_sections_nonce' ); ?>

		<?php foreach ( $mk_pages as $mk_page_id => $mk_page_label ) : ?>
			<?php
			$mk_page_sections = array_filter(
				$sections,
				static fn( $mk_section ) => $mk_section['page'] === $mk_page_id
			);

			if ( ! $mk_page_sections ) {
				continue;
			}
			?>
			<h2><?php echo esc_html( $mk_page_label ); ?></h2>
			<table class="form-table mk-sections">
				<?php foreach ( $mk_page_sections as $mk_id => $mk_section ) : ?>
					<?php $mk_row = $state[ $mk_id ]; ?>
					<tr>
						<th scope="row">
							<?php echo esc_html( $mk_section['label'] ); ?>
							<?php if ( $mk_section['core'] ) : ?>
								<span class="mk-sections__badge" title="<?php esc_attr_e( 'A built-in section. It can be hidden, but not removed.', 'maapkathi' ); ?>"><?php esc_html_e( 'built-in', 'maapkathi' ); ?></span>
							<?php endif; ?>
						</th>
						<td>
							<p>
								<label for="mk-section-title-<?php echo esc_attr( $mk_id ); ?>"><?php esc_html_e( 'Heading', 'maapkathi' ); ?></label><br />
								<input
									type="text"
									class="large-text"
									id="mk-section-title-<?php echo esc_attr( $mk_id ); ?>"
									name="mk_sections[<?php echo esc_attr( $mk_id ); ?>][title]"
									value="<?php echo esc_attr( (string) ( $text[ $mk_section['text_key'] ] ?? '' ) ); ?>"
								/>
							</p>
							<p>
								<label for="mk-section-subtitle-<?php echo esc_attr( $mk_id ); ?>"><?php esc_html_e( 'Short line underneath (optional)', 'maapkathi' ); ?></label><br />
								<textarea
									class="large-text"
									rows="2"
									id="mk-section-subtitle-<?php echo esc_attr( $mk_id ); ?>"
									name="mk_sections[<?php echo esc_attr( $mk_id ); ?>][subtitle]"
								><?php echo esc_textarea( $mk_row['subtitle'] ); ?></textarea>
							</p>
							<p>
								<label>
									<input type="hidden" name="mk_sections[<?php echo esc_attr( $mk_id ); ?>][show_title]" value="0" />
									<input type="checkbox" name="mk_sections[<?php echo esc_attr( $mk_id ); ?>][show_title]" value="1" <?php checked( $mk_row['show_title'] ); ?> />
									<?php esc_html_e( 'Show this heading', 'maapkathi' ); ?>
								</label>
							</p>
							<p>
								<label for="mk-section-anchor-<?php echo esc_attr( $mk_id ); ?>"><?php esc_html_e( 'Link name', 'maapkathi' ); ?></label><br />
								<input
									type="text"
									id="mk-section-anchor-<?php echo esc_attr( $mk_id ); ?>"
									name="mk_sections[<?php echo esc_attr( $mk_id ); ?>][anchor]"
									value="<?php echo esc_attr( $mk_row['anchor'] ); ?>"
									pattern="[a-z0-9\-]+"
								/>
								<?php if ( isset( $field_errors[ $mk_id ] ) ) : ?>
									<span class="mk-field-error"><?php echo esc_html( $field_errors[ $mk_id ] ); ?></span>
								<?php endif; ?>
							</p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endforeach; ?>

		<p class="description"><?php esc_html_e( 'Clearing a heading puts the original wording back rather than leaving a gap. The link name is what a menu item points at — lowercase letters, numbers and hyphens only, and no two sections on the same page can share one.', 'maapkathi' ); ?></p>

		<?php submit_button( __( 'Save sections', 'maapkathi' ) ); ?>
	</form>
</div>
