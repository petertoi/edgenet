<?php
/**
 * Filename class-edgenet-cat.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet\Taxonomies;


/**
 * Class Edgenet_Cat
 *
 * Summary
 *
 * @package USSC_Edgenet\Taxonomies
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Edgenet_Cat {

	/**
	 * Taxonomy slug.
	 */
	const TAXONOMY = 'edgenet_cat';

	/**
	 * Rewrite slug.
	 */
	const REWRITE = 'edgenet';

	/**
	 * Taxonomy link meta if.
	 */
	const META_EDGENET_2_PRODUCT = 'edgenet_2_product';

	/**
	 * Tax we are syncing with
	 */
	const TARGET_TAX = 'product_cat';

	/**
	 * Edgenet_Cat constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_document_type_taxonomy' ] );
		add_action( self::TAXONOMY . '_add_form_fields', [ $this, 'add_category_fields' ] );
		add_action( self::TAXONOMY . '_edit_form_fields', [ $this, 'edit_category_fields' ] );
		add_action( 'created_term', [ $this, 'save_category_fields' ], 10, 3 );
		add_action( 'edit_term', [ $this, 'save_category_fields' ], 10, 3 );
	}

	/**
	 * Register Doc_Type and link to Product.
	 */
	public function register_document_type_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			'product',
			[
				'label'        => __( 'Edgenet Category', 'ussc' ),
				'rewrite'      => [ 'slug' => self::REWRITE ],
				'hierarchical' => true,
			]
		);
	}

	public function add_category_fields() {

		?>
		<div class="form-field term-display-type-wrap">
			<label for="display_type"><?php _e( 'Link to Product Category', 'ussc' ); ?></label>

			<?php

			$dropdown_args = [
				'hide_empty'       => 0,
				'hide_if_empty'    => false,
				'taxonomy'         => self::TARGET_TAX,
				'name'             => self::META_EDGENET_2_PRODUCT,
				'orderby'          => 'name',
				'hierarchical'     => true,
				'show_option_none' => __( 'None' ),
			];

			wp_dropdown_categories( $dropdown_args );
			?>

		</div>

		<?php
	}

	public function edit_category_fields( $term ) {

		$current = get_term_meta( $term->term_id, self::META_EDGENET_2_PRODUCT, true );

		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php _e( 'Link to Product Category', 'woocommerce' ); ?></label></th>
			<td>
				<?php

				$dropdown_args = [
					'hide_empty'       => 0,
					'hide_if_empty'    => false,
					'taxonomy'         => self::TARGET_TAX,
					'name'             => self::META_EDGENET_2_PRODUCT,
					'orderby'          => 'name',
					'selected'         => $current,
					'hierarchical'     => true,
					'show_option_none' => __( 'None' ),
				];

				wp_dropdown_categories( $dropdown_args );
				?>

			</td>
		</tr>

		<?php
	}

	/**
	 * save_category_fields function.
	 *
	 * @param mixed  $term_id Term ID being saved
	 * @param mixed  $tt_id
	 * @param string $taxonomy
	 */
	public function save_category_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( isset( $_POST[ self::META_EDGENET_2_PRODUCT ] ) && self::TAXONOMY === $taxonomy ) {
			update_term_meta( $term_id, self::META_EDGENET_2_PRODUCT, esc_attr( $_POST[ self::META_EDGENET_2_PRODUCT ] ) );
		}
	}
}
