<?php
/**
 * Filename document-meta-box.php
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
	<div class="document">
		<?php
		if ( ! empty( $data['attachment_url'] ) ) {
			printf( '<a href="%1$s" target="_blank" class="file-link">%2$s</a>',
				esc_attr( $data['attachment_url'] ),
				wp_kses_post( $data['attachment_link'] )
			);
		}

		printf( '<input type="hidden" name="ussc_attachment_id" id="ussc_attachment_id" class="ussc_attachment_id" value="%1$s" />',
			absint( $data['attachment_id'] )
		);
		?>
	</div>
	<?php if ( empty( $data['edgenet_id'] ) ) : ?>
	<input type="hidden" name="ussc_action" value="edit_document" />
	<input type="button" id="ussc_upload_file" class="button" value="<?php esc_attr_e( 'Upload File', 'ussc' ); ?>" />

		<script>

          jQuery('#ussc_upload_file').click(function() {

            var send_attachment = wp.media.editor.send.attachment;

            wp.media.editor.send.attachment = function(props, attachment) {
              jQuery('.file-link').hide();
              jQuery('#ussc_attachment_id').show().val(attachment.id);

              wp.media.editor.send.attachment = send_attachment;
            };

            wp.media.editor.open();

            return false;
          });
		</script>

	<?php else : ?>
		<p><?php esc_html_e( 'Note: This file is managed by Edgenet.', 'ussc' ); ?></p>
	<?php endif; ?>
</div>
