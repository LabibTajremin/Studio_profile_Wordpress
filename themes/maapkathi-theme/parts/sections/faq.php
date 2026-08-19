<?php
/**
 * Homepage section: FAQ accordion.
 *
 * Rendered once per instance by front-page.php, which passes the
 * instance's id. Nothing here may assume the section appears only once on
 * the page (FR-03.5), so the anchor and every DOM id are derived from
 * $mk_id rather than hardcoded.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables supplied by front-page.php.
 *
 * @var string $mk_id Section instance id.
 */
$mk_data = mk_setting( 'section_faq_enabled', true ) ? mk_content( 'faqs' ) : array();

if ( ! $mk_data ) {
	return;
}
?>
<section id="<?php echo esc_attr( mk_section_anchor( $mk_id ) ); ?>" class="mk-section mk-faq" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( $mk_id ); ?>
		<div class="mk-accordion">
			<?php foreach ( $mk_data as $faq ) : ?>
				<details class="mk-accordion__item">
					<summary><?php echo esc_html( $faq['question'] ); ?></summary>
					<div class="mk-accordion__body"><?php echo esc_html( $faq['answer'] ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
