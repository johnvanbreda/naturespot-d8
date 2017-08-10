<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides a latest records block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_latest_records_block",
 *   admin_label = @Translation("NatureSpot wild place latest records block"),
 * )
 */
class NsWildPlaceLatestRecordsBlock extends BlockBase {

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
    $params = array(
      'month' => 0,
      'taxon_group' => 'all',
      'site_name' => $siteName,
      'orderby' => 'date',
      'sortdir' => 'DESC',
      'limit' => 15
    );
    $options=array(
      'id'=>'latest-records',
      'dataSource' => 'naturespot/occurrences_by_site',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'includeAllColumns'=>false,
      'columns'=>array(
        array('fieldname' => 'geom', 'visible' => false),
        array ('fieldname'=>'common', 'display'=>'Common Name', 'template'=>'<a href="{rootFolder}species_by_key?key={external_key}">{common}</a>'),
        array ('fieldname'=>'taxon', 'display'=>'Latin Name', 'template'=>'<a href="{rootFolder}species_by_key?key={external_key}"><em>{taxon}</em></a>'),
        array ('fieldname'=>'date', 'display'=>'Date Recorded'),
        array ('fieldname'=>'recorder', 'display'=>'Recorded By')
      ) ,
      'itemsPerPage' => 15,
      'autoParamsForm' => false,
      'extraParams' => $params,
      'pager' => false
    );
    $r = \report_helper::report_grid($options);
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return array(
      '#markup' => SafeMarkup::format($r, array()),
      '#attached' => array(
        'library' => array(
          'iform/base',
          'iform/indiciaFns',
          'iform/reportgrid'
        )
      )
    );

  }

}