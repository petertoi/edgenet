<?php
/**
 * Filename settings-page.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */
?>
<?php

use USSC_Edgenet\Template;

?>
<div id="ussc-edgenet-settings" class="wrap">
	<h1><?php esc_html_e( 'USSC Edgenet Settings', 'ussc' ); ?></h1>
	<form method="post" action="">
		<?php wp_nonce_field( 'ussc-edgenet' ) ?>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="option_page" value="ussc-edgenet" />

		<div class="tabs">
			<ul class="nav-tab-wrapper wp-clearfix">
				<li><a href="#api" class="nav-tab ui-state-active"><?php esc_html_e( 'API', 'ussc' ); ?></a></li>
				<?php if ( edgenet()->settings->is_core_valid() ) : ?>
					<li><a href="#field-map" class="nav-tab"><?php esc_html_e( 'Field Map', 'ussc' ); ?></a></li>
					<li><a href="#actions" class="nav-tab"><?php esc_html_e( 'Actions', 'ussc' ); ?></a></li>
				<?php endif; ?>
				<?php if ( edgenet()->debug ) : ?>
					<li><a href="#debug" class="nav-tab"><?php esc_html_e( 'Debug', 'ussc' ); ?></a></li>
				<?php endif; ?>
			</ul>
			<div id="api">
				<?php Template::load( 'admin/settings-tab-api' ); ?>
			</div>
			<?php if ( edgenet()->settings->is_core_valid() ) : ?>
				<div id="field-map">
					<?php Template::load( 'admin/settings-tab-field-map' ); ?>
				</div>
				<div id="actions">
					<?php Template::load( 'admin/settings-tab-actions' ); ?>
				</div>
			<?php endif; ?>
			<?php if ( edgenet()->debug ) : ?>
				<div id="debug">
					<?php Template::load( 'admin/settings-tab-debug' ); ?>
				</div>
			<?php endif; ?>
		</div>

	</form>

</div>