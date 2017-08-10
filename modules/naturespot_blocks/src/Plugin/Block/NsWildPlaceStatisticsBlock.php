<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides a statistics block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_statistics_block",
 *   admin_label = @Translation("NatureSpot wild place latest_image block"),
 * )
 */
class NsWildPlacestatisticsBlock extends BlockBase {

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
    $website_id = $config->get('website_id');
    $readAuth = \report_helper::get_read_auth($website_id, $config->get('password'));
    $siteName = $node->getTitle() . ($node->getType() === 'parish' ? ' CP' : '');
    $options = array(
      'id' => 'parish-stats',
      'dataSource' => 'stats/species_and_occurrence_counts_by_taxon_group_filtered_by_named_site',
      'mode' => 'report',
      'readAuth' => $readAuth,
      'itemsPerPage' => 30,
      'autoParamsForm' => TRUE,
      'extraParams' => array(
        'site_name' => $siteName,
        'website_id' => 8,
        'orderby' => 'species_count',
        'date_from' => '',
        'date_to' => '',
        'survey_id' => '',
        'sortdir' => 'desc',
        'include_total' => 'yes'
      ),
      'columns' => array(
        array('fieldname' => 'taxongroup', 'display' => 'Species group'),
        array('fieldname' => 'species_count', 'display' => 'Total no. of species'),
        array('fieldname' => 'occurrences_count', 'display' => 'Total no. of records')
      ),
      'caching' => true,
      'cachePerUser' => false,
      'cacheTimeout' => 300
    );
    $r = \report_helper::report_download_link($options);
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