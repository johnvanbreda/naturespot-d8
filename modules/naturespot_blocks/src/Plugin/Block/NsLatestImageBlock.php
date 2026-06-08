<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a latest image block.
 *
 * @Block(
 *   id = "ns_latest_image_block",
 *   admin_label = @Translation("NatureSpot latest image block"),
 * )
 */
class NsLatestImageBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(['report_helper']);
    $config = \Drupal::config('iform.settings');
    try {
      $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    }
    catch (\Exception $e) {
      \Drupal::logger('naturespot_blocks')->alert("Fetching read auth failed: " . $e->getMessage());
      return [
        '#markup' => Markup::create('<div class="alert alert-info">Server unavailable.</div>'),
      ];
    }
    $template = <<<HTML
<div>
  <a data-fancybox="gallery" href="https://warehouse1.indicia.org.uk/upload/{image_path}" title="{common} {taxon}, {recorder}, {date}"data-caption="{common} {taxon}, {recorder}, {date}">
    <img width="100%" src="https://warehouse1.indicia.org.uk/upload/med-{image_path}" alt="{common} {taxon}"/>
  </a><br/>
  <a href="{rootFolder}species_by_key?key={external_key}">{formatted_taxon}<br/>{recorder}<br/>{date}<br/>{site}</a>
</div>
HTML;

    $options = [
      'id' => 'latest-image',
      'dataSource' => 'projects/naturespot/latest_images',
      'class' => 'species-gallery',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'itemsPerPage' => 1,
      'autoParamsForm' => FALSE,
      'extraParams' => ['limit' => 1],
      'class' => 'species-gallery table',
      'caching' => TRUE,
      'cachePerUser' => FALSE,
      'bands' => [
        ['content' => $template],
      ],
    ];
    try {
      $r = \report_helper::freeform_report($options);
    }
    catch (\Exception $e) {
      \Drupal::logger('naturespot_blocks')->alert("Fetching latest image failed: " . $e->getMessage());
      return [
        '#markup' => Markup::create('<div class="alert alert-info">Server unavailable.</div>'),
      ];
    }
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return [
      '#markup' => Markup::create($r),
      '#cache' => [
        // No cache please.
        'max-age' => 0,
      ],
      '#attached' => [
        'library' => [
          'iform/base',
          'iform/indiciaFns',
          'iform/fancybox',
          'iform/reportgrid',
        ],
      ],
    ];

  }

}
