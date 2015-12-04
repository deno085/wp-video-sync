//admin JS

jQuery(document).ready(function() {
	
        jQuery('#deno-timeline-add-new-timeline-item').click(function(event) {
            event.preventDefault();
            jQuery('#deno-timeline-add-new-metabox').show();
        });
        
        jQuery('#deno-timeline-accordion').on('click',  'a.deno-timeline-delete-item', function(event) {
           event.preventDefault();
           containerId = jQuery(this).parents('h3.ui-accordion-header').attr('aria-controls');
           jQuery('#'+containerId).remove();
           jQuery(this).parents('h3.ui-accordion-header').remove();
           return false;
        });
        
        jQuery('#timeline-settings-add-content').click(function(event) {
           event.preventDefault();
           contentType = jQuery('.timeline-content-type-option:checked').val();
           preview = '';
           switch(contentType)
           {
               case 'text':
                   preview = 'New Plain Text Item';
                    break;
                case 'post':
                    preview = 'New Sync Post';
                    break;
           }
           data = {
               'seconds': jQuery('#new-content-seconds').val(),
               'content_type': contentType,
               'content_data' : '',
               'preview': preview
           };
           
           itemHtml = _.template(jQuery('#deno-timeline-item').html(), data);
           jQuery('#deno-timeline-accordion').append(itemHtml);
           jQuery('#deno-timeline-accordion').accordion("refresh");
          
           jQuery('#deno-timeline-add-new-metabox').hide();
        });
        
        jQuery('#deno-timeline-form').submit(function(event) {
           var contentData = [];
           jQuery('.deno-timeline-accordion-section-content').each(function() {
              var content_type= jQuery(this).find('.deno-timeline-settings-content_type:first').val();
              var content_data = jQuery(this).find('.deno-timeline-settings-content_data:first').val();
              var seconds = parseInt(jQuery(this).find('.deno-timeline-settings-seconds:first').val());
              contentData.push({'seconds':seconds, 'content_type':content_type, 'content_data':content_data});
           });
           jQuery('#deno-timeline-content-data-store').val(JSON.stringify(contentData));
//           jQuery('#deno-timeline-form').submit();
           return true; 
        });
        
        jQuery('#deno-timeline-accordion').accordion({
            collapsible: true
        });
               
        //New content metabox: Show the selected controls for adding content based on the content type selected
        jQuery('.timeline-content-type-option').click(function() {
           jQuery('.timeline-settings-content-type').hide();
           jQuery('#'+jQuery(this).attr('data-target-option')).show();
        });
        
        if(contentItems && contentItems.length)
        {
            jQuery.each(contentItems, function (item) {
               itemHtml = _.template(jQuery('#deno-timeline-item').html(), contentItems[item]);
               jQuery('#deno-timeline-accordion').append(itemHtml);
               jQuery('#deno-timeline-accordion').accordion("refresh");
               itemHtml = '';           
            });
        }
});
