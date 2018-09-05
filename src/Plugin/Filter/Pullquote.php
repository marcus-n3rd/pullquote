<?php

namespace Drupal\pullquote\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "pullquote",
 *   title = @Translation("Pullquote"),
 *   description = @Translation("Allows the user to create a pullquote by surrounding text with simple tokens."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class Pullquote extends FilterBase {

  public function process($text, $langcode) {
    $text = $this->_process($text, FALSE);
    $text = $this->_process($text, TRUE);
    return new FilterProcessResult($text);
  }

  protected function _process($text, $left) {
    $pullquote = 'pullquote';
    $pullquote_class = 'pullquote';
    if ($left === TRUE) {
      $pullquote = 'pullquote-left';
      $pullquote_class = 'pullquote pullquote-left';
    }
    // Replace the tokens with span tags if and only if the starting tag has a
    // matching ending tag.
    if (strpos($text, '[' . $pullquote . ']') !== FALSE && strpos($text, '[/' . $pullquote . ']') !== FALSE) {
      $text = str_replace('[' . $pullquote . ']', '<span class="' . $pullquote_class . '">', $text);
      $text = str_replace('[/' . $pullquote . ']', '</span>', $text);
    }
    else {
      $text = str_replace('[' . $pullquote . ']', '', $text);
      $text = str_replace('[/' . $pullquote . ']', '', $text);
    }
  
    // Alternate Syntax for conflicts with Markrdown.
    if (strpos($text, '{' . $pullquote . '}') !== FALSE && strpos($text, '{/' . $pullquote . '}') !== FALSE) {
      $text = str_replace('{' . $pullquote . '}', '<span class="' . $pullquote_class . '">', $text);
      $text = str_replace('{/' . $pullquote . '}', '</span>', $text);
    }
    else {
      $text = str_replace('{' . $pullquote . '}', '', $text);
      $text = str_replace('{/' . $pullquote . '}', '', $text);
    }
    return $text;
  }

  public function tips($long = FALSE) {
    return \Drupal\Component\Utility\Html::escape(t('To make a selection of text into a pullquote add [pullquote] at the start of the quote and [/pullquote] at the end. Users of Markdown may prefer the alternate {pullquote} {/pullquote} syntax. Note that failure to include a closing tag will prevent the pullquote from being processed. You may also use [pullquote-left] to creaet a pullquote on the left side of the text.'));
  }

}
