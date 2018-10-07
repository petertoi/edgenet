<?php
/**
 * @package USSC_Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet;

use USSC_Edgenet\Item\Attribute_Group;

/**
 * Class WP_Vote
 *
 * @package WP_Vote
 */
class Edgenet {
	use Singleton;

	const VERSION = '1.0.0-alpha';

	const PROD_USERNAME = 'UNITEDSTATESSTOVECOMPANY_API';

	const PROD_SECRET = 'y2T3adTgEHGMdspMRz0R0ZCkkdfcPK/ciZFXkdMIp6wCymefIU5104J+YhdqvIynKVQoS15uTEqCyFhAZ/4w8A==';

	const DATA_OWNER = '6a4bdd27-199b-4751-b10e-fa8273e84745';

	const REQUIREMENT_SET = 'c726fa92-7119-2e37-30fe-304a1a3e579d';


	/**
	 * Absolute path to plugin folder on server with trailing slash
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Absolute URL to plugin folder with trailing slash
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Debug mode
	 *
	 * @var bool
	 */
	public $debug = false;

	/**
	 * Settings
	 *
	 * @var Settings
	 */
	public $settings;

	/**
	 * Reference to the API Adapter.
	 *
	 * @var API_Adapter
	 */
	public $api_adapter;

	/**
	 * kewy for the cron controler.
	 *
	 * @var cron_running_key
	 */
	public  $cron_running_key;
	/**
	 * USSC_Edgenet constructor.
	 */
	private function __construct() {
		$this->plugin_path = plugin_dir_path( __DIR__ );
		$this->plugin_url  = plugin_dir_url( __DIR__ );

		// Init Debug early.
		if ( defined( 'USSC_EDGENET_DEBUG' ) && USSC_EDGENET_DEBUG ) {
			$this->debug = true;
		}

		$this->settings = new Settings();

		$this->api_adapter = new API_Adapter(
			self::PROD_USERNAME,
			self::PROD_SECRET,
			self::DATA_OWNER,
			new API()
		);

		// Init admin.
		add_action( 'plugins_loaded', [ $this, 'admin_init' ], 10 );
		add_action( 'edgenet_sync_now', [ $this, 'import_products' ] );
	}

	/**
	 * Init Admin
	 *
	 * @hook wp
	 * @hook admin_init
	 */
	public function admin_init() {
		if ( is_admin() ) {
			new Admin();
		}
	}

	/**
	 * Get the absolute file path.
	 *
	 * @param string $relative Path fragment to append to absolute file path.
	 *
	 * @return string
	 */
	public function get_plugin_path( $relative = '' ) {
		return $this->plugin_path . $relative;
	}

	/**
	 * Get the absolute url path.
	 *
	 * @param string $relative Path fragment to append to absolute web path.
	 *
	 * @return string
	 */
	public function get_plugin_url( $relative = '' ) {
		return $this->plugin_url . $relative;
	}

	/**
	 * Update Edgenet Distribution Requirement Set configuration.
	 *
	 * @param string $set_uid The requirement set UID.
	 *
	 * @return Item\Requirement_Set|\WP_Error
	 */
	public function update_requirement_set( $set_uid ) {

		$set = $this->api_adapter->requirementset( $set_uid );

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
				$chunk_objects     = $this->api_adapter->attribute( $chunk_ids );
				$attribute_objects = array_merge( $attribute_objects, $chunk_objects );
			}

