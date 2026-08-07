<?php
/**
 * Approvals queue screen controller.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Admin\Screens;

use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Approval\ApprovalService;
use Maapkathi\Core\Support\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Approvals queue (§3.3 #12, §8). Lists pending mk_revisions rows with a
 * diff view; approve/reject with a note. Every write re-checks capability.
 */
final class ApprovalsScreen {

	/**
	 * Checks capability, approves/rejects a revision on submit, and renders
	 * the pending-revisions queue.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( Roles::CAP_APPROVE_REVISIONS ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$service = new ApprovalService();
		$notice  = '';

		if ( isset( $_POST['mk_approval_nonce'] ) && check_admin_referer( 'mk_approval_action', 'mk_approval_nonce' ) ) {
			$id     = absint( $_POST['revision_id'] ?? 0 );
			$note   = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
			$action = sanitize_key( $_POST['mk_action'] ?? '' );

			if ( $id && 'approve' === $action ) {
				$service->approve( $id, get_current_user_id(), $note );
				$notice = __( 'Revision approved.', 'maapkathi' );
			} elseif ( $id && 'reject' === $action ) {
				$service->reject( $id, get_current_user_id(), $note );
				$notice = __( 'Revision rejected.', 'maapkathi' );
			}
		}

		global $wpdb;
		$table   = Database::revisions_table();
		$pending = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, entity, entity_id, payload, submitted_by, created_at FROM %i WHERE status = 'pending' ORDER BY created_at DESC",
				$table
			)
		);

		require MK_PLUGIN_DIR . 'src/Admin/views/approvals.php';
	}
}
