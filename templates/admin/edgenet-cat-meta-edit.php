<?php
/**
 * Filename edgenet-cat-meta-update.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<tr class="form-field">
	<h2><?php esc_html_e( 'WooCommerce Product Category Map', 'ussc' ); ?></h2>

	<th scope="row" valign="top">
		<label><?php esc_html_e( 'Select matching categories to map to:', 'ussc' ); ?></label>
	</th>
	<td>
		<?php wp_nonce_field( 'edgenet_2_product_' . $data['term_id'], '_edit_edgenet_2_product_nonce' ); ?>
		<?php echo $data['checkboxes']; //phpcs:ignore ?>
	</td>
</tr>
