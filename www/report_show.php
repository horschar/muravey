<?php
    require_once __DIR__ . '/layouts/header.php';

    $report = new \OsT\Reports\Report($_SESSION['reports']);
    if (count($report->reports)) {
        $report->generate();
        $report->getPDF();
        //echo $report->getHtml();
    }
