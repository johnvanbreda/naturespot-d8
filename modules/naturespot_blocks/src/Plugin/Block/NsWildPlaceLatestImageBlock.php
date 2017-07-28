<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

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
      drupal_set_message('NsWildPlaceLatestImageBlock must be placed on a parish or wild place node page');
      return array();
    }
    iform_load_helpers(array('report_helper'));
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $siteName = $node->getTitle() . ($node->getType() === 'parish' ? ' CP' : '');
    $params = array('month'=>0, 'taxon_group'=>'all','site_name'=>$siteName, 'limit'=>1);
    $template = <<<HTML
<li>
  <div>
    <a class="colorbox" href="http://warehouse1.indicia.org.uk/upload/{image_path}" title="{common} {taxon}, {recorder}, {date}">
      <img width="220" src="http://warehouse1.indicia.org.uk/upload/med-{image_path}" alt="{common} {taxon}"/>
    </a>
    <div class="panel-region-separator">&nbsp;</div>
    <strong><a href="{rootFolder}species_by_key?key={external_key}">Learn more about<br/>{common} <em>{taxon}</em></a><br/>
    {recorder}</strong><br/>
    {image_caption}<br/>
  </div>
</li>
HTML;

    $options=array(
      'id'=>'latest-image',
      'dataSource' => 'naturespot/images_by_site',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'includeAllColumns'=>false,
      'header' => '<div class="item-list"><ul>',
      'bands'=>array(
        array (
          'content'=>$template
        )
      ),
      'footer' => '</ul></div>',
      'itemsPerPage' => 1,
      'autoParamsForm'=>false,
      'extraParams'=>$params,
      'class' => 'species-gallery',
      'caching' => true,
      'cachePerUser' => false
    );
    $r = \report_helper::freeform_report($options);
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return array(
      '#markup' => SafeMarkup::format($r, array()),
      '#cache' => [
        'max-age' => 0, // no cache please
      ],
      '#attached' => array(
        'library' => array(
          'iform/base',
          'iform/indiciaFns',
          'iform/fancybox',
          'iform/reportgrid'
        )
      ),
    );

  }

}