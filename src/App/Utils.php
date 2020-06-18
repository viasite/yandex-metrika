<?php
namespace App;
use Yandex\Metrica\Management\ManagementClient;

class Utils {
  public $accessToken;

  public function __construct($accessToken) {
    $this->accessToken = $accessToken;
  }

  public function getCountersSelect($current = 0) {
    $countersData = $this->getCounters();

    $counters = [];
    /** @var \Yandex\Metrica\Management\Models\CounterItem $counter */
    foreach ($countersData->getAll() as $counter) {
      $site = str_replace('www.', '', $counter->getSite());
      $counters[$counter->getId()] = $site;
    }
    asort($counters);

    $opts = [];
    $opts[] = '<option value="0" ' . (!$current ? 'selected' : '') . '></option>';

    foreach ($counters as $counterId => $site) {
      $opts[] = '<option value="'.$counterId.'" ' . ($counterId == $current? 'selected' : '') . '>'
        . $site . '</option>';
    }

    return '<select id="counter_id" name="counter_id">'.implode("\n", $opts).'</select>';
  }

  public function getCounters() {
    $managementClient = new ManagementClient($this->accessToken);

    $params = new \Yandex\Metrica\Management\Models\CountersParams();
    $params
      ->setType(\Yandex\Metrica\Management\AvailableValues::TYPE_SIMPLE)
      ->setField('goals,mirrors,grants,filters,operations');

    return $managementClient
      ->counters()
      ->getCounters($params)
      ->getCounters();
  }
}