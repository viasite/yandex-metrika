<?php
$counterId = intval($_GET['counter_id']);

?>
<form action="" method="get">
  <label for="counter_id">id счётчика</label> <input type="text" name="counter_id" id="counter_id" value="<?=$counterId?>">
  <input type="submit" value="Получить отчёт">
</form>
<?php

require dirname(__FILE__) . '/../vendor/autoload.php';
require(dirname(__FILE__) . '/../config.php');
global $accessToken, $filename;
use App\Report;


if($counterId) {
    $report = new Report($accessToken, $counterId, $filename);

    $start = microtime(true);
    $output = $report->getReport();
    $time = round(microtime(true) - $start, 2);

    echo "time: $time<br>\n";

    echo '<textarea id="output" style="width:1000px;height:300px">' . $output . '</textarea>';
}
