<?php

namespace Drupal\naturespot_blocks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller that retrieves the image path update form.
 */
class NsUpdateWarehouseImagePaths extends ControllerBase {

  /**
   * Get a form.
   *
   * @return array
   *   Form build array.
   */
  public function upload(Request $request) {

    $form = \Drupal::formBuilder()->getForm('Drupal\naturespot_blocks\Form\NsUpdateWarehouseImagePaths');

    return $form;
  }

}
