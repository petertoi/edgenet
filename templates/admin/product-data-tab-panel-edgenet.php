<?php
/**
 * Filename product-data-tab-panel-edgenet.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<div id='ussc_edgenet' class='panel woocommerce_options_panel'>
	<?php foreach ( $data as $section_key => $metas ) : ?>
		<h2 class="meta-title"><?php echo esc_html( ucfirst( $section_key ) ); ?></h2>
		<?php if ( ! empty( $metas ) ) : ?>
			<div class="meta-table">
				<table>
					<tbody>
					<?php foreach ( $metas as $meta ) : ?>
						<tr>
							<th>
								<?php echo esc_attr( $meta['attribute']->description ); ?>
							</th>
							<td>
								<?php echo esc_attr( $meta['value'] ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
