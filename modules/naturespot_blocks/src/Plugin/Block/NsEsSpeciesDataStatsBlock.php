<?php

namespace Drupal\naturespot_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a data stats block for species.
 *
 * @Block(
 *   id = "ns_es_species_data_stats_block",
 *   admin_label = @Translation("NatureSpot Elasticsearch species data stats block"),
 * )
 */
class NsEsSpeciesDataStatsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      return [];
    }
    $nbnKey = $node->field_nbn_number->value;
    iform_load_helpers(['ElasticsearchProxyHelper', 'data_entry_helper']);
    $conn = iform_get_connection_details();
    try {
      $readAuth = \data_entry_helper::get_read_auth($conn['website_id'], $conn['password']);
    }
    catch (\Exception $e) {
      \Drupal::logger('naturespot_blocks')->alert("Fetching read auth failed: " . $e->getMessage());
      return [
        '#markup' => Markup::create('<div class="alert alert-info">Server unavailable.</div>'),
      ];
    }
    $taxonData = \data_entry_helper::get_population_data([
      'table' => 'taxa_taxon_list',
      'extraParams' => $readAuth + [
        'external_key' => $nbnKey,
        'preferred' => 't',
        'taxon_list_id' => 15,
        'view' => 'cache',
        'columns' => 'taxon_group_id,taxon_group,kingdom_taxon,order_taxon,family_taxon',
      ],
      'cachePerUser' => FALSE,
      // Monthly will do.
      'cachetimeout' => 3600 * 24 * 30,
    ]);
    $taxonInfo = $taxonData[0];
    $commonNameData = \data_entry_helper::get_population_data([
      'table' => 'taxa_taxon_list',
      'extraParams' => $readAuth + [
        'external_key' => $nbnKey,
        'preferred' => 'f',
        'taxon_list_id' => 15,
        'allow_data_entry' => 't',
        'language_iso' => 'eng',
        'view' => 'cache',
        'columns' => 'taxon',
      ],
      'cachePerUser' => FALSE,
      // Monthly will do.
      'cachetimeout' => 3600 * 24 * 30,
    ]);
    if (count($taxonData) === 0) {
      \Drupal::logger('naturespot_blocks')->alert("Fetching group info for species key $nbnKey failed");
      return [];
    }
    $commonNames = [];
    foreach ($commonNameData as $name) {
      $commonNames[] = $name['taxon'];
    }
    $commonNameOutput = count($commonNames) === 0
      ? ''
      : '<dt>Common names</dt> <dd>' . implode(', ', $commonNames) . '</dd>';
    $response = $this->getEsDataset1($nbnKey);
    $speciesStats = [
      'total_records' => $response->hits->total->value,
      'by_month' => $response->aggregations->by_month->buckets,
      'first_record' => '',
      'first_recorded_by' => '',
      'last_record' => '',
      'last_recorded_by' => '',
    ];
    $recordRangeInfo = '';
    if (count($response->hits->hits ?? 0) > 0) {
      $speciesStats['last_record'] = $this->formatDate($response->hits->hits[0]->_source->event->date_start);
      $speciesStats['last_recorded_by'] = $response->hits->hits[0]->_source->event->recorded_by;
    }
    $response = $this->getEsDataset2($nbnKey);
    // Add first and last record info only if there are some records.
    if (count($response->hits->hits ?? 0) > 0) {
      $speciesStats['first_record'] = $this->formatDate($response->hits->hits[0]->_source->event->date_start);
      $speciesStats['first_recorded_by'] = $response->hits->hits[0]->_source->event->recorded_by;
      $recordRangeInfo = <<<HTML
              <dt>First record:</dt>
              <dd>$speciesStats[first_record] ($speciesStats[first_recorded_by])</dd>
              <dt>Last record:</dt>
              <dd>$speciesStats[last_record] ($speciesStats[last_recorded_by])</dd>
      HTML;
    }
    $response = $this->getEsDataset3($nbnKey, $taxonInfo['taxon_group_id']);
    $speciesStats['species_records_and_squares_by_year'] = $response->aggregations->this_species->by_year->buckets;
    $speciesStats['group_records_by_year'] = $response->aggregations->group_by_year->buckets;
    \helper_base::$indiciaData['speciesStats'] = $speciesStats;
    $r = <<<HTML
