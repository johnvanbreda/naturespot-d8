<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides a map block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_species_count_block",
 *   admin_label = @Translation("NatureSpot wild place species count block"),
 * )
 */
class NsWildPlaceSpeciesCountBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      drupal_set_message('NsWildPlaceSpeciesCountBlock must be placed on a parish or wild place node page');
      return array();
    }
    iform_load_helpers(array('report_helper'));
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $siteName = $node->getTitle() . ($node->getType() === 'parish' ? ' CP' : '');
    $output = \report_helper::get_report_data(array(
      'dataSource' => 'naturespot/species_and_occurrence_counts_total_filtered_by_named_site',
      'readAuth' => $readAuth,
      'extraParams' => array(
        'website_id'=>$config->get('website_id'),
        'site_name'=>$siteName,
        'date_from'=>'',
        'date_to'=>'',
        'survey_id'=>''
      ),
      'mode'=>'report'
    ));
    $r = '<p id="site-species-count">Total species seen at this site: ' .
        $output[0]['species_count'] . '</p>';
    // Correct default paths for D8 since we are outside the iform module.
    global $indicia_theme_path;
    $indicia_theme_path = iform_media_folder_path() . 'themes/';
    return array(
      '#markup' => SafeMarkup::format($r, array())
    );

  }

}