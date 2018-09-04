<?php

/**
 * @file
 * Contains \Drupal\pullquote\Form\PullquoteAdminStylesForm.
 */

namespace Drupal\pullquote\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class PullquoteAdminStylesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pullquote_admin_styles_form';
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
    $ctools_enabled = \Drupal::moduleHandler()->moduleExists('ctools');
    $path = drupal_get_path('module', 'pullquote');
    $css_files = [];
    $files = file_scan_directory($path . '/css', '/.css$/');

    foreach ($files as $file) {
      $css_files[$file->filename] = str_replace('_', ' ', $file->name);
    }
    natsort($css_files);
    $form = [];

    $form['title'] = [
      '#markup' => t('These settings allow you to change the look of pullquotes on your site. This is handled by using different style sheets. You can choose from default styles or use your own.')
      ];

    $form['pullquote_example'] = [
      '#type' => 'markup',
      '#prefix' => '<span class="pullquote-processed pullquote-quote">',
      '#suffix' => '<br /><span class="attribution">Thomas Jefferson</span></span>',
      '#markup' => '<span class="pullquote-content">If we can but prevent the government from wasting the labours of the people, under the pretence of taking care of them, they must become happy.</span>',
    ];

    // We only allow file upload as an option if Ctools is available because it
    // has CSS filtering tools.
    $source_options = [];
    if ($ctools_enabled) {
      $source_options = [
        'selection' => 'Pullquote module supplied styles',
        'path' => 'User supplied path to a stylesheet',
        'upload' => 'User uploaded stylesheet',
      ];
    }
    else {
      $source_options = [
        'selection' => 'Pullquote module supplied styles',
        'path' => 'User supplied path to a stylesheet',
      ];

    }
    $form['pullquote_css_source'] = [
      '#type' => 'radios',
      '#title' => t('CSS Source'),
      '#options' => $source_options,
      '#default_value' => variable_get('pullquote_css_source', 'selection'),
      '#required' => TRUE,
      '#description' => t('Choose the source of your pullquote stylesheet'),
    ];

    $form['pullquote_css_selection'] = [
      '#type' => 'select',
      '#title' => t('Pullquote Style'),
      '#options' => $css_files,
      '#default_value' => variable_get('pullquote_css_selection', PULLQUOTE_DEFAULT_CSS),
      '#required' => TRUE,
      '#description' => t('Choose from one of the provided style sheets.'),
      '#states' => [
        'visible' => [
          ':input[name="pullquote_css_source"]' => [
            'value' => 'selection'
            ]
          ]
        ],
    ];

    $form['pullquote_css_path'] = [
      '#type' => 'textfield',
      '#title' => t('Path to custom Pullquote style sheet'),
      '#description' => t('The path to the file you would like to use for you pullquote css. We recommend putting your custom CSS in a libraries directory called pullquote: i.e. "sites/all/libraries/pullquote/mypullquote.css"'),
      '#default_value' => variable_get('pullquote_css_source', '') == 'path' ? variable_get('pullquote_css', '') : '',
      '#states' => [
        'visible' => [
          ':input[name="pullquote_css_source"]' => [
            'value' => 'path'
            ]
          ]
        ],
    ];

    if ($ctools_enabled) {
      $description = t("If you don't have direct file access to the server, use this field to upload your style sheet.");
      if (variable_get('pullquote_css_source', '') == 'upload') {
        $description .= '<br /><strong>' . t('The currently used CSS file is:') . ' ' . variable_get('pullquote_css', '') . '</strong>';
      }

      $form['pullquote_css_upload'] = [
        '#type' => 'file',
        '#title' => t('Upload css file'),
        '#maxlength' => 40,
        '#description' => $description,
        '#states' => [
          'visible' => [
            ':input[name="pullquote_css_source"]' => [
              'value' => 'upload'
              ]
            ]
          ],
      ];
    }

    $attached_settings = [
      'pullquoteCurrent' => file_create_url(variable_get('pullquote_css', $path . '/css/' . PULLQUOTE_DEFAULT_CSS)),
      'pullQuoteModulePath' => '/' . $path . '/css/',
    ];

    $form['#attached']['js'] = [
      drupal_get_path('module', 'pullquote') . '/js/pullquote.admin.js',
      [
        'data' => $attached_settings,
        'type' => 'setting',
      ],
    ];

    $form = parent::buildForm($form, $form_state);
    // We don't want to call system_settings_form_submit(), so change #submit.
    array_pop($form['#submit']);
    $form['#submit'][] = 'pullquote_admin_styles_form_submit';
    $form['#validate'][] = 'pullquote_admin_styles_form_validate';
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Handle file uploads.
    $validators = [
      'file_validate_extensions' => [
        'css'
        ]
      ];

    // Check for a new uploaded style sheet.
    $file = file_save_upload('pullquote_css_upload', $validators);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValue([
          'pullquote_css_upload'
          ], $file);
      }
      else {
        // File upload failed.
        $form_state->setErrorByName('pullquote_css_upload', t('The css file could not be uploaded.'));
      }
    }

    if ($form_state->getValue(['pullquote_css_source']) == 'upload' && !$file) {
      $form_state->setErrorByName('pullquote_css_upload', t('You must choose a CSS file to upload.'));
    }

    if ($form_state->getValue(['pullquote_css_source']) == 'path' && !$form_state->getValue([
      'pullquote_css_path'
      ])) {
      $form_state->setErrorByName('pullquote_css_path', t('A valid CSS file path is required.'));
    }

    // If the user provided a path for a css file, make sure it exists at that
    // path.
    if ($form_state->getValue([
      'pullquote_css_path'
      ])) {
      $path = _pullquote_settings_validate_path($form_state->getValue([
        'pullquote_css_path'
        ]));
      if (!$path) {
        $form_state->setErrorByName('pullquote_css_path', t('The custom css path is invalid.'));
      }
    }
  }

  public function _submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // If the user uploaded a style sheet, save it to a permanent location.
    $source = $form_state->getValue(['pullquote_css_source']);

    if ($source == 'selection') {
      $path = drupal_get_path('module', 'pullquote');
      $pullquote_css = $path . '/css/' . $form_state->getValue(['pullquote_css_selection']);
    }
    elseif ($source == 'path') {
      $pullquote_css = $form_state->getValue(['pullquote_css_path']);
    }
    elseif ($source == 'upload') {
      if ($file = $form_state->getValue(['pullquote_css_upload'])) {
        unset($form_state->getValue(['pullquote_css_upload']));
        $css_contents = file_get_contents($file->uri);
        if (\Drupal::moduleHandler()->moduleExists('ctools')) {
          ctools_include('css');
          $css_contents = ctools_css_filter($css_contents);
        }
        $filename = file_save_data($css_contents, file_default_scheme() . '://' . $file->filename);
        // Get rid of the temporary file.
        file_delete($file);
        $pullquote_css = $filename->uri;
      }
    }
    else {
      //set a default.
      $pullquote_css = $path . '/css/pullquote_style_1.css';
    }

    variable_set('pullquote_css', $pullquote_css);
    variable_set('pullquote_css_selection', $form_state->getValue(['pullquote_css_selection']));
    variable_set('pullquote_css_path', $form_state->getValue(['pullquote_css_path']));
    variable_set('pullquote_css_source', $form_state->getValue(['pullquote_css_source']));
    drupal_set_message(t('The configuration options have been saved.'));
  }

}
?>
