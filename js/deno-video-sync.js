/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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