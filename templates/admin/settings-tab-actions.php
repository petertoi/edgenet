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
				get_submit_button( __( 'Import Requirement Set', 'ussc' ), 'secondary', 'edgenet_action[import_requirement_set]' )
			);
			?>

		<?php endif; ?>

		<?php if ( edgenet()->settings->is_import_valid() ) : ?>

			<?php
			echo Template::render_admin_table_row(
				__( 'Import Products', 'ussc' ),
				get_submit_button( __( 'Import Products', 'ussc' ), 'secondary', 'edgenet_action[import_products]' )
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
					get_submit_button( __( 'Import Product', 'ussc' ), 'secondary', 'edgenet_action[import_product_by_id]' )
				)
			);

			echo Template::render_admin_table_row(
				__( 'Map Categories', 'ussc' ),
				get_submit_button( __( 'Map Categories', 'ussc' ), 'secondary', 'edgenet_action[map_categories]' )
			);
			?>

		<?php endif; ?>

		</tbody>
	</table>

<?php if ( edgenet()->debug ) : ?>
	<h2><?php esc_html_e( 'Remove Imported Data', 'ussc' ); ?></h2>
	<table class="form-table">
		<tbody>
		<?php
		// echo Template::render_admin_table_row(
		// __( 'Images', 'ussc' ),
		// get_submit_button( __( 'Delete all', 'ussc' ), 'secondary', 'edgenet_delete_images' )
		// );
		echo Template::render_admin_table_row(
			__( 'Delete Products', 'ussc' ),
			get_submit_button( __( 'Delete Products', 'ussc' ), 'alert', 'edgenet_action[delete_products]' )
		);
		echo Template::render_admin_table_row(
			__( 'Delete Documents', 'ussc' ),
			get_submit_button( __( 'Delete Documents', 'ussc' ), 'alert', 'edgenet_action[delete_documents]' )
		);
		echo Template::render_admin_table_row(
			__( 'Delete All', 'ussc' ),
			get_submit_button( __( 'Delete All', 'ussc' ), 'alert', 'edgenet_action[delete_all]' )
		);
		?>
		</tbody>
	</table>
<?php endif;