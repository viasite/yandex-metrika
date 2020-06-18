<?php
namespace App;

use Yandex\Metrica\Stat\MetricConst;
use Yandex\Metrica\Stat\StatClient;
use Yandex\Metrica\Stat\AvailableValues;

class Report {
  public $counterId;
  public $filename;
  public $_statClient;

  public function __construct($accessToken, $counterId, $filename) {
    $this->counterId = $counterId;
    $this->filename = $filename;

    $this->_statClient = new StatClient($accessToken);
  }

  private function getStatPresetByDay($preset, $from, $to) {
    $paramsModel = new \Yandex\Metrica\Stat\Models\ByTimeParams();
    $paramsModel
        ->setId($this->counterId)
        ->setMetrics(MetricConst::S_VISITS)
        ->setPreset($preset)
        ->setDate1($from)
        ->setDate2($to)
        ->setGroup('day');
    return $this->_statClient->data()->getByTime($paramsModel);
  }

  private function dataToTotals($data) {
    $collection = $data->getData()->getAll();
    $result = [];
    /** @param $items \Yandex\Metrica\Stat\Models\Items */

    foreach($collection as $ind => $items) {
      $dims = $items->getDimensions()->getAll();
      foreach ($dims as $dimInd => $dim) {
        // $id = $dim->getId();
        $name = $dim->getName();
        $total = array_sum($items->getMetrics()[0]);
        $result[$name] = $total;
      }
    }
    return $result;
  }

  private function comparePreset($preset, $period = 30, $ago = 365) {
    $from1 = (1 + $period) . 'daysAgo';
    $to1 = '1daysAgo';

    $from2 = ($ago + $period) . 'daysAgo';
    $to2 = $ago . 'daysAgo';

    $data = $this->getStatPresetByDay($preset, $from1, $to1); //
    $result = $this->dataToTotals($data);
    $data2 = $this->getStatPresetByDay($preset, $from2, $to2);
    $result2 = $this->dataToTotals($data2);

    $rows = [[$preset, "last $period days", "$ago days ago"]];
    foreach ($result as $id => $total) {
      $rows[] = [$id, $total, @$result2[$id] ? $result2[$id] : 0];
    }
    return $rows;
  }

  public function getReport() {
    $reportsAll = [];

    $reportsAll[] = $this->comparePreset(AvailableValues::PRESET_SOURCES_SUMMARY); // источники
    $reportsAll[] = $this->comparePreset(AvailableValues::PRESET_SEARCH_ENGINES); // поисковики
    $reportsAll[] = $this->comparePreset('tech_devices'); // устройства, TODO: PR with tech_devices
    $reportsAll[] = $this->comparePreset('deepness_depth'); // глубина
    $reportsAll[] = $this->comparePreset('deepness_time'); // время на сайте
    $reportsAll[] = $this->comparePreset('age'); // возраст
    $reportsAll[] = $this->comparePreset('gender'); // пол

    $lines = [];
    foreach ($reportsAll as $report) {
      // склеиваем все отчёты в одну строку, у отчётов одинаковое кол-во колонок (3), но разное кол-во рядов
      foreach ($report as $i => $cols) {
        if (!isset($lines[$i])) {
          $lines[$i] = [];
        }
        $lines[$i] = array_merge($lines[$i], $cols);
      }

      // добиваем до 15 пустотой, чтобы колонки не разъехались
      for ($i = count($report); $i < 15; $i++) {
        if (!isset($lines[$i])) {
          $lines[$i] = [];
        }
        $cols = array_fill(0, count($report[0]), ''); // пустые колонки
        $lines[$i] = array_merge($lines[$i], $cols);
      }
    }

    $output = '';
    $file = fopen($this->filename, 'w');
    foreach ($lines as $line) {
      $line = implode("\t", $line) . "\n";
      $output .= $line;
      fwrite($file, $line);
    }
    fclose($file);

    return $output;
  }
}
