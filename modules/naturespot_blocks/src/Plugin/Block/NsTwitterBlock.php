<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigDuplicateUUIDException;

/**
 * Provides a 'Latest Image' Block.
 *
 * @Block(
 *   id = "ns_twitter_block",
 *   admin_label = @Translation("NatureSpot twitter block"),
 * )
 */
class NsTwitterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
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