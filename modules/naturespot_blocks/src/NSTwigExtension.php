<?php

namespace Drupal\naturespot_blocks;

use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Site\Settings;
use Drupal\image\Entity\ImageStyle;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Twig extension to allow plugin blocks in twig templates.
 */
class NSTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('drupal_plugin_block', [$this, 'drupalPluginBlock'])
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
    return render($render);
  }
}
