<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a UK map block for species.
 *
 * @Block(
 *   id = "ns_species_uk_map_block",
 *   admin_label = @Translation("NatureSpot species UK map block"),
 * )
 */
class NsSpeciesUkMapBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      return [];
    }
    $nbnKey = $node->field_nbn_number->value;
    if (preg_match('/^[A-Z0-9]{16}$/', $nbnKey)) {
      $r = <<<HTML
<iframe class="map-frame" width="100%" height="590" src="https://easymap.nbnatlas.org/EasyMap?tvk=$nbnKey&w=332&b0from=1800&b0to=2014&b0fill=99c2ff&b1from=2015&b1to=2019&b1fill=000099&b2from=2020&b2fill=990000">
</iframe>
HTML;
    }
    else {
      $r = '<div class="alert alert-info">This species or aggregate is not available on the NBN Atlas currently</div>';
    }
    return [
      '#markup' => Markup::create($r),
    ];

  }

}
