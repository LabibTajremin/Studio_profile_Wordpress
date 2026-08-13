<?php
/**
 * Approvals queue screen markup.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View variables provided by ApprovalsScreen::render().
 *
 * @var array<int,object> $pending
 * @var string $notice
 */
?>
<div class="wrap mk-admin">
	<h1><?php esc_html_e( 'Approvals', 'maapkathi' ); ?></h1>
	<p class="mk-admin-intro"><?php esc_html_e( 'Changes submitted by Editors wait here for review. Approve to publish a change, or reject to discard it — nothing an Editor edits goes live until it is approved on this screen.', 'maapkathi' ); ?></p>
	<?php
	if ( $notice ) :
		?>
		<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

	<?php if ( empty( $pending ) ) : ?>
		<p><?php esc_html_e( 'No pending revisions.', 'maapkathi' ); ?></p>
	<?php else : ?>
		<?php foreach ( $pending as $revision ) : ?>
			<div class="mk-card" style="margin-bottom:1rem">
				<?php $mk_entity_title = get_the_title( (int) $revision->entity_id ); ?>
				<p>
					<strong><?php echo esc_html( $revision->entity ); ?> #<?php echo esc_html( (string) $revision->entity_id ); ?></strong>
					&mdash; <?php echo esc_html( $mk_entity_title ? $mk_entity_title : '' ); ?>
					<br />
					<small>
						<?php
						printf(
							/* translators: 1: submitting user ID, 2: submission date/time. */
							esc_html__( 'Submitted by user #%1$d on %2$s', 'maapkathi' ),
							(int) $revision->submitted_by,
							esc_html( $revision->created_at )
						);
						?>
					</small>
				</p>
				<pre style="white-space:pre-wrap;background:#f6f7f7;padding:0.75rem;border-radius:4px;max-height:200px;overflow:auto"><?php echo esc_html( wp_json_encode( json_decode( (string) $revision->payload, true ), JSON_PRETTY_PRINT ) ); ?></pre>

				<form method="post" style="display:flex;gap:0.5rem;align-items:center">
					<?php wp_nonce_field( 'mk_approval_action', 'mk_approval_nonce' ); ?>
					<input type="hidden" name="revision_id" value="<?php echo esc_attr( (string) $revision->id ); ?>" />
					<input type="text" name="note" placeholder="<?php esc_attr_e( 'Optional note', 'maapkathi' ); ?>" class="regular-text" />
					<button type="submit" name="mk_action" value="approve" class="button button-primary"><?php esc_html_e( 'Approve', 'maapkathi' ); ?></button>
					<button type="submit" name="mk_action" value="reject" class="button"><?php esc_html_e( 'Reject', 'maapkathi' ); ?></button>
				</form>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
