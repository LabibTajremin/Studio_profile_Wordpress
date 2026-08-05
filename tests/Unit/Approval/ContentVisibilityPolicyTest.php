<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Unit\Approval;

use Maapkathi\Core\Approval\ContentVisibilityPolicy as Policy;
use PHPUnit\Framework\TestCase;

/**
 * §8 full 12-case test matrix: {admin, editor} x {create, edit-published,
 * edit-draft} x {verification on, off}.
 */
final class ContentVisibilityPolicyTest extends TestCase {

	public static function matrix(): array {
		return array(
			// [is_admin, is_create, target_is_published, verification_required, expected]
			'admin create, verification on'             => array( true, true, false, true, Policy::ACTION_PUBLISH_DIRECT ),
			'admin edit published, verification on'      => array( true, false, true, true, Policy::ACTION_PUBLISH_DIRECT ),
			'admin edit draft, verification on'          => array( true, false, false, true, Policy::ACTION_PUBLISH_DIRECT ),
			'admin create, verification off'             => array( true, true, false, false, Policy::ACTION_PUBLISH_DIRECT ),
			'admin edit published, verification off'     => array( true, false, true, false, Policy::ACTION_PUBLISH_DIRECT ),
			'admin edit draft, verification off'         => array( true, false, false, false, Policy::ACTION_PUBLISH_DIRECT ),
			'editor create, verification on'             => array( false, true, false, true, Policy::ACTION_SAVE_PENDING ),
			'editor edit published, verification on'     => array( false, false, true, true, Policy::ACTION_QUEUE_REVISION ),
			'editor edit draft, verification on'         => array( false, false, false, true, Policy::ACTION_SAVE_PENDING ),
			'editor create, verification off'            => array( false, true, false, false, Policy::ACTION_PUBLISH_DIRECT ),
			'editor edit published, verification off'    => array( false, false, true, false, Policy::ACTION_PUBLISH_DIRECT ),
			'editor edit draft, verification off'        => array( false, false, false, false, Policy::ACTION_PUBLISH_DIRECT ),
		);
	}

	/**
	 * @dataProvider matrix
	 */
	public function test_decision_matrix( bool $is_admin, bool $is_create, bool $target_published, bool $verification_required, string $expected ): void {
		$this->assertSame(
			$expected,
			Policy::decide( $is_admin, $is_create, $target_published, $verification_required )
		);
	}
}
