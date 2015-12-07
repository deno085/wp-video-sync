/* 
 * jQuery plugin to sync content to videos based on time
 * http://github.com/deno085/jquery-video-sync
 * 
 * Copyright 2015, Chris Walker
 * http://www.github.com/deno085
 * 
  * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
(function ( $ ) {
    $.fn.videoSync = function( options ) {
        var settings = $.extend({
            'contentContainer': '',
            'debug': false,
            'content': {}
        }, options );
        var lastFiredTime = null;
        
        this.on('timeupdate', function(event) {
            if(settings.debug)
            {
                console.log(event.target.currentTime+':'+event.target.currentSrc);
            }
            var second = Math.floor(event.target.currentTime);
            if(isNaN(parseInt(lastFiredTime)) || lastFiredTime !== second)
            {
               if(settings.content.hasOwnProperty(second))
               {
                    if(settings.content[second]===null)
                    {
                        if(settings.debug)
                        {
                            console.log('videoSync: Firing event for '+second+ ' second');
                        }
                        $(this).trigger({
                            type: "contentSync",
                            second: second,
                            source: event.target.currentSrc
                        });                       
                    }   
                    else if(typeof settings.content[second]==='function')
                    {
                        if(settings.debug)
                        {
                            console.log('videoSync: Calling function for '+second+ ' second');
                        }                        
                        func = settings.content[second];
                        eval(func({
                            type: "contentSync",
                            second: second,
                            element: $(this),
                            source: event.target.currentSrc                            
                        }));
                    }
                    else
                    {
                        if(settings.debug)
                        {
                            console.log('videoSync: Setting content in container '+settings.contentContainer+' for '+second+ ' second');
                        }                            
                        $(settings.contentContainer).html(settings.content[second]);
                    }
                    lastFiredTime = second;
               }           
            }
        });        
        
        this.on('ended', function() {
           lastFiredTime = 0; 
        });        
        
        return this;
    };
}( jQuery ));