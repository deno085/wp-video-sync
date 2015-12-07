/*
 * Author: Chris Walker
 * Author URI: http://github.com/deno085
 * License: MIT
*/
jQuery(document).ready(function() {
    jQuery('[data-deno-timeline-id]').videoSync(denoVideoSync.getTimeline(jQuery(this).attr('data-deno-timeline-id')));
});

denoVideoSync = {
  getConfig: function() {
      return denoVideoSyncConfig.pluginConfig;
  },
  getTimeline: function(timelineId) {
    if(denoVideoSyncConfig.timelines[timelineId]) {
        return denoVideoSyncConfig.timelines[timelineId];
    }
    return {};
  }
};