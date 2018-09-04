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
    $ctools_enabled = TRUE; // \Drupal::moduleHandler()->moduleExists('ctools');
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

    $form['example'] = [
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
    $form['css_source'] = [
      '#type' => 'radios',
      '#title' => t('CSS Source'),
      '#options' => $source_options,
      '#default_value' => \Drupal::config('pullquote.settings')->get('css_source'),
      '#required' => TRUE,
      '#description' => t('Choose the source of your pullquote stylesheet'),
    ];

    $form['css_selection'] = [
      '#type' => 'select',
      '#title' => t('Pullquote Style'),
      '#options' => $css_files,
      '#default_value' => \Drupal::config('pullquote.settings')->get('css'),
      '#required' => TRUE,
      '#description' => t('Choose from one of the provided style sheets.'),
      '#states' => [
        'visible' => [
          ':input[name="css_source"]' => [
            'value' => 'selection'
          ]
        ]
      ],
    ];

    $form['css_path'] = [
      '#type' => 'textfield',
      '#title' => t('Path to custom Pullquote style sheet'),
      '#description' => t('The path to the file you would like to use for you pullquote css. We recommend putting your custom CSS in a libraries directory called pullquote: i.e. "sites/all/libraries/pullquote/mypullquote.css"'),
      '#default_value' => \Drupal::config('pullquote.settings')->get('css_source') == 'path' ? \Drupal::config('pullquote.settings')->get('css') : '',
      '#states' => [
        'visible' => [
          ':input[name="css_source"]' => [
            'value' => 'path'
          ]
        ]
      ],
    ];

    if ($ctools_enabled) {
      $description = t("If you don't have direct file access to the server, use this field to upload your style sheet.");
      if (\Drupal::config('pullquote.settings')->get('css_source') == 'upload') {
        $description .= '<br /><strong>' . t('The currently used CSS file is:') . ' ' . \Drupal::config('pullquote.settings')->get('css') . '</strong>';
      }

      $form['css_upload'] = [
        '#type' => 'file',
        '#title' => t('Upload css file'),
        '#maxlength' => 40,
        '#description' => $description,
        '#states' => [
          'visible' => [
            ':input[name="css_source"]' => [
              'value' => 'upload'
            ]
          ]
        ],
      ];
    }

    $attached_settings = [
      'pullquoteCurrent' => file_create_url(\Drupal::config('pullquote.settings')->get('css')),
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
    $file = file_save_upload('css_upload', $validators);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValue(['css_upload'], $file);
      }
      else {
        // File upload failed.
        $form_state->setErrorByName('css_upload', t('The css file could not be uploaded.'));
      }
    }

    if ($form_state->getValue(['css_source']) == 'upload' && !$file) {
      $form_state->setErrorByName('css_upload', t('You must choose a CSS file to upload.'));
    }

    if ($form_state->getValue(['css_source']) == 'path' && !$form_state->getValue(['css_path'])) {
      $form_state->setErrorByName('css_path', t('A valid CSS file path is required.'));
    }

    // If the user provided a path for a css file, make sure it exists at that
    // path.
    if ($form_state->getValue(['css_path'])) {
      $path = _pullquote_settings_validate_path($form_state->getValue(['css_path']));
      if (!$path) {
        $form_state->setErrorByName('css_path', t('The custom css path is invalid.'));
      }
    }
  }

  public function _submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // If the user uploaded a style sheet, save it to a permanent location.
    $source = $form_state->getValue(['css_source']);

    if ($source == 'selection') {
      $path = drupal_get_path('module', 'pullquote');
      $css = $path . '/css/' . $form_state->getValue(['css_selection']);
    }
    elseif ($source == 'path') {
      $css = $form_state->getValue(['css_path']);
    }
    elseif ($source == 'upload') {
      if ($file = $form_state->getValue(['css_upload'])) {
        $css_contents = file_get_contents($file->uri);
        if (\Drupal::moduleHandler()->moduleExists('ctools')) {
          ctools_include('css');
          $css_contents = ctools_css_filter($css_contents);
        }
        $filename = file_save_data($css_contents, file_default_scheme() . '://' . $file->filename);
        // Get rid of the temporary file.
        file_delete($file);
        $css = $filename->uri;
      }
    }
    else {
      //set a default.
      $css = $path . '/css/pullquote_style_1.css';
    }

    \Drupal::configFactory()->getEditable('pullquote.settings')->set('css', $css)->save();
    \Drupal::configFactory()->getEditable('pullquote.settings')->set('css_source', $form_state->getValue(['css_source']))->save();
    drupal_set_message(t('The configuration options have been saved.'));
  }

}

/**
 * Helper function for the to validate paths.
 *
 * @see _system_theme_settings_validate_path()
 * as this was copied directly from there.
 *
 * Attempts to validate normal system paths, paths relative to the public files
 * directory, or stream wrapper URIs. If the given path is any of the above,
 * returns a valid path or URI that the theme system can display.
 *
 * @param $path
 *   A path relative to the Drupal root or to the public files directory, or
 *   a stream wrapper URI.
 * @return mixed
 *   A valid path that can be displayed through the theme system, or FALSE if
 *   the path could not be validated.
 */
function _pullquote_settings_validate_path($path) {
  // Absolute local file paths are invalid.
  if (\Drupal::service("file_system")->realpath($path) == $path) {
    return FALSE;
  }
  // A path relative to the Drupal root or a fully qualified URI is valid.
  if (is_file($path)) {
    return $path;
  }
  // Prepend 'public://' for relative file paths within public filesystem.
  if (\Drupal::service("file_system")->uriScheme($path) === FALSE) {
    $path = 'public://' . $path;
  }
  if (is_file($path)) {
    return $path;
  }
  return FALSE;
}
