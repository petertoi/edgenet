<?php
/**
 * Filename class-verified-content.php
 *
 * @package syndigo
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet\Item;


/**
 * Class Verified_Content
 *
 * Summary
 *
 * @package Syndigo\Item
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.2
 */
class Verified_Content {

	const ITEM_PRODUCT = 'Product';

	const TYPE = 'VerifiedContent';

	public $id;
	public $enclosed_item;
	public $verification_date;
	public $audit_info;

	public function __construct( $params, $item_type ) {
		$this->id                = $params['id'];
		$this->verification_date = $params['VerificationDate'];
		$this->audit_info        = $params['AuditInfo'];

		switch ( $item_type ) {
			case self::ITEM_PRODUCT:
				$this->set_product_item( $params );
		}
	}

	public function set_product_item( $params ) {
		if ( isset( $params[ Product::TYPE ] ) ) {
			$this->enclosed_item = $params[ Product::TYPE ];
		} else {
			$this->enclosed_item = new \WP_Error(
				'syndigo-verified-content-error',
				__( 'Unable to set Verified Content item.', 'syndigo' ),
				$params
			);
		}
	}
}
