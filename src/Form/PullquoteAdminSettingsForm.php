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

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
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

    $form['pullquote_scope'] = [
      '#type' => 'radios',
      '#title' => t('Choose how pullquote JavaScript and CSS are added to the site'),
      '#options' => [
        0 => 'Content Only',
        1 => 'Global',
      ],
      '#default_value' => variable_get('pullquote_scope', PULLQUOTE_SCOPE_DEFAULT),
      '#required' => TRUE,
      '#description' => t('"Global" will load the pullquote CSS and JS on every page. "Content Only" will load the CSS and JS only on nodes. You can choose specific nodes or allow the code to be loaded on every node.'),
    ];

    $form['content_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Content Settings'),
    ];

    $form['content_settings']['pullquote_content_by_type'] = [
      '#type' => 'radios',
      '#title' => t('Select how pullquote will load content based on node types:'),
      '#options' => [
        0 => 'All',
        1 => 'By Type',
      ],
      '#default_value' => variable_get('pullquote_content_by_type', PULLQUOTE_CONTENT_BY_TYPE_DEFAULT),
      '#required' => TRUE,
      '#description' => t('Choose if pullquote can be added for all content (all nodes) or specify by content type.'),
      '#states' => [
        'visible' => [
          ':input[name="pullquote_scope"]' => [
            'value' => 0
            ]
          ]
        ],
    ];

    $form['content_settings']['pullquote_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Content Types'),
      '#options' => node_type_get_names(),
      '#default_value' => variable_get('pullquote_content_types', []),
      '#required' => FALSE,
      '#description' => t('Choose which content types will load pullquote code and render pullquotes. For better performance you should only load pullquote where you need it.'),
      '#states' => [
        'visible' => [
          ':input[name="pullquote_content_by_type"]' => [
            'value' => 1
            ],
          ':input[name="pullquote_scope"]' => ['value' => 0],
        ]
        ],
    ];

    $form['view_mode_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('View Mode Settings'),
    ];

    $form['view_mode_settings']['pullquote_load_by_view_mode'] = [
      '#type' => 'radios',
      '#title' => t('Load by View Mode'),
      '#options' => [
        0 => t('Load on all view modes'),
        1 => t('Load on specific view modes'),
      ],
      '#default_value' => variable_get('pullquote_load_by_view_mode', 1),
      '#required' => TRUE,
      '#description' => t('Load Pullquote by view mode? You can choose to enable pullquote only on certain view modes. This is helpful if you want to show pullquotes on the full node but not on teasers.'),
      '#states' => [
        'visible' => [
          ':input[name="pullquote_scope"]' => [
            'value' => 0
            ]
          ]
        ],
    ];

    // Load the list of avialable view modes.
    $entity_info = entity_get_info('node');
    $view_modes = $entity_info['view modes'];
    $view_mode_options = [];

    foreach ($view_modes as $mode => $settings) {
      $view_mode_options[$mode] = $settings['label'];
    }

    $form['view_mode_settings']['pullquote_view_modes'] = [
      '#type' => 'checkboxes',
      '#title' => t('View Modes'),
      '#options' => $view_mode_options,
      '#default_value' => variable_get('pullquote_view_modes', [
        'full'
        ]),
      '#description' => t('Choose which view modes will load pullquote code and render pullquotes. For better performance you should only load pullquote where you need it.'),
      '#states' => [
        'visible' => [
          ':input[name="pullquote_load_by_view_mode"]' => [
            'value' => 1
            ],
          ':input[name="pullquote_scope"]' => ['value' => 0],
        ]
        ],
    ];

    return parent::buildForm($form, $form_state);
  }

}
?>
