<?php
/**
 * Filename settings-tab-import.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<?php

use USSC_Edgenet\Template;

?>
<h2><?php esc_html_e( 'Import Settings', 'ussc' ); ?></h2>
<table class="form-table">
	<tbody>

	<?php
	echo Template::render_admin_table_row(
		__( 'Import User', 'ussc' ),
		Template::render_select(
			'edgenet_settings[import][user]',
			'user',
			edgenet()->settings->get_users_for_select(),
			isset( edgenet()->settings->import['user'] )
				? edgenet()->settings->import['user']
				: '',
			[]
		)
	);

	echo Template::render_admin_table_row(
		__( 'Enable Automatic Import', 'ussc' ),
		Template::render_select(
			'edgenet_settings[import][is_cron_active]',
			'is_cron_active',
			[ [ 'value' => 'on', 'label' => 'On' ], [ 'value' => 'off', 'label' => 'Off' ] ], // phpcs:ignore
			isset( edgenet()->settings->import['is_cron_active'] )
				? edgenet()->settings->import['is_cron_active']
				: 'off',
			[]
		)
	);

	?>
	</tbody>
</table>

<?php submit_button( __( 'Save Import Settings', 'ussc' ), 'primary', 'edgenet_save_import' ); ?>

<?php if ( edgenet()->debug ) : ?>
	<h2><?php esc_html_e( 'Debug', 'ussc' ); ?></h2>
	<pre><?php print_r( edgenet()->settings->import ); // phpcs:ignore ?></pre>
<?php endif; ?>
