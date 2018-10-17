<?php
/**
 * Filename settings-core.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<?php

use USSC_Edgenet\Template;

?>
<h2><?php esc_html_e( 'API Settings', 'ussc' ); ?></h2>
	<table class="form-table">
		<tbody>

		<?php
		echo Template::render_admin_table_row(
			__( 'API Username', 'ussc' ),
			Template::render_field(
				'text',
				'edgenet_settings[api][username]',
				'username',
				USSC_Edgenet\Edgenet::PROD_USERNAME,
				[ 'class' => 'regular-text', 'readonly' => 'readonly' ]
			)
		);

		echo Template::render_admin_table_row(
			__( 'API Secret', 'ussc' ),
			Template::render_field(
				'textarea',
				'edgenet_settings[api][secret]',
				'secret',
				USSC_Edgenet\Edgenet::PROD_SECRET,
				[ 'class' => 'regular-text', 'readonly' => 'readonly', 'rows' => '4' ]
			)
		);

		echo Template::render_admin_table_row(
			__( 'Data Owner', 'ussc' ),
			Template::render_field(
				'text',
				'edgenet_settings[api][data_owner]',
				'data_owner',
				USSC_Edgenet\Edgenet::DATA_OWNER,
				[ 'class' => 'regular-text', 'readonly' => 'readonly' ]
			)
		);

		echo Template::render_admin_table_row(
			__( 'Requirement Set', 'ussc' ),
			Template::render_field(
				'text',
				'edgenet_settings[api][requirement_set]',
				'secret',
				USSC_Edgenet\Edgenet::REQUIREMENT_SET,
				[ 'class' => 'regular-text', 'readonly' => 'readonly' ]
			)
		);

		echo Template::render_admin_table_row(
			__( 'Taxonomy ID', 'ussc' ),
			Template::render_field(
				'text',
				'edgenet_settings[api][taxonomy_id]',
				'secret',
				USSC_Edgenet\Edgenet::TAXONOMY_ID,
				[ 'class' => 'regular-text', 'readonly' => 'readonly' ]
			)
		);

		echo Template::render_admin_table_row(
			__( 'Import User', 'ussc' ),
			Template::render_select(
				'edgenet_settings[api][import_user]',
				'import_user',
				edgenet()->settings->get_users_for_select(),
				isset( edgenet()->settings->api['import_user'] )
					? edgenet()->settings->api['import_user']
					: '',
				[]
			)
		);

		echo Template::render_admin_table_row(
			__( 'Corn control', 'ussc' ),
			Template::render_select(
				'edgenet_settings[api][cron_control]',
				'cron_control',
				[ [ 'value' => 'on', 'label' =>'On' ], [  'value' => 'off', 'label' =>'Off'] ],
				isset( edgenet()->settings->api['cron_control'] )
					? edgenet()->settings->api['cron_control']
					: 'on',
				[]
			)
		);

		?>
		</tbody>
	</table>

	<?php submit_button( __( 'Save API Settings', 'ussc' ), 'primary', 'edgenet_save_api' ); ?>

<?php if ( edgenet()->debug ) : ?>
	<h2><?php esc_html_e( 'Debug', 'ussc' ); ?></h2>
	<pre><?php print_r( edgenet()->settings->api ); // phpcs:ignore ?></pre>
<?php endif; ?>
