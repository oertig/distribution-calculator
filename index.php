<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


include_once('ProbabilityDistribution.class.php');
$probabilityDistribution = new ProbabilityDistribution();

$probabilityDistribution->setProbabilityOfsuccess(1, 80);
$probabilityDistribution->setDesiredCopiesAmounts(range(1,6));
$probabilityDistribution->setPullAmounts(range(11, 330, 11));

echo '<pre>';
print_r($probabilityDistribution->getExactBinomialDistribution());
print_r($probabilityDistribution->getAtLeastBinomialDistribution());
?>