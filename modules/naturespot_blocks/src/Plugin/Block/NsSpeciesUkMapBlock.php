<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

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
      drupal_set_message('NsSpeciesUkMapBlock must be placed on a parish or wild place node page');
      return array();
    }
    $nbnKey = $node->field_nbn_number->value;
    $r = <<<HTML
<iframe class="map-frame" width="100%" height="590" src="https://easymap.nbnatlas.org/EasyMap?tvk=$nbnKey&w=332&b0fill=ff0000">
</iframe>
HTML;

    return array(
      '#markup' => SafeMarkup::format($r, array()),
      '#cache' => [
        'max-age' => 0, // no cache please
      ],
      '#attached' => array(
        'library' => array(
          'iform/base',
          'iform/indiciaFns',
          'iform/openlayers',
          'iform/indiciaMapPanel',
          //'iform/googlemaps'
        )
      ),
    );

  }

}