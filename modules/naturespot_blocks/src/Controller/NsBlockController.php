<?php

namespace Drupal\naturespot_blocks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\RedirectResponse;

class NsBlockController extends ControllerBase {

  public function speciesByKey() {
    if (empty($_GET['key'])) {
      \Drupal::logger('naturespot_blocks')->error('Missing key in call to species_by_key path');
      drupal_set_message('Missing key');
      return $this->redirect('<front>');
    }
    $query = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'species')
        ->condition('field_nbn_number', $_GET['key']);
    $result = $query->execute();
    if (count($result) > 1) {
      \Drupal::logger('naturespot_blocks')->error(
          "Duplicate species found for key $_GET[key]: " . json_encode(array_keys($result))
      );
      drupal_set_message("Duplicate species found for key $_GET[key]");
      return $this->redirect('<front>');
    } elseif (count($result) === 0) {
      \Drupal::logger('naturespot_blocks')->error("No species found for key $_GET[key]");
      drupal_set_message("No species found for key $_GET[key]");
      return $this->redirect('<front>');
    }
    $nid = array_pop($result);
    return $this->redirect('entity.node.canonical', ['node' => $nid]);
  }

  private function taxonomyCreate($vid) {
    if (empty($_POST['taxon']) || empty($_POST['redirect'])) {
      \Drupal::logger('naturespot_blocks')->error('Invalid call to taxonomyCreate');
      return $this->redirect('<front>');
    } else {
      $termData = array(
        'name' => $_POST['taxon'],
        'description' => $_POST['description'],
        'vid' => $vid,
      );
      if (!empty($_POST['parent_id']))
        $termData['parent'] = $_POST['parent_id'];
      Term::create($termData)->save();
    }
    return new RedirectResponse($_POST['redirect']);
  }

  public function taxonCreate() {
    return $this->taxonomyCreate('taxa');
  }

  public function menuCreate() {
    return $this->taxonomyCreate('menu');
  }
}