			$group->attributes = $attribute_objects;
		}

		edgenet()->settings->save_requirement_set( $set );

		return $set;

	}

	/**
	 * Get the product IDs based on search parameters.
	 *
	 * @param array $search Search parameters.
	 *
	 * @return array|\WP_Error
	 */
	public function get_product_ids( $search = [] ) {

		$iteration      = 0;
		$max_iterations = 10;

		$skip = 0;
		$take = 100;

		$ids = [];

		do {
			$iteration ++;
			$results = $this->api_adapter->productsearch( $search, $skip, $take );
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
	 * Get a Product by UID.
	 *
	 * @param string $product_uid The product UID.
	 *
	 * @return array|Item\Product|\WP_Error
	 */
	public function get_product( $product_uid = '' ) {
		$results = $this->api_adapter->product( $product_uid );

		return $results;
	}

	/**
	 * Utility function to import all products.
	 */
	public function import_products( $id = null ) {
		global $post;

		$single = false;

		if( null === $id ){
			set_transient( $this->cron_running_key, true, MINUTE_IN_SECONDS * 5   );
			$product_ids = $this->get_product_ids([
				'Archived'                 => false,
				'DataOwner'                => self::DATA_OWNER,
				'Desc'                     => false,
				'Recipients'               => [ 'c964a170-12e7-4e70-bc72-11016d97864f' ],
				'SubscriptionStatusFilter' => 'All',
			]);
		}elseif (is_array( $id ) ){
			$product_ids = $id;
		}else {
			$product_ids = array( $id );
			$single = true;
		}
//		$product_ids = ['8f6863de-47f0-426d-bf84-b6e8b9cc68e1'];

		wp_defer_term_counting( true );

		foreach ( $product_ids as $product_id ) {
			$product = $this->get_product( $product_id );

			if( is_wp_error( $product ) ){

				continue;
			}

			$args = [
				'meta_key'   => '_edgenet_id',
				'meta_value' => $product->id,
				'post_type'    => 'product',
			];

			$meta_input = [
				'_edgenet_id'              => $product->id,
				'_last_verified_date_time' => $product->last_verified_date_time,
				'_is_verified'             => $product->is_verified,
				'_archived'                => $product->archived,
				'_archived_metadata'       => $product->archived_metadata,
				'_record_date'             => $product->record_date,
				'_audit_info'              => $product->audit_info,
				'_gtin'                    => $product->get_attribute_value( $this->settings->_gtin, '' ),
				'_sku'                     => $product->get_attribute_value( $this->settings->_sku, '' ),
				'_regular_price'           => $product->get_attribute_value( $this->settings->_regular_price, '' ),
				'_price'                   => $product->get_attribute_value( $this->settings->_regular_price, '' ),
				'_weight'                  => $product->get_attribute_value( $this->settings->_weight, '' ),
				'_length'                  => $product->get_attribute_value( $this->settings->_length, '' ),
				'_width'                   => $product->get_attribute_value( $this->settings->_width, '' ),
				'_height'                  => $product->get_attribute_value( $this->settings->_height, '' ),
			];

			$postarr = [
				'post_author'  => $this->settings->api['import_user'],
				'post_title'   => $product->get_attribute_value( $this->settings->post_title, '' ),
				'post_content' => $product->get_attribute_value( $this->settings->post_content, '' ),
				'post_excerpt' => $product->get_attribute_value( $this->settings->post_excerpt, '' ),
				'post_status'  => 'publish',
				'post_type'    => 'product',
			];

			$query = new \WP_Query( $args );

			if ( $query->have_posts() ) {
				$query->the_post();

				$last_verified_meta      = get_post_meta( $post->ID, '_last_verified_date_time', true );
				$last_verified_date_time = new \DateTime( $last_verified_meta );

				$import_last_verified_date_time = new \DateTime( $product->last_verified_date_time );



				if ( $import_last_verified_date_time <= $last_verified_meta || $single  ) {
					// update
					// code not here yet

					$postarr['post_id']  = $query->ID;
					$result = wp_update_post( $postarr, true );

					if ( is_wp_error( $result ) ) {

						die();
					}
					// Iterate over Marketing fields and add to meta_input
					$id = $this->settings->_marketing;
					$meta_input['_marketing'] = $this->get_attribute_data( $id, $product );

					// Iterate over Specification fields and add to meta_input
					$id = $this->settings->_specifications;
					$meta_input['_specifications'] = $this->get_attribute_data( $id, $product );

					if ( ! empty( $meta_input ) ) {
						foreach ( $meta_input as $field => $value ) {
							update_post_meta( $query->ID, $field, $value );
						}
					}

					// Sideload Digital Assets to image gallery + primary image.
					$primary_image_id = $this->upload_image_from_id( $product->get_asset_value( $this->settings->_primary_image ), $query->ID );

					if ( ! is_wp_error( $primary_image_id ) ) {
						update_post_meta( $result, '_thumbnail_id', $primary_image_id );
					}

					// upload Assets
					$id = $this->settings->_digital_assets;
					$this->insert_digital_assets( $id, $product, $result );

				} else {
//					echo 'skiped';
				}
			} else {
				// insert


				// Iterate over Marketing fields and add to meta_input
				$id = $this->settings->_marketing;
				$meta_input['_marketing'] = $this->get_attribute_data( $id, $product );

				// Iterate over Specification fields and add to meta_input
				$id = $this->settings->_specifications;
				$meta_input['_specifications'] = $this->get_attribute_data( $id, $product );

				$postarr['meta_input' ] = $meta_input;

				$result = wp_insert_post( $postarr, true );

				if ( is_wp_error( $result ) ) {

					die();
				}

				// Sideload Digital Assets to image gallery + primary image.
				$primary_image_id = $this->upload_image_from_id( $product->get_asset_value( $this->settings->_primary_image ), $result );

				if ( ! is_wp_error( $primary_image_id ) ) {
					update_post_meta( $result, '_thumbnail_id', $primary_image_id );
				}

				// upload Assets
				$id = $this->settings->_digital_assets;
				$this->insert_digital_assets( $id, $product, $result );



				// Sideload Documents to post.
				$id = $this->settings->_documents;
				$file_data = array();
				$spec_attributes = $this->settings->requirement_set->get_attribute_group_attributes( $id );
				$spec_attributes = array_map( function ( $attr ) use ( $product ){
					$attr->value = $product->get_attribute_value( $attr->id );
					return $attr;
				}, $spec_attributes ) ;
				foreach( $spec_attributes as $data ) {
					//TODO: create posts fro docs with tax
//					$file = $this->upload_image_from_url( $edgenet_image_url );
//
//					if ( ! is_wp_error( $file_id ) ) {
//						update_post_meta( $result, '_thumbnail_id', $file_id );
//					}
				}
//				update_post_meta( $result, '_documents', $file_data );

				wp_defer_term_counting( false );

				delete_transient( $this->cron_running_key );
			}
		}
	}

	/**
	 * Upload an image from an url, with support for filenames without an extension
	 *
	 * @link   http://wordpress.stackexchange.com/a/145349/26350
	 *
	 * @param  string $image_url
	 *
	 * @return string $attachment_url
	 */
	function upload_image_from_id( $image_id, $post_id = 0 ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		// let look the image by ID so we down re-upload
		$attachment = get_page_by_title($image_id, OBJECT, 'attachment');

		if( null !== $attachment ) {

			return $attachment->ID;
		}

		$image_url = sprintf(
			'https://assets.edgenet.com/%s?fileType=jpg&size=2400',
			$image_id
		);

		// Get the file extension for the image.
		$file_ext = image_type_to_extension( exif_imagetype( $image_url ), false );

		// Save as a temporary file.
		$temp_file = download_url( $image_url );

		// Check for download errors.
		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		// Get file path components.
		$pathinfo = pathinfo( $temp_file );

		// Rename with correct extension.
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

		// Upload the image into the WordPress Media Library.
		$file_array = [
			'name'     => basename( $temp_file ),
			'tmp_name' => $temp_file,
		];

		$id = media_handle_sideload( $file_array, $post_id );

		// Check for sideload errors.
		if ( is_wp_error( $id ) ) {
			unlink( $file_array['tmp_name'] );
		}

		return $id;
	}

	/**
	 *
	 *
	 * @param $id
	 * @param $product
	 * @param $post_id
	 *
	 * @return void
	 */
	public function insert_digital_assets( $id, $product, $post_id ) {
		$image_data      = array();
		$spec_attributes = $this->settings->requirement_set->get_attribute_group_attributes( $id );
		$spec_attributes = array_map( function ( $attr ) use ( $product ) {
			$attr->value = $product->get_attribute_value( $attr->id );

			return $attr;
		}, $spec_attributes );
		foreach ( $spec_attributes as $data ) {
			$value         = $product->get_asset_value( $data->id );
			$image_id      = null;
			$woo_image_ids = array();
			if ( ! empty( $value ) ) {


				$image_id = $this->upload_image_from_id( $value, $post_id );
			}
			$data->wp_id     = $image_id;
			$woo_image_ids[] = $image_id;

			$image_data[ $data->id ] = $data;
		}
		update_post_meta( $post_id, '_digital_assets', $image_data );
		update_post_meta( $post_id, '_product_image_gallery', implode( ',', $woo_image_ids ) );

	}

	/**
	 *
	 *
	 * @param $id
	 * @param $product
	 *
	 * @return array
	 */
	public function get_attribute_data( $id, $product ) {
		$spec_data       = array();
		$spec_attributes = $this->settings->requirement_set->get_attribute_group_attributes( $id );
		$spec_attributes = array_map( function ( $attr ) use ( $product ) {
			$attr->value = $product->get_attribute_value( $attr->id );

			return $attr;
		}, $spec_attributes );
		foreach ( $spec_attributes as $data ) {
			$spec_data[ $data->id ] = $data;
		}

		return $spec_data;
	}


}