<?php
/**
 * Filename settings-map.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<?php

use Edgenet\Template;

?>

<h2><?php esc_html_e( 'Product Field Map', 'edgenet' ); ?></h2>
<table class="form-table">
	<tbody>
	<?php
	/**
	 * Product Name
	 */
	echo Template::render_admin_table_row(
		__( 'Product Title', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][post][post_title]',
			'post_title',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( 'post_title' ),
			[]
		)
	);

	/**
	 * Short Description
	 */
	echo Template::render_admin_table_row(
		__( 'Short Description', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][post][post_excerpt]',
			'post_excerpt',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( 'post_excerpt' ),
			[]
		)
	);

	/**
	 * Long Description
	 */
	echo Template::render_admin_table_row(
		__( 'Long Description', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][post][post_content]',
			'post_content',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( 'post_content' ),
			[]
		)
	);

	/**
	 * GTIN
	 */
	echo Template::render_admin_table_row(
		__( 'GTIN', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_gtin]',
			'_gtin',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( '_gtin' ),
			[]
		)
	);

	/**
	 * SKU
	 */
	echo Template::render_admin_table_row(
		__( 'SKU', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_sku]',
			'_sku',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( '_sku' ),
			[]
		)
	);

	/**
	 * Model No
	 */
	echo Template::render_admin_table_row(
		__( 'Model No', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_model_no]',
			'_model_no',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( '_model_no' ),
			[]
		)
	);

	/**
	 * Brand
	 */
	echo Template::render_admin_table_row(
		__( 'Brand', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_brand]',
			'_brand',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( '_brand' ),
			[]
		)
	);
	/**
	 * Regular Price
	 */
	echo Template::render_admin_table_row(
		__( 'Regular Price', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_regular_price]',
			'_regular_price',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( '_regular_price' ),
			[]
		)
	);


	/**
	 * Weight
	 */
	echo Template::render_admin_table_row(
		__( 'Shipping Weight', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_weight]',
			'_weight',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( '_weight' ),
			[]
		)
	);

	/**
	 * Length
	 */
	echo Template::render_admin_table_row(
		__( 'Shipping Length', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_length]',
			'_length',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( '_length' ),
			[]
		)
	);

	/**
	 * Width
	 */
	echo Template::render_admin_table_row(
		__( 'Shipping Width', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_width]',
			'_width',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( '_width' ),
			[]
		)
	);

	/**
	 * Height
	 */
	echo Template::render_admin_table_row(
		__( 'Shipping Height', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_height]',
			'_height',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( '_height' ),
			[]
		)
	);
	?>
	</tbody>
</table>

<h2><?php esc_html_e( 'Attribute Groups', 'edgenet' ); ?></h2>
<table class="form-table">

	<tbody>
	<?php
	/**
	 * Features
	 */
	echo Template::render_admin_table_row(
		__( 'Features', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_features]',
			'_features',
			edgenet()->settings->get_attribute_groups_for_select(),
			edgenet()->settings->get_field_map( '_features' ),
			[]
		)
	);

	/**
	 * Dimensions
	 */
	echo Template::render_admin_table_row(
		__( 'Dimensions', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_dimensions]',
			'_dimensions',
			edgenet()->settings->get_attribute_groups_for_select(),
			edgenet()->settings->get_field_map( '_dimensions' ),
			[]
		)
	);

	/**
	 * Other
	 */
	echo Template::render_admin_table_row(
		__( 'Other', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_other]',
			'_other',
			edgenet()->settings->get_attribute_groups_for_select(),
			edgenet()->settings->get_field_map( '_other' ),
			[]
		)
	);

	/**
	 * Regulatory
	 */
	echo Template::render_admin_table_row(
		__( 'Regulatory', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_regulatory]',
			'_regulatory',
			edgenet()->settings->get_attribute_groups_for_select(),
			edgenet()->settings->get_field_map( '_regulatory' ),
			[]
		)
	);
	?>
	</tbody>
</table>

<h2><?php esc_html_e( 'Digital Assets', 'edgenet' ); ?></h2>
<table class="form-table">
	<tbody>
	<?php
	/**
	 * Primary Image
	 */
	echo Template::render_admin_table_row(
		__( 'Primary Image', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_primary_image]',
			'_primary_image',
			edgenet()->settings->get_attributes_for_select(),
			edgenet()->settings->get_field_map( '_primary_image' ),
			[]
		)
	);

	/**
	 * Digital Assets
	 */
	echo Template::render_admin_table_row(
		__( 'Digital Assets', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_digital_assets]',
			'_digital_assets',
			edgenet()->settings->get_attribute_groups_for_select(),
			edgenet()->settings->get_field_map( '_digital_assets' ),
			[]
		)
	);
	?>
	</tbody>
</table>

<h2><?php esc_html_e( 'Documents', 'edgenet' ); ?></h2>
<table class="form-table">

	<tbody>
	<?php
	/**
	 * Documents
	 */
	echo Template::render_admin_table_row(
		__( 'Documents Group', 'edgenet' ),
		Template::render_select(
			'edgenet_settings[field_map][postmeta][_documents]',
			'_documents',
			edgenet()->settings->get_attribute_groups_for_select(),
			edgenet()->settings->get_field_map( '_documents' ),
			[]
		)
	);
	?>
	</tbody>
</table>

<?php submit_button( __( 'Save Field Map', 'edgenet' ), 'primary', 'edgenet_action[save_field_map]' ); ?>

<?php if ( edgenet()->debug ) : ?>
	<h2><?php esc_html_e( 'Debug', 'edgenet' ); ?></h2>
	<pre><?php print_r( edgenet()->settings->field_map ); // phpcs:ignore ?></pre>
<?php endif; ?>
