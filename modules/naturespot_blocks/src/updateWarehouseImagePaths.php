<?php

namespace Drupal\naturespot_blocks;

class UpdateWarehouseImagePaths {

  public static function updateWarehouseImagePathItem($item, &$context) {
    $context['sandbox']['current_item'] = $item;
    $message = 'Updating ' . $item['old_path'];
    self::doUpdate($item);
    $context['message'] = $message;
    $context['results'][] = $item;
  }

  public static function updateWarehouseImagePathItemCallback($success, $results, $operations) {
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
        'type' => 'image_link',
        'field_file_name' => $item['old_path'],
      ]);
    foreach ($nodes as $node) {
      $node->set('field_file_name', $item['new_path']);
      $node->save();
      \Drupal::logger('naturespot_blocks')->info("Updated image link $item[old_path] to $item[new_path]");
    }
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'wildlife_guide_entry',
        'field_image_url' => $item['old_path'],
      ]);
    foreach ($nodes as $node) {
      $node->set('field_image_url', $item['new_path']);
      $node->save();
      \Drupal::logger('naturespot_blocks')->info("Updated guide entry $item[old_path] to $item[new_path]");
    }
  }

}
