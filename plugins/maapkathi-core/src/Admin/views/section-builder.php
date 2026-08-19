<?php
/**
 * Section Builder screen markup (FR-03).
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

use Maapkathi\Core\Sections\SectionLayout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View variables provided by SectionBuilderScreen::render().
 *
 * @var array<int,array{id:string,type:string,enabled:bool}>                                  $layout
 * @var array<string,array{label:string,anchor:string,text_key:string,page:string,core:bool}> $registry
 * @var array<string,array{type:string,title:string,subtitle:string,show_title:bool,anchor:string}> $state
 * @var string[]                                                                              $orphans
 * @var string                                                                                $notice
 */
$mk_repeatable = SectionLayout::repeatable_types();
?>
<div class="wrap mk-admin">
	<h1><?php esc_html_e( 'Section Builder', 'maapkathi' ); ?></h1>
	<p class="mk-admin-intro"><?php esc_html_e( 'The order your homepage sections appear in, and which ones are switched on. Drag a row by its handle to move it. Duplicating a section gives you a second, independent copy — it starts switched off, so nothing appears on your live site until you are ready.', 'maapkathi' ); ?></p>

	<?php if ( $notice ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<?php if ( $orphans ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Some menu links point at sections that are no longer on the page.', 'maapkathi' ); ?></strong>
				<?php esc_html_e( 'They still work — they just take visitors to the top of the page. You may want to update or remove them:', 'maapkathi' ); ?>
				<?php echo esc_html( implode( ', ', $orphans ) ); ?>
			</p>
		</div>
	<?php endif; ?>

	<form method="post" id="mk-builder-form">
		<?php wp_nonce_field( 'mk_save_layout', 'mk_layout_nonce' ); ?>

		<table class="widefat mk-builder">
			<thead>
				<tr>
					<th scope="col" class="mk-builder__handle-col"><span class="screen-reader-text"><?php esc_html_e( 'Reorder', 'maapkathi' ); ?></span></th>
					<th scope="col"><?php esc_html_e( 'Section', 'maapkathi' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Heading', 'maapkathi' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Shown', 'maapkathi' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Actions', 'maapkathi' ); ?></th>
				</tr>
			</thead>
			<tbody id="mk-builder-rows">
				<?php foreach ( $layout as $mk_index => $mk_row ) : ?>
					<?php
					$mk_type      = $mk_row['type'];
					$mk_section   = $registry[ $mk_type ] ?? null;
					$mk_deletable = SectionLayout::is_deletable( $mk_row['id'], $layout );

					if ( null === $mk_section ) {
						continue;
					}
					?>
					<tr class="mk-builder__row" data-id="<?php echo esc_attr( $mk_row['id'] ); ?>" data-type="<?php echo esc_attr( $mk_type ); ?>">
						<td class="mk-builder__handle" aria-hidden="true">⋮⋮</td>
						<td>
							<strong><?php echo esc_html( $mk_section['label'] ); ?></strong>
							<?php if ( $mk_row['id'] !== $mk_type ) : ?>
								<span class="mk-sections__badge"><?php esc_html_e( 'copy', 'maapkathi' ); ?></span>
							<?php endif; ?>
							<div class="mk-builder__meta"><code><?php echo esc_html( $mk_row['id'] ); ?></code></div>
						</td>
						<?php
						// The instance's own title when it has one (a
						// duplicate does), otherwise the shared Site Text
						// heading the original reads.
						$mk_heading = (string) $state[ $mk_row['id'] ]['title'];
						if ( '' === trim( $mk_heading ) && '' !== $mk_section['text_key'] ) {
							$mk_heading = mk_text( $mk_section['text_key'] );
						}
						?>
						<td><?php echo esc_html( $mk_heading ); ?></td>
						<td>
							<label>
								<input type="checkbox" class="mk-builder__enabled" <?php checked( $mk_row['enabled'] ); ?> />
								<span class="screen-reader-text"><?php echo esc_html( $mk_section['label'] ); ?></span>
							</label>
						</td>
						<td class="mk-builder__actions">
							<?php if ( in_array( $mk_type, $mk_repeatable, true ) ) : ?>
								<button type="button" class="button-link mk-builder__duplicate"><?php esc_html_e( 'Duplicate', 'maapkathi' ); ?></button>
							<?php endif; ?>
							<?php if ( $mk_deletable ) : ?>
								<button type="button" class="button-link delete mk-builder__delete"><?php esc_html_e( 'Delete', 'maapkathi' ); ?></button>
							<?php else : ?>
								<span class="mk-builder__locked" title="<?php esc_attr_e( 'A built-in section. Switch it off instead of removing it.', 'maapkathi' ); ?>"><?php esc_html_e( 'built-in', 'maapkathi' ); ?></span>
							<?php endif; ?>
							<a class="button-link" href="<?php echo esc_url( admin_url( 'admin.php?page=maapkathi-sections#mk-section-title-' . $mk_row['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'maapkathi' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Add a section', 'maapkathi' ); ?></h2>
		<p>
			<?php
			// Only homepage types that make sense more than once are
			// offerable, so the list is filtered before it is rendered
			// rather than skipped over mid-loop.
			$mk_addable = array_filter(
				$registry,
				static fn( $mk_candidate, $mk_candidate_id ) =>
					'home' === $mk_candidate['page'] && in_array( $mk_candidate_id, $mk_repeatable, true ),
				ARRAY_FILTER_USE_BOTH
			);
			?>
			<select id="mk-builder-add-type">
				<?php foreach ( $mk_addable as $mk_type_id => $mk_type_section ) : ?>
					<option value="<?php echo esc_attr( $mk_type_id ); ?>"><?php echo esc_html( $mk_type_section['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="button" id="mk-builder-add"><?php esc_html_e( 'Add section', 'maapkathi' ); ?></button>
		</p>
		<p class="description"><?php esc_html_e( 'Only sections that make sense more than once can be added or duplicated — a second hero or a second closing call to action would be a mistake rather than a feature.', 'maapkathi' ); ?></p>

		<?php
		// The real payload. Kept in sync by the builder script, and posted
		// normally when JavaScript is unavailable so the screen still works.
		?>
		<input type="hidden" name="mk_layout" id="mk-builder-payload" value="<?php echo esc_attr( (string) wp_json_encode( $layout ) ); ?>" />

		<p class="mk-builder__save">
			<button type="submit" class="button button-primary" id="mk-builder-save"><?php esc_html_e( 'Save layout', 'maapkathi' ); ?></button>
			<span class="mk-builder__status" role="status" aria-live="polite"></span>
		</p>
	</form>
</div>
