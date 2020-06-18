<?php
namespace App;

use Yandex\Metrica\Stat\DimensionsConst;
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

    /** @var $items \Yandex\Metrica\Stat\Models\Items */
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

  /**
   * @param $dimension - по чему группировать цели - \Yandex\Metrica\Stat\DimensionsConst
   * @param int $period
   *
   * @return string строка для таблицы, разделена табами, выравнивается до 10 строк
   */
  private function getGoalsByDimension($dimension, $period = 30) {
    $from1 = (1 + $period) . 'daysAgo';
    $to1 = '1daysAgo';
    $dimensions = $dimension . ',' . DimensionsConst::S_GOAL_DIMENSION;

    $resultByDimension = [];

    $paramsModel = new \Yandex\Metrica\Stat\Models\TableParams();
    $paramsModel
        ->setId($this->counterId)
        ->setMetrics(MetricConst::S_VISITS)
        ->setDimensions($dimensions)
        ->setDate1($from1)
        ->setDate2($to1);
    $data = $this->_statClient->data();
    $dataLevel0 = $data->getDrillDown($paramsModel);

    $goalsNames = [];
    $goalsNames[] = 'Визиты';

    /** @var $items \Yandex\Metrica\Stat\Models\DrillDownItems */
    foreach ($dataLevel0->getData()->getAll() as $items) {
      $dim = $items->getDimension();
      $dimName = $dim->getName();
      $resultByDimension[$dimName] = ['Визиты' => $items->getMetrics()[0]];

      if($items->getExpand()){
        $dataLevel1 = $data->getDrillDown($paramsModel, [$dim->getId()]);
        /** @var $itemsL1 \Yandex\Metrica\Stat\Models\DrillDownItems */
        foreach ($dataLevel1->getData()->getAll() as $itemsL1) {
          $dimL1 = $itemsL1->getDimension();
          $goalName = $dimL1->getName();
          if(!in_array($goalName, $goalsNames)) $goalsNames[] = $goalName;
          $resultByDimension[$dimName][$goalName] = $itemsL1->getMetrics()[0];
        }
      }
    }

    // дополнение нулями для симметрии (на всякий случай копируем в другой массив)
    $result = [];
    foreach ($resultByDimension as $dimName => $goals) {
      // $result[$dimName]['Визиты'] = $goals['Визиты'];
      foreach ($goalsNames as $goalsName) {
        if(!isset($goals[$goalsName])) $result[$dimName][$goalsName] = 0;
        else $result[$dimName][$goalsName] = $goals[$goalsName];
      }
    }

    $lines = [];
    $lines[] = $dimension . "\t" . implode("\t", $goalsNames);
    foreach ($result as $dimName => $goals) {
      $lines[] = $dimName . "\t" . implode("\t", $goals);
    }
    return implode("\n", $lines) . implode('', array_fill(0, 10 - count($lines) + 1, "\n"));
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

  public function getReportGoals() {
    $bySource = $this->getGoalsByDimension(DimensionsConst::S_TRAFFIC_SOURCE);
    $byDevice = $this->getGoalsByDimension('ym:s:deviceCategory');
    return $bySource . "\n" . $byDevice;
  }
}
