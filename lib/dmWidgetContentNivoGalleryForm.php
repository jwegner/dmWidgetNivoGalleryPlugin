<?php

class dmWidgetContentNivoGalleryForm extends dmWidgetPluginForm
{
  protected static
  $methods = array(
    'center' => 'Center',
    'scale' => 'Scale',
    'inflate' => 'Inflate',
    'fit' => 'Fit'
  ),
  $fx = array(
    'fade' => 'fade',
    'sliceDown' => 'sliceDown',
    'sliceDownLeft' => 'sliceDownLeft',
    'sliceUp' => 'sliceUp',
    'sliceUpLeft' => 'sliceUpLeft',
    'sliceUpDown' => 'sliceUpDown',
    'sliceUpDownLeft' => 'sliceUpDownLeft',
    'fold' => 'fold',
    'slideInRight' => 'slideInRight',
    'slideInLeft' => 'slideInLeft',
    'boxRandom' => 'boxRandom',
    'boxRain' => 'boxRain',
    'boxRainReverse' => 'boxRainReverse',
    'boxRainGrow' => 'boxRainGrow',
    'boxRainGrowReverse' => 'boxRainGrowReverse',
    'random' => 'random'
  );


  
  public function configure()
  {
    $this->widgetSchema['media_id'] = new sfWidgetFormDoctrineChoice(array(
      'model'    => 'DmMedia',
      'multiple' => true
    ));
    
    $this->validatorSchema['media_id'] = new sfValidatorDoctrineChoice(array(
      'model'    => 'DmMedia',
      'multiple' => true
    ));
    
    $this->validatorSchema['media_link'] = new sfValidatorPass();
    
    $this->validatorSchema['media_alt'] = new sfValidatorPass();
    
    $this->validatorSchema['media_position'] = new sfValidatorPass();

    $this->widgetSchema['controlNavThumbs'] = new sfWidgetFormInputCheckbox();
    $this->validatorSchema['controlNavThumbs'] = new sfValidatorBoolean(array('required' => false));
    
    $this->widgetSchema['width'] = new sfWidgetFormInputText(array(), array('size' => 5));
    $this->validatorSchema['width'] = new dmValidatorCssSize(array(
      'required' => true
    ));

    $this->widgetSchema['height'] = new sfWidgetFormInputText(array(), array('size' => 5));
    $this->validatorSchema['height'] = new dmValidatorCssSize(array(
      'required' => true
    ));

    $methods = $this->getService('i18n')->translateArray(self::$methods);
    $this->widgetSchema['method'] = new sfWidgetFormSelect(array(
      'choices' => $methods
    ));
    $this->validatorSchema['method'] = new sfValidatorChoice(array(
      'choices' => array_keys($methods)
    ));
    if (!$this->getDefault('method'))
    {
      $this->setDefault('method', dmConfig::get('image_resize_method', 'center'));
    }

    $fx = $this->getService('i18n')->translateArray(self::$fx);
    $this->widgetSchema['fx'] = new sfWidgetFormSelect(array(
      'choices' => $fx
    ));
    $this->validatorSchema['fx'] = new sfValidatorChoice(array(
      'choices' => array_keys($fx)
    ));
    if (!$this->getDefault('fx'))
    {
      $this->setDefault('fx', dmArray::first(array_keys($fx)));
    }

    $this->widgetSchema['animspeed'] = new sfWidgetFormInputText(array(), array('size' => 5));
    $this->validatorSchema['animspeed'] = new sfValidatorNumber(array(
      'required' => false,
      'min' => 0,
      'max' => 1000
    ));
    if (!$this->hasDefault('animspeed'))
    {
      $this->setDefault('animspeed', 0.5);
    }
    
    $this->widgetSchema['pausetime'] = new sfWidgetFormInputText(array(), array('size' => 5));
    $this->validatorSchema['pausetime'] = new sfValidatorNumber(array(
      'required' => false,
      'min' => 0,
      'max' => 1000
    ));
    if (!$this->hasDefault('pausetime'))
    {
      $this->setDefault('pausetime', 3);
    }

    $this->widgetSchema['quality'] = new sfWidgetFormInputText(array(), array('size' => 5));
    $this->validatorSchema['quality'] = new sfValidatorInteger(array(
      'required' => false,
      'min' => 0,
      'max' => 100
    ));
  
    if (!$this->getDefault('medias'))
    {
      $this->setDefault('medias', array());
    }

    $this->widgetSchema['background'] = new sfWidgetFormInputText(array(), array('size' =>7));
    $this->validatorSchema['background'] = new sfValidatorString(array(
      'required' => false
    ));
    
    $this->validatorSchema['widget_width'] = new sfValidatorInteger(array('required' => false));

    parent::configure();
  }

  public function getStylesheets()
  {
    return array(
      'lib.ui-tabs',
      'dmWidgetNivoGalleryPlugin.form'
    );
  }

  public function getJavascripts()
  {
    return array(
      'lib.ui-tabs',
      'core.tabForm',
      'dmWidgetNivoGalleryPlugin.form'
    );
  }
  
  protected function renderContent($attributes)
  {
    return $this->getHelper()->renderPartial('dmWidgetNivoGallery', 'form', array(
      'form' => $this,
      'medias' => $this->getMedias(),
      'baseTabId' => 'dm_widget_nivo_gallery_'.$this->dmWidget->get('id')
    ));
  }
  
  protected function getMedias()
  {
    // extract media ids
    $mediaConfigs = $this->getValueOrDefault('medias');
    $mediaIds = array();
    foreach($mediaConfigs as $index => $mediaConfig)
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
      $medias[] = array(
        'id'     => $mediaRecord->id,
        'link'   => $mediaConfigs[$index]['link'],
        'alt'    => $mediaConfigs[$index]['alt']
      );
    }
    
    return $medias;
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

  public function checkMediaSource($validator, $values)
  {
    if (!$values['mediaId'] && !$values['file'])
    {
      throw new sfValidatorError($validator, 'You must use a media or upload a file');
    }

    return $values;
  }

  public function checkBackground($validator, $values)
  {
    if ('fit' == $values['method'] && !dmString::hexColor($values['background']))
    {
      throw new sfValidatorErrorSchema($validator, array('background' => new sfValidatorError($validator, 'This is not a valid hexadecimal color')));
    }

    return $values;
  }
  
  public function getWidgetValues()
  {
    $values = parent::getWidgetValues();
    
    $values['medias'] = array();
    
    foreach($values['media_id'] as $index => $mediaId)
    {
      $values['medias'][] = array(
        'id'   => $mediaId,
        'link' => $values['media_link'][$index],
        'alt'  => $values['media_alt'][$index]
      );
    }
    
    if (empty($values['width']))
    {
      if ($values['widget_width'])
      {
        $values['width'] = $values['widget_width'];
      }
      else
      {
        $values['width'] = 300;
      }
      
      $values['height'] = dmArray::get($values, 'height', (int) ($values['width'] * 2/3));
    }
    elseif (empty($values['height']))
    {
      $values['height'] = (int) ($values['width'] * 2/3);
    }
    
    unset($values['widget_width'], $values['media_position'], $values['media_id'], $values['media_link']);
    
    return $values;
  }

}