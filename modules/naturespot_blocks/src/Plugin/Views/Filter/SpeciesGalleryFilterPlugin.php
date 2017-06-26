<?php

namespace Drupal\naturespot_blocks\Plugin\views\filter;

use Drupal\views\Views;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Filters the output of the species gallery view.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("ns_species_gallery_filter")
 */


class SpeciesGalleryFilterPlugin extends FilterPluginBase {
  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Species Gallery filter');
  }

  public function query() {
    $configuration = [
      'table' => 'node__field_caterpillar',
      'field' => 'entity_id',
      'left_table' => 'taxa_taxonomy_term_field_data',
      'left_field' => 'nid',
      'operator' => '='
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('node__field_caterpillar', $join, 'taxa_taxonomy_term_field_data');

    $configuration = [
      'table' => 'node__field_gall',
      'field' => 'entity_id',
      'left_table' => 'taxa_taxonomy_term_field_data',
      'left_field' => 'nid',
      'operator' => '='
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('node__field_gall', $join, 'taxa_taxonomy_term_field_data');

    $expression = <<<SQL
(
  taxonomy_term_field_data_node__menu__taxonomy_term_hierarchy.parent not in (19327, 19701)
  or (
    node__field_caterpillar.field_caterpillar_value = '1' and 
    taxonomy_term_field_data_node__menu__taxonomy_term_hierarchy.parent = 19327
  )
  or (
    node__field_gall.field_gall_value = '1' and 
    taxonomy_term_field_data_node__menu__taxonomy_term_hierarchy.parent = 19701
  )
) 
SQL;

    $this->query->addWhereExpression(0, $expression);
  }
}