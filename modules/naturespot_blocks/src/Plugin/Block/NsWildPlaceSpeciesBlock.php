<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides a species list block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_species_block",
 *   admin_label = @Translation("NatureSpot wild place species block"),
 * )
 */
class NsWildPlaceSpeciesBlock extends BlockBase {

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
    $params = array('site_name'=>$siteName);
    $loggedIn = \Drupal::currentUser()->id() > 0;
    $options=array(
      'id'=>'species-list',
      'dataSource' => 'naturespot/species_by_site',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'includeAllColumns' => false,
      'columns'=>array(
        array('display'=>'Group', 'fieldname'=>'taxon_group'),
        array ('fieldname'=>'common', 'display'=>'Common Name', 'template'=>'<a href="{rootFolder}species_by_key?key={external_key}">{common}</a>'),
        array ('fieldname'=>'taxon', 'display'=>'Latin Name', 'template'=>'<a href="{rootFolder}species_by_key?key={external_key}"><em>{taxon}</em></a>'),
        array('display'=>'Last Seen', 'fieldname'=>'date')
      ) ,
      'autoParamsForm'=>true,
      'extraParams'=>$params,
      'itemsPerPage' => 30,
      'paramDefaults'=>array('taxon_group_id'=>'','date_from'=>'','date_to'=>''),
      'pager' => $loggedIn,
      'forceNoFilterRow'=>!$loggedIn
    );
    $r = '';
    if ($loggedIn)
      $r = '<br/>' . \report_helper::report_download_link($options);
    else {
      // not logged in so limit to 1 page
      $options['extraParams']['limit'] = 30;
      $options['itemsPerPage'] = 30;
    }
    $r .= '<br/>' . \report_helper::report_grid($options);

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