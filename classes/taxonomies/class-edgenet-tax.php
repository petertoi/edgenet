<?php
/**
 * Filename class-doc-type.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet\Taxonomies;


/**
 * Class Doc_Type
 *
 * Summary
 *
 * @package USSC_Edgenet\Taxonomies
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Edgenet_Tax {

	/**
	 * Taxonomy slug.
	 */
	const TAXONOMY = 'edgenet_tax';

	/**
	 * Rewrite slug.
	 */
	const REWRITE = 'edgenet';

	/**
	 * Taxonomy link meta if.
	 */
	const LINK_META = 'edgenet_2_product';

	/**
	 * Tax we are syncing with
	 */
	const TARGET_TAX = 'product_cat';

	public function __construct() {
		add_action( 'init', [ $this, 'register_document_type_taxonomy' ] );

		add_action( self::TAXONOMY . '_add_form_fields', array( $this, 'add_category_fields' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( $this, 'edit_category_fields' ), 10 );
		add_action( 'created_term', array( $this, 'save_category_fields' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save_category_fields' ), 10, 3 );

		if( isset( $_GET['sync_tax'] ) ) {
			add_action( 'admin_init', array( $this, 'sync_edgenet_tax_to_product_tax' ) );
        }
	}

	/**
	 * Register Doc_Type and link to Product.
	 */
	public function register_document_type_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			'product',
			[
				'label'        => __( 'Edgenet Tax', 'ussc' ),
				'rewrite'      => [ 'slug' => self::REWRITE ],
				'hierarchical' => true,
			]
		);
	}

	public function add_category_fields() {

		?>
        <div class="form-field term-display-type-wrap">
            <label for="display_type"><?php _e( 'Linked parent product tax', 'ussc' ); ?></label>

			<?php

			$dropdown_args = array(
				'hide_empty'       => 0,
				'hide_if_empty'    => false,
				'taxonomy'         => self::TARGET_TAX,
				'name'             => self::LINK_META,
				'orderby'          => 'name',
				'hierarchical'     => true,
				'show_option_none' => __( 'None' ),
			);

			wp_dropdown_categories( $dropdown_args );
			?>

        </div>

		<?php
	}

	public function edit_category_fields( $term ) {

		$current = get_term_meta( $term->term_id, self::LINK_META, true );

		?>
        <tr class="form-field">
            <th scope="row" valign="top"><label><?php _e( 'Linked parent product tax', 'woocommerce' ); ?></label></th>
            <td>
				<?php

				$dropdown_args = array(
					'hide_empty'       => 0,
					'hide_if_empty'    => false,
					'taxonomy'         => self::TARGET_TAX,
					'name'             => self::LINK_META,
					'orderby'          => 'name',
					'selected'         => $current,
//	                'exclude_tree'     => $tag->term_id,
					'hierarchical'     => true,
					'show_option_none' => __( 'None' ),
				);

				wp_dropdown_categories( $dropdown_args );
				?>

            </td>
        </tr>

		<?php
	}

	/**
	 * save_category_fields function.
	 *
	 * @param mixed $term_id Term ID being saved
	 * @param mixed $tt_id
	 * @param string $taxonomy
	 */
	public function save_category_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( isset( $_POST[ self::LINK_META ] ) && self::TAXONOMY === $taxonomy ) {
			update_term_meta( $term_id, self::LINK_META, esc_attr( $_POST[ self::LINK_META ] ) );
		}
	}

	public function sync_edgenet_tax_to_product_tax() {

		$edgenet_tax = \get_terms( [
			'taxonomy'   => self::TAXONOMY,
			'hide_empty' => false, // TODO: should push empty tax?
		] );
		foreach ( $edgenet_tax as $tax ) {
			$linked_product_term_id = get_term_meta( $tax->term_id, self::LINK_META, true );

			if ( empty( $linked_product_term_id ) ) {

				continue;
			}
			$term_children = get_term_children( $linked_product_term_id, self::TARGET_TAX );
			$not_found     = true;
			foreach ( $term_children as $child ) {
				$term = get_term_by( 'id', $child, self::TARGET_TAX );
				// let look for a match exit if found
				if ( $term->name === $tax->name ) {
					$not_found = false;
					continue;
				}
			}

			// not found so lets add the tax
			if ( $not_found ) {
				$term = wp_insert_term(
					$tax->name,
					self::TARGET_TAX,
					array(
						'slug'   => strtolower( str_ireplace( ' ', '-', $tax->slug ) ),
						'parent' => $linked_product_term_id
					)
				);
			}

			// now add the product term to all posts that had the edgenet term
			$post_args = array(
				'posts_per_page' => - 1,
				'post_type'      => 'product',
				'tax_query'      => array(
					array(
						'taxonomy' => self::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => $tax->term_id,
					)
				)
			);
			$products = get_posts( $post_args );

			foreach ( $products as $post ) {

				$ff = wp_set_object_terms( $post->ID, $term->term_id, self::TARGET_TAX );
			}

		}
	}
}
