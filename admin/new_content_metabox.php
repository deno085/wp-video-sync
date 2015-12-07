<?php
/*
 * Author: Chris Walker
 * Author URI: http://github.com/deno085
 * License: MIT
*/
?>
<form id="timeline-settings-new-text">
    <div class="timeline-settings-form-field">
       <label for="name" style="display: inline"><?php _e('Elapsed Time', 'custom_table_example')?>: </label>
       <input id="new-content-seconds" name="seconds" type="text" style="width: 50px;" value="" required>
       seconds
    </div>
    <div class="timeline-settings-form-field">
        <input class="timeline-content-type-option" type="radio" name="content_type" value="text" />Plain Text
    </div>
    <div class="timeline-settings-form-field">
        <input class="timeline-content-type-option" type="radio" name="content_type" value="post" />Sync Post
    </div>
    <div class="timeline-settings-form-field">
        <input type="button" name="timeline-settings-add-content" id="timeline-settings-add-content" value="Add Item" />
    </div>
</form>
