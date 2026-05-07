<?php

namespace Drupal\naturespot_blocks\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Link;
use Drupal\taxonomy\Entity\Term;

/**
 * Service to replace Drupal's breadcrumb builder for species account pages.
 */
class TaxonomyBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * Filter when this breadcrumb gets applied.
   *
   * @inheritdoc
   */
  public function applies(RouteMatchInterface $route_match) {
    // This breadcrumb apply only for all species accounts.
    $node = $route_match->getParameter('node');
    if ($node) {
      return $node->getType() == 'species';
    }
  }

  /**
   * Builds the breadcrumb trail of links.
   *
   * @inheritdoc
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $links = [Link::createFromRoute($this->t('Home'), '<front>')];
    $links[] = Link::createFromRoute($this->t('Species galleries'), 'entity.node.canonical', ['node' => 265084]);
    $node = $route_match->getParameter('node');
    if ($node) {
      $tids = $node->field_species_library_menu->getValue();
      $storage = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term');
      foreach ($tids as $tid) {
        $term = Term::load($tid['target_id']);
        if ($term) {
          $specialMenuItems = [
            'Caterpillars',
            'Galls',
            'Leaf-mines',
            'Nymphs & Larvae',
            'Oddities',
            'Tracks & Signs',
          ];
          if (!in_array($term->getName(), $specialMenuItems)) {
            $parents = $storage->loadParents($tid['target_id']);
            foreach ($parents as $parentTid => $parent) {
              $links[] = Link::createFromRoute($parent->getName(), 'entity.taxonomy_term.canonical',
                ['taxonomy_term' => $parentTid]);
            }
            $links[] = Link::createFromRoute($term->getName(), 'entity.taxonomy_term.canonical',
              ['taxonomy_term' => $tid['target_id']]);
            // Break from foreach.
            break;
          }
        }
      }
    }
    $breadcrumb->setLinks($links);
    return $breadcrumb;
  }

}
