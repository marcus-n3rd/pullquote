<?php

namespace Drupal\pullquote;

use \Drupal\Core\Asset\CssOptimizer;

class PullquoteCssOptimizer extends CssOptimizer {

  public function optimizeContent($contents) {
    return $this->processCss($contents);
  }
}
