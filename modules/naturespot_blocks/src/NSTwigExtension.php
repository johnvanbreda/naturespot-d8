<?php

namespace Drupal\naturespot_blocks;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension to allow plugin blocks in twig templates.
 */
class NSTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('drupal_plugin_block', [
        $this,
        'drupalPluginBlock',
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'nstwig_tweak';
  }

  public function drupalPluginBlock($id) {
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [];
    $plugin_block = $block_manager->createInstance($id, $config);
    $render = $plugin_block->build();
    $render_service = \Drupal::service('renderer');
    return $render_service->renderInIsolation($render);
  }

}
