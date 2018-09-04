<?php

/**
 * @file
 * Contains \Drupal\pullquote\Form\PullquoteAdminSettingsForm.
 */

namespace Drupal\pullquote\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class PullquoteAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pullquote_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('pullquote.settings');

    foreach ($form_state->getValues() as $variable => $value) {
      $config->set($variable, $value);
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pullquote.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = [];

    $form['title'] = [
      '#markup' => t('These settings allow you to choose how pullquote will function on your site.')
      ];

    $form['scope'] = [
      '#type' => 'radios',
      '#title' => t('Choose how pullquote JavaScript and CSS are added to the site'),
      '#options' => [
        0 => 'Content Only',
        1 => 'Global',
      ],
      '#default_value' => \Drupal::config('pullquote.settings')->get('scope'),
      '#required' => TRUE,
      '#description' => t('"Global" will load the pullquote CSS and JS on every page. "Content Only" will load the CSS and JS only on nodes. You can choose specific nodes or allow the code to be loaded on every node.'),
    ];

    $form['content_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Content Settings'),
    ];

    $form['content_settings']['content_by_type'] = [
      '#type' => 'radios',
      '#title' => t('Select how pullquote will load content based on node types:'),
      '#options' => [
        0 => 'All',
        1 => 'By Type',
      ],
      '#default_value' => \Drupal::config('pullquote.settings')->get('content_by_type'),
      '#required' => TRUE,
      '#description' => t('Choose if pullquote can be added for all content (all nodes) or specify by content type.'),
      '#states' => [
        'visible' => [
          ':input[name="scope"]' => [
            'value' => 0
            ]
          ]
        ],
    ];

    $form['content_settings']['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Content Types'),
      '#options' => node_type_get_names(),
      '#default_value' => \Drupal::config('pullquote.settings')->get('content_types'),
      '#required' => FALSE,
      '#description' => t('Choose which content types will load pullquote code and render pullquotes. For better performance you should only load pullquote where you need it.'),
      '#states' => [
        'visible' => [
          ':input[name="content_by_type"]' => [
            'value' => 1
            ],
          ':input[name="scope"]' => ['value' => 0],
        ]
        ],
    ];

    $form['view_mode_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('View Mode Settings'),
    ];

    $form['view_mode_settings']['load_by_view_mode'] = [
      '#type' => 'radios',
      '#title' => t('Load by View Mode'),
      '#options' => [
        0 => t('Load on all view modes'),
        1 => t('Load on specific view modes'),
      ],
      '#default_value' => \Drupal::config('pullquote.settings')->get('load_by_view_mode'),
      '#required' => TRUE,
      '#description' => t('Load Pullquote by view mode? You can choose to enable pullquote only on certain view modes. This is helpful if you want to show pullquotes on the full node but not on teasers.'),
      '#states' => [
        'visible' => [
          ':input[name="scope"]' => [
            'value' => 0
            ]
          ]
        ],
    ];

    // Load the list of avialable view modes.
    $view_modes = \Drupal::entityQuery('entity_view_mode')
      ->condition('targetEntityType', 'node')
      ->execute();
    $view_mode_options = [];

    foreach ($view_modes as $mode) {
      $view_mode_options[str_replace('node.', '', $mode)] = \Drupal::config('core.entity_view_mode.' . $mode)->get('label');
    }

    $form['view_mode_settings']['view_modes'] = [
      '#type' => 'checkboxes',
      '#title' => t('View Modes'),
      '#options' => $view_mode_options,
      '#default_value' => \Drupal::config('pullquote.settings')->get('view_modes'),
      '#description' => t('Choose which view modes will load pullquote code and render pullquotes. For better performance you should only load pullquote where you need it.'),
      '#states' => [
        'visible' => [
          ':input[name="load_by_view_mode"]' => [
            'value' => 1
            ],
          ':input[name="scope"]' => ['value' => 0],
        ]
        ],
    ];

    return parent::buildForm($form, $form_state);
  }

}
?>
