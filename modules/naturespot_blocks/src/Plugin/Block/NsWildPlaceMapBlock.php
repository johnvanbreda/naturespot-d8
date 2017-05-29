<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigDuplicateUUIDException;

/**
 * Provides a map block for wild places.
 *
 * @Block(
 *   id = "ns_wild_place_map_block",
 *   admin_label = @Translation("NatureSpot wild place map block"),
 * )
 */
class NsWildPlaceMapBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    iform_load_helpers(array('data_entry_helper', 'report_helper'));
    $readAuth = data_entry_helper::get_read_auth(variable_get('indicia_website_id', ''), variable_get('indicia_password', ''));
    $js_path = base_path() . drupal_get_path('module', 'iform').'/media/js/';
    drupal_add_js(drupal_get_path('module', 'iform').'/media/js/indicia.functions.js');
    drupal_add_js(drupal_get_path('module', 'iform').'/media/js/OpenLayers.js');
    drupal_add_js(drupal_get_path('module', 'iform').'/media/js/proj4js.js');
    drupal_add_js(drupal_get_path('module', 'iform').'/media/js/proj4defs.js');
    drupal_add_js(drupal_get_path('module', 'iform').'/media/js/jquery.indiciaMapPanel.js');
    drupal_add_js(drupal_get_path('module', 'iform').'/media/js/fancybox/jquery.fancybox.pack.js');
    drupal_add_js(drupal_get_path('module', 'iform').'/media/js/jquery.reportgrid.js');
    drupal_set_html_head('<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>');
    $options = array(
      'presetLayers' => array('google_satellite'),
      'editLayer' => false,
      'jsPath'=>'/sites/all/modules/iform/media/js/',
      'initial_lat'=>52.67721,
      'initial_long'=>-1.08765,
      'initial_zoom'=>9,
      'width'=>415,
      'height'=>350
    );
    $olOptions=array('theme'=>'/sites/all/modules/iform/media/js/theme/default/style.css');
    echo data_entry_helper::map_panel($options, $olOptions);
    echo report_helper::report_map(array(
      'readAuth' => $readAuth,
      'dataSource' => 'naturespot/site_boundary',
      'extraParams' => array('site_name' => '%node:title'),
      'caching' => true,
      'cachePerUser' => false,
      'clickable'=>false
    ));
    data_entry_helper::$required_resources=array();
    echo data_entry_helper::dump_javascript();
    return array(
      '#markup' => <<<CODE
<p><a class="twitter-timeline" href="https://twitter.com/Nature_Spot">Tweets by @Nature_Spot</a></p>
<!--

Note we can limit width/height or number of tweets but not both!!

Limit to 3 tweets:
<p><a class="twitter-timeline" href="https://twitter.com/Nature_Spot" data-widget-id="719598821441937409" data-tweet-limit="3" width="400" height="300">Tweets by @Nature_Spot</a></p>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

Allow height limit to apply:
<p><a class="twitter-timeline" href="https://twitter.com/Nature_Spot" data-widget-id="719598821441937409" width="400" height="450">Tweets by @Nature_Spot</a></p>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

-->
CODE
    );
  }

}