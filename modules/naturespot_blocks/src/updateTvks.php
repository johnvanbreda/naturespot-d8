<?php

namespace Drupal\naturespot_blocks;

class updateTvks {

  public static function updateTvkItem($item, &$context) {
    $context['sandbox']['current_item'] = $item;
    $message = 'Updating ' . $item['taxon_on_naturespot'];
    self::doUpdate($item);
    $context['message'] = $message;
    $context['results'][] = $item;
  }

  public static function updateTvkItemCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One item processed.', '@count items processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
  }

  private static function doUpdate($item) {
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'species',
        'field_nbn_number' => $item['tvk_on_naturespot'],
      ]);
    foreach ($nodes as $node) {
      $node->set('field_nbn_number', $item['tvk_on_uksi']);
      $node->save();
    }
  }

}
