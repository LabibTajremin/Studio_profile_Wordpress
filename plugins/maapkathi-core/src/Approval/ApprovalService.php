<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Approval;

use Maapkathi\Core\Support\Database;
use Maapkathi\Core\Roles\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * mk_revisions queue + audit log writes (§8). The Approvals admin screen
 * (§9 #12) reads pending rows from here.
 */
final class ApprovalService {

	public function register_hooks(): void {
		add_action( 'save_post', array( $this, 'maybe_queue_revision' ), 20, 3 );
	}

	public function maybe_queue_revision( int $post_id, \WP_Post $post, bool $update ): void {
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

	public function verification_required(): bool {
		$settings = get_option( 'mk_site_settings', array() );
		return ! isset( $settings['editor_verification_required'] ) || (bool) $settings['editor_verification_required'];
	}

	/**
	 * @param array<string,mixed> $payload
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

	public function approve( int $revision_id, int $reviewer_id, string $note = '' ): bool {
		global $wpdb;

		$revision = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . Database::revisions_table() . ' WHERE id = %d', $revision_id ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.PreparedSQLPlaceholders -- table name has no user input
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
	 * @param array<string,mixed> $diff
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
