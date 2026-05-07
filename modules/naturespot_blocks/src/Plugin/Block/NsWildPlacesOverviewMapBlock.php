<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a map block for an overview map of all wild places.
 *
 * @Block(
 *   id = "ns_wild_places_overview_map_block",
 *   admin_label = @Translation("NatureSpot wild places overview map block"),
 * )
 */
class NsWildPlacesOverviewMapBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(['helper_base']);
    \helper_base::$indiciaData['googleApiKey'] = \Drupal::config('iform.settings')->get('google_maps_api_key');
    $r = <<<HTML
<div id="map" style="width: 100%; height: 450px;"></div>

HTML;
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return [
      '#markup' => Markup::create($r),
      '#attached' => [
        'library' => [
          'iform/base',
          'iform/indiciaFns',
          'naturespot_blocks/fullOpenLayers',
          'iform/googlemaps',
          'naturespot_blocks/wildPlacesOverviewMap',
        ],
      ],
      '#cache' => [
        // No cache please.
        'max-age' => 0,
      ],
    ];
  }

}
