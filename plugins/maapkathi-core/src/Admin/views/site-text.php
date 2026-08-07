<?php
/**
 * Site Text screen markup.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

use Maapkathi\Core\Support\SiteText;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View variables provided by SiteTextScreen::render().
 *
 * @var array<string,string> $values
 * @var string $notice
 */
?>
<div class="wrap mk-admin">
	<h1><?php esc_html_e( 'Site Text', 'maapkathi' ); ?></h1>
	<?php
	if ( $notice ) :
		?>
		<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'mk_save_site_text', 'mk_site_text_nonce' ); ?>

		<?php foreach ( SiteText::schema() as $group => $fields ) : ?>
			<h2><?php echo esc_html( $group ); ?></h2>
			<table class="form-table">
				<?php foreach ( $fields as $field ) : ?>
					<tr>
						<th><label for="<?php echo esc_attr( $field['key'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
						<td><input type="text" id="<?php echo esc_attr( $field['key'] ); ?>" name="<?php echo esc_attr( $field['key'] ); ?>" value="<?php echo esc_attr( $values[ $field['key'] ] ?? '' ); ?>" class="large-text" /></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endforeach; ?>

		<?php submit_button( __( 'Save site text', 'maapkathi' ) ); ?>
	</form>
</div>
