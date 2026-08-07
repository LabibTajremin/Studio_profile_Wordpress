<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Integration;

use Maapkathi\Core\Setup\SetupWizard;
use WP_UnitTestCase;

/**
 * Verifies the first-run setup gate against a real WordPress instance.
 * Only exercises the early-return branches of maybe_redirect() — the
 * redirect branch itself calls exit() and can't run inside PHPUnit.
 */
final class SetupWizardTest extends WP_UnitTestCase {

	protected function tearDown(): void {
		delete_option( SetupWizard::OPTION_COMPLETE );
		parent::tearDown();
	}

	public function test_is_complete_defaults_to_false(): void {
		delete_option( SetupWizard::OPTION_COMPLETE );
		$this->assertFalse( SetupWizard::is_complete() );
	}

	public function test_is_complete_reflects_the_option(): void {
		update_option( SetupWizard::OPTION_COMPLETE, true );
		$this->assertTrue( SetupWizard::is_complete() );
	}

	public function test_maybe_redirect_is_a_no_op_once_setup_is_complete(): void {
		update_option( SetupWizard::OPTION_COMPLETE, true );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Would call exit() if it decided to redirect, which would kill the
		// test run — reaching this assertion at all proves it didn't.
		( new SetupWizard() )->maybe_redirect();
		$this->assertTrue( true );
	}

	public function test_maybe_redirect_is_a_no_op_for_non_admin_users(): void {
		delete_option( SetupWizard::OPTION_COMPLETE );

		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		( new SetupWizard() )->maybe_redirect();
		$this->assertTrue( true );
	}
}
