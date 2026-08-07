<?php
/**
 * Content visibility decision logic.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Approval;

use Maapkathi\Core\Roles\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ports the source app's ContentVisibilityPolicy exactly (§8). Pure decision
 * logic, no WordPress calls, so it is unit-testable without Brain Monkey.
 */
final class ContentVisibilityPolicy {

	public const ACTION_PUBLISH_DIRECT = 'publish_direct';
	public const ACTION_SAVE_PENDING   = 'save_pending';
	public const ACTION_QUEUE_REVISION = 'queue_revision';

	/**
	 * Decide which publish action a content edit should take.
	 *
	 * @param bool $is_admin                true for mk_admin (or WP administrator), false for mk_editor.
	 * @param bool $is_create                true when creating new content, false when editing existing.
	 * @param bool $target_is_published      true when the item being edited is already live (ignored on create).
	 * @param bool $verification_required    site_settings.editor_verification_required.
	 * @return string One of the ACTION_* constants.
	 */
	public static function decide(
		bool $is_admin,
		bool $is_create,
		bool $target_is_published,
		bool $verification_required
	): string {
		if ( $is_admin ) {
			return self::ACTION_PUBLISH_DIRECT;
		}

		if ( ! $verification_required ) {
			return self::ACTION_PUBLISH_DIRECT;
		}

		if ( $is_create ) {
			return self::ACTION_SAVE_PENDING;
		}

		return $target_is_published ? self::ACTION_QUEUE_REVISION : self::ACTION_SAVE_PENDING;
	}

	/**
	 * Decide the publish action for a given user, resolving admin status from their capabilities.
	 *
	 * @param int  $user_id                 WordPress user ID performing the edit.
	 * @param bool $is_create                true when creating new content, false when editing existing.
	 * @param bool $target_is_published      true when the item being edited is already live (ignored on create).
	 * @param bool $verification_required    site_settings.editor_verification_required.
	 * @return string One of the ACTION_* constants.
	 */
	public static function decide_for_user( int $user_id, bool $is_create, bool $target_is_published, bool $verification_required ): string {
		$is_admin = user_can( $user_id, Roles::CAP_PUBLISH_CONTENT );
		return self::decide( $is_admin, $is_create, $target_is_published, $verification_required );
	}
}
