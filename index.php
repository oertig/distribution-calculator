<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once('ProbabilityDistribution.class.php');
$probabilityDistribution = new DistributionCalculator\ProbabilityDistribution();

include_once('TableRenderer.class.php'); // TODO: should this class be static?
$tableRenderer = new DistributionCalculator\TableRenderer();

$baseProbability = 1;
$rateUpWeight = 80;
$amountForMulti = 11;
$pullCounts = range(1,6);
$pullAmounts = range(
    $amountForMulti,
    $amountForMulti * 30,
    $amountForMulti);

$probabilityDistribution->setProbabilityOfsuccess($baseProbability, $rateUpWeight);
$probabilityDistribution->setDesiredCopiesAmounts($pullCounts);
$probabilityDistribution->setPullAmounts($pullAmounts);

$resultsExact = $probabilityDistribution->getExactBinomialDistribution();
$resultsAtleast = $probabilityDistribution->getAtLeastBinomialDistribution();

$tableExact = $tableRenderer->renderTable($resultsExact, $pullCounts, $amountForMulti, 'Exact Copies', true);
$tableAtleast = $tableRenderer->renderTable($resultsAtleast, $pullCounts, $amountForMulti, 'AtLeast Copies', true);

$chartExact = $tableRenderer->renderChart($resultsExact, 'Exact Copies');
$chartAtleast = $tableRenderer->renderChart($resultsAtleast, 'AtLeast Copies');

echo $tableRenderer->renderHtmlSkeleton($tableExact.$tableAtleast.$chartExact.$chartAtleast);
?>