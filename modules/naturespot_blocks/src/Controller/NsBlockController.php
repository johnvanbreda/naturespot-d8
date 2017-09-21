<?php

namespace Drupal\naturespot_blocks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class NsBlockController extends ControllerBase {

  public function speciesByKey() {
    if (empty($_GET['key'])) {
      \Drupal::logger('naturespot_blocks')->error('Missing key in call to species_by_key path');
      drupal_set_message('Missing key');
      return $this->redirect('<front>');
    }
    $query = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'species')
        ->condition('field_nbn_number', $_GET['key']);
    $result = $query->execute();
    if (count($result) > 1) {
      \Drupal::logger('naturespot_blocks')->error(
          "Duplicate species found for key $_GET[key]: " . json_encode(array_keys($result))
      );
      drupal_set_message("Duplicate species found for key $_GET[key]");
      return $this->redirect('<front>');
    } elseif (count($result) === 0) {
      \Drupal::logger('naturespot_blocks')->error("No species found for key $_GET[key]");
      drupal_set_message("No species found for key $_GET[key]");
      return $this->redirect('<front>');
    }
    $nid = array_pop($result);
    return $this->redirect('entity.node.canonical', ['node' => $nid]);
  }

  public function repatriateImportedRecords() {
    iform_load_helpers(['data_entry_helper']);
    $userId = hostsite_get_user_field('indicia_user_id', '');
    $config = \Drupal::config('iform.settings');
    $auth = \data_entry_helper::get_read_write_auth($config->get('website_id'), $config->get('password'));
    $url = $config->get('base_url') . 'index.php/services/data_utils/naturespot_repatriate_imported_records/' . $userId;
    $session = curl_init();
    // Set the POST options.
    curl_setopt($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_POSTFIELDS, $auth['write_tokens']);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    // Do the POST and then close the session.
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    $curlErrno = curl_errno($session);
    if ($httpCode !== 200) {
      drupal_set_message('Record ownership tidy failed: ' . var_export($response, TRUE), 'error');
    }
    else {
      $output = json_decode($response);
      $affected = $output[0]->repatriate_imported_records;
      $recordsWere = $affected === 1 ? 'record was' : 'records were';
      drupal_set_message("Record ownership tidy complete. $affected $recordsWere tidied.");
    }
    return new RedirectResponse('/import-tidy');
  }

  private function taxonomyCreate($vid) {
    if (empty($_POST['taxon']) || empty($_POST['redirect'])) {
      \Drupal::logger('naturespot_blocks')->error('Invalid call to taxonomyCreate');
      return $this->redirect('<front>');
    } else {
      $termData = array(
        'name' => $_POST['taxon'],
        'description' => $_POST['description'],
        'vid' => $vid,
      );
      if (!empty($_POST['parent_id'])) {
        $termData['parent'] = $_POST['parent_id'];
      }
      Term::create($termData)->save();
    }
    return new RedirectResponse($_POST['redirect']);
  }

  public function taxonCreate() {
    return $this->taxonomyCreate('taxa');
  }

  public function menuCreate() {
    return $this->taxonomyCreate('menu');
  }

  private function getImagesFromLinkNodes($speciesTid, &$images) {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'image_link')
      ->condition('taxa.target_id', $speciesTid)
      ->sort('field_priority', 'ASC')
      ->execute();
    $nodes = entity_load_multiple('node', $nids);
    foreach ($nodes as $node) {
      $path = $node->field_file_name->value;
      $nid = $node->id();
      $isPlausible = in_array(strtolower($node->field_confidence->value), ['maybe', 'likely']) ? ' plausible' : '';
      $caption = empty($node->field_comment->value) ? '' : "\n<div>" . $node->field_comment->value . "</div>";
      $editUrl = $node->toUrl('edit-form')->toString();
      $imageHtml = <<<HTML
<li class="draggable$isPlausible" data-path="$path" data-nid="$nid">
<a href="https://warehouse1.indicia.org.uk/upload/$path" class="fancybox-popup" rel="gallery">
  <img height="180" src="https://warehouse1.indicia.org.uk/upload/med-$path"/>
</a>$caption
 <div class="links"><a href="$editUrl">edit</a></div>
</li>
<li class="droppable"></li>
HTML;
      if ($node->isPublished()) {
        if ($node->field_priority->value === '1' && $node->isPromoted()) {
          $images['priority1'][] = $imageHtml;
        }
        elseif ($node->isPromoted()) {
          $images['main'][] = $imageHtml;
        }
        else {
          $images['additional'][] = $imageHtml;
        }
      }
      else {
        $images['unused'][] = $imageHtml;
      }
    }
  }

  private function getImagesFromWarehouse($speciesTid, &$images, $tvk) {
    iform_load_helpers(['report_helper']);
    $config = \Drupal::config('iform.settings');
    $auth = \report_helper::get_read_auth($config->get('website_id'), $config->get('password'));
    $warehouseImages = \report_helper::get_report_data(array(
        'dataSource' => 'naturespot/images_to_copy_for_species',
        'readAuth' => $auth,
        'extraParams' => array('tvk' => $tvk),
    ));
    foreach ($warehouseImages as $image) {
      $isPlausible = ($image['confidence'] === 'Certain' ? '' : ' plausible');
      $caption = empty($image['caption']) ? '' : "\n<div>$image[caption]</div>";
      $imageHtml = <<<HTML
<li class="draggable$isPlausible" data-path="$image[path]" data-wid="$image[id]">
<div class="panel-heading">Warehouse image</div>
<a href="https://warehouse1.indicia.org.uk/upload/$image[path]" class="fancybox-popup" rel="gallery">
  <img height="180" src="https://warehouse1.indicia.org.uk/upload/med-$image[path]"/>
</a>$caption
</li>
<li class="droppable"></li>
HTML;
      $images['unused'][] = $imageHtml;
    }
  }

  public function imageOrganiser() {
    if (empty($_GET['species']) || !preg_match('/^\d+$/', $_GET['species'])) {
      $markup = 'Missing or invalid species parameter';
    } else {
      $speciesTid = $_GET['species'];
      // We need the NBN number field value for lookup
      $nids = \Drupal::entityQuery('node')
        ->condition('type', 'species')
        ->condition('taxa.target_id', $speciesTid)
        ->execute();
      $speciesNode = entity_load('node', array_pop($nids));
      $speciesPageUrl = $speciesNode->toUrl()->toString();
      $tvk = $speciesNode->field_nbn_number->value;
      $images = array(
          'priority1' => [],
          'main' => [],
          'additional' => [],
          'unused' => []
      );
      $this->getImagesFromLinkNodes($speciesTid, $images);
      $this->getImagesFromWarehouse($speciesTid, $images, $tvk);
      $priority1 = implode("\n", $images['priority1']);
      $main = implode("\n", $images['main']);
      $additional = implode("\n", $images['additional']);
      $unused = implode("\n", $images['unused']);
      $markup = <<<HTML
<div class="panel panel-info">
  <div class="panel-body">
    Drag images into the pale yellow drop placeholders to sort them and organise where they will
    appear. Images flagged as only plausible are highlighted with a red border and should be used with caution.
    Press the <strong>Save positions</strong> button when done. <a href="$speciesPageUrl">Return to the species page</a>.
  </div>
</div>
<div id="image-organiser">
  <div id="images-priority1"><h3>Gallery image</h3>
    <ul>
      <li class="droppable"></li>
      $priority1
    </ul>
  </div>
  <div id="images-main"><h3>Main tab</h3>
    <ul>
      <li class="droppable"></li>
      $main
    </ul>
  </div>
  <div id="images-additional"><h3>Additional tab</h3>
    <ul>
      <li class="droppable"></li>
      $additional
    </ul>
  </div>
  <div id="images-unused"><h3>Unused images (either linked to Drupal or from warehouse)</h3>
    <div class="panel panel-warning">
      <div class="panel-body">All unused images will be removed from Drupal when you save the positions.</div>
    </div>
    <ul>
      <li class="droppable">Drop here to remove from Drupal</li>
      $unused
    </ul>
  </div>
</div>
HTML;
    }
    $build = array(
      'images' => ['#markup' => $markup],
      'submit' => [
        '#type' => 'button',
        '#value' => $this->t('Save positions'),
        '#class' => 'btn btn-primary',
        '#id' => 'save-positions'
      ],
      // pass through the tid and tvk to make AJAX easier.
      'hiddenSpeciesTid' => [
        '#type' => 'hidden',
        '#name' => 'species-tid',
        '#value' => $speciesTid
      ],
      'hiddenTvk' => [
        '#type' => 'hidden',
        '#name' => 'tvk',
        '#value' => $tvk
      ]
    );
    $build['#attached']['library'][] = 'iform/fancybox';
    $build['#attached']['library'][] = 'core/jquery.ui.droppable';
    $build['#attached']['library'][] = 'naturespot_blocks/imageOrganiser';
    return $build;
  }

  private function updateNodeImage($image, $priority, $published, $promoted, $auth) {
    if (isset($image['nid'])) {
      $node = entity_load('node', $image['nid']);
    } else {
      // Grab the warehouse image node to get details from
      $warehouseImages = \report_helper::get_report_data(array(
        'dataSource' => 'naturespot/images_to_copy_for_species',
        'readAuth' => $auth['read'],
        'extraParams' => array(
            'tvk' => $_POST['tvk'],
            'id' => $image['wid'],
            'path' => $image['path'],
            'exclude_copied' => 0
        )
      ));
      \Drupal::logger('naturespot_blocks')->notice('Finding image ' . var_export(array(
            'id' => $image['wid'],
            'path' => $image['path'],
            'exclude_copied' => 0
        ), true));
      \Drupal::logger('naturespot_blocks')->notice('Found: ' . var_export($warehouseImages, true));
      $wImg = $warehouseImages[0];
      \Drupal::logger('naturespot_blocks')->notice('Creating new image node from ' . var_export($wImg, true));
      // Create a new image node
      // reformat the date
      $date = $wImg['date'];
      $time = strtotime(str_replace('/','-',$date));
      $date = date('d F Y', $time);
      $node = Node::create([
        'type'        => 'image_link',
        'title'       => $wImg['path'],
        'field_file_name' => [$wImg['path']],
        'field_priority' => [1000],
        'field_site' => [$wImg['location_name']],
        'field_date' => [$date],
        'field_recorder' => [$wImg['recorders']],
        'field_comment' => [$wImg['caption']],
        'field_confidence' => [$wImg['confidence']],
        'field_moderator' => [\Drupal::currentUser()->getDisplayName()],
        'taxa' => ['target_id' => $_POST['speciesTid']],
      ]);
      // Mark the warehouse image node as copied to Drupal
      $config = \Drupal::config('iform.settings');
      $values = array(
        'occurrence_image:id' => $wImg['id'],
        'occurrence_image:external_details' => 'Copied to Drupal',
        'website_id' => $config->get('website_id')
      );
      $submission = \data_entry_helper::build_submission($values, array('model' => 'occurrence_image'));
      $response = \data_entry_helper::forward_post_to('occurrence_image', $submission, array_merge($auth['write_tokens']));
      \Drupal::logger('naturespot_blocks')->notice('Submitting to warehouse (copied to Drupal): ' . var_export($submission, true));
    }
    if ((int)$node->field_priority->value !== (int)$priority ||
        $node->isPublished() !== $published || $node->isPromoted() !== $promoted) {
      $node->field_priority->value = $priority;
      $node->setPublished($published);
      $node->setPromoted($promoted);
      $node->save();
      \Drupal::logger('naturespot_blocks')->notice("Set node $image[nid] to promoted:" . var_export($promoted, true) .
          ", published:" . var_export($published, true) .", priority: " . var_export($priority, true));
      \Drupal::logger('naturespot_blocks')->notice("Original values - promoted:" . var_export($node->isPromoted(), true) .
          " published:" . var_export($node->isPublished(), true) . " priority: " . var_export($node->field_priority->value, true));
    }
  }

  /**
   * Updates the state of a node image or warehouse image so that it is only stored on the warehouse and not linked
   * to Drupal any more.
   * @param type $image
   */
  private function updateWarehouseImage($image, $auth) {
    // Only bother with node images, warehouse images are already done.
    if (isset($image['nid'])) {
      $node = entity_load('node', $image['nid']);
      $nids = \Drupal::entityQuery('node')
        ->condition('type', 'species')
        ->condition('taxa.target_id', $node->taxa->target_id)
        ->execute();
      $speciesNode = entity_load('node', array_pop($nids));
      // @todo Obtain the wid
      $path = $image['path'];
      $warehouseImages = \report_helper::get_report_data(array(
        'dataSource' => 'naturespot/images_to_copy_for_species',
        'readAuth' => $auth['read'],
        'extraParams' => array(
            'tvk' => $speciesNode->field_nbn_number->value,
            'path' => $path,
            'exclude_copied' => 0
        )
      ));
      $wid = $warehouseImages[0]['id'];
      \Drupal::logger('naturespot_blocks')->notice('Deleting node ' . $image['nid']);

      /* IF leaving nodes in Drupal unpublished */
      // unpublish the unwanted node
      $imageNode = entity_load('node', $image['nid']);
      $imageNode->setPublished(false);
      $imageNode->setPromoted(false);
      $imageNode->save();

      /* ELSEIF deleting nodes (conflicts with cron) *
      entity_delete_multiple('node', [$image['nid']]);
      $config = \Drupal::config('iform.settings');
      $submission = array(
        'occurrence_image:id' => $wid,
        'occurrence_image:external_details' => '', // clear this out so flagged as not on Drupal
        'website_id' => $config->get('website_id')
      );
      $submission = \data_entry_helper::build_submission($submission, array('model' => 'occurrence_image'));
      \Drupal::logger('naturespot_blocks')->notice('Submitting to warehouse (not copied to Drupal): ' . var_export($submission, true));
      $response = \data_entry_helper::forward_post_to('occurrence_image', $submission, array_merge($auth['write_tokens']));
       */
    }
  }

  public function imageOrganiserSave() {
    if (empty($_POST['data']) || empty($_POST['speciesTid'])) {
      return new JsonResponse(array(error => 'Data not valid'));
    }
    iform_load_helpers(['data_entry_helper', 'report_helper']);
    $config = \Drupal::config('iform.settings');
    $auth = \report_helper::get_read_write_auth($config->get('website_id'), $config->get('password'));
    $auth['write_tokens']['persist_auth']=true;
    $data = array_merge(array(
      'priority1' => [],
      'main' => [],
      'additional' => [],
      'unused' => []
    ), $_POST['data']);
    \Drupal::logger('naturespot_blocks')->notice('Data: ' . var_export(array_keys($data), true));
    foreach($data['priority1'] as $idx => $image) {
      $this->updateNodeImage($image, 1, true, true, $auth);
    }
    foreach($data['main'] as $idx => $image) {
      $this->updateNodeImage($image, $idx + 2, true, true, $auth);
    }
    foreach($data['additional'] as $idx => $image) {
      $this->updateNodeImage($image, $idx + 2 + count($data['main']), true, false, $auth);
    }
    foreach ($data['unused'] as $idx => $image) {
      $this->updateWarehouseImage($image, $auth);
    }
    return new JsonResponse(array('msg' => 'OK'));
  }

  public function getImageOrganiserTitle() {
    $nids = \Drupal::entityQuery('node')
        ->condition('type', 'species')
        ->condition('taxa.target_id', $_GET['species'])
        ->execute();
    $speciesNode = entity_load('node', array_pop($nids));
    $species = $speciesNode->getTitle();
    return "Image organiser for $species";
  }
}