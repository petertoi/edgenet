<?php
/**
 * Filename local-document-meta-box.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

/**
 * Data passed via Template::load();
 *
 * @var array $data Data passed via Template::load();
 */
?>
<div class="wrapper">
	<div class="doc-link">
		<?php echo $data['link']; ?>
	</div>
	<div class="doc-url">
		<a href="<?php echo esc_attr( $data['url'] ); ?>">
			<?php echo esc_html( $data['url'] ); ?>
		</a>
	</div>
</div>