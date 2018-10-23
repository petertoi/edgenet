<?php
/**
 * Filename class-importer.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet;

use USSC_Edgenet\Item\Attribute;
use USSC_Edgenet\Item\Attribute_Group;
use USSC_Edgenet\Item\Product;
use USSC_Edgenet\Post_Types\Document;
use USSC_Edgenet\Taxonomies\Brand;
use USSC_Edgenet\Taxonomies\Doc_Type;
use USSC_Edgenet\Taxonomies\Edgenet_Cat;

/**
 * Class Importer
 *
 * Summary
 *
 * @package USSC_Edgenet
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
	 * @param array $product_ids  Specific Product ID(s) to import. Leave null for all products.
	 * @param bool  $force_update Force update products regardless of verified date.
	 *
	 * @return array|\WP_Error Array of Product IDs or WP_Error if another import already running.
	 */
	public function import_products( $product_ids = [], $force_update = false ) {
		$status = [];

		// Validate input.
		if ( ! is_array( $product_ids ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				wp_kses_post( __( 'Please provide an array of Product IDs', 'ussc' ) ),
				'1.0.0'
			);
		}

		// Check if we're already in the process of importing.
		$import_active = get_transient( self::META_IMPORT_MUTEX );

		if ( $import_active ) {
			return new \WP_Error(
				'ussc-edgenet-import-error',
				__( 'Another import is still underway. Please try again later.', 'ussc' )
			);
		}

		// Set flag to block consecutive imports from occuring. Expires in 30 seconds.
		set_transient( self::META_IMPORT_MUTEX, true, MINUTE_IN_SECONDS / 2 );

		// Get $product_ids via API if not provided.
		if ( empty( $product_ids ) ) {
			$product_ids = $this->get_product_ids( [
				'DataOwner'                => Edgenet::DATA_OWNER,
				'Archived'                 => false,
				'Desc'                     => false,
				'Recipients'               => [ 'c964a170-12e7-4e70-bc72-11016d97864f' ],
				'SubscriptionStatusFilter' => 'All',
			] );
		}

		// Defer term counting while doing a batch import.
		wp_defer_term_counting( true );

		foreach ( $product_ids as $product_id ) {

			// Reset flag to block consecutive imports from occuring. Expires in 30 seconds.
			set_transient( self::META_IMPORT_MUTEX, true, MINUTE_IN_SECONDS / 2 );

			$status[] = $this->import_product( $product_id, $force_update );

		}

		// Re-enable term counting to sync taxonomy term counts.
		wp_defer_term_counting( false );

		// Clear import mutex.
		delete_transient( self::META_IMPORT_MUTEX );

		return $status;
	}

	/**
	 * Import a single Product
	 *
	 * @param int  $product_id   The product ID to import.
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

		// Bail early if we're unable to get the Product record.
		if ( is_wp_error( $product ) ) {
			return $product;
		}

		// Setup WP_Query args to check if this product already exists.
		// @see https://vip.wordpress.com/documentation/querying-on-meta_value/ for info on this query.
		$args = [
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

			$last_verified_meta      = get_post_meta( $post->ID, '_last_verified_date_time', true );
			$last_verified_date_time = new \DateTime( $last_verified_meta );

			$import_last_verified_date_time = new \DateTime( $product->last_verified_date_time );

			// Does this product need to be updated? Check verified dates, or force_update.
			if ( $import_last_verified_date_time > $last_verified_date_time || $force_update ) {
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
			} else {
				// No update, set reference to post for assets, files, and taxonomy calls yet to come.
				$update_skipped = true;
				$post_id        = $post->ID;
			}
		} else {
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

		// Sideload Primary Image.
		$primary_image_id = $product->get_asset_value( edgenet()->settings->_primary_image );

		if ( $primary_image_id ) {
			$primary_image_attribute = edgenet()->settings->requirement_set->get_attribute_by_id( edgenet()->settings->_primary_image );
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
				update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
			}

			unset( $attachment_id );
		}

		// Sideload Other Images.
		$digital_assets_group_id = edgenet()->settings->_digital_assets;
		$attachment_ids          = $this->update_digital_assets( $digital_assets_group_id, $product, $post_id );

		// Sideload Documents.
		$document_group_id = edgenet()->settings->_documents;
		$document_ids      = $this->update_documents( $document_group_id, $product, $post_id );

		// Set Product Categories.
		$taxonomy_node_ids = $product->taxonomy_node_ids;
		$this->update_edgenet_taxonomy( $taxonomy_node_ids, $product, $post_id );

		// Set Brand.
		$this->update_edgenet_brand( $product, $post_id );

		return $post_id;
	}

	/**
	 * Sync Edgenet Terms to Product Categories
	 *
	 * @return array
	 */
	public function sync_edgenet_cat_to_product_cat() {
		$sync_status = [];

		/**
		 * Array of edgenet_cat terms.
		 *
		 * @var \WP_Term[] $edgenet_terms
		 */
		$edgenet_terms = get_terms( [
			'taxonomy'   => Edgenet_Cat::TAXONOMY,
			'hide_empty' => false, // TODO: should push empty tax?
		] );

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

		return $sync_status;

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
			'_gtin'                       => $product->get_attribute_value( edgenet()->settings->_gtin, '' ),
			'_sku'                        => $product->get_attribute_value( edgenet()->settings->_sku, '' ),
			'_model_no'                   => $product->get_attribute_value( edgenet()->settings->_model_no, '' ),
			'_regular_price'              => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->_regular_price, '' ) ) ),
			'_price'                      => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->_regular_price, '' ) ) ),
			'_weight'                     => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->_weight, '' ) ) ),
			'_length'                     => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->_length, '' ) ) ),
			'_width'                      => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->_width, '' ) ) ),
			'_height'                     => floatval( str_replace( [ ',' ], [ '' ], $product->get_attribute_value( edgenet()->settings->_height, '' ) ) ),
		];

		// Grab all the Features attributes from this Product.
		$features_group_id = edgenet()->settings->_features;
		if ( ! empty( $features_group_id ) ) {
			$features_attributes = edgenet()->settings->requirement_set->get_attributes_by_group_id( $features_group_id );

			$meta_input['_features'] = $product->get_attributes_values( $features_attributes );
		} else {
			$meta_input['_features'] = [];
		}

		// Grab all the Dimensions attributes from this Product.
		$dimensions_group_id = edgenet()->settings->_dimensions;
		if ( ! empty( $dimensions_group_id ) ) {
			$dimensions_attributes = edgenet()->settings->requirement_set->get_attributes_by_group_id( $dimensions_group_id );

			$meta_input['_dimensions'] = $product->get_attributes_values( $dimensions_attributes );
		} else {
			$meta_input['_dimensions'] = [];
		}

		// Grab all the Other attributes from this Product.
		$other_group_id = edgenet()->settings->_other;
		if ( ! empty( $other_group_id ) ) {
			$other_attributes = edgenet()->settings->requirement_set->get_attributes_by_group_id( $other_group_id );

			$meta_input['_other'] = $product->get_attributes_values( $other_attributes );
		} else {
			$meta_input['_other'] = [];
		}

		// Grab all the Regulatory attributes from this Product.
		$regulatory_group_id = edgenet()->settings->_regulatory;
		if ( ! empty( $regulatory_group_id ) ) {
			$regulatory_attributes = edgenet()->settings->requirement_set->get_attributes_by_group_id( $regulatory_group_id );

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
			'post_author'  => edgenet()->settings->import['user'],
			'post_title'   => $product->get_attribute_value( edgenet()->settings->post_title, '' ),
			'post_content' => $product->get_attribute_value( edgenet()->settings->post_content, '' ),
			'post_excerpt' => $product->get_attribute_value( edgenet()->settings->post_excerpt, '' ),
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
	 * @param string $asset_id  Asset ID.
	 * @param string $file_type Options are [jpg, png].
	 * @param int    $size      Define size of square in pixels that the image shall fit within.
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
	 * @param string $url             URL of image to import.
	 * @param array  $attachment_args Attachment arguments.
	 *
	 * @return int|\WP_Error $attachment_id The ID of the Attachment post or \WP_Error if failure.
	 */
	public function sideload_attachment( $url, $attachment_args = [] ) {
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
				'post_author'      => edgenet()->settings->importer['import_user'],
				'edgenet_id'       => false,
			]
		);

		// If we have an Edgenet ID, use it to check if the Attachment exists.
		if ( ! empty( $args['edgenet_id'] ) ) {
			$query_args = [
				'meta_key'     => '_edgenet_id_' . $args['edgenet_id'], /* phpcs:ignore */
				'meta_compare' => 'EXISTS',
				'post_type'    => 'product',
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
			? image_type_to_extension( exif_imagetype( $url ), false )
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
					'ussc-edgenet-file-error',
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

		if ( ! empty( $args['edgenet_id'] ) ) {
			add_post_meta( $id, '_edgenet_id', $args['edgenet_id'], true );
			add_post_meta( $id, '_edgenet_id_' . $args['edgenet_id'], $args['edgenet_id'], true );
		}

		return $id;
	}

	/**
	 * Bulk Sideload Assets by Attribute Group.
	 *
	 * @param string  $attribute_group_id The Attribute Group ID for Digital Assets.
	 * @param Product $product            The Product.
	 * @param int     $post_id            The Post ID.
	 * @param string  $file_ext           The attachment extension, leave empty to auto-sense with exif_imagetype (Note: does not work for all file types).
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
	 * @param Product  $product           The Product.
	 * @param int      $post_id           The Product's \WP_Post id.
	 */
	private function update_edgenet_taxonomy( $taxonomy_node_ids, $product, $post_id ) {
		if ( ! empty( $taxonomy_node_ids ) ) {

			// Iterate over taxonomy nodes until we find the right one.
			foreach ( $taxonomy_node_ids as $taxonomy_node_id ) {
				$taxonomynode_path = edgenet()->api_adapter->taxonomynode_pathtoroot( $taxonomy_node_id );

				// Bypass 'other' taxonomies. We're only interested in one.
				if ( is_wp_error( $taxonomynode_path ) || ! empty( $taxonomynode_path ) && Edgenet::TAXONOMY_ID !== $taxonomynode_path[0]->taxonomy_id ) {
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
					wp_set_object_terms( $post_id, $leaf_term->term_id, Edgenet_Cat::TAXONOMY );
				}
			}
		}
	}

	/**
	 * Assign Edgenet Brand Term to Post
	 *
	 * @param Product $product proctuct being imported.
	 * @param int     $post_id The Product's \WP_Post id.
	 *
	 * @return array|int|\WP_Error|bool
	 */
	private function update_edgenet_brand( $product, $post_id ) {
		// Get Brand Name.
		$brand = $product->get_attribute_value( edgenet()->settings->_brand );

		if ( ! is_wp_error( $brand ) && ! empty( $brand ) ) {

			// add the term to the post
			$done = wp_set_object_terms( $post_id, $brand, Brand::TAXONOMY );

			return $done;
		}

		return false;
	}

	/**
	 * Update the Product image gallery.
	 *
	 * @param string  $attribute_group_id The Edgenet Attribute Group ID.
	 * @param Product $product            The Edgenet Product.
	 * @param int     $post_id            The WordPress \WP_Post ID of the WooCommerce product.
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
	 * @param string  $attribute_group_id
	 * @param Product $product
	 * @param int     $post_id
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
				'post_author' => edgenet()->settings->import['user'],
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

				$this->set_post_term( $document_id, $doc_type, Doc_Type::TAXONOMY );

			} else {
				$postarr['meta_input'] = [
					'_edgenet_id_' . $asset_id   => $asset_id,
					Document::META_ATTACHMENT_ID => $attachment_id,
				];

				$document_id = wp_insert_post( $postarr );

				$this->set_post_term( $document_id, $doc_type, Doc_Type::TAXONOMY );
			}

			$document_ids[] = $document_id;
		}

		return $document_ids;
	}

	/**
	 * Generate standardized Attachment title for an Asset (Doc or Img) based on the Product and Attribute Description.
	 *
	 * @param Product   $product   The Edgenet Product.
	 * @param Attribute $attribute The Edgenet Attribute.
	 * @param string    $delim     Delimiter between Product identifier and document description.
	 *
	 * @return string
	 */
	private function generate_attachment_post_title( $product, $attribute, $delim = ' - ' ) {

		// Prefer Model# for the attachment prefix.
		$prefix = $product->get_attribute_value( edgenet()->settings->_model_no, '' );

		// Fallback to SKU (should be set to Edgenet UPC) if Model not set.
		// Final fallback to "PRODUCT" should _never_ occur as Model and SKU are mandatory fields.
		if ( empty( $prefix ) ) {
			$prefix = $product->get_attribute_value( edgenet()->settings->_sku, __( 'PRODUCT', 'ussc' ) );
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
	 * @param Product   $product   The Edgenet Product.
	 * @param Attribute $attribute The Edgenet Attribute.
	 * @param string    $delim     Delimeter between Product identifier and document description.
	 *
	 * @return string
	 */
	private function generate_attachment_filename( $product, $attribute, $delim = '-' ) {

		// Prefer Model# for the document prefix.
		$prefix = $product->get_attribute_value( edgenet()->settings->_model_no, '' );

		// Fallback to SKU (should be set to Edgenet UPC) if Model not set.
		// Final fallback to "PRODUCT" should _never_ occur as Model and SKU are mandatory fields.
		if ( empty( $prefix ) ) {
			$prefix = $product->get_attribute_value( edgenet()->settings->_sku, __( 'PRODUCT', 'ussc' ) );
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
	 * @param    int    $post_id  The post to which we're adding the taxonomy term.
	 * @param    string $value    The name of the taxonomy term.
	 * @param    string $taxonomy The name of the taxonomy.
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
		wp_set_post_terms( $post_id, $value, $taxonomy );
	}

	/**
	 * Parse Taxonomy Path for attributes and store attribute-value array in post meta.
	 *
	 * @param array   $taxonomynode_path
	 * @param Product $product
	 * @param int     $post_id
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