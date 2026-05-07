<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a latest images block.
 *
 * @Block(
 *   id = "ns_latest_images_block",
 *   admin_label = @Translation("NatureSpot latest images block"),
 * )
 */
class NsLatestImagesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(['report_helper']);
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $loggedIn = \Drupal::currentUser()->id() > 0;
    if (!$loggedIn) {
      $params['limit'] = 20;
    }
    $template = <<<HTML
<div>
  <a data-fancybox="gallery" href="https://warehouse1.indicia.org.uk/upload/{image_path}" title="{common} {taxon}, {recorder}, {date}" data-caption="{common} {taxon}, {recorder}, {date}">
    <img width="100%" src="https://warehouse1.indicia.org.uk/upload/med-{image_path}" alt="{common} {taxon}"/>
  </a><br/>
  <a href="{rootFolder}species_by_key?key={external_key}">{formatted_taxon}<br/>{recorder}<br/>{date}<br/>{site}</a>
</div>
HTML;

    $options = [
      'id' => 'latest-images',
      'dataSource' => 'projects/naturespot/latest_images',
      'class' => 'species-gallery',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'headers' => FALSE,
      'includeAllColumns' => FALSE,
      'columns' => [
        ['display' => 'Photo', 'template' => $template],
      ],
      'itemsPerPage' => 20,
      'autoParamsForm' => FALSE,
      'extraParams' => [],
      'class' => 'species-gallery table',
      'galleryColCount' => 4,
      'pager' => $loggedIn,
      'caching' => TRUE,
      'cachePerUser' => FALSE,
    ];
    $r = \report_helper::report_grid($options);
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
