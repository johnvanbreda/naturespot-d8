<?php

namespace Drupal\naturespot_blocks\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Link;
use Drupal\Core\Url;

class TaxonomyBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * @inheritdoc
   */
  public function applies(RouteMatchInterface $route_match) {
    // This breadcrumb apply only for all articles
    $parameters = $route_match->getParameters()->all();
    if (isset($parameters['node'])) {
      return $parameters['node']->getType() == 'species';
    }
  }

  /**
   * @inheritdoc
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $links = [Link::createFromRoute($this->t('Home'), '<front>')];
    $links[] = Link::createFromRoute($this->t('Species library'), 'entity.node.canonical', ['node' => 17]);
    $parameters = $route_match->getParameters()->all();
    if (isset($parameters['node'])) {
      $tids = $parameters['node']->field_species_library_menu->getValue();
      $storage = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term');
      foreach ($tids as $tid) {
        $term = \Drupal\taxonomy\Entity\Term::load($tid['target_id']);
        if (stripos($term->getName(), 'caterpillar') === FALSE && stripos($term->getName(), 'gall') === FALSE) {
          $parents = $storage->loadParents($tid['target_id']);
          foreach ($parents as $parentTid => $parent) {
            $links[] = Link::createFromRoute($parent->getName(), 'entity.taxonomy_term.canonical',
              ['taxonomy_term' => $parentTid]);
          }
          $links[] = Link::createFromRoute($term->getName(), 'entity.taxonomy_term.canonical',
            ['taxonomy_term' => $tid['target_id']]);
          break; // from foreach
        }
      }
    }
    $breadcrumb->setLinks($links);
    return $breadcrumb;
  }
}