<?php

namespace Drupal\naturespot_blocks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller class for various custom routes.
 */
class NsBlockController extends ControllerBase {
  use MessengerTrait;

  /**
   * Route action to redirect to a species account page using NBN key.
   */
  public function speciesByKey() {
    if (!isset($_GET['key'])) {
      \Drupal::logger('naturespot_blocks')->error('Missing key in call to species_by_key path');
      $this->messenger()->addMessage('Missing key');
      return $this->redirect('<front>');
    }
    elseif (trim($_GET['key'] ?? '') === '') {
      // Redirect to new species page.
      return $this->redirect('entity.node.canonical', ['node' => 245383]);
    }
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'species')
      ->condition('field_nbn_number', $_GET['key'])
      ->accessCheck(FALSE);
    $result = $query->execute();
    if (count($result) > 1) {
      \Drupal::logger('naturespot_blocks')->error(
          "Duplicate species found for key $_GET[key]: " . json_encode(array_keys($result))
      );
      $this->messenger()->addMessage("Duplicate species found for key $_GET[key]");
      return $this->redirect('<front>');
    }
    elseif (count($result) === 0) {
      // Redirect to new species page as species account page doesn't exist yet.
      return $this->redirect('entity.node.canonical', ['node' => 245383]);
    }
    $nid = array_pop($result);
    return $this->redirect('entity.node.canonical', ['node' => $nid]);
  }

  /**
   * Redirect to project exploration page using a project title URL slug.
   *
   * @param string $title
   *   URL formatted name of the project.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function exploreProjectFromTitle($title) {
    iform_load_helpers(['report_helper']);
    $config = \Drupal::config('iform.settings');
    $auth = \report_helper::get_read_write_auth($config->get('website_id'), $config->get('password'));
    $indiciaUserId = hostsite_get_user_field('indicia_user_id', 0);
    $groups = \report_helper::get_report_data([
      'dataSource' => 'library/groups/find_group_by_url',
      'readAuth' => $auth['read'],
      'extraParams' => [
        'title' => $title,
        'currentUser' => $indiciaUserId,
      ],
    ]);
    if (isset($groups['error'])) {
      \Drupal::logger('naturespot_blocks')->notice('Group lookup error: ' . var_export($groups, TRUE));
      $this->messenger()->addWarning($this->t('An error occurred when trying to access the group.'));
      return $this->redirect('<front>');
    }
    if (!count($groups)) {
      $this->messenger()->addWarning($this->t('The group could not be found.'));
      return $this->redirect('<front>');
    }
    if (count($groups) > 1) {
      $this->messenger()->addWarning($this->t('More than one group matched that title.'));
      return $this->redirect('<front>');
    }
    return new RedirectResponse(Url::fromUri('internal:/projects/explore', [
      'query' => [
        'group_id' => $groups[0]['id'],
        'implicit' => 'f',
      ],
    ])->toString());
  }

  public function imageEditByFile() {
    if (empty($_GET['file'])) {
      \Drupal::logger('naturespot_blocks')->error('Missing file in call to image_edit_by_file path');
      $this->messenger()->addMessage('Missing file');
      return $this->redirect('<front>');
    }
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'image_link')
      ->condition('field_file_name', $_GET['file'])
      ->accessCheck(FALSE);
    $result = $query->execute();
    if (count($result) > 1) {
      \Drupal::logger('naturespot_blocks')->error(
          "Duplicate image link found for file $_GET[file]: " . json_encode(array_keys($result))
      );
      $this->messenger()->addMessage("Duplicate species found for file $_GET[file]");
      return $this->redirect('<front>');
    }
    elseif (count($result) === 0) {
      \Drupal::logger('naturespot_blocks')->error(
        "Cannot find image link found for file $_GET[file]: " . json_encode(array_keys($result))
      );
      $this->messenger()->addMessage("No image link found for file $_GET[file]");
      return $this->redirect('<front>');
    }
    $nid = array_pop($result);
    return $this->redirect('entity.node.edit_form', ['node' => $nid]);
  }

  /**
   * Controller route for species recording advice markup to add to input page.
   */
  public function speciesRecordingAdvice() {
    if (empty($_GET['key'])) {
      \Drupal::logger('naturespot_blocks')->error('Missing key in call to species_by_key path');
      return new JsonResponse(['msg' => '', 'additional' => 'Missing key']);
    }
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'species')
      ->condition('field_nbn_number', $_GET['key'])
      ->accessCheck(FALSE);

    $result = $query->execute();
    if (count($result) === 0) {
      return new JsonResponse(['msg' => '', 'additional' => 'Species not found']);
    }
    $nodes = Node::loadMultiple($result);
    $markup = '';
    $path = \Drupal::service('file_url_generator')->generateAbsoluteString("public://") . '/upload';
    $icons = [
      'G' => "<img src=\"$path/PhotoID_green.jpg\" width=\"32\" height=\"20\" title=\"Common and easily identified.\" />",
      'A' => "<img src=\"$path/PhotoID_amber.jpg\" width=\"32\" height=\"20\" title=\"Identifiable with care and/or uncommon.\" />",
      'R' => "<img src=\"$path/PhotoID_red.jpg\" width=\"32\" height=\"20\" title=\"Requires detailed examination to identify and/or scarce.\" />"
    ];
    foreach ($nodes as $node) {
      if (!empty($node->field_photo_id->value)) {
        $markup .= '<strong>Identification difficulty</strong><br/>';
        $markup .= preg_replace([
          '/\[G\]/',
          '/^(<p>)?G(<\/p>)?$/',
          '/\[A\]/',
          '/^(<p>)?A(<\/p>)?$/',
          '/\[R\]/',
          '/^(<p>)?R(<\/p>)?$/',
        ], [
          $icons['G'],
          $icons['G'],
          $icons['A'],
          $icons['A'],
          $icons['R'],
          $icons['R'],
        ], trim($node->field_photo_id->value));
      }
      if (!empty($node->field_recording_advice->value)) {
        $markup .= $node->field_recording_advice->value;
      }
      $tids = [];
      foreach ($node->field_species_library_menu->getValue() as $menuItem) {
        $ancestors = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadAllParents($menuItem['target_id']);
        foreach ($ancestors as $term) {
          $tids[] = $term->id();
        }
      }
      $terms = Term::loadMultiple($tids);
      foreach ($terms as $term) {
        if (!empty($term->field_recording_advice->value)) {
          $markup .= $term->field_recording_advice->value;
        }
      }

    }
    return new JsonResponse(['msg' => $markup]);
  }

  /**
   * Species Red/Amber/Green icon service.
   *
   * Provides a simple lookup for the red/amber/green icons for species which
   * can be integrated into reports. The species NBN keys should be provided in
   * a URL parameter called keys (comma separated).
   *
   * @return string
   *   Icon HTML in a JSON array, keyed by NBN key.
   */
  public function speciesRag() {
    $keys = \Drupal::request()->query->get('keys');
    if (empty($keys)) {
      \Drupal::logger('naturespot_blocks')->error('Missing key in call to species_rag path');
      return ['#markup' => 'Missing key'];
    }
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'species')
      ->condition('field_nbn_number', explode(',', $keys), 'IN')
      ->accessCheck(FALSE);
    $result = $query->execute();
    if (count($result) === 0) {
      return ['#markup' => 'Species not found'];
    }
    $nodes = Node::loadMultiple($result);
    $r = [];
    foreach ($nodes as $node) {
      if (!empty($node->field_photo_id->value)) {
        $renderArray = $node->field_photo_id->view('full');
        $renderArray['#title'] = '';
        $renderArray['#title_display'] = 'invisible';
        $markup = \Drupal::service('renderer')->renderRoot($renderArray);
        $r[$node->field_nbn_number->value] = $markup;
      }
    }
    return new JsonResponse($r);
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
      $this->messenger()->addMessage('Record ownership tidy failed: ' . var_export($response, TRUE), 'error');
    }
    else {
      $output = json_decode($response);
      $affected = $output[0]->repatriate_imported_records;
      $recordsWere = $affected === 1 ? 'record was' : 'records were';
      $this->messenger()->addMessage("Record ownership tidy complete. $affected $recordsWere tidied.");
    }
    return new RedirectResponse('/import-tidy');
  }

  private function taxonomyCreate($vid) {
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    if (empty($request->request->get('taxon')) || empty($request->request->get('redirect'))) {
      \Drupal::logger('naturespot_blocks')->error('Invalid call to taxonomyCreate');
      return $this->redirect('<front>');
    }
    else {
      $termData = [
        'name' => $request->request->get('taxon'),
        'description' => $request->request->get('description'),
        'vid' => $vid,
      ];
      if (!empty($request->request->get('parent_id'))) {
        $termData['parent'] = $request->request->get('parent_id');
      }
      Term::create($termData)->save();
    }
    $redirect = $request->request->get('redirect');
    // Validate redirect URL - only allow species-taxonomy-child/{id} or
    // species-taxonomy-top.
    // Strip optional protocol and base URL at the start.
    $baseUrl = \Drupal::request()->getSchemeAndHttpHost() . base_path();
    $redirect = preg_replace('#^' . preg_quote($baseUrl, '#') . '#', '/', $redirect);
    if (!preg_match('#^/species-taxonomy-child/\d+$#', $redirect) && $redirect !== '/species-taxonomy-top') {
      $redirect = '/species-taxonomy-top';
    }
    return new RedirectResponse($redirect);
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
      ->sort('nid', 'ASC')
      ->accessCheck(FALSE)
      ->execute();
    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node) {
      $path = $node->field_file_name->value;
      $nid = $node->id();
      $isPlausible = in_array(
        strtolower($node->field_confidence->value), ['maybe', 'likely']) ? ' plausible' : '';
      $caption = empty($node->field_comment->value) ? '' : "\n<div>" . $node->field_comment->value . "</div>";
      $captionAttr = empty($node->field_comment->value) ? '' : $node->field_comment->value;
      $editUrl = $node->toUrl('edit-form')->toString() . '?destination=/ns/image-organiser?species=' . $_GET['species'];
      $imageHtml = <<<HTML
<li class="draggable$isPlausible" data-path="$path" data-nid="$nid">
<a href="https://warehouse1.indicia.org.uk/upload/$path" class="fancybox-popup" rel="gallery" data-caption="$captionAttr">
  <img height="180" src="https://warehouse1.indicia.org.uk/upload/med-$path"/>
</a>$caption
 <div class="links"><a href="$editUrl"><span class="fas fa-pen"></span></a> <span class="fas fa-trash-alt" title="Permanently remove this image from the Image Organiser"></span></div>
</li>
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
    $warehouseImages = \report_helper::get_report_data([
      'dataSource' => 'projects/naturespot/images_to_copy_for_species',
      'readAuth' => $auth,
      'extraParams' => [
        'tvk' => $tvk,
        'exclude_copied' => isset($_GET['checkall']) ? '0' : '1',
      ],
    ]);
    foreach ($warehouseImages as $image) {
      $isPlausible = ($image['confidence'] === 'Certain' ? '' : ' plausible');
      $caption = empty($image['caption']) ? '' : "\n<div>$image[caption]</div>";
      $imageHtml = <<<HTML
<li class="draggable$isPlausible" data-path="$image[path]" data-wid="$image[id]">
<div class="panel-heading">Warehouse image</div>
<a href="https://warehouse1.indicia.org.uk/upload/$image[path]" class="fancybox-popup" rel="gallery" data-caption="$image[caption]">
  <img height="180" src="https://warehouse1.indicia.org.uk/upload/med-$image[path]"/>
</a>$caption
<div class="links"><span class="fas fa-trash-alt" title="Permanently remove this image from the Image Organiser"></span></div>
</li>
HTML;
      $images['unused'][] = $imageHtml;
    }
  }

  private function getImageCleanupOutput() {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'image_link')
      ->condition('status', 0)
      ->sort('nid', 'ASC')
      ->range(0, 200)
      ->accessCheck(FALSE)
      ->execute();
    $content = '';
    $images = Node::loadMultiple($nids);
    $config = \Drupal::config('iform.settings');
    iform_load_helpers(['report_helper', 'data_entry_helper']);
    $auth = \report_helper::get_read_write_auth($config->get('website_id'), $config->get('password'));
    $auth['write_tokens']['persist_auth'] = TRUE;

    foreach ($images as $image) {
      $content .= '<div class="panel panel-info">Nid:' . $image->id() . '<br/>Path: ' . $image->field_file_name->value .
      '<br/>Occurrence ID: ' . $image->field_record_id->value . '</div>';
      if (empty($image->taxa->target_id)) {
        $content .= '<div class="panel panel-danger">Empty image node</div>';
        \Drupal::logger('naturespot_blocks')->notice('Deleting node ' . $image->id());
        $image->delete();
      }
      else {
        $this->updateWarehouseImage([
          'nid' => $image->id(),
          'path' => $image->field_file_name->value,
        ], $auth, $image);
      }
    }
    return [
      'output' => [
        '#markup' => '<h1>Image cleanup</h1>' . $content,
      ],
    ];
  }

  public function imageOrganiser() {
    if (!empty($_GET['clean_images'])) {
      return $this->getImageCleanupOutput();
    }
    if (empty($_GET['species']) || !preg_match('/^\d+$/', $_GET['species'])) {
      $markup = 'Missing or invalid species parameter';
    }
    else {
      $speciesTid = $_GET['species'];
      // We need the NBN number field value for lookup.
      $nids = \Drupal::entityQuery('node')
        ->condition('type', 'species')
        ->condition('taxa.target_id', $speciesTid)
        ->accessCheck(FALSE)
        ->execute();
      $speciesNode = Node::load(array_pop($nids));
      $speciesPageUrl = $speciesNode->toUrl()->toString();
      $tvk = $speciesNode->field_nbn_number->value;
      $images = [
        'priority1' => [],
        'main' => [],
        'additional' => [],
        'unused' => [],
      ];
      $this->getImagesFromLinkNodes($speciesTid, $images);
      $this->getImagesFromWarehouse($speciesTid, $images, $tvk);
      $priority1 = implode("\n", $images['priority1']);
      $main = implode("\n", $images['main']);
      $additional = implode("\n", $images['additional']);
      $unused = implode("\n", $images['unused']);
      $checkAllToggleLink = isset($_GET['checkall'])
        ? '<a href="/ns/image-organiser?species=' . $_GET['species'] . '" class="btn btn-info btn-xs">Hide previously discarded warehouse images.</a>'
        : '<a href="/ns/image-organiser?species=' . $_GET['species'] . '&checkall" class="btn btn-info btn-xs">Show previously discarded warehouse images.</a>';
      $markup = <<<HTML
<div class="alert alert-info">
  Drag and drop the images into the correct order and group of images. Images flagged as only plausible are
  highlighted with a red border and should be used with caution. Press the <strong>Save positions</strong>
  button when done.
  $checkAllToggleLink
  <a id="species-page-link" href="$speciesPageUrl" class="btn btn-info btn-xs">Return to the species page.</a>.
</div>
<div id="image-organiser">
  <div id="images-priority1"><h3>Gallery image</h3>
    <ul>
      $priority1
    </ul>
  </div>
  <div id="images-main"><h3>Main tab</h3>
    <ul>
      $main
    </ul>
  </div>
  <div id="images-additional"><h3>Additional tab</h3>
    <ul>
      $additional
    </ul>
  </div>
  <div id="images-unused"><h3>Unused images (either linked to Drupal or from warehouse)</h3>
    <div class="panel panel-warning">
      <div class="panel-body">Set the Trash icon to permanently remove images from the Image Organiser.</div>
    </div>
    <ul>
      <li class="droppable">Drop here to remove from Drupal</li>
      $unused
    </ul>
  </div>
</div>
HTML;
    }
    $build = [
      'images' => ['#markup' => $markup],
      'submit' => [
        '#type' => 'button',
        '#value' => $this->t('Save positions'),
        '#class' => 'btn btn-primary',
        '#id' => 'save-positions',
      ],
      // Pass through the tid and tvk to make AJAX easier.
      'hiddenSpeciesTid' => [
        '#type' => 'hidden',
        '#name' => 'species-tid',
        '#value' => $speciesTid,
      ],
      'hiddenTvk' => [
        '#type' => 'hidden',
        '#name' => 'tvk',
        '#value' => $tvk,
      ]
    ];
    $build['#attached']['library'][] = 'iform/fancybox';
    $build['#attached']['library'][] = 'iform/sortable';
    $build['#attached']['library'][] = 'naturespot_blocks/imageOrganiser';
    return $build;
  }

  private function updateNodeImage($image, $priority, $published, $promoted, $auth) {
    if (isset($image['nid'])) {
      $node = Node::load($image['nid']);
    }
    else {
      // Grab the warehouse image node to get details from.
      $warehouseImages = \report_helper::get_report_data([
        'dataSource' => 'projects/naturespot/images_to_copy_for_species',
        'readAuth' => $auth['read'],
        'extraParams' => [
          'tvk' => $_POST['tvk'],
          'id' => $image['wid'],
          'path' => $image['path'],
          'exclude_copied' => 0,
        ],
      ]);
      \Drupal::logger('naturespot_blocks')->notice('Finding image ' . var_export([
        'id' => $image['wid'],
        'path' => $image['path'],
        'exclude_copied' => 0,
      ], TRUE));
      \Drupal::logger('naturespot_blocks')->notice('Found: ' . var_export($warehouseImages, TRUE));
      $wImg = $warehouseImages[0];
      \Drupal::logger('naturespot_blocks')->notice('Creating new image node from ' . var_export($wImg, TRUE));
      // Create a new image node
      // Reformat the date.
      $date = $wImg['date'];
      $time = strtotime(str_replace('/', '-', $date));
      // Display label.
      $dateLabel = date('d F Y', $time);
      // Date formatted as date for sort.
      $dateSort = date('Y-m-d', $time);
      $node = Node::create([
        'type'        => 'image_link',
        'title'       => $wImg['path'],
        'field_file_name' => [$wImg['path']],
        'field_priority' => [1000],
        'field_site' => [$wImg['location_name']],
        'field_date' => [$dateLabel],
        'field_date_sort' => [$dateSort],
        'field_recorder' => [$wImg['recorders']],
        'field_comment' => [$wImg['caption']],
        'field_confidence' => [$wImg['confidence']],
        'field_record_id' => [$wImg['occurrence_id']],
        'field_moderator' => [\Drupal::currentUser()->getDisplayName()],
        'taxa' => ['target_id' => $_POST['speciesTid']],
      ]);
      $node->save();
      // Mark the warehouse image node as copied to Drupal.
      $config = \Drupal::config('iform.settings');
      $values = [
        'occurrence_image:id' => $wImg['id'],
        'occurrence_image:external_details' => 'Copied to Drupal',
        'website_id' => $config->get('website_id'),
      ];
      $submission = \submission_builder::build_submission($values, ['model' => 'occurrence_image']);
      $response = \data_entry_helper::forward_post_to('occurrence_image', $submission, array_merge($auth['write_tokens']));
      \Drupal::logger('naturespot_blocks')->notice('Submitting to warehouse (copied to Drupal): ' . var_export($submission, TRUE));
    }
    if ((int) $node->field_priority->value !== (int) $priority ||
        $node->isPublished() !== $published || $node->isPromoted() !== $promoted) {
      $node->field_priority->value = $priority;
      $node->setPublished($published);
      $node->setPromoted($promoted);
      $node->save();
      \Drupal::logger('naturespot_blocks')->notice("Set node $image[nid] to promoted:" . var_export($promoted, TRUE) .
          ", published:" . var_export($published, TRUE) . ", priority: " . var_export($priority, TRUE));
      \Drupal::logger('naturespot_blocks')->notice("Original values - promoted:" . var_export($node->isPromoted(), TRUE) .
          " published:" . var_export($node->isPublished(), TRUE) . " priority: " . var_export($node->field_priority->value, TRUE));
    }
  }

  /**
   * Unlink an image from Drupal.
   *
   * Updates the state of a node image or warehouse image so that it is only
   * stored on the warehouse and not linked to Drupal any more.
   *
   * @param array $image
   *   Image info, containing nid and or wid (warehouse ID) plus deleted flag.
   * @param array $auth
   *   Auth tokens.
   * @param object $imageNode
   *   Image node object, if available.
   */
  private function updateWarehouseImage(array $image, array $auth, $imageNode = NULL) {
    // Only bother with node images, warehouse images are already done.
    if (isset($image['nid'])) {
      // Use the image node to find the species node and it's TVK.
      if (!$imageNode) {
        $imageNode = Node::load($image['nid']);
      }
      $nids = \Drupal::entityQuery('node')
        ->condition('type', 'species')
        ->condition('taxa.target_id', $imageNode->taxa->target_id)
        ->accessCheck(FALSE)
        ->execute();
      $speciesNode = Node::load(array_pop($nids));
      $tvk = $speciesNode->field_nbn_number->value;
      // Hard-delete the node.
      \Drupal::logger('naturespot_blocks')->notice('Deleting node ' . $image['nid']);
      $imageNode->delete();
      // Obtain the wid.
      $path = $image['path'];
      $warehouseImages = \report_helper::get_report_data([
        'dataSource' => 'projects/naturespot/images_to_copy_for_species',
        'readAuth' => $auth['read'],
        'extraParams' => [
          'tvk' => $tvk,
          'path' => $path,
          'exclude_copied' => 0,
        ],
      ]);
      if (count($warehouseImages) === 0) {
        return;
      }
      $wid = $warehouseImages[0]['id'];
    }
    else {
      $wid = $image['wid'];
    }
    $externalDetails = empty($image['deleted']) ? '' : 'Rejected for Drupal import';
    $config = \Drupal::config('iform.settings');
    $submission = [
      'occurrence_image:id' => $wid,
      'occurrence_image:external_details' => $externalDetails,
      'website_id' => $config->get('website_id'),
    ];
    $submission = \submission_builder::build_submission($submission, ['model' => 'occurrence_image']);
    $response = \data_entry_helper::forward_post_to('occurrence_image', $submission, array_merge($auth['write_tokens']));
  }

  public function imageOrganiserSave() {
    if (empty($_POST['data']) || empty($_POST['speciesTid'])) {
      return new JsonResponse(['error' => 'Data not valid']);
    }
    iform_load_helpers(['data_entry_helper', 'report_helper']);
    $config = \Drupal::config('iform.settings');
    $auth = \report_helper::get_read_write_auth($config->get('website_id'), $config->get('password'));
    $auth['write_tokens']['persist_auth'] = TRUE;
    $data = array_merge([
      'priority1' => [],
      'main' => [],
      'additional' => [],
      'unused' => []
    ], $_POST['data']);
    \Drupal::logger('naturespot_blocks')->notice('Data: ' . var_export(array_keys($data), TRUE));
    foreach ($data['priority1'] as $idx => $image) {
      $this->updateNodeImage($image, 1, TRUE, TRUE, $auth);
    }
    foreach ($data['main'] as $idx => $image) {
      $this->updateNodeImage($image, $idx + 2, TRUE, TRUE, $auth);
    }
    foreach ($data['additional'] as $idx => $image) {
      $this->updateNodeImage($image, $idx + 2 + count($data['main']), TRUE, FALSE, $auth);
    }
    foreach ($data['unused'] as $idx => $image) {
      $this->updateWarehouseImage($image, $auth);
    }
    return new JsonResponse(['msg' => 'OK']);
  }

  public function getImageOrganiserTitle() {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'species')
      ->condition('taxa.target_id', $_GET['species'])
      ->accessCheck(FALSE)
      ->execute();
    $speciesNode = Node::load(array_pop($nids));
    $species = $speciesNode->getTitle();
    return "Image organiser for $species";
  }

}
