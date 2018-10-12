<?php
/**
 * Filename edgenet-cat-meta-add.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<div class="form-field term-display-type-wrap">
	<h2><?php esc_html_e( 'WooCommerce Product Category Map', 'ussc' ); ?></h2>
	<label for="display_type">
		<?php esc_html_e( 'Select matching categories to map to:', 'ussc' ); ?>
	</label>

	<?php echo $data['checkboxes']; // phpcs:ignore ?>
</div>
