<div class="wrap">
    <form id="deno-timeline-form" method="POST">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Timeline'); ?>
            <a class="add-new-h2" 
               href="<?php echo get_admin_url(get_current_blog_id()); ?>admin.php?page=deno_timelines"><?php _e('back to list'); ?></a>
            <input type="submit" value="<?php _e('Save'); ?>" id="deno-new-timeline-submit" class="button-primary" name="submit">
        </h2>
    <?php
            if (!empty($notice))
            {
                echo '<div id="notice" class="error"><p>'.$notice.'</p></div>';
            }
            if (!empty($message)) 
            {
                echo '<div id="message" class="updated"><p><'.$message.'</p></div>';
            }
    ?>    
        <input type="hidden" name="action" value="deno_timeline_edit" />
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('deno_timeline_edit'); ?>"/>
        <input type="hidden" name="id" value="<?php echo $item['id']; ?>"/>
        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <?php do_meta_boxes('timeline', 'normal', $item); ?>
                </div>
            </div>
        </div>      
        <input type="hidden" name="contentData" id="deno-timeline-content-data-store" />    
    </form>        
    <h3>
        <?php _e('Timeline Content'); ?>
        <a href="#" class="add-new-h2" id="deno-timeline-add-new-timeline-item"><?php _e('add new'); ?></a>
    </h3>        
    <div id="deno-timeline-add-new-metabox" class="metabox-holder">
        <?php do_meta_boxes('timeline', 'side', $item); ?>
    </div>    
    <div id="timeline-frame">        
        <div id="timeline-liquid">
            <div id="deno-timeline-accordion">
            </div>
        </div>            
    </div>
 
</div>
<script type="text/javascript">
    //content items from initial load
    var contentItems = <?php echo json_encode($item['content']); ?>;

    // List of available posts
    var syncPosts = <?php echo json_encode($syncPosts); ?>;
</script>

<script type="text/html" id="deno-timeline-item">
    <h3>
        <span class="deno-timeline-delete-item">[<a href="#" class="deno-timeline-delete-item">x</a>]</span>
        <span class="timeline-elapsed">
            <%= seconds %>
        </span>
        <%= preview %>
        <span class="screen-reader-text">Press return or enter to expand</span>
    </h3>
    <div class="deno-timeline-accordion-section-content ">
        <input type="hidden" name="content_type" class="deno-timeline-settings-content_type" value="<%= content_type %>" />
        <div class="timeline-settings-form-field">
           <label for="seconds" style="display: inline"><?php _e('Elapsed Time', 'custom_table_example')?>: </label>
           <input class="deno-timeline-settings-seconds" name="seconds" type="text" style="width: 50px;" value=" <%= seconds %>" required />
           seconds
        </div>
        <div class="timeline-settings-form-field"> 
            
            <% if(content_type=='text') { %>
            <label for=""><?php _e('Plain Text', 'custom_table_example')?>: </label>
            <textarea class="deno-timeline-settings-plain-text-content deno-timeline-settings-content_data" name="content_data"> <%= content_data %></textarea>
            <% } %>
            <% if(content_type == 'post') { %>
            <label for=""><?php _e('Select Post', 'custom_table_example')?>: </label>
            <select class='deno-timeline-settings-select-post-content deno-timeline-settings-content_data' name='content_data'>
                <option value="0">--- Select Post ---</option>
                <% _.each(syncPosts, function(post) { %>
                <% if(parseInt(content_data)==post.id) { selected=' selected'; } else { selected=''; } %>
                <option value="<%=post.id %>"<%=selected %>><%=post.title %></option>
                <% }); %>
            </select>                
            <% } %>
        </div>
    </div>
</script>