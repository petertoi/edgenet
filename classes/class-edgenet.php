<?php
/**
 * @package Edgenet
 * @since   1.0.0
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet;

use Edgenet\Post_Types\Document;
use Edgenet\Taxonomies\Brand;
use Edgenet\Taxonomies\Doc_Type;
use Edgenet\Taxonomies\Edgenet_Cat;

/**
 * Class WP_Vote
 *
 * @package WP_Vote
 */
class Edgenet {
	use Singleton;

	const VERSION = '1.0.0';

	const ACTIVE_CRON_KEY = 'edgenet_cron_active';

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
	 * Assets manifest
	 *
	 * @var array
	 */
	public $assets;

	/**
	 * Reference to Debug object.
	 *
	 * @var Debug
	 */
	public $debug;

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
	 * Reference to the Importer.
	 *
	 * @var Importer
	 */
	public $importer;

	/**
	 * Edgenet constructor.
	 */
	private function __construct() {
		$this->plugin_path = plugin_dir_path( __DIR__ );
		$this->plugin_url  = plugin_dir_url( __DIR__ );

		$this->debug = Debug::get_instance();

		// Init Debug early.
		if ( defined( 'EDGENET_DEBUG' ) && EDGENET_DEBUG ) {
			$this->debug->enable( Debug::LOG_ALL ^ Debug::LOG_INFO );
		}

		// Init Post Types & Taxonomies.
		new Document();
		new Doc_Type();
		new Edgenet_Cat();
		new Brand();

		// Init Settings.
		$this->settings = new Settings();

		// Init API.
		$this->api_adapter = new API_Adapter(
			$this->settings->get_api( 'username' ),
			$this->settings->get_api( 'secret' ),
			$this->settings->get_api( 'data_owner' ),
			new API()
		);

		// Init Importer.
		$this->importer = new Importer();

		// Init CRON.
		new CRON();

		// Init admin.
		if ( is_admin() ) {
			new Admin();
			new Woo_Product();
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
	 * Get the absolute URL for CSS and JS files.
	 * Parses dist/assets.json to retrieve production asset if they exist.
	 *
	 * @param string $path Relative path to asset in dist/ folder, ex: 'scripts/main.js'.
	 *
	 * @return string
	 */
	public function get_assets_url( $path ) {
		if ( ! isset( $this->assets ) ) {
			$manifest = $this->get_plugin_path( 'dist/assets.json' );
			if ( file_exists( $manifest ) ) {
				$this->assets = json_decode( file_get_contents( $manifest ), true ); // phpcs:ignore
			} else {
				$this->assets = [];
			}
		}

		$url = ( isset( $this->assets[ $path ] ) )
			? $this->get_plugin_url( 'dist/' . $this->assets[ $path ] )
			: $this->get_plugin_url( 'dist/' . $path );

		return $url;
	}
}
