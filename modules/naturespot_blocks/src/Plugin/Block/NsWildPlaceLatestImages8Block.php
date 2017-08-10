<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides a map block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_latest_images8_block",
 *   admin_label = @Translation("NatureSpot wild place latest_images8 block"),
 * )
 */
class NsWildPlaceLatestImages8Block extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      return array();
    }
    iform_load_helpers(array('report_helper'));
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $siteName = $node->getTitle() . ($node->getType() === 'parish' ? ' CP' : '');
    $params = array('month'=>0, 'taxon_group'=>'all','site_name'=>$siteName, 'limit' => 8);
    $template = <<<HTML
<li>
  <div>
    <a class="colorbox" href="http://warehouse1.indicia.org.uk/upload/{image_path}" title="{common} {taxon}, {recorder}, {date}">
      <img src="http://warehouse1.indicia.org.uk/upload/med-{image_path}" alt="{common} {taxon}"/>
    </a><br/>
    <a href="{rootFolder}species_by_key?key={external_key}">{formatted_taxon}</a>
  </div>
</li>
HTML;

    $options=array(
      'id'=>'latest-images',
      'dataSource' => 'naturespot/images_by_site',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'header' => '<div class="item-list"><ul>',
      'bands'=>array(
        array('content' => $template)
      ),
      'footer' => '</ul></div>',
      'itemsPerPage' => 8,
      'autoParamsForm'=>false,
      'extraParams'=>$params,
      'class' => 'species-gallery small',
      'caching' => true,
      'cachePerUser' => false
    );
    $r = \report_helper::freeform_report($options);
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return array(
      '#markup' => SafeMarkup::format($r, array()),
    );

  }

}