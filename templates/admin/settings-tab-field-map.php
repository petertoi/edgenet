<?php
/**
 * Filename settings-map.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<?php

use USSC_Edgenet\Template;

?>

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
	 * Model No
	 */
	echo Template::render_admin_table_row(
		__( 'Model No', 'ussc' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_model_no]',
			'_model_no',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->_model_no,
			[]
		)
	);

	/**
	 * Brand
	 */
	echo Template::render_admin_table_row(
		__( 'Brand', 'ussc' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_brand]',
			'_brand',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->_brand,
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

<h2><?php esc_html_e( 'Attribute Groups', 'ussc' ); ?></h2>
<table class="form-table">

	<tbody>
	<?php
	/**
	 * Features
	 */
	echo Template::render_admin_table_row(
		__( 'Features', 'ussc' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_features]',
			'_features',
			edgenet()->settings->get_attribute_groups_for_select(),
			edgenet()->settings->_features,
			[]
		)
	);

	/**
	 * Dimensions
	 */
	echo Template::render_admin_table_row(
		__( 'Dimensions', 'ussc' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_dimensions]',
			'_dimensions',
			edgenet()->settings->get_attribute_groups_for_select(),
			edgenet()->settings->_dimensions,
			[]
		)
	);

	/**
	 * Other
	 */
	echo Template::render_admin_table_row(
		__( 'Other', 'ussc' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_other]',
			'_other',
			edgenet()->settings->get_attribute_groups_for_select(),
			edgenet()->settings->_other,
			[]
		)
	);

	/**
	 * Regulatory
	 */
	echo Template::render_admin_table_row(
		__( 'Regulatory', 'ussc' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_regulatory]',
			'_regulatory',
			edgenet()->settings->get_attribute_groups_for_select(),
			edgenet()->settings->_regulatory,
			[]
		)
	);
	?>
	</tbody>
</table>

<h2><?php esc_html_e( 'Digital Assets', 'ussc' ); ?></h2>
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
		__( 'Digital Assets', 'ussc' ),
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

<h2><?php esc_html_e( 'Documents', 'ussc' ); ?></h2>
<table class="form-table">

	<tbody>
	<?php
	/**
	 * Documents
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

<?php submit_button( __( 'Save Field Map', 'ussc' ), 'primary', 'edgenet_save_field_map' ); ?>

<?php if ( edgenet()->debug ) : ?>
	<h2><?php esc_html_e( 'Debug', 'ussc' ); ?></h2>
	<pre><?php print_r( edgenet()->settings->field_map ); // phpcs:ignore ?></pre>
<?php endif; ?>
