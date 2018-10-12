<?php
/**
 * @package USSC_Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet;

use USSC_Edgenet\Post_Types\Document;
use USSC_Edgenet\Taxonomies\Doc_Type;

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

	const TAXONOMY_ID = 'cf5b4e7e-30ab-4c04-ba3e-bad6f9e853e2';

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
	 * Debug mode
	 *
	 * @var bool
	 */
	public $debug = false;

	/**
	 * Assets manifest
	 *
	 * @var array
	 */
	public $assets;

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
	 * USSC_Edgenet constructor.
	 */
	private function __construct() {
		$this->plugin_path = plugin_dir_path( __DIR__ );
		$this->plugin_url  = plugin_dir_url( __DIR__ );

		// Init Debug early.
		if ( defined( 'USSC_EDGENET_DEBUG' ) && USSC_EDGENET_DEBUG ) {
			$this->debug = true;
		}

		// Init Post Types & Taxonomies.
		new Document();
		new Doc_Type();
		new Taxonomies\Edgenet_Cat();

		// Init Settings.
		$this->settings = new Settings();

		// Init API.
		$this->api_adapter = new API_Adapter(
			self::PROD_USERNAME,
			self::PROD_SECRET,
			self::DATA_OWNER,
			new API()
		);

		// Init Importer.
		$this->importer = new Importer();

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
