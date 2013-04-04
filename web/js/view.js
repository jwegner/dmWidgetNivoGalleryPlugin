(function($) {
  
  $('#dm_page div.dm_widget.content_nivo_gallery').live('dmWidgetLaunch', function()
  {
    var $gallerycontainer = $(this).find('div#dm_widget_nivo_gallery_container');
    var $gallery = $($gallerycontainer).find('div#dm_widget_nivo_gallery');

    // only if elements in gallery
    if(!$gallery.find('>img').length)
    {
      return;
    }

    // get options from gallery metadata
    var options = $gallery.metadata();
    
    $gallery.nivoSlider({
        effect:     options.fx,                    
        animSpeed:  options.animspeed * 1000,
        pauseTime:  options.pausetime * 1000,
        controlNavThumbs: options.nav_thumbs
      });
    
  });

})(jQuery);