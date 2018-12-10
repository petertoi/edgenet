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
	$username_atts = [
		'class' => 'regular-text',
	];
	if ( defined( 'EDGENET_PROD_USERNAME' ) ) {
		$username_atts['readonly'] = 'readonly';
	}
	echo Template::render_admin_table_row(
		__( 'API Username', 'edgenet' ),
		Template::render_field(
			'text',
			'edgenet_settings[api][username]',
			'username',
			edgenet()->settings->get_api( 'username' ),
			$username_atts
		)
	);

	$secret_atts = [
		'class' => 'regular-text',
		'rows'  => '4',
	];
	if ( defined( 'EDGENET_PROD_SECRET' ) ) {
		$secret_atts['readonly'] = 'readonly';
	}
	echo Template::render_admin_table_row(
		__( 'API Secret', 'edgenet' ),
		Template::render_field(
			'textarea',
			'edgenet_settings[api][secret]',
			'secret',
			edgenet()->settings->get_api( 'secret' ),
			$secret_atts
		)
	);

	$data_owner_atts = [
		'class' => 'regular-text',
	];
	if ( defined( 'EDGENET_DATA_OWNER' ) ) {
		$data_owner_atts['readonly'] = 'readonly';
	}
	echo Template::render_admin_table_row(
		__( 'Data Owner', 'edgenet' ),
		Template::render_field(
			'text',
			'edgenet_settings[api][data_owner]',
			'data_owner',
			edgenet()->settings->get_api( 'data_owner' ),
			$data_owner_atts
		)
	);

	$recipient_atts = [
		'class' => 'regular-text',
	];
	if ( defined( 'EDGENET_RECIPIENT' ) ) {
		$recipient_atts['readonly'] = 'readonly';
	}
	echo Template::render_admin_table_row(
		__( 'Recipient', 'edgenet' ),
		Template::render_field(
			'text',
			'edgenet_settings[api][recipient]',
			'recipient',
			edgenet()->settings->get_api( 'recipient' ),
			$recipient_atts
		)
	);

	$requirement_set_atts = [
		'class' => 'regular-text',
	];
	if ( defined( 'EDGENET_REQUIREMENT_SET' ) ) {
		$requirement_set_atts['readonly'] = 'readonly';
	}
	echo Template::render_admin_table_row(
		__( 'Requirement Set', 'edgenet' ),
		Template::render_field(
			'text',
			'edgenet_settings[api][requirement_set]',
			'requirement_set',
			edgenet()->settings->get_api( 'requirement_set' ),
			$requirement_set_atts
		)
	);

	$taxonomy_id_atts = [
		'class' => 'regular-text',
	];
	if ( defined( 'EDGENET_TAXONOMY_ID' ) ) {
		$taxonomy_id_atts['readonly'] = 'readonly';
	}
	echo Template::render_admin_table_row(
		__( 'Taxonomy ID', 'edgenet' ),
		Template::render_field(
			'text',
			'edgenet_settings[api][taxonomy_id]',
			'taxonomy_id',
			edgenet()->settings->get_api( 'taxonomy_id' ),
			$taxonomy_id_atts
		)
	);
	?>
	</tbody>
</table>

<?php submit_button( __( 'Save API Settings', 'edgenet' ), 'primary', 'edgenet_action[save_api]' ); ?>

<?php if ( edgenet()->debug->enabled ) : ?>
	<h2><?php esc_html_e( 'Debug', 'edgenet' ); ?></h2>
	<pre><?php print_r( edgenet()->settings->api ); // phpcs:ignore ?></pre>
<?php endif; ?>
