<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides block that warns users to log out during planned warehouse down time.
 *
 * @Block(
 *   id = "warehouse_down_warning_block",
 *   admin_label = @Translation("NatureSpot Warehouse down warning block"),
 * )
 */
class WarehouseDownWarningBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $nowDate = new \DateTime();
    $downStart = \DateTime::createFromFormat('Y-m-d H:i', '2022-11-11 15:00');
    $downEnd = \DateTime::createFromFormat('Y-m-d H:i', '2022-11-14 08:30');
    if ($downStart < $nowDate && $downEnd > $nowDate) {
      $markup = '<div class="alert alert-warning"><h3>NatureSpot offline this weekend</h3>' .
        '<p>Due to essential maintenance work we are expecting that the iRecord and Indicia systems will be unavailable ' .
        'from 3pm on Friday 11 November until 8.30am on Monday 14 November. This means that parts of the NatureSpot ' .
        'website will be unavailable and you will not be able to submit records during this time. Apologies for any inconvenience.</p>' .
        '<p><strong>Please log out of NatureSpot until after this period - this will allow you to continue using many parts of the site but without being able to submit records.</strong></div>';
    }
    else {
      $markup = '';
    }
    return [
      '#markup' => Markup::create($markup),
      '#cache' => [
        // No cache please.
        'max-age' => 0,
      ],
    ];

  }

}
