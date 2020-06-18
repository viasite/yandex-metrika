<title>Яндекс.Метрика: отчёты</title>
<?php
require dirname(__FILE__) . '/../vendor/autoload.php';
require(dirname(__FILE__) . '/../config.php');
global $accessToken, $filename;

$counterId = intval($_GET['counter_id']);

$utils = new \App\Utils($accessToken);
$select = $utils->getCountersSelect($counterId);

?>
<form action="" method="get">
  <!--<label for="counter_id">id счётчика</label> <input type="text" name="counter_id" id="counter_id" value="<?=$counterId?>">-->
  <?=$select?>
  <input type="submit" value="Получить отчёт">
</form>
<?php



if($counterId) {
    $report = new App\Report($accessToken, $counterId, $filename);

    $start = microtime(true);
    $outputMain = $report->getReport();
    $outputGoals = $report->getReportGoals();
    $output = $outputMain . "\n" . $outputGoals;
    $time = round(microtime(true) - $start, 2);

    echo "time: $time<br>\n";

    echo '<textarea id="output" style="width:100%;height:400px">' . $output . '</textarea>';
}
