<?php
/**
 * Site header: logo (light/dark), primary nav, header actions, mobile menu,
 * and the optional admin sign-in shield.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mk_phone       = mk_setting( 'contact_phone' );
$mk_show_shield = ! empty( mk_setting( 'show_admin_shield' ) );
$mk_show_title  = mk_setting( 'logo_show_title', true );
$mk_logo_light  = mk_logo_light_url();
$mk_logo_dark   = mk_logo_dark_url();
$mk_studio_name = mk_setting( 'studio_name', get_bloginfo( 'name' ) );
?>
<header class="mk-site-header" data-glass>
	<div class="mk-site-header__inner">
		<a class="mk-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php if ( $mk_logo_light || $mk_logo_dark ) : ?>
				<?php
				// Both logos render; CSS shows the right one per active
				// theme. Doing this in CSS rather than JS is what stops a
				// flash of the wrong logo on load (§3.5).
				$mk_light_src = $mk_logo_light ? $mk_logo_light : $mk_logo_dark;
				$mk_dark_src  = $mk_logo_dark ? $mk_logo_dark : $mk_logo_light;
				?>
				<img class="mk-logo__img mk-logo__img--light" src="<?php echo esc_url( $mk_light_src ); ?>" alt="<?php echo esc_attr( $mk_studio_name ); ?>" />
				<img class="mk-logo__img mk-logo__img--dark" src="<?php echo esc_url( $mk_dark_src ); ?>" alt="<?php echo esc_attr( $mk_studio_name ); ?>" />
			<?php else : ?>
				<img class="mk-logo__mark" src="<?php echo esc_url( mk_default_mark_url() ); ?>" alt="" aria-hidden="true" />
			<?php endif; ?>

			<?php if ( $mk_show_title ) : ?>
				<span class="mk-logo__title"><?php echo esc_html( $mk_studio_name ); ?></span>
			<?php else : ?>
				<span class="screen-reader-text"><?php echo esc_html( $mk_studio_name ); ?></span>
			<?php endif; ?>
		</a>

		<nav class="mk-nav mk-nav--desktop" aria-label="<?php esc_attr_e( 'Primary', 'maapkathi' ); ?>">
			<ul>
				<?php foreach ( mk_nav_items() as $mk_item ) : ?>
					<li><a href="<?php echo esc_url( $mk_item['href'] ); ?>"><?php echo esc_html( $mk_item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="mk-header-actions">
			<button type="button" class="mk-theme-toggle" aria-label="<?php esc_attr_e( 'Toggle light and dark mode', 'maapkathi' ); ?>">
				<span class="mk-theme-toggle__icon" aria-hidden="true"></span>
			</button>

			<?php if ( $mk_phone ) : ?>
				<a class="mk-call-btn" href="<?php echo esc_attr( mk_tel_href( $mk_phone ) ); ?>"><?php esc_html_e( 'Call us', 'maapkathi' ); ?></a>
			<?php endif; ?>

			<button type="button" class="mk-menu-toggle" aria-label="<?php esc_attr_e( 'Open menu', 'maapkathi' ); ?>" aria-expanded="false" aria-controls="mk-mobile-menu">
				<span></span><span></span><span></span>
			</button>
		</div>
	</div>

	<div id="mk-mobile-menu" class="mk-mobile-menu" hidden>
		<button type="button" class="mk-mobile-menu__close" aria-label="<?php esc_attr_e( 'Close menu', 'maapkathi' ); ?>">&times;</button>
		<ul>
			<?php foreach ( mk_nav_items() as $mk_i => $mk_item ) : ?>
				<li style="--mk-stagger-index: <?php echo esc_attr( (string) $mk_i ); ?>">
					<a href="<?php echo esc_url( $mk_item['href'] ); ?>"><?php echo esc_html( $mk_item['label'] ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $mk_phone ) : ?>
			<a class="mk-call-btn mk-call-btn--mobile" href="<?php echo esc_attr( mk_tel_href( $mk_phone ) ); ?>"><?php esc_html_e( 'Call us', 'maapkathi' ); ?></a>
		<?php endif; ?>
	</div>

	<?php if ( $mk_show_shield ) : ?>
		<p class="mk-admin-shield-note">
			<?php esc_html_e( 'Studio team:', 'maapkathi' ); ?>
			<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Sign in', 'maapkathi' ); ?></a>
			<button type="button" class="mk-copy-login" data-login-url="<?php echo esc_url( wp_login_url() ); ?>">
				<?php esc_html_e( 'Copy login URL', 'maapkathi' ); ?>
			</button>
		</p>
	<?php endif; ?>
</header>
