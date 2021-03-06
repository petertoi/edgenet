<?php
/**
 * Filename class-importer.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet;

use Edgenet\Item\Attribute;
use Edgenet\Item\Attribute_Group;
use Edgenet\Item\Product;
use Edgenet\Post_Types\Document;
use Edgenet\Taxonomies\Brand;
use Edgenet\Taxonomies\Doc_Type;
use Edgenet\Taxonomies\Edgenet_Cat;

/**
 * Class Importer
 *
 * Summary
 *
 * @package Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Importer {

	const META_IMPORT_MUTEX = 'edgenet_import_mutex';

	/**
	 * Importer constructor.
	 */
	public function __construct() {
	}

	/**
	 * Update Edgenet Distribution Requirement Set configuration.
	 *
	 * @param string $requirement_set_id The requirement set ID.
	 *
	 * @return Item\Requirement_Set|\WP_Error
	 */
	public function import_requirement_set( $requirement_set_id ) {

		$set = edgenet()->api_adapter->requirementset( $requirement_set_id );

		/**
		 * Convert Attribute arrays to fully-hydrated Attribute objects.
		 *
		 * @var $group Attribute_Group
		 */
		foreach ( $set->attribute_groups as $group ) {

			$attribute_ids = $group->get_attribute_ids();

			$attribute_objects = [];

			$chunked_attribute_ids = array_chunk( $attribute_ids, 100 );

			foreach ( $chunked_attribute_ids as $chunk_ids ) {
				$chunk_objects     = edgenet()->api_adapter->attribute( $chunk_ids );
				$attribute_objects = array_merge( $attribute_objects, $chunk_objects );
			}

			$group->attributes = $attribute_objects;
		}

		edgenet()->settings->save_requirement_set( $set );

		return $set;

	}

	/**
	 * Import a set of Products
	 *
	 * @param array $product_ids Specific Product ID(s) to import. Leave null for all products.
	 * @param bool $force_update Force update products regardless of verified date.
	 *
	 * @return array|\WP_Error Array of Product IDs or WP_Error if another import already running.
	 */
	public function import_products( $product_ids = [], $force_update = false ) {
		$status = [];

		edgenet()->debug->notice( __( 'Importing Products' ), func_get_args() );

		// Validate input.
		if ( ! is_array( $product_ids ) ) {
			edgenet()->debug->error( __( 'Please provide an array of Product IDs', 'edgenet' ) );
			_doing_it_wrong(
				__FUNCTION__,
				wp_kses_post( __( 'Please provide an array of Product IDs', 'edgenet' ) ),
				'1.0.0'
			);
		}

		// Check if we're already in the process of importing.
		$import_active = get_transient( self::META_IMPORT_MUTEX );

		if ( $import_active ) {
			edgenet()->debug->warning( __( 'Another import is still underway. Please try again later.', 'edgenet' ) );

			return new \WP_Error(
				'edgenet-import-error',
				__( 'Another import is still underway. Please try again later.', 'edgenet' )
			);
		}

		// Set flag to block consecutive imports from occuring. Expires in 30 seconds.
		set_transient( self::META_IMPORT_MUTEX, true, MINUTE_IN_SECONDS / 2 );

		// Get $product_ids via API if not provided.
		if ( empty( $product_ids ) ) {
			edgenet()->debug->notice( __( 'No product IDs provided. Getting list of all products...', 'edgenet' ) );
			$product_ids = $this->get_product_ids( [
				'DataOwner'                => edgenet()->settings->get_api( 'data_owner' ),
				'Archived'                 => false,
				'Desc'                     => false,
				'Recipients'               => [ edgenet()->settings->get_api( 'recipient' ) ],
				'SubscriptionStatusFilter' => 'All',
			] );
		}

		edgenet()->debug->notice( __( sprintf( 'Importing %d products.', count( $product_ids ) ), 'edgenet' ) );
		edgenet()->debug->info( __( 'Product IDs to be imported:', 'edgenet' ), $product_ids );

		// Defer term counting while doing a batch import.
		wp_defer_term_counting( true );

		foreach ( $product_ids as $key => $product_id ) {

			edgenet()->debug->notice( __( sprintf( 'Importing Product %d of %d: %s', $key + 1, count( $product_ids ), $product_id ), 'edgenet' ) );

			edgenet()->debug->indent();

			// Reset flag to block consecutive imports from occuring. Expires in 30 seconds.
			set_transient( self::META_IMPORT_MUTEX, true, MINUTE_IN_SECONDS / 2 );

			$post_id = $this->import_product( $product_id, $force_update );

			if ( is_wp_error( $post_id ) ) {
				edgenet()->debug->warning( __( 'Import failed:', 'edgenet' ), [
					$post_id->get_error_code(),
					$post_id->get_error_message(),
				] );
			} else {
				edgenet()->debug->notice( __( sprintf( 'Imported Product %d of %d successfully: %d', $key + 1, count( $product_ids ), $post_id ), 'edgenet' ) );
			}

			edgenet()->debug->outdent();

			$status[] = $post_id;

		}

		// Re-enable term counting to sync taxonomy term counts.
		wp_defer_term_counting( false );

		// Clear import mutex.
		delete_transient( self::META_IMPORT_MUTEX );

		edgenet()->debug->notice( __( 'Importing Products Complete' ) );

		return $status;
	}

	/**
	 * Import a single Product
	 *
	 * @param int $product_id The product ID to import.
	 * @param bool $force_update Whether to force import/update data regardless of verified_date.
	 *
	 * @return int|\WP_Error The Post ID on success or \WP_Error if failure.
	 */
	private function import_product( $product_id, $force_update = false ) {
		global $post;

		$post_id = 0;

		// Track if we skipped the product update when comparing last_verified times.
		$update_skipped = false;

		// Get the full Product record from Edgenet.
		$product = $this->get_product( $product_id );

		// Bail early if we're unable to get the Product record .
		if ( is_wp_error( $product ) ) {
			return $product;
		}

		// Bail if product isn't verified.
		if ( ! $product->is_verified ) {
			return new \WP_Error(
				'edgenet-import-product-not-verified',
				__( 'Product is not verified, skipping.', 'edgenet' ),
				$product
			);
		}

		// Setup WP_Query args to check if this product already exists.
		// @see https://vip.wordpress.com/documentation/querying-on-meta_value/ for info on this query.
		$args = [
			'post_status'  => 'any',
			'meta_key'     => '_edgenet_id_' . $product->id, /* phpcs:ignore */
			'meta_compare' => 'EXISTS',
			'post_type'    => 'product',
		];

		// Run the WP_Query.
		$query = new \WP_Query( $args );

		// Insert, update, or bypass this post?
		if ( $query->have_posts() ) {
			// Product exists! Setup post data.
			$query->the_post();

			edgenet()->debug->notice( __( sprintf( 'Attempting to update existing product: %d', $post->ID ), 'edgenet' ) );

			$last_verified_meta      = get_post_meta( $post->ID, '_last_verified_date_time', true );
			$last_verified_date_time = new \DateTime( $last_verified_meta );

			$import_last_verified_date_time = new \DateTime( $product->last_verified_date_time );

			if ( $force_update ) {
				edgenet()->debug->notice( __( 'Force update flag enabled.', 'edgenet' ) );
			}
			if ( $import_last_verified_date_time > $last_verified_date_time ) {
				edgenet()->debug->notice( __( sprintf( 'Last verified time: %s [local] < [edgenet] %s', $last_verified_date_time->format( 'Y-m-d H:i:s' ), $import_last_verified_date_time->format( 'Y-m-d H:i:s' ) ), 'edgenet' ) );
			} else {
				edgenet()->debug->notice( __( sprintf( 'Last verified time: %s [local] >= [edgenet] %s', $last_verified_date_time->format( 'Y-m-d H:i:s' ), $import_last_verified_date_time->format( 'Y-m-d H:i:s' ) ), 'edgenet' ) );
			}

			// Does this product need to be updated? Check verified dates, or force_update.
			// TODO: $force_update should update the last verified version of the product, not the latest version (that might be unverified).
			if ( $import_last_verified_date_time > $last_verified_date_time || $force_update ) {

				edgenet()->debug->notice( __( 'Updating...', 'edgenet' ) );
				// Update, setup postarr args.
				$postarr       = $this->get_post_postarr( $product );
				$postarr['ID'] = $post->ID;

				// Setup meta_input.
				$meta_input = $this->get_post_meta_input( $product );

				// Update the post!
				$post_id = wp_update_post( $postarr, true );

				// Bail if error.
				if ( is_wp_error( $post_id ) ) {
					return $post_id;
				}

				// Update the meta_input!
				if ( ! empty( $meta_input ) ) {
					foreach ( $meta_input as $meta_key => $meta_value ) {
						update_post_meta( $post_id, $meta_key, $meta_value );
					}
				}

				// TODO: What about old meta_input?
			} else {
				edgenet()->debug->notice( __( 'Skipped update.', 'edgenet' ) );
				// No update, set reference to post for assets, files, and taxonomy calls yet to come.
				$update_skipped = true;
				$post_id        = $post->ID;
			}
		} else {

			edgenet()->debug->notice( __( 'Creating a new product.', 'edgenet' ) );

			// New post, setup postarr args.
			$postarr = $this->get_post_postarr( $product );

			// Insert post accepts meta_input directly.
			$postarr['meta_input'] = $this->get_post_meta_input( $product );

			// Insert the post!
			$post_id = wp_insert_post( $postarr, true );

			// Bail if error.
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
		}

		if ( ! $update_skipped ) {
			// Sideload Primary Image.
			$primary_image_id = $product->get_asset_value( edgenet()->settings->get_field_map( '_primary_image' ) );

			if ( $primary_image_id ) {
				edgenet()->debug->notice( __( sprintf( 'Setting primary image: %s', $primary_image_id ), 'edgenet' ) );

				edgenet()->debug->indent();

				$primary_image_attribute = edgenet()->settings->requirement_set->get_attribute_by_id( edgenet()->settings->get_field_map( '_primary_image' ) );
				$attachment_id           = $this->sideload_attachment(
					$this->generate_edgenet_image_url( $primary_image_id, 'jpg' ),
					[
						'attached_post_id' => $post_id,
						'filename'         => $this->generate_attachment_filename( $product, $primary_image_attribute ),
						'file_ext'         => 'jpg',
						'post_title'       => $this->generate_attachment_post_title( $product, $primary_image_attribute ),
						'edgenet_id'       => $primary_image_id,
					]
				);

				if ( ! is_wp_error( $attachment_id ) ) {
					edgenet()->debug->notice( __( sprintf( 'Primary image set: %d', $attachment_id ), 'edgenet' ) );
					update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
				} else {
					edgenet()->debug->warning( __( 'Primary image error.', 'edgenet' ), $attachment_id );
				}

				edgenet()->debug->outdent();
				unset( $attachment_id );
			}

			// Sideload Other Images.
			// TODO: Should we pass $force_update into these functions?
			$digital_assets_group_id = edgenet()->settings->get_field_map( '_digital_assets' );

			edgenet()->debug->notice( __( sprintf( 'Sideloading digital image assets group: %s', $digital_assets_group_id ), 'edgenet' ) );
			edgenet()->debug->indent();

			$attachment_ids = $this->update_digital_assets( $digital_assets_group_id, $product, $post_id );

			edgenet()->debug->notice( __( sprintf( 'Attachment IDs: %s', implode( ', ', $attachment_ids ) ), 'edgenet' ) );
			edgenet()->debug->outdent();

			// Sideload Documents.
			// TODO: Should we pass $force_update into these functions?
			$document_group_id = edgenet()->settings->get_field_map( '_documents' );

			edgenet()->debug->notice( __( sprintf( 'Sideloading document group: %s', $document_group_id ), 'edgenet' ) );
			edgenet()->debug->indent();

			$document_ids = $this->update_documents( $document_group_id, $product, $post_id );

			edgenet()->debug->notice( __( sprintf( 'Document IDs: %s', implode( ', ', $document_ids ) ), 'edgenet' ) );
			edgenet()->debug->outdent();

			// Set Product Categories.
			// TODO: Should we pass $force_update into these functions?
			$taxonomy_node_ids = $product->taxonomy_node_ids;

			edgenet()->debug->notice( __( 'Setting Edgenet categories.', 'edgenet' ) );
			edgenet()->debug->indent();

			$this->update_edgenet_taxonomy( $taxonomy_node_ids, $product, $post_id );

			edgenet()->debug->outdent();

			// Set Brand.
			// TODO: Should we pass $force_update into these functions?
			edgenet()->debug->notice( __( 'Setting brand.', 'edgenet' ) );
			edgenet()->debug->indent();

			$this->update_edgenet_brand( $product, $post_id );

			edgenet()->debug->outdent();

			/**
			 * Allow developers to include additional functionality required for their theme.
			 */
			edgenet()->debug->notice( __( 'Running additional hooks.', 'edgenet' ) );
			edgenet()->debug->indent();

			do_action( 'edgenet_import_product_after_update', $product, $post_id );

			edgenet()->debug->outdent();

		}

		return $post_id;
	}

	/**
	 * Sync Edgenet Terms to Product Categories
	 *
	 * @param int[] $term_ids An array of Term IDs.
	 *
	 * @return array
	 */
	public function sync_edgenet_cat_to_product_cat( $term_ids = [] ) {
		$sync_status = [];

		$edgenet_term_args = [
			'taxonomy'   => Edgenet_Cat::TAXONOMY,
			'hide_empty' => false, // TODO: should push empty tax?
		];

		if ( ! empty( $term_ids ) ) {
			$edgenet_term_args['include'] = $term_ids;
		}
		/**
		 * Array of edgenet_cat terms.
		 *
		 * @var \WP_Term[] $edgenet_terms
		 */
		$edgenet_terms = get_terms( $edgenet_term_args );

		foreach ( $edgenet_terms as $edgenet_term ) {
			$status = [
				'edgenet_cat'  => $edgenet_term,
				'product_cats' => null,
				'products'     => [],
			];

			$linked_product_term_ids = json_decode( get_term_meta( $edgenet_term->term_id, Edgenet_Cat::META_EDGENET_2_PRODUCT, true ) );

			if ( empty( $linked_product_term_ids ) ) {
				continue;
			}

			$status['product_cats'] = $linked_product_term_ids;

			// now add the product term to all posts that had the edgenet term.
			$post_args = [
				'posts_per_page' => - 1,
				'post_type'      => 'product',
				'tax_query'      => [ // phpcs:ignore
					[
						'taxonomy' => Edgenet_Cat::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => $edgenet_term->term_id,
					],
				],
				'fields'         => 'ids',
			];

			$product_ids = get_posts( $post_args );

			foreach ( $product_ids as $product_id ) {
				$result = wp_set_object_terms( $product_id, $linked_product_term_ids, 'product_cat' );

				$status['products'][] = [
					'product_id' => $product_id,
					'result'     => $result,
				];
			}
			$sync_status[] = $status;
			unset( $status );
		}

		// Update product_cat term counts.
		$product_cats = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		] );

		$product_cat_term_tax_ids = array_map( function ( $term ) {
			return $term->term_taxonomy_id;
		}, $product_cats );

		wp_update_term_count( $product_cat_term_tax_ids, 'product_cat', true );

		return $sync_status;

	}

	/**
	 * Sync all Edgenet Data with Custom Fields and Product Attributes
	 */
	public function sync_all_custom_fields_attributes() {
		global $sync_taxonomies;
		$sync_taxonomies = [];
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( __( 'Start SYNC_ALL_CUSTOM_FIELDS_ATTRIBUTES', 'edgenet' ) );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );

		// Get array of all product IDs
		$args = [
			'post_type'      => 'product',
			'post_status'    => 'all',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		];

		$product_ids = new \WP_Query( $args );

		edgenet()->debug->notice( __( sprintf( 'Syncing %s products', $product_ids->post_count ), 'edgenet' ) );

		foreach ( $product_ids->posts as $product_id ) {
			edgenet()->debug->notice( __( sprintf( 'Starting sync for Product ID: %s', $product_id ), 'edgenet' ) );
			$this->sync_product_custom_fields_attributes( $product_id );
			edgenet()->debug->notice( __( sprintf( 'Stopping sync for Product ID: %s', $product_id ), 'edgenet' ) );
		}

		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( __( 'End SYNC_ALL_CUSTOM_FIELDS_ATTRIBUTES', 'edgenet' ) );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( "\n" );
	}

	/**
	 * Sync all Edgenet Model numbers
	 */
	public function sync_all_model_nos() {
		global $sync_taxonomies;
		$sync_taxonomies = [];
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( __( 'Start SYNC_ALL_CUSTOM_FIELDS_ATTRIBUTES', 'edgenet' ) );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );

		// Get array of all product IDs
		$args = [
			'post_type'      => 'product',
			'post_status'    => 'all',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		];

		$product_ids = new \WP_Query( $args );

		edgenet()->debug->notice( __( sprintf( 'Syncing %s products', $product_ids->post_count ), 'edgenet' ) );

		foreach ( $product_ids->posts as $product_id ) {
			edgenet()->debug->notice( __( sprintf( 'Starting sync for Product ID: %s', $product_id ), 'edgenet' ) );
			$this->sync_product_model_no( $product_id );
			edgenet()->debug->notice( __( sprintf( 'Stopping sync for Product ID: %s', $product_id ), 'edgenet' ) );
		}

		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( __( 'End SYNC_ALL_CUSTOM_FIELDS_ATTRIBUTES', 'edgenet' ) );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( "\n" );
	}

	public function sync_all_document_product_relationships() {
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( __( 'Start SYNC_ALL_DOCUMENT_PRODUCT_RELATIONSHIPS', 'edgenet' ) );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );

		// Get array of all product IDs
		$args = [
			'post_type'      => 'product',
			'post_status'    => 'all',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		];

		$product_ids = new \WP_Query( $args );

		edgenet()->debug->notice( __( sprintf( 'Syncing %s products', $product_ids->post_count ), 'edgenet' ) );

		foreach ( $product_ids->posts as $product_id ) {
			edgenet()->debug->notice( __( sprintf( 'Starting sync for Product ID: %s', $product_id ), 'edgenet' ) );
			$this->sync_document_product_relationship( $product_id );
			edgenet()->debug->notice( __( sprintf( 'Stopping sync for Product ID: %s', $product_id ), 'edgenet' ) );
		}

		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( __( 'End SYNC_ALL_DOCUMENT_PRODUCT_RELATIONSHIPS', 'edgenet' ) );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( "\n" );
	}

	/**
	 * Sync single product Edgenet Data with Custom Fields and Product Attributes
	 *
	 * @param $product_id
	 */
	public function sync_product_custom_fields_attributes( $product_id ) {
		global $sync_taxonomies;

		// Features.
		$features = get_post_meta( $product_id, '_features', true );
		if ( ! is_array( $features ) ) {
			$features = [];
		}

		delete_field( 'ussc_features', $product_id );

		$features = array_map( function ( $feature ) {
			return [
				'feature' => $feature['value'],
			];
		}, $features );

		edgenet()->debug->notice( __( sprintf( 'Updating %s features.', count( $features ) ), 'edgenet' ) );
		update_field( 'ussc_features', $features, $product_id );

		// Others.
		$others = get_post_meta( $product_id, '_other', true );

		if ( ! $others || ! is_array( $others ) ) {
			$others = [];
		}

		delete_field( 'ussc_other', $product_id );

		$others = array_map( function ( $other ) {
			return [
				'label' => $other['attribute']->description,
				'value' => $other['value'],
			];
		}, $others );

		edgenet()->debug->notice( __( sprintf( 'Updating %s others.', count( $others ) ), 'edgenet' ) );
		update_field( 'ussc_other', $others, $product_id );

		// Regulatory.
		$regulatory = get_post_meta( $product_id, '_regulatory', true );
		if ( ! $regulatory || ! is_array( $regulatory ) ) {
			$regulatory = [];
		}

		delete_field( 'ussc_regulatory', $product_id );

		$regulatory = array_map( function ( $regulation ) {
			return [
				'label' => $regulation['attribute']->description,
				'value' => $regulation['value'],
			];
		}, $regulatory );

		edgenet()->debug->notice( __( sprintf( 'Updating %s regulatory.', count( $regulatory ) ), 'edgenet' ) );
		update_field( 'ussc_regulatory', $regulatory, $product_id );

		// Dimensions.
		$dimensions = get_post_meta( $product_id, '_dimensions', true );
		if ( ! $dimensions || ! is_array( $dimensions ) ) {
			$dimensions = [];
		}

		delete_field( 'ussc_dimensions', $product_id );

		$dimensions = array_map( function ( $dimension ) {
			return [
				'label' => $dimension['attribute']->description,
				'value' => $dimension['value'],
			];
		}, $dimensions );

		// update field.
		edgenet()->debug->notice( __( sprintf( 'Updating %s dimensions.', count( $dimensions ) ), 'edgenet' ) );
		update_field( 'ussc_dimensions', $dimensions, $product_id );

		$specs = get_post_meta( $product_id, '_category_attributes', true );
		if ( ! is_array( $specs ) ) {
			$specs = [];
		}

		$att_meta = [];
		edgenet()->debug->notice( __( sprintf( 'Updating %s attributes.', count( $specs ) ), 'edgenet' ) );
		foreach ( $specs as $spec ) {
			$label   = $spec['attribute']->description;
			$pa_args = [
				'name' => $label,
				'slug' => substr( sanitize_title_with_dashes( preg_replace( '/[^A-Za-z0-9\-_ ]/', '', $label ) ), 0, 27 ),
				'type' => 'text',
			];

			$taxonomy = wc_attribute_taxonomy_name( $pa_args['slug'] );

			edgenet()->debug->notice( __( sprintf( 'Taxonomy: %s', $taxonomy ), 'edgenet' ) );
			$tax_name = wc_attribute_taxonomy_name( $taxonomy );
			if ( ! taxonomy_exists( $tax_name ) && ! in_array( $tax_name, $sync_taxonomies ) ) {
				edgenet()->debug->notice( __( '- Creating', 'edgenet' ) );

				$status = wc_create_attribute( $pa_args );

				$sync_taxonomies[] = $tax_name;

				edgenet()->debug->notice( __( '- Created', 'edgenet' ), $status );
			} else {
				edgenet()->debug->notice( __( '- Found', 'edgenet' ) );
			}

			if ( ! has_term( $spec['value'], $taxonomy, $product_id ) ) {
				edgenet()->debug->notice( __( sprintf( '- Assigning term "%s" to Product ID: %s', $spec['value'], $product_id ), 'edgenet' ) );
				wp_set_object_terms( $product_id, $spec['value'], $taxonomy );
			} else {
				edgenet()->debug->notice( __( sprintf( '- Term exists "%s" on Product ID: %s', $spec['value'], $product_id ), 'edgenet' ) );
			}

			$att_meta[] = [
				'name'         => $taxonomy,
				'value'        => $spec['value'],
				'is_visible'   => 1,
				'is_variation' => 1,
				'is_taxonomy'  => 1,
			];
		}

		edgenet()->debug->notice( __( '- Updating product attributes post meta', 'edgenet' ) );
		update_post_meta( $product_id, '_product_attributes', $att_meta );
	}

	public function sync_product_model_no( $product_id ) {
		$model_no = get_post_meta( $product_id, '_model_no', true );
		if ( ! empty( $model_no ) ) {
			update_field( 'ussc_model_no', $model_no, $product_id );
			edgenet()->debug->notice( __( sprintf( 'Adding Model #: %s to Product ID: %s', $model_no, $product_id ), 'edgenet' ) );
		} else {
			edgenet()->debug->notice( __( sprintf( 'No model number found for Product ID: %s', $product_id ), 'edgenet' ) );
		}
	}

	public function sync_document_product_relationship( $product_id ) {
		edgenet()->debug->notice( __( sprintf( 'Looking for documents linked to Product ID: %s', $product_id ), 'edgenet' ) );
		$documents = get_posts( [
			'post_type'    => Document::POST_TYPE,
			'meta_key'     => '_product_id_' . $product_id,
			'meta_compare' => 'EXISTS',
		] );

		if ( ! $documents ) {
			edgenet()->debug->notice( __( sprintf( 'No documents found for Product ID: %s', $product_id ), 'edgenet' ) );

			return false;
		}

		edgenet()->debug->notice( __( sprintf( 'Found %s documents linked to Product ID: %s', count( $documents ), $product_id ), 'edgenet' ) );

		foreach ( $documents as $document ) {
			// populate ACF
			$related_products = get_field( 'related_products', $document->ID );
			if ( ! $related_products ) {
				$related_products = [];
			}
			$related_products[] = $product_id;

			$related_products = array_unique( $related_products );

			update_field( 'related_products', $related_products, $document->ID );

			edgenet()->debug->notice( __( sprintf( 'Updated Document ID: %s with Product ID: %s', $document->ID, $product_id ), 'edgenet' ) );
		}

		edgenet()->debug->notice( __( sprintf( 'Finished syncing documents for Product ID: %s', $product_id ), 'edgenet' ) );
	}


	public function migrate_edgenet_attachments_to_acf_fields() {
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( __( 'Start MIGRATE_EDGENET_ATTACHMENT_TO_ACF_FIELD', 'edgenet' ) );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );

		// Get array of all product IDs
		$args = [
			'post_type'      => 'document',
			'post_status'    => 'all',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		];

		$document_ids = new \WP_Query( $args );

		edgenet()->debug->notice( __( sprintf( 'Syncing %s documents', $document_ids->post_count ), 'edgenet' ) );

		foreach ( $document_ids->posts as $document_id ) {
			edgenet()->debug->notice( __( sprintf( 'Starting sync for Product ID: %s', $document_id ), 'edgenet' ) );
			$this->migrate_edgenet_attachment_to_acf_field( $document_id );
			edgenet()->debug->notice( __( sprintf( 'Stopping sync for Product ID: %s', $document_id ), 'edgenet' ) );
		}

		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( __( 'End MIGRATE_EDGENET_ATTACHMENT_TO_ACF_FIELD', 'edgenet' ) );
		edgenet()->debug->notice( str_repeat( '*', 32 ) );
		edgenet()->debug->notice( "\n" );
		edgenet()->debug->notice( "\n" );
	}

	public function migrate_edgenet_attachment_to_acf_field( $document_id ) {
		$attachment_id = get_post_meta( $document_id, Document::META_ATTACHMENT_ID, true );
		if ( $attachment_id ) {
			update_field( 'document', $attachment_id, $document_id );
		}
	}

	/**
	 * Generate meta_input array for insert/update post.
	 *
	 * @param Product $product The Product.
	 *
	 * @return array Array of meta_input for wp_insert_post or wp_update_post.
	 */
	private function get_post_meta_input( $product ) {
		// Setup meta_input to prep for insert or update.
		$meta_input = [
			'_edgenet_id'                 => $product->id,
			'_edgenet_id_' . $product->id => $product->id,
			'_last_verified_date_time'    => $product->last_verified_date_time,
			'_is_verified'                => $product->is_verified,
			'_archived'                   => $product->archived,
			'_archived_metadata'          => $product->archived_metadata,
			'_record_date'                => $product->record_date,
			'_audit_info'                 => $product->audit_info,
			'_gtin'                       => $product->get_attribute_value( edgenet()->settings->get_field_map( '_gtin' ), '' ),
			'_sku'                        => $product->get_attribute_value( edgenet()->settings->get_field_map( '_sku' ), '' ),
			'_model_no'                   => $product->get_attribute_value( edgenet()->settings->get_field_map( '_model_no' ), '' ),
			'_models_used_with'           => $product->get_attribute_value( edgenet()->settings->get_field_map( '_models_used_with' ), '' ),
			'_regular_price'              => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->get_field_map( '_regular_price' ), '' ) ) ),
			'_price'                      => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->get_field_map( '_regular_price' ), '' ) ) ),
			'_weight'                     => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->get_field_map( '_weight' ), '' ) ) ),
			'_length'                     => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->get_field_map( '_length' ), '' ) ) ),
			'_width'                      => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->get_field_map( '_width' ), '' ) ) ),
			'_height'                     => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->get_field_map( '_height' ), '' ) ) ),
		];

		// Grab all the Features attributes from this Product.
		$features_group_id = edgenet()->settings->get_field_map( '_features' );
		if ( ! empty( $features_group_id ) ) {
			$features_attributes = edgenet()->settings->requirement_set->get_attributes_by_group_id( $features_group_id );
			// TODO: Create custom field entries.
			$meta_input['_features'] = $product->get_attributes_values( $features_attributes );
		} else {
			$meta_input['_features'] = [];
		}

		// Grab all the Dimensions attributes from this Product.
		$dimensions_group_id = edgenet()->settings->get_field_map( '_dimensions' );
		if ( ! empty( $dimensions_group_id ) ) {
			$dimensions_attributes = edgenet()->settings->requirement_set->get_attributes_by_group_id( $dimensions_group_id );

			$meta_input['_dimensions'] = $product->get_attributes_values( $dimensions_attributes );
		} else {
			$meta_input['_dimensions'] = [];
		}

		// Grab all the Other attributes from this Product.
		$other_group_id = edgenet()->settings->get_field_map( '_other' );
		if ( ! empty( $other_group_id ) ) {
			$other_attributes = edgenet()->settings->requirement_set->get_attributes_by_group_id( $other_group_id );
			// TODO: Create custom field entries.
			$meta_input['_other'] = $product->get_attributes_values( $other_attributes );
		} else {
			$meta_input['_other'] = [];
		}

		// Grab all the Regulatory attributes from this Product.
		$regulatory_group_id = edgenet()->settings->get_field_map( '_regulatory' );
		if ( ! empty( $regulatory_group_id ) ) {
			$regulatory_attributes = edgenet()->settings->requirement_set->get_attributes_by_group_id( $regulatory_group_id );
			// TODO: Create custom field entries.
			$meta_input['_regulatory'] = $product->get_attributes_values( $regulatory_attributes );
		} else {
			$meta_input['_regulatory'] = [];
		}

		return $meta_input;
	}

	/**
	 * Generate postarr args for insert/update post.
	 *
	 * @param Product $product The Product.
	 *
	 * @return array Array of args for wp_insert_post or wp_update_post.
	 */
	private function get_post_postarr( $product ) {
		// Setup postarr to prep for insert or update.
		$postarr = [
			'post_author'  => edgenet()->settings->get_import( 'user' ),
			'post_title'   => $product->get_attribute_value( edgenet()->settings->get_field_map( 'post_title' ), '' ),
			'post_content' => $product->get_attribute_value( edgenet()->settings->get_field_map( 'post_content' ), '' ),
			'post_excerpt' => $product->get_attribute_value( edgenet()->settings->get_field_map( 'post_excerpt' ), '' ),
			'post_status'  => 'publish',
			'post_type'    => 'product',
		];

		return $postarr;
	}

	/**
	 * Get the product IDs based on search parameters.
	 *
	 * @param array $search Search parameters.
	 *
	 * @return array|\WP_Error
	 */
	private function get_product_ids( $search = [] ) {

		$iteration      = 0;
		$max_iterations = 10;

		$skip = 0;
		$take = 100;

		$ids = [];

		do {
			$iteration ++;
			$results = edgenet()->api_adapter->productsearch( $search, $skip, $take );
			if ( is_wp_error( $results ) ) {
				$ids = $results;
				break;
			}
			$ids   = array_merge( $ids, $results['Results'] );
			$total = $results['TotalHitCount'];
			$skip  = $skip + $results['ResultCount'];
		} while ( $skip < $total && $iteration <= $max_iterations );

		return $ids;
	}

	/**
	 * Get a Product by Product ID.
	 *
	 * @param string $product_id The product ID.
	 *
	 * @return Item\Product|\WP_Error
	 */
	private function get_product( $product_id = '' ) {
		$product = edgenet()->api_adapter->product( $product_id );

		return $product;
	}

	/**
	 * Generate an Edgenet Image URL from Asset ID
	 *
	 * @param string $asset_id Asset ID.
	 * @param string $file_type Options are [jpg, png].
	 * @param int $size Define size of square in pixels that the image shall fit within.
	 *
	 * @return string The URL of the image on Edgenet.
	 */
	private function generate_edgenet_image_url( $asset_id, $file_type = 'jpg', $size = 1200 ) {

		$asset_url = sprintf(
			'https://assets.edgenet.com/%s',
			rawurlencode( $asset_id )
		);

		$asset_url = add_query_arg( [
			'fileType' => $file_type,
			'size'     => $size,
		], $asset_url );

		return $asset_url;
	}

	/**
	 * Generate an Edgenet Document (PDF) URL from Asset ID
	 *
	 * @param string $asset_id Asset ID.
	 *
	 * @return string The URL of the Document (PDF) on Edgenet.
	 */
	private function generate_edgenet_document_url( $asset_id ) {

		$asset_url = sprintf(
			'https://assets.edgenet.com/%s',
			rawurlencode( $asset_id )
		);

		$asset_url = add_query_arg( [
			'fileType' => 'pdf',
		], $asset_url );

		return $asset_url;
	}

	/**
	 * Sideload Asset by URL.
	 *
	 * @link   http://wordpress.stackexchange.com/a/145349/26350
	 *
	 * @param string $url URL of image to import.
	 * @param array $attachment_args Attachment arguments.
	 *
	 * @return int|\WP_Error $attachment_id The ID of the Attachment post or \WP_Error if failure.
	 */
	private function sideload_attachment( $url, $attachment_args = [] ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$parsed_url = wp_parse_url( $url );

		$args = wp_parse_args(
			$attachment_args,
			[
				'attached_post_id' => 0,
				'filename'         => basename( $parsed_url['path'] ),
				'file_ext'         => false,
				'post_title'       => basename( $parsed_url['path'] ),
				'post_author'      => edgenet()->settings->get_import( 'user' ),
				'edgenet_id'       => false,
			]
		);

		// If we have an Edgenet ID, use it to check if the Attachment exists.
		if ( ! empty( $args['edgenet_id'] ) ) {
			$query_args = [
				'meta_key'     => '_edgenet_id_' . $args['edgenet_id'], /* phpcs:ignore */
				'meta_compare' => 'EXISTS',
				'post_type'    => 'attachment',
				'post_status'  => 'inherit',
			];

			// Run the WP_Query.
			$query = new \WP_Query( $query_args );
			if ( $query->have_posts() ) {
				// Attachment exists, return the ID.
				return $query->posts[0]->ID;
			}
		}

		// Set $file_ext via exif, if not provided explicitly via $args.
		$file_ext = ( empty( $args['file_ext'] ) )
			? \image_type_to_extension( \exif_imagetype( $url ), false )
			: $args['file_ext'];

		// Save as a temporary file.
		$temp_file = download_url( $url );

		// Check for download errors.
		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		// Get file path components.
		$pathinfo = pathinfo( $temp_file );

		// Rename with correct extension so media_handle_sideload() doesn't choke.
		if ( $file_ext !== $pathinfo['extension'] ) {
			$new_filepath = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . '.' . $file_ext;

			$success = rename( $temp_file, $new_filepath );

			if ( ! $success ) {
				return new \WP_Error(
					'edgenet-file-error',
					'Unable to rename temp file.',
					[ $pathinfo, $new_filepath ]
				);
			}

			$temp_file = $new_filepath;
		}

		// Upload the attachment into the WordPress Media Library with desired filename.
		$file_array = [
			'name'     => $args['filename'] . '.' . $file_ext,
			'tmp_name' => $temp_file,
		];

		$post_data = [
			'post_title'   => $args['post_title'],
			'post_content' => $args['post_title'],
			'post_excerpt' => $args['post_title'],
			'post_author'  => $args['post_author'],
			'meta_input'   => [
				'_edgenet_id'                        => $args['edgenet_id'],
				'_edgenet_id_' . $args['edgenet_id'] => $args['edgenet_id'],
				'_wp_attachment_image_alt'           => $args['post_title'],
			],
		];

		// Set meta key to identify if the asset is an Image or Document (used by bulk delete).
		if ( 'pdf' === $file_ext ) {
			$post_data['meta_input']['_edgenet_document'] = $file_ext;
		} else {
			$post_data['meta_input']['_edgenet_image'] = $file_ext;
		}

		$id = media_handle_sideload( $file_array, $args['attached_post_id'], null, $post_data );

		// Check for sideload errors.
		if ( is_wp_error( $id ) ) {
			unlink( $file_array['tmp_name'] );
		}

		return $id;
	}

	/**
	 * Bulk Sideload Assets by Attribute Group.
	 *
	 * @param string $attribute_group_id The Attribute Group ID for Digital Assets.
	 * @param Product $product The Product.
	 * @param int $post_id The Post ID.
	 * @param string $file_ext The attachment extension, leave empty to auto-sense with exif_imagetype (Note: does not work for all file types).
	 *
	 * @return int[] Array of Attachment IDs sideloaded and attached to the post.
	 */
	private function sideload_attribute_group( $attribute_group_id, $product, $post_id = 0, $file_ext = '' ) {
		$product_image_ids = [];

		$attributes = edgenet()->settings->requirement_set->get_attributes_by_group_id( $attribute_group_id );

		foreach ( $attributes as $attribute ) {
			$asset_id = $product->get_asset_value( $attribute->id );

			if ( ! empty( $asset_id ) ) {

				$attachment_id = $this->sideload_attachment(
					( 'pdf' === $file_ext )
						? $this->generate_edgenet_document_url( $asset_id )
						: $this->generate_edgenet_image_url( $asset_id ),
					[
						'attached_post_id' => $post_id,
						'filename'         => $this->generate_attachment_filename( $product, $attribute ),
						'file_ext'         => $file_ext,
						'post_title'       => $this->generate_attachment_post_title( $product, $attribute ),
						'edgenet_id'       => $asset_id,
					]
				);

				if ( ! is_wp_error( $attachment_id ) ) {
					$product_image_ids[] = $attachment_id;
				}
			}
		}

		return $product_image_ids;
	}

	/**
	 * Assign Edgenet Taxonomy Term to Post
	 * Takes an array of Taxonomy Ids, identifies the Edgenet Term, and proceeds with that one.
	 * Generates Edgenet Taxonomy Term heirarchy from Taxonomy_Node[] if it doesn't already exist.
	 *
	 * @param string[] $taxonomy_node_ids Array of Taxonomy Node Ids.
	 * @param Product $product The Product.
	 * @param int $post_id The Product's \WP_Post id.
	 */
	private function update_edgenet_taxonomy( $taxonomy_node_ids, $product, $post_id ) {
		if ( ! empty( $taxonomy_node_ids ) ) {

			// Iterate over taxonomy nodes until we find the right one.
			foreach ( $taxonomy_node_ids as $taxonomy_node_id ) {
				$taxonomynode_path = edgenet()->api_adapter->taxonomynode_pathtoroot( $taxonomy_node_id );

				// Bypass 'other' taxonomies. We're only interested in one.
				if ( is_wp_error( $taxonomynode_path ) || ! empty( $taxonomynode_path ) && edgenet()->settings->get_api( 'taxonomy_id' ) !== $taxonomynode_path[0]->taxonomy_id ) {
					continue;
				}

				$taxonomynode_path = array_reverse( $taxonomynode_path );
				break;
			}

			if ( ! is_wp_error( $taxonomynode_path ) && ! empty( $taxonomynode_path ) ) {

				$this->update_edgenet_taxonomy_attributes( $taxonomynode_path, $product, $post_id );

				$term_args = [
					'taxonomy'     => Edgenet_Cat::TAXONOMY,
					'hide_empty'   => false,
					'meta_key'     => '_edgenet_id', /* phpcs:ignore */
					'meta_compare' => 'EXISTS',
				];

				$product_cats = get_terms( $term_args );

				$product_cats_with_meta = array_map( function ( $product_cat ) {
					$product_cat->_edgenet_id = get_term_meta( $product_cat->term_id, '_edgenet_id', true );

					return $product_cat;
				}, $product_cats );

				foreach ( $taxonomynode_path as $taxonomy_node ) {

					$existing = array_filter( $product_cats_with_meta, function ( $product_cat ) use ( $taxonomy_node ) {
						return $taxonomy_node->id === $product_cat->_edgenet_id;
					} );

					if ( ! $existing ) {
						$parent = array_filter( $product_cats, function ( $product_cat ) use ( $taxonomy_node ) {
							return $taxonomy_node->parent_id === $product_cat->_edgenet_id;
						} );

						if ( ! empty( $parent ) ) {
							$parent = array_shift( $parent );
						}

						$term = wp_insert_term(
							$taxonomy_node->description,
							Edgenet_Cat::TAXONOMY,
							[
								'parent' => ( ! empty( $parent ) ) ? $parent->term_id : 0,
							]
						);

						if ( ! is_wp_error( $term ) ) {
							add_term_meta( $term['term_id'], '_edgenet_id', $taxonomy_node->id, true );
							add_term_meta( $term['term_id'], '_edgenet_id_' . $taxonomy_node->id, $taxonomy_node->id, true );
							add_term_meta( $term['term_id'], '_edgenet_parent_id', $taxonomy_node->parent_id, true );
							add_term_meta( $term['term_id'], '_edgenet_taxonomy_id', $taxonomy_node->taxonomy_id, true );
						}

						// Refresh list of Product Categories after insert.
						$product_cats = get_terms( $term_args );

						$product_cats_with_meta = array_map( function ( $product_cat ) {
							$product_cat->_edgenet_id = get_term_meta( $product_cat->term_id, '_edgenet_id', true );

							return $product_cat;
						}, $product_cats );

					}
				}

				$leaf_term = array_filter( $product_cats_with_meta, function ( $product_cat ) use ( $taxonomy_node ) {
					return $product_cat->_edgenet_id === $taxonomy_node->id;
				} );

				if ( ! empty( $leaf_term ) ) {
					$leaf_term = array_shift( $leaf_term );
					edgenet()->debug->notice( __( sprintf( 'Setting Edgenet term: %s', $leaf_term->name ), 'edgenet' ) );
					$status = wp_set_object_terms( $post_id, $leaf_term->term_id, Edgenet_Cat::TAXONOMY );

					if ( is_wp_error( $status ) ) {
						edgenet()->debug->error( __( 'Error setting Edgenet term.', 'edgenet' ), $status );
					}
				}
			}
		}
	}

	/**
	 * Assign Edgenet Brand Term to Post
	 *
	 * @param Product $product proctuct being imported.
	 * @param int $post_id The Product's \WP_Post id.
	 *
	 * @return array|int|\WP_Error|bool
	 */
	private function update_edgenet_brand( $product, $post_id ) {
		// Get Brand Name.
		$brand = $product->get_attribute_value( edgenet()->settings->get_field_map( '_brand' ) );

		if ( ! is_wp_error( $brand ) && ! empty( $brand ) ) {

			edgenet()->debug->notice( __( sprintf( 'Setting Brand term: %s', $brand ), 'edgenet' ) );
			// add the term to the post.
			$status = wp_set_object_terms( $post_id, $brand, Brand::TAXONOMY );

			if ( is_wp_error( $status ) ) {
				edgenet()->debug->error( __( 'Error setting Brand term.', 'edgenet' ), $status );
			}

			return $status;
		}

		return false;
	}

	/**
	 * Update the Product image gallery.
	 *
	 * @param string $attribute_group_id The Edgenet Attribute Group ID.
	 * @param Product $product The Edgenet Product.
	 * @param int $post_id The WordPress \WP_Post ID of the WooCommerce product.
	 *
	 * @return int[]
	 */
	private function update_digital_assets( $attribute_group_id, $product, $post_id ) {
		if ( ! empty( $attribute_group_id ) ) {
			$attachment_ids = $this->sideload_attribute_group( $attribute_group_id, $product, $post_id );

			update_post_meta( $post_id, '_product_image_gallery', implode( ',', $attachment_ids ) );
		}

		return $attachment_ids;
	}

	/**
	 * Update the Product documents.
	 *
	 * @param string $attribute_group_id
	 * @param Product $product
	 * @param int $post_id
	 *
	 * @return int[]
	 */
	private function update_documents( $attribute_group_id, $product, $post_id ) {
		$document_ids = [];

		// Get Attributes from Document group.
		$attributes = edgenet()->settings->requirement_set->get_attributes_by_group_id( $attribute_group_id );

		foreach ( $attributes as $attribute ) {

			$attachment_id = null;

			$asset_id = $product->get_asset_value( $attribute->id, '' );

			// Skip to next Asset if ID wasn't returned.
			if ( empty( $asset_id ) ) {
				continue;
			}

			// Get the Attachment ID (new or existing).
			$attachment_id = $this->sideload_attachment(
				$this->generate_edgenet_document_url( $asset_id ),
				[
					'attached_post_id' => $post_id,
					'filename'         => $this->generate_attachment_filename( $product, $attribute ),
					'file_ext'         => 'pdf',
					'post_title'       => $this->generate_attachment_post_title( $product, $attribute ),
					'edgenet_id'       => $asset_id,
				]
			);

			// Skip to next Asset if attachment ID wasn't returned.
			if ( is_wp_error( $attachment_id ) ) {
				continue;
			}

			$postarr = [
				'post_author' => edgenet()->settings->get_import( 'user' ),
				'post_title'  => $this->generate_attachment_post_title( $product, $attribute ),
				'post_status' => 'publish',
				'post_type'   => Document::POST_TYPE,
			];

			// Setup WP_Query args to check if this product already exists.
			// @see https://vip.wordpress.com/documentation/querying-on-meta_value/ for info on this query.
			$args = [
				'meta_key'     => '_edgenet_id_' . $asset_id, /* phpcs:ignore */
				'meta_compare' => 'EXISTS',
				'post_type'    => Document::POST_TYPE,
			];

			// Run the WP_Query.
			$query = new \WP_Query( $args );

			$doc_type = str_replace( ' - PDF', '', $attribute->description );

			if ( $query->have_posts() ) {
				// Product exists! Setup post data.
				$query->the_post();

				$postarr['ID'] = $query->post->ID;

				$document_id = wp_update_post( $postarr );

				update_post_meta( $document_id, Document::META_ATTACHMENT_ID, $attachment_id );
				update_post_meta( $document_id, '_product_id_' . $post_id, $post_id );

			} else {
				$postarr['meta_input'] = [
					'_edgenet_id'                => $asset_id,
					'_edgenet_id_' . $asset_id   => $asset_id,
					'_product_id_' . $post_id    => $post_id,
					Document::META_ATTACHMENT_ID => $attachment_id,
				];

				$document_id = wp_insert_post( $postarr );

			}

			$this->set_post_term( $document_id, $doc_type, Doc_Type::TAXONOMY );

			$document_ids[] = $document_id;
		}

		return $document_ids;
	}

	/**
	 * Generate standardized Attachment title for an Asset (Doc or Img) based on the Product and Attribute Description.
	 *
	 * @param Product $product The Edgenet Product.
	 * @param Attribute $attribute The Edgenet Attribute.
	 * @param string $delim Delimiter between Product identifier and document description.
	 *
	 * @return string
	 */
	private function generate_attachment_post_title( $product, $attribute, $delim = ' - ' ) {

		// Prefer Model# for the attachment prefix.
		$prefix = $product->get_attribute_value( edgenet()->settings->get_field_map( '_model_no' ), '' );

		// Fallback to SKU (should be set to Edgenet UPC) if Model not set.
		// Final fallback to "PRODUCT" should _never_ occur as Model and SKU are mandatory fields.
		if ( empty( $prefix ) ) {
			$prefix = $product->get_attribute_value( edgenet()->settings->get_field_map( '_sku' ), __( 'PRODUCT', 'edgenet' ) );
		}

		$description = $attribute->description;

		// Strip the ' - PDF' from the end of the description, if it exists. Applicable for Document attributes.
		$suffix = str_replace( ' - PDF', '', $description );

		$title = sprintf(
			'%s%s%s',
			$prefix,
			$delim,
			$suffix
		);

		return $title;
	}

	/**
	 * Generate standardized Attachment filename for an Asset (Doc or Img) based on the Product and Attribute Description.
	 *
	 * @param Product $product The Edgenet Product.
	 * @param Attribute $attribute The Edgenet Attribute.
	 * @param string $delim Delimeter between Product identifier and document description.
	 *
	 * @return string
	 */
	private function generate_attachment_filename( $product, $attribute, $delim = '-' ) {

		// Prefer Model# for the document prefix.
		$prefix = $product->get_attribute_value( edgenet()->settings->get_field_map( '_model_no' ), '' );

		// Fallback to SKU (should be set to Edgenet UPC) if Model not set.
		// Final fallback to "PRODUCT" should _never_ occur as Model and SKU are mandatory fields.
		if ( empty( $prefix ) ) {
			$prefix = $product->get_attribute_value( edgenet()->settings->get_field_map( '_sku' ), __( 'PRODUCT', 'edgenet' ) );
		}

		$description = $attribute->description;

		// Strip the ' - PDF' from the end of the string, if it exists.
		$suffix = str_replace( ' - PDF', '', $description );

		return sanitize_title( sprintf(
			'%s%s%s',
			$prefix,
			$delim,
			$suffix
		) );

	}

	/**
	 * Set specified taxonomy term to the incoming post object. If
	 * the term doesn't already exist in the database, it will be created.
	 *
	 * @param int $post_id The post to which we're adding the taxonomy term.
	 * @param string $value The name of the taxonomy term.
	 * @param string $taxonomy The name of the taxonomy.
	 *
	 * @access   private
	 * @since    1.0.0
	 */
	private function set_post_term( $post_id, $value, $taxonomy ) {
		$term = term_exists( $value, $taxonomy );
		// If the taxonomy doesn't exist, then we create it.
		if ( 0 === $term || null === $term ) {
			$term = wp_insert_term(
				$value,
				$taxonomy,
				[ 'slug' => strtolower( str_ireplace( ' ', '-', $value ) ) ]
			);
		}
		// Then we can set the post - term relationship.
		wp_set_post_terms( $post_id, $term['term_id'], $taxonomy );
	}

	/**
	 * Parse Taxonomy Path for attributes and store attribute-value array in post meta.
	 *
	 * @param array $taxonomynode_path
	 * @param Product $product
	 * @param int $post_id
	 */
	private function update_edgenet_taxonomy_attributes( $taxonomynode_path, $product, $post_id ) {
		foreach ( $taxonomynode_path as $taxonomynode ) {
			if ( isset( $taxonomynode->attributes ) && ! empty( $taxonomynode->attributes ) ) {

				$attribute_ids = array_map( function ( $attribute ) {
					return $attribute['BaseAttribute'];
				}, $taxonomynode->attributes );

				$attributes = edgenet()->api_adapter->attribute( $attribute_ids );

				$meta = $product->get_attributes_values( $attributes );

				update_post_meta( $post_id, '_category_attributes', (array) $meta );
			}
		}
	}
}
