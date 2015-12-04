<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
    <tbody>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="name"><?php _e('Name')?></label>
        </th>
        <td>
            <input id="name" name="name" type="text" style="width: 95%" value="<?php echo esc_attr($item['name'])?>"
                   size="50" class="code" placeholder="<?php _e('Timeline name')?>" required>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="videourl"><?php _e('Video URL')?></label>
        </th>
        <td>
            <input id="email" name="videourl" type="url" style="width: 95%" value="<?php echo esc_attr($item['videourl'])?>"
                   size="50" class="code" placeholder="<?php _e('Video URL')?>" required>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="enabled"><?php _e('Enabled')?></label>
        </th>
        <td>
            <input id="enabled" name="enabled" type="checkbox" value="1" <?php if((int)$item['enabled']==1) echo 'checked'; ?> />
        </td>
    </tr>
    </tbody>
</table>