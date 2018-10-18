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
<?php if ( edgenet()->settings->is_core_valid() ) : ?>
	<?php
	echo Template::render_admin_table_row(
		__( 'Import Requirement Set', 'ussc' ),
		get_submit_button( __( 'Import Requirement Set', 'ussc' ), 'secondary', 'edgenet_import_requirement_set' )
	);
	?>
<?php endif; ?>
<?php if ( edgenet()->settings->is_import_valid() ) : ?>
	<?php
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
	); ?>

	<?php if ( edgenet()->debug ) :
		echo Template::render_admin_table_row(
		       '<h2>' . esc_html__( 'Clean data', 'ussc' ). '</h2>', ''
        );


//		echo Template::render_admin_table_row(
//			__( 'Images', 'ussc' ),
//			get_submit_button( __( 'Delete all', 'ussc' ), 'secondary', 'edgenet_delete_images' ),
//            [ 'tr' => [ 'class' => 'danger', 'id' => '' ] ]
//		);
		echo Template::render_admin_table_row(
			__( 'Products', 'ussc' ),
			get_submit_button( __( 'Delete all', 'ussc' ), 'secondary', 'edgenet_delete_product' ),
			[ 'tr' => [ 'class' => 'danger', 'id' => '' ] ]
		);
		echo Template::render_admin_table_row(
			__( 'Documents', 'ussc' ),
			get_submit_button( __( 'Delete all', 'ussc' ), 'secondary', 'edgenet_delete_docs' ),
			[ 'tr' => [ 'class' => 'danger', 'id' => '' ] ]
		);
		echo Template::render_admin_table_row(
			__( 'Reset', 'ussc' ),
			get_submit_button( __( 'Delete all', 'ussc' ), 'secondary', 'edgenet_delete_all' ),
			[ 'tr' => [ 'class' => 'danger', 'id' => '' ] ]
		);
		?>
	<?php endif; ?>
    <?php endif; ?>
	</tbody>
</table>

