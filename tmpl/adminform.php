<div class="wrap">
    <div class="icon32" id="icon-options-general"></div>
    <h2><?php echo __("Giftd Settings", 'giftd'); ?></h2>

    <div id="message"
         class="updated fade" <?php if (!isset($_REQUEST['giftd_plgn_form_submit']) || $message == "") echo "style=\"display:none\""; ?>>
        <p><?php echo $message; ?></p>
    </div>

    <div class="error" <?php if ("" == $error) echo "style=\"display:none\""; ?>>
        <p>
            <strong><?php echo $error; ?></strong>
        </p>
    </div>



    <div>
        <form name="form1" method="post" action="admin.php?page=giftd" enctype="multipart/form-data">

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php echo __("Gift User ID", 'giftd'); ?></th>
                    <td>
                        <input
                            class="regular-text code"
                            name='user_id'
                            type='text'
                            value='<?php echo $giftd_plgn_options['user_id']; ?>'
                            />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __("Gift API Key", 'giftd'); ?></th>
                    <td>
                        <input
                            class="regular-text code"
                            name='api_key'
                            type='text'
                            value='<?php echo $giftd_plgn_options['api_key']; ?>'
                            />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __("Gift Partner code", 'giftd'); ?></th>
                    <td>
                        <input
                            class="regular-text code"
                            name='partner_code'
                            type='text'
                            value='<?php echo $giftd_plgn_options['partner_code']; ?>'
                            />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __("Gift Partner token prefix", 'giftd'); ?></th>
                    <td>
                        <input
                            class="regular-text code"
                            name='partner_token_prefix'
                            type='text'
                            value='<?php echo $giftd_plgn_options['partner_token_prefix']; ?>'
                            />
                    </td>
                </tr>

            </table>

            <input type="hidden" name="giftd_plgn_form_submit" value="submit"/>
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>

            <?php wp_nonce_field(plugin_basename(dirname(__DIR__)), 'giftd_plgn_nonce_name'); ?>
        </form>
    </div>
</div>
