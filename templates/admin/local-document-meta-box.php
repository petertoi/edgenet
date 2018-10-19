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
<table class="file-link-table">
    <tr>
        <td>
        <td width="100%">
			<?php
			if ( ! empty( $data['url'] ) ) {
				printf( '<a href="%1$s" target="_blank" class="file-link">%2$s</a>', $data['url'], $data['link'] );
			}
			printf( '<input type="hidden" name="meta-file-id" id="meta-file-id" class="meta_image_id" value="%1$s" />', $data['id']  );

			?>
        </td>
        <td style="white-space: nowrap"><?php
			?><input type="button" id="meta-file-button" class="button" value="Choose or Upload an Image" /></td>
    </tr>
</table>
<script>

    jQuery('#meta-file-button').click(function () {

        var send_attachment_bkp = wp.media.editor.send.attachment;

        wp.media.editor.send.attachment = function (props, attachment) {
            jQuery('.file-link').hide();
            jQuery('#meta-file-id').show().val(attachment.id);

            wp.media.editor.send.attachment = send_attachment_bkp;
        }

        wp.media.editor.open();

        return false;
    });
</script>
<style>
    #meta-file {
        border: none;
        box-shadow: none;
        width: 100%;
    }

    .file-link + #meta-file {
        display: none;
    }

    .file-link {
        font-size: 14px;
        padding-left: 5px;
    }
</style>