<div id="data-profile">
  <div class="panel-group">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h2>
          <a data-toggle="collapse" href="#collapse-stats">Species data profile</a>
          <a href="/species-data-analysis" target="_blank"><span class="help-tip badge" style="margin-bottom: 3px" title="Click to open a page explaining the Species data profile in a new tab.">?</span></a>
        </h4>
      </div>
      <div id="collapse-stats" class="panel-collapse collapse in">
        <div class="row">
          <div id="stats-text" class="col-md-6">
            <h3>Species profile</h3>
            <dl class="dl-horizontal">
              $commonNameOutput
              <dt>Species group:</dt>
              <dd>$taxonInfo[taxon_group]</dd>
              <dt>Kingdom:</dt>
              <dd>$taxonInfo[kingdom_taxon]</dd>
              <dt>Order:</dt>
              <dd>$taxonInfo[order_taxon]</dd>
              <dt>Family:</dt>
              <dd>$taxonInfo[family_taxon]</dd>
              <dt>Records on NatureSpot:</dt>
              <dd>$speciesStats[total_records]</dd>
              $recordRangeInfo
            </dl>
          </div>
          <div class="col-md-6">
            <h3>Total records by month</h3>
            <div id="records_by_month" ></div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <h3>% of records within its species group</h3>
            <div id="percentage_of_group_by_year"></div>
          </div>
          <div class="col-md-6">
            <h3>10km squares with records</h3>
            <div id="squares_by_year"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
