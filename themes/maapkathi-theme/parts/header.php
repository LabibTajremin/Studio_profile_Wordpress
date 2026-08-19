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
// FR-01: a custom header colour paints a solid, auto-contrasted bar; the
// default accent-following header keeps its translucent wash untouched.
$mk_header_custom = mk_header_is_custom();
$mk_logo_mode     = mk_header_logo_mode();
?>
<header class="mk-site-header<?php echo $mk_header_custom ? ' mk-site-header--custom' : ''; ?>" data-logo-mode="<?php echo esc_attr( $mk_logo_mode ); ?>" data-glass>
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
			<?php
			/*
			 * Inline SVG, not a font glyph. The sun/moon code points aren't in
			 * the themeable body fonts, and a ::before glyph left an empty box
			 * whenever the symbol fallback didn't kick in. An SVG carries its
			 * own shape in the markup, so it renders even if the stylesheet is
			 * stale or missing.
			 *
			 * The moon carries display="none" as an SVG *presentation
			 * attribute*, which any stylesheet rule outranks. So with no CSS,
			 * or with CSS too old to know these class names, exactly one icon
			 * (the sun) still shows — never two, never none.
			 */
			?>
			<button type="button" class="mk-theme-toggle" aria-label="<?php esc_attr_e( 'Toggle light and dark mode', 'maapkathi' ); ?>">
				<svg class="mk-theme-toggle__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
					<g class="mk-theme-toggle__sun">
						<circle cx="12" cy="12" r="4.5" />
						<path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8" />
					</g>
					<path class="mk-theme-toggle__moon" display="none" d="M20 13.5A8.5 8.5 0 0 1 10.5 4 7 7 0 1 0 20 13.5Z" />
				</svg>
			</button>

			<?php if ( $mk_phone ) : ?>
				<a class="mk-call-btn" href="<?php echo esc_attr( mk_tel_href( $mk_phone ) ); ?>"><?php esc_html_e( 'Call us', 'maapkathi' ); ?></a>
			<?php endif; ?>

			<button type="button" class="mk-menu-toggle" aria-label="<?php esc_attr_e( 'Open menu', 'maapkathi' ); ?>" aria-expanded="false" aria-controls="mk-mobile-menu">
				<span class="mk-menu-toggle__bars" aria-hidden="true"></span>
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
