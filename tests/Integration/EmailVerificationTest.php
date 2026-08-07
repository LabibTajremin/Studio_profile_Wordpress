<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Integration;

use Maapkathi\Core\Users\EmailVerification;
use WP_UnitTestCase;

/**
 * Verifies the "unverified email can't be used for account recovery" gate
 * against a real WordPress instance, and that completing a password reset
 * counts as verification.
 */
final class EmailVerificationTest extends WP_UnitTestCase {

	private EmailVerification $verification;

	protected function setUp(): void {
		parent::setUp();
		$this->verification = new EmailVerification();
	}

	public function test_password_reset_is_blocked_for_unverified_email(): void {
		$user_id = self::factory()->user->create();

		$result = $this->verification->gate_password_reset( true, $user_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_password_reset_is_allowed_once_verified(): void {
		$user_id = self::factory()->user->create();
		EmailVerification::mark_verified( $user_id );

		$this->assertTrue( $this->verification->gate_password_reset( true, $user_id ) );
	}

	public function test_already_denied_decision_passes_through_unchanged(): void {
		$user_id = self::factory()->user->create();
		$denied  = new \WP_Error( 'some_other_reason', 'blocked elsewhere' );

		$this->assertSame( $denied, $this->verification->gate_password_reset( $denied, $user_id ) );
	}

	public function test_completing_a_password_reset_marks_email_verified(): void {
		$user_id = self::factory()->user->create();
		$user    = get_userdata( $user_id );
		$this->assertNotFalse( $user );

		$this->verification->mark_verified_after_reset( $user );

		$this->assertNotEmpty( get_user_meta( $user_id, 'mk_email_verified', true ) );
	}
}
