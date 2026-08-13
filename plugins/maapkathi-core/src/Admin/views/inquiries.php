<?php
/**
 * Enquiries inbox screen markup.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View variables provided by InquiriesScreen::render().
 *
 * @var array<int,object> $rows
 * @var int    $total
 * @var int    $unread
 * @var int    $paged
 * @var int    $total_pages
 * @var string $filter
 * @var string $notice
 */

$mk_base = admin_url( 'admin.php?page=maapkathi-inquiries' );
?>
<div class="wrap mk-admin">
	<h1>
		<?php esc_html_e( 'Enquiries', 'maapkathi' ); ?>
		<?php if ( $unread > 0 ) : ?>
			<span class="mk-badge"><?php echo esc_html( sprintf( /* translators: %d: unread count. */ __( '%d unread', 'maapkathi' ), $unread ) ); ?></span>
		<?php endif; ?>
	</h1>
	<p class="mk-admin-intro"><?php esc_html_e( 'Messages people have sent through the site\'s contact form. You can read them, mark them as handled, and delete them — this is a record of what visitors sent, so nothing here changes the public site.', 'maapkathi' ); ?></p>

	<?php if ( $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<ul class="subsubsub">
		<?php
		$mk_filters = array(
			'all'    => __( 'All', 'maapkathi' ),
			'unread' => __( 'Unread', 'maapkathi' ),
			'read'   => __( 'Read', 'maapkathi' ),
		);
		$mk_last    = array_key_last( $mk_filters );
		foreach ( $mk_filters as $mk_key => $mk_label ) :
			?>
			<li>
				<a href="<?php echo esc_url( add_query_arg( 'status', $mk_key, $mk_base ) ); ?>" <?php echo $filter === $mk_key ? 'class="current"' : ''; ?>>
					<?php echo esc_html( $mk_label ); ?>
				</a>
				<?php echo $mk_key !== $mk_last ? ' |' : ''; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( empty( $rows ) ) : ?>
		<p><?php esc_html_e( 'No enquiries yet. Submissions from the public contact form appear here.', 'maapkathi' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:16%"><?php esc_html_e( 'From', 'maapkathi' ); ?></th>
					<th style="width:18%"><?php esc_html_e( 'Contact', 'maapkathi' ); ?></th>
					<th><?php esc_html_e( 'Message', 'maapkathi' ); ?></th>
					<th style="width:14%"><?php esc_html_e( 'Received', 'maapkathi' ); ?></th>
					<th style="width:16%"><?php esc_html_e( 'Actions', 'maapkathi' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $rows as $mk_row ) : ?>
				<tr<?php echo $mk_row->is_read ? '' : ' style="font-weight:600"'; ?>>
					<td>
						<?php echo esc_html( $mk_row->name ); ?>
						<?php if ( ! $mk_row->is_read ) : ?>
							<span class="mk-badge mk-badge--new"><?php esc_html_e( 'New', 'maapkathi' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<a href="mailto:<?php echo esc_attr( $mk_row->email ); ?>"><?php echo esc_html( $mk_row->email ); ?></a>
						<?php if ( $mk_row->phone ) : ?>
							<br /><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $mk_row->phone ) ); ?>"><?php echo esc_html( $mk_row->phone ); ?></a>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $mk_row->message ); ?></td>
					<td>
						<?php
						echo esc_html(
							wp_date(
								get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
								strtotime( $mk_row->created_at . ' UTC' )
							)
						);
						?>
					</td>
					<td>
						<form method="post" style="display:flex;gap:.25rem;flex-wrap:wrap">
							<?php wp_nonce_field( 'mk_inquiry_action', 'mk_inquiry_action_nonce' ); ?>
							<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( (string) $mk_row->id ); ?>" />
							<?php if ( $mk_row->is_read ) : ?>
								<button class="button button-small" name="mk_action" value="mark_unread"><?php esc_html_e( 'Unread', 'maapkathi' ); ?></button>
							<?php else : ?>
								<button class="button button-small button-primary" name="mk_action" value="mark_read"><?php esc_html_e( 'Mark read', 'maapkathi' ); ?></button>
							<?php endif; ?>
							<a class="button button-small" href="mailto:<?php echo esc_attr( $mk_row->email ); ?>"><?php esc_html_e( 'Reply', 'maapkathi' ); ?></a>
							<?php if ( current_user_can( \Maapkathi\Core\Roles\Roles::CAP_PUBLISH_CONTENT ) ) : ?>
								<button class="button button-small button-link-delete" name="mk_action" value="delete"
									onclick="return confirm('<?php echo esc_js( __( 'Delete this enquiry permanently?', 'maapkathi' ) ); ?>');">
									<?php esc_html_e( 'Delete', 'maapkathi' ); ?>
								</button>
							<?php endif; ?>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav"><div class="tablenav-pages">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%', add_query_arg( 'status', $filter, $mk_base ) ),
							'format'    => '',
							'current'   => $paged,
							'total'     => $total_pages,
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
						)
					)
				);
				?>
			</div></div>
		<?php endif; ?>
	<?php endif; ?>
</div>
