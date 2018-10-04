<?php
/**
 * Filename settings-page.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */
?>
<?php

use USSC_Edgenet\Template;

?>
<div class="wrap">
    <h1><?php esc_html_e( 'USSC Edgenet API', 'ussc' ); ?></h1>

    <form method="post" action="">
		<?php wp_nonce_field( 'ussc-edgenet' ) ?>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="option_page" value="ussc-edgenet" />

        <h2><?php esc_html_e( 'Core', 'ussc' ); ?></h2>
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

			if ( false !== edgenet()->settings->requirement_set ) {
				echo Template::render_admin_table_row(
					'',
					get_submit_button( __( 'manual sync', 'ussc' ), 'primary', 'sync' )
				);
			}
			?>

            </tbody>
        </table>


		<?php ?>
        <h2><?php esc_html_e( 'Product Field Map', 'ussc' ); ?></h2>
        <table class="form-table">

            <tbody>
			<?php
			/**
			 * Product Name
			 */
			echo Template::render_admin_table_row(
				__( 'Product Title', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][post][post_title]',
					'post_title',
					edgenet()->settings->get_attributes_for_select(),
					edgenet()->settings->post_title,
					[]
				)
			);

			/**
			 * Short Description
			 */
			echo Template::render_admin_table_row(
				__( 'Short Description', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][post][post_excerpt]',
					'post_excerpt',
					edgenet()->settings->get_attributes_for_select(),
					edgenet()->settings->post_excerpt,
					[]
				)
			);

			/**
			 * Long Description
			 */
			echo Template::render_admin_table_row(
				__( 'Long Description', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][post][post_content]',
					'post_content',
					edgenet()->settings->get_attributes_for_select(),
					edgenet()->settings->post_content,
					[]
				)
			);

			/**
			 * GTIN
			 */
			echo Template::render_admin_table_row(
				__( 'GTIN', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_gtin]',
					'_gtin',
					edgenet()->settings->get_attributes_for_select(),
					edgenet()->settings->_gtin,
					[]
				)
			);

			/**
			 * SKU
			 */
			echo Template::render_admin_table_row(
				__( 'SKU', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_sku]',
					'_sku',
					edgenet()->settings->get_attributes_for_select(),
					edgenet()->settings->_sku,
					[]
				)
			);

			/**
			 * Regular Price
			 */
			echo Template::render_admin_table_row(
				__( 'Regular Price', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_regular_price]',
					'_regular_price',
					edgenet()->settings->get_attributes_for_select(),
					edgenet()->settings->_regular_price,
					[]
				)
			);


			/**
			 * Weight
			 */
			echo Template::render_admin_table_row(
				__( 'Weight', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_weight]',
					'_weight',
					edgenet()->settings->get_attributes_for_select(),
					edgenet()->settings->_weight,
					[]
				)
			);

			/**
			 * Length
			 */
			echo Template::render_admin_table_row(
				__( 'Length', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_length]',
					'_length',
					edgenet()->settings->get_attributes_for_select(),
					edgenet()->settings->_length,
					[]
				)
			);

			/**
			 * Width
			 */
			echo Template::render_admin_table_row(
				__( 'Width', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_width]',
					'_width',
					edgenet()->settings->get_attributes_for_select(),
					edgenet()->settings->_width,
					[]
				)
			);

			/**
			 * Height
			 */
			echo Template::render_admin_table_row(
				__( 'Height', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_height]',
					'_height',
					edgenet()->settings->get_attributes_for_select(),
					edgenet()->settings->_height,
					[]
				)
			);
			?>
            </tbody>
        </table>

        <h2><?php esc_html_e( 'Marketing', 'ussc' ); ?></h2>
        <table class="form-table">

            <tbody>
			<?php
			/**
			 * Product Name
			 */
			echo Template::render_admin_table_row(
				__( 'Marketing Group', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_marketing]',
					'_digital_assets',
					edgenet()->settings->get_attribute_groups_for_select(),
					edgenet()->settings->_marketing,
					[]
				)
			);
			?>
            </tbody>
        </table>

        <h2><?php esc_html_e( 'Specifications', 'ussc' ); ?></h2>
        <table class="form-table">

            <tbody>
			<?php
			/**
			 * Product Name
			 */
			echo Template::render_admin_table_row(
				__( 'Specifications Group', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_specifications]',
					'_specifications',
					edgenet()->settings->get_attribute_groups_for_select(),
					edgenet()->settings->_specifications,
					[]
				)
			);
			?>
            </tbody>
        </table>

        <h2><?php esc_html_e( 'Images', 'ussc' ); ?></h2>
        <table class="form-table">

            <tbody>
			<?php
			/**
			 * Primary Image
			 */
			echo Template::render_admin_table_row(
				__( 'Primary Image', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_primary_image]',
					'_primary_image',
					edgenet()->settings->get_attributes_for_select(),
					edgenet()->settings->_primary_image,
					[]
				)
			);

			/**
			 * Digital Assets
			 */
			echo Template::render_admin_table_row(
				__( 'Digital Assets Group', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_digital_assets]',
					'_digital_assets',
					edgenet()->settings->get_attribute_groups_for_select(),
					edgenet()->settings->_digital_assets,
					[]
				)
			);
			?>
            </tbody>
        </table>

        <h2><?php esc_html_e( 'PDFs', 'ussc' ); ?></h2>
        <table class="form-table">

            <tbody>
			<?php
			/**
			 * Product Name
			 */
			echo Template::render_admin_table_row(
				__( 'Documents Group', 'ussc' ),
				Template::render_select(
					'edgenet_settings[field_map][postmeta][_documents]',
					'_documents',
					edgenet()->settings->get_attribute_groups_for_select(),
					edgenet()->settings->_documents,
					[]
				)
			);
			?>
            </tbody>
        </table>

		<?php submit_button( __( 'Save Changes', 'ussc' ), 'primary', 'Update' ); ?>

    </form>

	<?php if ( edgenet()->debug ) : ?>
        <h2><?php esc_html_e( 'Debug', 'ussc' ); ?></h2>
        <pre><?php print_r( edgenet() ); ?></pre>
	<?php endif; ?>


</div>