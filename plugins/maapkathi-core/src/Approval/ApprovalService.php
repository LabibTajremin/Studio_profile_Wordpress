<?php
/**
 * The mk_revisions queue and audit log writes.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Approval;

use Maapkathi\Core\Support\Database;
use Maapkathi\Core\Roles\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The mk_revisions queue and audit log writer (§8). The Approvals admin screen
 * (§9 #12) reads pending rows from here.
 */
final class ApprovalService {

	/**
	 * Wire the save_post hook that enforces editor publish restrictions.
	 */
	public function register_hooks(): void {
		add_action( 'save_post', array( $this, 'maybe_queue_revision' ), 20, 1 );
	}

	/**
	 * Prevent an editor's direct save from leaving a post in the 'publish' status.
	 *
	 * @param int $post_id ID of the post being saved.
	 */
	public function maybe_queue_revision( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( Roles::CAP_EDIT_CONTENT ) || current_user_can( Roles::CAP_PUBLISH_CONTENT ) ) {
			return;
		}
		// Editors below this line: the actual pending/queue-on-edit behaviour
		// is wired per-CPT in the admin save handlers (§9), which have the
		// full before/after payload needed for a diff. This hook only
		// enforces that an editor's direct save can never leave 'publish'.
		if ( 'publish' === get_post_status( $post_id ) && $this->verification_required() ) {
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'pending',
				)
			);
		}
	}

	/**
	 * Whether the site currently requires editor verification before content goes live.
	 *
	 * @return bool True when pending review is required, defaulting to true when unset.
	 */
	public function verification_required(): bool {
		$settings = get_option( 'mk_site_settings', array() );
		return ! isset( $settings['editor_verification_required'] ) || (bool) $settings['editor_verification_required'];
	}

	/**
	 * Insert a pending revision row for an entity awaiting approval.
	 *
	 * @param string              $entity        Entity type, e.g. a CPT slug.
	 * @param int                 $entity_id     ID of the entity the revision applies to.
	 * @param array<string,mixed> $payload       Proposed field changes to store as JSON.
	 * @param int                 $submitted_by  User ID that submitted the revision.
	 * @return int The inserted revision row's ID.
	 */
	public function queue_revision( string $entity, int $entity_id, array $payload, int $submitted_by ): int {
		global $wpdb;

		$wpdb->insert(
			Database::revisions_table(),
			array(
				'entity'       => $entity,
				'entity_id'    => $entity_id,
				'payload'      => wp_json_encode( $payload ),
				'status'       => 'pending',
				'submitted_by' => $submitted_by,
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%s', '%s', '%d', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Approve a pending revision, applying its payload to the live entity.
	 *
	 * @param int    $revision_id ID of the pending revision to approve.
	 * @param int    $reviewer_id User ID of the reviewer approving it.
	 * @param string $note        Optional reviewer note to store alongside the decision.
	 * @return bool True on success, false if the revision was not found or not pending.
	 */
	public function approve( int $revision_id, int $reviewer_id, string $note = '' ): bool {
		global $wpdb;

		$revision = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name comes from Database::revisions_table(), built from $wpdb->prefix, no user input; the %d value is properly prepared.
			$wpdb->prepare( 'SELECT * FROM ' . Database::revisions_table() . ' WHERE id = %d', $revision_id )
		);

		if ( ! $revision || 'pending' !== $revision->status ) {
			return false;
		}

		$payload = json_decode( (string) $revision->payload, true );
		if ( is_array( $payload ) && ! empty( $payload ) ) {
			wp_update_post(
				array_merge( array( 'ID' => (int) $revision->entity_id ), $payload )
			);
		}

		$wpdb->update(
			Database::revisions_table(),
			array(
				'status'      => 'approved',
				'reviewed_by' => $reviewer_id,
				'reviewed_at' => current_time( 'mysql', true ),
				'note'        => $note,
			),
			array( 'id' => $revision_id )
		);

		$this->log( $reviewer_id, 'approve_revision', $revision->entity, (int) $revision->entity_id, array( 'revision_id' => $revision_id ) );

		return true;
	}

	/**
	 * Reject a pending revision.
	 *
	 * @param int    $revision_id ID of the pending revision to reject.
	 * @param int    $reviewer_id User ID of the reviewer rejecting it.
	 * @param string $note        Reviewer note explaining the rejection.
	 * @return bool True if a pending row was updated, false otherwise.
	 */
	public function reject( int $revision_id, int $reviewer_id, string $note ): bool {
		global $wpdb;

		$updated = $wpdb->update(
			Database::revisions_table(),
			array(
				'status'      => 'rejected',
				'reviewed_by' => $reviewer_id,
				'reviewed_at' => current_time( 'mysql', true ),
				'note'        => $note,
			),
			array(
				'id'     => $revision_id,
				'status' => 'pending',
			)
		);

		if ( $updated ) {
			$this->log( $reviewer_id, 'reject_revision', 'mk_revision', $revision_id, array( 'note' => $note ) );
		}

		return (bool) $updated;
	}

	/**
	 * Write an audit log entry.
	 *
	 * @param int                 $actor_id  User ID performing the action.
	 * @param string              $action    Short action identifier, e.g. 'approve_revision'.
	 * @param string              $entity    Entity type the action applies to.
	 * @param int                 $entity_id ID of the entity the action applies to.
	 * @param array<string,mixed> $diff      Optional data describing what changed.
	 */
	public function log( int $actor_id, string $action, string $entity, int $entity_id, array $diff = array() ): void {
		global $wpdb;

		$wpdb->insert(
			Database::audit_log_table(),
			array(
				'actor_id'   => $actor_id,
				'action'     => $action,
				'entity'     => $entity,
				'entity_id'  => $entity_id,
				'diff'       => wp_json_encode( $diff ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}
}
