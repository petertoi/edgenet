<?php
/**
 * Filename settings_actions.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<?php

use USSC_Edgenet\Template;

?>
<h2><?php esc_html_e( 'Actions', 'ussc' ); ?></h2>
<table class="form-table">
	<tbody>

	<?php
	echo Template::render_admin_table_row(
		__( 'Import Requirement Set', 'ussc' ),
		get_submit_button( __( 'Import Requirement Set', 'ussc' ), 'secondary', 'edgenet_import_requirement_set' )
	);

	echo Template::render_admin_table_row(
		__( 'Import Products', 'ussc' ),
		get_submit_button( __( 'Import Products', 'ussc' ), 'secondary', 'edgenet_import_products' )
	);

	echo Template::render_admin_table_row(
		__( 'Import Product By ID', 'ussc' ),
		sprintf( '%s<br>%s',
			Template::render_field(
				'text',
				'edgenet_import_product_id',
				'edgenet_import_product_id',
				'',
				[ 'class' => 'regular-text' ]
			),
			get_submit_button( __( 'Import Product', 'ussc' ), 'secondary', 'edgenet_import_product_by_id' )
		)
	);

	echo Template::render_admin_table_row(
		__( 'Map Categories', 'ussc' ),
		get_submit_button( __( 'Map Categories', 'ussc' ), 'secondary', 'edgenet_map_categories' )
	);
	?>
	</tbody>
</table>

