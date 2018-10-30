<?php
/**
 * Filename settings_actions.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<?php

use Edgenet\Template;

?>
	<h2><?php esc_html_e( 'Actions', 'edgenet' ); ?></h2>
	<table class="form-table">
		<tbody>

		<?php if ( edgenet()->settings->is_core_valid() ) : ?>

			<?php
			echo Template::render_admin_table_row(
				__( 'Import Requirement Set', 'edgenet' ),
				get_submit_button( __( 'Import Requirement Set', 'edgenet' ), 'secondary', 'edgenet_action[import_requirement_set]' )
			);
			?>

		<?php endif; ?>

		<?php if ( edgenet()->settings->is_import_valid() ) : ?>

			<?php
			echo Template::render_admin_table_row(
				__( 'Import Products', 'edgenet' ),
				get_submit_button( __( 'Import Products', 'edgenet' ), 'secondary', 'edgenet_action[import_products]' )
			);

			echo Template::render_admin_table_row(
				__( 'Import Product By ID', 'edgenet' ),
				sprintf( '%s<br>%s',
					Template::render_field(
						'text',
						'edgenet_import_product_id',
						'edgenet_import_product_id',
						'',
						[ 'class' => 'regular-text' ]
					),
					get_submit_button( __( 'Import Product', 'edgenet' ), 'secondary', 'edgenet_action[import_product_by_id]' )
				)
			);

			echo Template::render_admin_table_row(
				__( 'Map Categories', 'edgenet' ),
				get_submit_button( __( 'Map Categories', 'edgenet' ), 'secondary', 'edgenet_action[map_categories]' )
			);
			?>

		<?php endif; ?>

		</tbody>
	</table>

<?php if ( edgenet()->debug ) : ?>
	<h2><?php esc_html_e( 'Remove Imported Data', 'edgenet' ); ?></h2>
	<table class="form-table">
		<tbody>
		<?php
		// echo Template::render_admin_table_row(
		// __( 'Images', 'edgenet' ),
		// get_submit_button( __( 'Delete all', 'edgenet' ), 'secondary', 'edgenet_delete_images' )
		// );
		echo Template::render_admin_table_row(
			__( 'Delete Products', 'edgenet' ),
			get_submit_button( __( 'Delete Products', 'edgenet' ), 'alert', 'edgenet_action[delete_products]' )
		);
		echo Template::render_admin_table_row(
			__( 'Delete Documents', 'edgenet' ),
			get_submit_button( __( 'Delete Documents', 'edgenet' ), 'alert', 'edgenet_action[delete_documents]' )
		);
		echo Template::render_admin_table_row(
			__( 'Delete All', 'edgenet' ),
			get_submit_button( __( 'Delete All', 'edgenet' ), 'alert', 'edgenet_action[delete_all]' )
		);
		?>
		</tbody>
	</table>
<?php endif;