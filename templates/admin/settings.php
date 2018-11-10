<?php
/**
 * Filename settings-page.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */
?>
<?php

use Edgenet\Template;

?>
<div id="edgenet-settings" class="wrap">
	<h1><?php esc_html_e( 'EDGENET Edgenet Settings', 'edgenet' ); ?></h1>
	<form method="post" action="">
		<?php wp_nonce_field( 'edgenet' ); ?>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="option_page" value="edgenet" />

		<div class="tabs">
			<ul class="nav-tab-wrapper wp-clearfix">
				<li><a href="#core" class="nav-tab ui-state-active"><?php esc_html_e( 'Core', 'edgenet' ); ?></a></li>
				<?php if ( edgenet()->settings->is_core_valid() ) : ?>
					<li><a href="#field-map" class="nav-tab"><?php esc_html_e( 'Field Map', 'edgenet' ); ?></a></li>
				<?php endif; ?>
				<?php if ( edgenet()->settings->is_core_valid() ) : ?>
					<li><a href="#import" class="nav-tab"><?php esc_html_e( 'Import', 'edgenet' ); ?></a></li>
				<?php endif; ?>
				<?php if ( edgenet()->settings->is_core_valid() ) : ?>
					<li><a href="#actions" class="nav-tab"><?php esc_html_e( 'Actions', 'edgenet' ); ?></a></li>
				<?php endif; ?>
				<?php if ( edgenet()->debug->enabled ) : ?>
					<li><a href="#debug" class="nav-tab"><?php esc_html_e( 'Debug', 'edgenet' ); ?></a></li>
				<?php endif; ?>
			</ul>
			<div id="core">
				<?php Template::load( 'admin/settings-tab-core' ); ?>
			</div>
			<?php if ( edgenet()->settings->is_core_valid() ) : ?>
				<div id="field-map">
					<?php Template::load( 'admin/settings-tab-field-map' ); ?>
				</div>
			<?php endif; ?>
			<?php if ( edgenet()->settings->is_core_valid() ) : ?>
				<div id="import">
					<?php Template::load( 'admin/settings-tab-import' ); ?>
				</div>
			<?php endif; ?>
			<?php if ( edgenet()->settings->is_core_valid() ) : ?>
				<div id="actions">
					<?php Template::load( 'admin/settings-tab-actions' ); ?>
				</div>
			<?php endif; ?>
			<?php if ( edgenet()->debug->enabled ) : ?>
				<div id="debug">
					<?php Template::load( 'admin/settings-tab-debug' ); ?>
				</div>
			<?php endif; ?>
		</div>

	</form>

</div>