<?php
include_once('ProbabilityDistribution.class.php');
$probabilityDistribution = new ProbabilityDistribution();

$probabilityDistribution->setProbabilityOfsuccess(1, 80);
$probabilityDistribution->setAmountOfDesiredCopies(6);
$probabilityDistribution->setAmountOfPulls(330);
$probabilityDistribution->generateResults();

echo '<pre>';
print_r($probabilityDistribution->getResults());
?>
