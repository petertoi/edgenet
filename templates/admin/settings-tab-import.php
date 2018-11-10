<?php
/**
 * Filename settings-tab-import.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<?php

use Edgenet\Template;

?>
<h2><?php esc_html_e( 'Import Settings', 'edgenet' ); ?></h2>
<table class="form-table">
	<tbody>

	<?php
	echo Template::render_admin_table_row(
		__( 'Import User', 'edgenet' ),
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
		__( 'Enable Automatic Import', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[import][is_cron_enabled]',
			'is_cron_enabled',
			[ [ 'value' => 'on', 'label' => __('On', 'edgenet') ], [ 'value' => 'off', 'label' => __('Off', 'edgenet')  ] ], // phpcs:ignore
			isset( edgenet()->settings->import['is_cron_enabled'] )
				? edgenet()->settings->import['is_cron_enabled']
				: 'off',
			[]
		)
	);

	?>
	</tbody>
</table>

<?php submit_button( __( 'Save Import Settings', 'edgenet' ), 'primary', 'edgenet_action[save_import]' ); ?>

<?php if ( edgenet()->debug->enabled ) : ?>
	<h2><?php esc_html_e( 'Debug', 'edgenet' ); ?></h2>
	<pre><?php print_r( edgenet()->settings->import ); // phpcs:ignore ?></pre>
<?php endif; ?>
