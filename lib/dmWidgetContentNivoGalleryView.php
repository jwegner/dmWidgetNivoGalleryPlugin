<?php

class dmWidgetContentNivoGalleryView extends dmWidgetPluginView
{
  
  public function configure()
  {
    parent::configure();
    
    $this->addRequiredVar(array('medias', 'method', 'fx'));

    $this->addJavascript(array('dmWidgetNivoGalleryPlugin.view',
      sfConfig::get('app_dmWidgetNivoGalleryPlugin_js')
        ? sfConfig::get('app_dmWidgetNivoGalleryPlugin_js')
        : 'dmWidgetNivoGalleryPlugin.nivo'));
    
    $this->addStylesheet(array('dmWidgetNivoGalleryPlugin.view',
      sfConfig::get('app_dmWidgetNivoGalleryPlugin_css')
        ? sfConfig::get('app_dmWidgetNivoGalleryPlugin_css')
        : 'dmWidgetNivoGalleryPlugin.theme_' . sfConfig::get('app_dmWidgetNivoGalleryPlugin_theme')));
    
  }

  protected function filterViewVars(array $vars = array())
  {
    $vars = parent::filterViewVars($vars);
        
    // extract media ids
    $mediaIds = array();
    foreach($vars['medias'] as $index => $mediaConfig)
    {
      $mediaIds[] = $mediaConfig['id'];
    }
    
    // fetch media records
    $mediaRecords = empty($mediaIds) ? array() : $this->getMediaQuery($mediaIds)->fetchRecords()->getData();
    
    // sort records
    $this->mediaPositions = array_flip($mediaIds);
    usort($mediaRecords, array($this, 'sortRecordsCallback'));
    
    // build media tags
    $medias = array();
    foreach($mediaRecords as $index => $mediaRecord)
    {
      $mediaTag = $this->getHelper()->media($mediaRecord);
  
      $mediaTag->method($vars['method']);
  
      if ($vars['method'] === 'fit')
      {
        $mediaTag->background($vars['background']);
      }
      
      if ($alt = $vars['medias'][$index]['alt'])
      {
        $mediaTag->alt($this->__($alt));
      }
      
      if ($quality = dmArray::get($vars, 'quality'))
      {
        $mediaTag->quality($quality);
      }
      
      $medias[] = array(
        'tag'   => $mediaTag,
        'link'  => $vars['medias'][$index]['link'],
        'alt'  => $this->__($vars['medias'][$index]['alt'])
      );
    }
  
    // replace media configuration by media tags
    $vars['medias'] = $medias;
    
    return $vars;
  }
  
  protected function sortRecordsCallback(DmMedia $a, DmMedia $b)
  {
    return $this->mediaPositions[$a->get('id')] > $this->mediaPositions[$b->get('id')];
  }
  
  protected function getMediaQuery($mediaIds)
  {
    return dmDb::query('DmMedia m')
    ->leftJoin('m.Folder f')
    ->whereIn('m.id', $mediaIds);
  }

  protected function doRender()
  {
    if ($this->isCachable() && $cache = $this->getCache())
    {
      return $cache;
    }
    
    $vars = $this->getViewVars();    
    $helper = $this->getHelper();
    $count = count($vars['medias']);
    
    $html = $helper->open('div#dm_widget_nivo_gallery_container.slider-wrapper.theme-'. sfConfig::get('app_dmWidgetNivoGalleryPlugin_theme'));
    $html .= $helper->open('div#dm_widget_nivo_gallery.nivoSlider', array('json' => array(
      'fx'             => dmArray::get($vars, 'fx', '0.5', 'fade'),
      'animspeed'      => dmArray::get($vars, 'animspeed', 0.5),
      'pausetime'      => dmArray::get($vars, 'pausetime', 3),
      'nav_thumbs'      => dmArray::get($vars, 'controlNavThumbs', false),
      'count'          => $count
    )));
    
    foreach($vars['medias'] as $media)
    {
      if($vars['controlNavThumbs'] == true) {       
        $html .= sprintf('<img src="%s" alt="%s" title="%s" data-thumb="%s" />', $media['tag']->getSrc(), $media['alt'], $media['alt'], $media['tag']->width($vars['width'])->getSrc());
      } else {
        $html .= sprintf('<img src="%s" alt="%s" title="%s" />', $media['tag']->getSrc(), $media['alt'], $media['alt']);
      }
    }
    
    $html .= '</div></div>';

    if ($this->isCachable())
    {
      $this->setCache($html);
    }
    
    return $html;
  }
  
  protected function doRenderForIndex()
  {
    $alts = array();
    foreach($this->compiledVars['medias'] as $media)
    {
      if (!empty($media['alt']))
      {
        $alts[] = $media['alt'];
      }
    }
    
    return implode(', ', $alts);
  }
  
}