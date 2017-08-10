<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a user league block by count of records.
 *
 * @Block(
 *   id = "ns_my_contribution_summary_block",
 *   admin_label = @Translation("NatureSpot my contribution summary block"),
 * )
 */
class NsMyContributionSummaryBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(array('report_helper'));
    $config = \Drupal::config('iform.settings');
    $readAuth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $userId = hostsite_get_user_field('indicia_user_id', '');
    $r = '<h2>Your contribution summary</h2>';
    $r .= \report_helper::freeform_report(array(
      'id' => 'my-year-summary',
      'reportGroup' => 'my-records',
      'readAuth' => $readAuth,
      'dataSource' => 'naturespot/user_contribution_summary',
      'itemsPerPage' => 20,
      'rowId' => 'year',
      'autoParamsForm' => FALSE,
      'extraParams' => array(
        'taxon_list_id' => 8,
        'username' => hostsite_get_user_field('name'),
        'user_id' => $userId
      ),
      'paramDefaults' => array(
        'taxon_group_id' => '',
        'site_name' => '',
        'date_from' => '',
        'date_to' => ''
      ),
      'header' => '<div>',
      'bands' => array(
        array(
          'content' =>
'<div class="row"><div class="col-md-8"><strong>Number of records</strong></div><div class="col-md-4">{occurrences_count}</div></div>
<div class="row"><div class="col-md-8"><strong>Number of species</strong></div><div class="col-md-4">{species_count}</div></div>
<div class="row"><div class="col-md-8"><strong>Percentage of NatureSpot total species count</strong></div><div class="col-md-4">{species_percent}</div></div>'
        )
      ),
      'footer' => '</div>'
    ));
    $r .= '<p>Visit the <a href="user-league">User League</a> page to see how many species and records you have recorded with NatureSpot compared to other users.</p>';
    return [
      '#markup' => $r,
      '#cache' => [
        'contexts' => [
          // output is different per user
          'user'
        ],
        'tags' => [
          // output updates when the user posts a record
          'user_records:$userId'
        ]
      ]
    ];
  }

}