<?php
/**
 * Filename settings-debug.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

?>

<?php if ( edgenet()->debug ) : ?>
	<h2><?php esc_html_e( 'Debug', 'ussc' ); ?></h2>
	<pre><?php print_r( edgenet() ); // phpcs:ignore ?></pre>
<?php endif; ?>
