<?php
/**
 * Enquiries inbox screen controller.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Admin\Screens;

use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Support\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact-form inbox (§3.4) with read/unread state.
 *
 * Without this screen a submitted enquiry is invisible to the site owner —
 * the form would silently swallow real business.
 */
final class InquiriesScreen {

	private const PER_PAGE = 20;

	/**
	 * Checks capability, handles a mark-read/mark-unread/delete action, and
	 * renders the filtered, paginated inbox.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( Roles::CAP_EDIT_CONTENT ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$notice = '';

		if ( isset( $_POST['mk_inquiry_action_nonce'] ) && check_admin_referer( 'mk_inquiry_action', 'mk_inquiry_action_nonce' ) ) {
			$notice = $this->handle_action();
		}

		global $wpdb;
		$table = Database::inquiries_table();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination/filter.
		$filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'all';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged  = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
		$offset = ( $paged - 1 ) * self::PER_PAGE;

		$where = '';
		if ( 'unread' === $filter ) {
			$where = 'WHERE is_read = 0';
		} elseif ( 'read' === $filter ) {
			$where = 'WHERE is_read = 1';
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );
		$unread = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_read = 0" );
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				self::PER_PAGE,
				$offset
			)
		);
		// phpcs:enable

		$total_pages = (int) ceil( $total / self::PER_PAGE );

		require MK_PLUGIN_DIR . 'src/Admin/views/inquiries.php';
	}

	/**
	 * Marks an enquiry read/unread or deletes it, per the posted action.
	 *
	 * Called only from render(), which has already verified the
	 * mk_inquiry_action nonce before invoking this method.
	 *
	 * @return string Notice message describing the result.
	 */
	private function handle_action(): string {
		if ( ! current_user_can( Roles::CAP_EDIT_CONTENT ) ) {
			return '';
		}

		global $wpdb;
		$table = Database::inquiries_table();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already verified in render() before this method is called.
		$id = absint( $_POST['inquiry_id'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already verified in render() before this method is called.
		$action = sanitize_key( $_POST['mk_action'] ?? '' );

		if ( ! $id ) {
			return '';
		}

		switch ( $action ) {
			case 'mark_read':
				$wpdb->update( $table, array( 'is_read' => 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
				return __( 'Marked as read.', 'maapkathi' );

			case 'mark_unread':
				$wpdb->update( $table, array( 'is_read' => 0 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
				return __( 'Marked as unread.', 'maapkathi' );

			case 'delete':
				// Deleting a customer enquiry is destructive and
				// unrecoverable, so it is gated on the higher capability.
				if ( ! current_user_can( Roles::CAP_PUBLISH_CONTENT ) ) {
					return __( 'You do not have permission to delete enquiries.', 'maapkathi' );
				}
				$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
				return __( 'Enquiry deleted.', 'maapkathi' );
		}

		return '';
	}

	/**
	 * Counts unread enquiries, used for the admin-menu badge.
	 *
	 * @return int
	 */
	public static function unread_count(): int {
		global $wpdb;

		$table = Database::inquiries_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_read = 0" );
	}
}
