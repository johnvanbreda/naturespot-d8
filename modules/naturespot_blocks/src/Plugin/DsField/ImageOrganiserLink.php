<?php

namespace Drupal\naturespot_blocks\Plugin\DsField;

use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\Core\Url;

/**
 * Plugin that renders the terms from a chosen taxonomy vocabulary.
 *
 * @DsField(
 *   id = "image_organiser_link",
 *   title = @Translation("Image organiser link"),
 *   entity_type = "node",
 *   provider = "naturespot_blocks",
 *   ui_limit = {"species|default"}
 * )
 */
class ImageOrganiserLink extends DsFieldBase {
  
  public function build() {
    if (!\Drupal::currentUser()->hasPermission('create species node')) {
      return [];
    }
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      // You can get nid and anything else you need from the node object.
      $tid = $node->taxa->target_id;
      return [
          '#title' => $this->t('Organise images'),
          '#type' => 'link',
          '#url' => Url::fromRoute('naturespot_blocks.image_organiser', [], ['query' => ['species' => $tid]])
      ];
    } else {
      return [];
    }
  }
}