HTML;
    return [
      '#markup' => Markup::create($r),
      '#attached' => [
        'library' => [
          'naturespot_blocks/es-blocks',
          'naturespot_blocks/species-data-stats',
          'iform/brc_charts',
        ],
      ],
      '#cache' => [
        // No cache please.
        'max-age' => 0,
      ],
    ];
  }

  private function getEsDataset1($nbnKey) {
    $request = <<<JSON
{
  "size": "1",
  "query": {
    "bool": {
      "must": [
        { "term": {
          "taxon.accepted_taxon_id": "$nbnKey"
        } },
        { "term": {
          "metadata.website.id": 8
        } },
        { "query_string": {
          "query": "((metadata.sensitivity_blur:B) OR (!metadata.sensitivity_blur:*))"
        } },
        { "term": {
          "metadata.release_status": "R"
        } },
        { "term": {
          "metadata.trial": false
        } },
        {"term": {
          "metadata.confidential": false
        } },
        { "term": {
          "identification.verification_status": "V"
        } }
      ]
    }
  },
  "aggs": {
    "by_month": {
      "terms": {
        "field": "event.month",
        "size": 12
      }
    }
  },
  "sort": [
    {
      "event.date_start": {
        "order": "desc"
      }
    }
  ]
}
JSON;
    return $this->getEsResponse($request, "$nbnKey-1", ['_source' => 'event.date_start,event.recorded_by']);
  }

  private function getEsDataset2($nbnKey) {
    $request = <<<JSON
{
  "size": "1",
  "query": {
    "bool": {
      "must": [
        { "term": {
          "taxon.accepted_taxon_id": "$nbnKey"
        } },
        { "term": {
          "metadata.website.id": 8
        } },
        { "query_string": {
          "query": "((metadata.sensitivity_blur:B) OR (!metadata.sensitivity_blur:*))"
        } },
        { "term": {
          "metadata.release_status": "R"
        } },
        { "term": {
          "metadata.trial": false
        } },
        {"term": {
          "metadata.confidential": false
        } },
        { "term": {
          "identification.verification_status": "V"
        } }
      ]
    }
  },
  "sort": [
    {
      "event.date_start": {
        "order": "asc"
      }
    }
  ]
}
JSON;
    return $this->getEsResponse($request, "$nbnKey-2", ['_source' => 'event.date_start,event.recorded_by']);
  }

  private function getEsDataset3($nbnKey, $groupId) {
    $firstYear = date("Y") - 9;
    $request = <<<JSON
{
  "size": "0",
  "query": {
    "bool": {
      "must": [
        { "term": {
          "taxon.input_group_id": $groupId
        } },
        { "term": {
          "metadata.website.id": 8
        } },
        { "query_string": {
          "query": "((metadata.sensitivity_blur:B) OR (!metadata.sensitivity_blur:*))"
        } },
        { "term": {
          "metadata.release_status": "R"
        } },
        { "term": {
          "metadata.trial": false
        } },
        {"term": {
          "metadata.confidential": false
        } },
        { "range": {
          "event.year": { "gte": $firstYear }
        } },
        { "term": {
          "identification.verification_status": "V"
         } }
      ]
    }
  },
  "aggs": {
    "this_species": {
      "filter": {
        "term": {
          "taxon.accepted_taxon_id": "$nbnKey"
        }
      },
      "aggs": {
        "by_year": {
          "terms": { "field": "event.year" },
          "aggs": {
            "sq_count": {
              "cardinality": {
                "field": "location.grid_square.10km.centre"
              }
            }
          }
        }
      }
    },
    "group_by_year": {
      "terms": { "field": "event.year" }
    }
  }
}
JSON;
    return $this->getEsResponse($request, "$nbnKey-3");
  }

  private function getEsResponse($request, $requestId, array $getParams = []) {
    $cacheKey = ['NsEsSpeciesDataStatsBlock' => $requestId];
    // Fetch content from cache. Reduce expiry for logged in users so they get
    // reasonably up to date data.
    $response = \helper_base::cacheGet($cacheKey, 3600 * 22);
    if ($response) {
      return json_decode($response);
    }
    $config = hostsite_get_es_config(NULL);
    $warehouseUrl = $config['indicia']['base_url'];
    $esEndpoint = $config['es']['endpoint'];
    $url = "{$warehouseUrl}index.php/services/rest/$esEndpoint/_search";
    if (!empty($getParams)) {
      $url .= '?' . http_build_query($getParams);
    }
    $session = curl_init();
    // Set the POST options.
    curl_setopt($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($session, CURLOPT_HTTPHEADER, \ElasticsearchProxyHelper::getHttpRequestHeaders($config));
    curl_setopt($session, CURLOPT_POST, 1);
    curl_setopt($session, CURLOPT_POSTFIELDS, $request);
    // Do the request.
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    $curlErrno = curl_errno($session);
    // Check for an error, or check if the http response was not OK.
    if ($curlErrno || $httpCode != 200) {
      $errorInfo = json_decode($response);
      if ($errorInfo && $errorInfo->status) {
        // If a handled server error, we can set a proper response error.
        \Drupal::logger('naturespot_blocks')->error("Error in NsEsSpeciesDataStatsBlock ES request: ($httpCode, $errorInfo->status, $errorInfo->message)");
        \Drupal::messenger()->addError("Error in NsEsSpeciesDataStatsBlock ES request: ($httpCode, $errorInfo->status, $errorInfo->message)");
      }
      else {
        // If we can't do it properly, still best not to swallow it.
        \Drupal::logger('naturespot_blocks')->error("Error in NsEsSpeciesDataStatsBlock ES request: Internal server error. " . var_export($response, TRUE));
        \Drupal::messenger()->addError("Error in NsEsSpeciesDataStatsBlock ES request: Internal server error. " . var_export($response, TRUE));
      }
      return [];
    }
    \helper_base::cacheSet($cacheKey, $response, 3600 * 24);
    return json_decode($response);
  }

  /**
   * Takes an ISO date string YYYY-MM-DD and formats to DD/MM/YYYY.
   *
   * @return string
   *   Reformatted date.
   */
  private function formatDate($dateStr) {
    [$year, $month, $day] = explode('-', $dateStr);
    return "$day/$month/$year";
  }

}
