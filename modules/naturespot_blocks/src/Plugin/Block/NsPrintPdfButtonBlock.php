<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a button for printing the page to a PDF.
 *
 * @Block(
 *   id = "ns_print_pdf_button_block",
 *   admin_label = @Translation("NatureSpot print PDF button block"),
 * )
 */
class NsPrintPdfButtonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      return [];
    }
    $nid = \Drupal::routeMatch()->getParameter('node')->id();
    // Prevent errors if block loaded off node page.
    if (!$nid) {
      return [];
    }
    iform_load_helpers(['helper_base']);
    $path = iform_client_helpers_path();
    require_once $path . 'prebuilt_forms/extensions/print.php';

    $r = \extension_print::pdf([], [], NULL, [
      'format' => 'portrait',
      'includeSelector' => 'div.content',
      'fileName' => hostsite_get_page_title($nid),
      'pagebreak' => [
        'mode' => ['css'],
        'avoid' => '.views-row,.block-field-blocknodewildlife-guidefield-footer,.block-field-blocknodewildlife-guidefield-footer-image',
      ]
    ], NULL);
    return [
      '#markup' => Markup::create($r),
      '#cache' => [
        'max-age' => 0,
      ],
      '#attached' => [
        'library' => [
          'naturespot_blocks/printPdf',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Prevent caching.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
