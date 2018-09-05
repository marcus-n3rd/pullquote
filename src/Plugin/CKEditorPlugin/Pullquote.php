<?php

namespace Drupal\pullquote\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "pullquote" plugin.
 *
 * @CKEditorPlugin(
 *   id = "pullquote",
 *   label = @Translation("Pullquote"),
 *   module = "pullquote"
 * )
 */
class Pullquote extends CKEditorPluginBase {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  function getFile() {
    return drupal_get_path('module', 'pullquote') . '/plugins/ckeditor/plugin.js';
  }
  
  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginButtonsInterface::getButtons().
   */
  function getButtons() {
    return [
      'pullquote' => [
        'label' => t('Pullquote'),
        'image' => drupal_get_path('module', 'pullquote') . '/plugins/wysiwyg/pullquote.gif',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }
}
