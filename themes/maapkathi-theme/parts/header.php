<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_settings = get_option( 'mk_site_settings', array() );
$phone         = $site_settings['contact_phone'] ?? '';
$show_shield   = ! empty( $site_settings['show_admin_shield'] );
?>
<header class="mk-site-header" data-glass>
	<div class="mk-site-header__inner">
		<a class="mk-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( has_custom_logo() ) : ?>
				<span class="mk-logo__light"><?php the_custom_logo(); ?></span>
			<?php else : ?>
				<span class="mk-logo__mark" aria-hidden="true">M</span>
				<?php if ( ! isset( $site_settings['logo_show_title'] ) || $site_settings['logo_show_title'] ) : ?>
					<span class="mk-logo__title"><?php bloginfo( 'name' ); ?></span>
				<?php endif; ?>
			<?php endif; ?>
		</a>

		<nav class="mk-nav mk-nav--desktop" aria-label="<?php esc_attr_e( 'Primary', 'maapkathi' ); ?>">
			<ul>
				<?php foreach ( mk_nav_items() as $item ) : ?>
					<li><a href="<?php echo esc_url( $item['href'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="mk-header-actions">
			<button type="button" class="mk-theme-toggle" aria-label="<?php esc_attr_e( 'Toggle light and dark mode', 'maapkathi' ); ?>">
				<span class="mk-theme-toggle__icon" aria-hidden="true"></span>
			</button>

			<?php if ( $phone ) : ?>
				<a class="mk-call-btn" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php esc_html_e( 'Call us', 'maapkathi' ); ?></a>
			<?php endif; ?>

			<button type="button" class="mk-menu-toggle" aria-label="<?php esc_attr_e( 'Open menu', 'maapkathi' ); ?>" aria-expanded="false" aria-controls="mk-mobile-menu">
				<span></span><span></span><span></span>
			</button>
		</div>
	</div>

	<div id="mk-mobile-menu" class="mk-mobile-menu" hidden>
		<ul>
			<?php foreach ( mk_nav_items() as $i => $item ) : ?>
				<li style="--mk-stagger-index: <?php echo esc_attr( (string) $i ); ?>"><a href="<?php echo esc_url( $item['href'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $phone ) : ?>
			<a class="mk-call-btn mk-call-btn--mobile" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php esc_html_e( 'Call us', 'maapkathi' ); ?></a>
		<?php endif; ?>
	</div>

	<?php if ( $show_shield ) : ?>
		<p class="mk-admin-shield-note">
			<?php esc_html_e( 'Admin login:', 'maapkathi' ); ?>
			<button type="button" class="mk-copy-login" data-login-url="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Copy /login URL', 'maapkathi' ); ?></button>
		</p>
	<?php endif; ?>
</header>
