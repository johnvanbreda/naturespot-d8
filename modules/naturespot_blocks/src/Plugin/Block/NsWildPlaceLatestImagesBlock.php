<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a map block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_latest_images_block",
 *   admin_label = @Translation("NatureSpot wild place latest_images block"),
 * )
 */
class NsWildPlaceLatestImagesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      return [];
    }
    iform_load_helpers(['report_helper']);
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $siteName = $node->getTitle() . ($node->getType() === 'parish' ? ' CP' : '');
    $params = [
      'month' => 0,
      'taxon_group' => 'all',
      'site_name' => $siteName,
    ];
    $loggedIn = \Drupal::currentUser()->id() > 0;
    if (!$loggedIn) {
      $params['limit'] = 20;
    }
    $template = <<<HTML
<div>
  <a data-fancybox="gallery" href="https://warehouse1.indicia.org.uk/upload/{image_path}" title="{common} {taxon}, {recorder}, {date}" data-caption="{common} {taxon}, {recorder}, {date}">
    <img width="100" src="https://warehouse1.indicia.org.uk/upload/thumb-{image_path}" alt="{common} {taxon}"/>
  </a><br/>
  <a href="{rootFolder}species_by_key?key={external_key}">{formatted_taxon}</a>
</div>
HTML;

    $options = [
      'id' => 'latest-images',
      'dataSource' => 'projects/naturespot/images_by_site',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'includeAllColumns' => FALSE,
      'headers' => FALSE,
      'includeAllColumns' => FALSE,
      'columns' => [
        ['display' => 'Photo', 'template' => $template],
      ],
      'itemsPerPage' => 32,
      'autoParamsForm' => FALSE,
      'extraParams' => $params,
      'class' => 'species-gallery table',
      'galleryColCount' => 8,
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
