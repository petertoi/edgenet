<?php
/**
 * Filename product-data-tab-panel-edgenet.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

?>
<div id='edgenet' class='panel woocommerce_options_panel'>

	<h2 class="meta-title"><?php esc_html_e( 'Core', 'edgenet' ); ?></h2>
	<div class="meta-table">
		<table>
			<tbody>
			<?php if ( ! empty( $data['core'] ) ) : ?>
				<?php foreach ( $data['core'] as $meta ) : ?>
					<tr>
						<th>
							<?php echo esc_html( $meta['label'] ); ?>
						</th>
						<td>
							<?php echo wp_kses_post( $meta['value'] ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php foreach ( $data['attribute_group'] as $section_key => $metas ) : ?>
		<?php
		$title = join( ' ', array_map( 'ucfirst', explode( '_', $section_key ) ) );
		?>
		<h2 class="meta-title"><?php echo esc_html( $title ); ?></h2>
		<div class="meta-table">
			<table>
				<tbody>
				<?php if ( ! empty( $metas ) ) : ?>
					<?php
					uasort( $metas, function ( $a, $b ) {
						if ( $a['attribute']->description < $b['attribute']->description ) {
							return - 1;
						} elseif ( $a['attribute']->description > $b['attribute']->description ) {
							return 1;
						}

						return 0;
					} );
					?>
					<?php foreach ( $metas as $meta ) : ?>
						<tr>
							<th>
								<?php echo esc_html( $meta['attribute']->description ); ?>
							</th>
							<td>
								<?php echo wp_kses_post( $meta['value'] ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php endforeach; ?>
</div>
