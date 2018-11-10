<?php
/**
 * Filename settings-debug.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

?>

<?php if ( edgenet()->debug->enabled ) : ?>
	<h2><?php esc_html_e( 'Debug', 'edgenet' ); ?></h2>
	<pre><?php print_r( edgenet() ); // phpcs:ignore ?></pre>
<?php endif; ?>
