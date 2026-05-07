<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a map block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_latest_image_block",
 *   admin_label = @Translation("NatureSpot wild place latest_image block"),
 * )
 */
class NsWildPlaceLatestImageBlock extends BlockBase {

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
      'limit' => 1,
    ];
    $template = <<<HTML
<li>
  <div>
    <a data-fancybox="gallery" href="https://warehouse1.indicia.org.uk/upload/{image_path}" title="{common} {taxon}, {recorder}, {date}" data-caption="{common} {taxon}, {recorder}, {date}">
      <img width="220" src="https://warehouse1.indicia.org.uk/upload/med-{image_path}" alt="{common} {taxon}"/>
    </a>
    <div class="panel-region-separator">&nbsp;</div>
    <strong><a href="{rootFolder}species_by_key?key={external_key}">Learn more about<br/>{common} <em>{taxon}</em></a><br/>
    {recorder}</strong><br/>
    {image_caption}<br/>
  </div>
</li>
HTML;

    $options = [
      'id' => 'latest-image',
      'dataSource' => 'projects/naturespot/images_by_site',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'includeAllColumns' => FALSE,
      'header' => '<div class="item-list"><ul>',
      'bands' => [
        [
          'content' => $template,
        ],
      ],
      'footer' => '</ul></div>',
      'itemsPerPage' => 1,
      'autoParamsForm' => FALSE,
      'extraParams' => $params,
      'class' => 'species-gallery',
      'caching' => TRUE,
      'cachePerUser' => FALSE,
    ];
    $r = \report_helper::freeform_report($options);
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
