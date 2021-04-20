<head>
    <title>Яндекс.Метрика: отчёты</title>
    <link href="/favicon.png" rel="icon" type="image/x-icon" sizes="16x16">
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script type="text/javascript" src="date.js"></script>
    <script src="script.js"></script>
    <script>
    </script>
</head>
<?php
require dirname(__FILE__) . '/../vendor/autoload.php';
require(dirname(__FILE__) . '/../config.php');
global $accessToken, $filename;

$counterId = intval($_REQUEST['counter_id']);

$utils = new \App\Utils($accessToken);
$select = $utils->getCountersSelect($counterId);

?>
<form action="" method="post">
  <!--<label for="counter_id">id счётчика</label> <input type="text" name="counter_id" id="counter_id" value="<?=$counterId?>">-->
  <?=$select?>
  <input type="submit" value="Получить отчёт">
</form>
<div id="bookmarks"></div>
<div id="current-site">
    <div id="current-site-brief"></div>
    <ul id="metrika-reports"></ul>
</div>
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
