<?php
/**
 * Filename settings-core.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<?php

use Edgenet\Template;

?>
<h2><?php esc_html_e( 'API Settings', 'edgenet' ); ?></h2>
	<table class="form-table">
		<tbody>

		<?php
		echo Template::render_admin_table_row(
			__( 'API Username', 'edgenet' ),
			Template::render_field(
				'text',
				'edgenet_settings[api][username]',
				'username',
				edgenet()->settings->get_api( 'username' ),
				[ 'class' => 'regular-text', 'readonly' => 'readonly' ]
			)
		);

		echo Template::render_admin_table_row(
			__( 'API Secret', 'edgenet' ),
			Template::render_field(
				'textarea',
				'edgenet_settings[api][secret]',
				'secret',
				edgenet()->settings->get_api( 'secret' ),
				[ 'class' => 'regular-text', 'readonly' => 'readonly', 'rows' => '4' ]
			)
		);

		echo Template::render_admin_table_row(
			__( 'Data Owner', 'edgenet' ),
			Template::render_field(
				'text',
				'edgenet_settings[api][data_owner]',
				'data_owner',
				edgenet()->settings->get_api( 'data_owner' ),
				[ 'class' => 'regular-text', 'readonly' => 'readonly' ]
			)
		);

		echo Template::render_admin_table_row(
			__( 'Requirement Set', 'edgenet' ),
			Template::render_field(
				'text',
				'edgenet_settings[api][requirement_set]',
				'secret',
				edgenet()->settings->get_api( 'requirement_set' ),
				[ 'class' => 'regular-text', 'readonly' => 'readonly' ]
			)
		);

		echo Template::render_admin_table_row(
			__( 'Taxonomy ID', 'edgenet' ),
			Template::render_field(
				'text',
				'edgenet_settings[api][taxonomy_id]',
				'secret',
				edgenet()->settings->get_api( 'taxonomy_id' ),
				[ 'class' => 'regular-text', 'readonly' => 'readonly' ]
			)
		);
		?>
		</tbody>
	</table>

	<?php submit_button( __( 'Save API Settings', 'edgenet' ), 'primary', 'edgenet_action[save_api]' ); ?>

<?php if ( edgenet()->debug ) : ?>
	<h2><?php esc_html_e( 'Debug', 'edgenet' ); ?></h2>
	<pre><?php print_r( edgenet()->settings->api ); // phpcs:ignore ?></pre>
<?php endif; ?>
