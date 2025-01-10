<?php
class ProbabilityDistribution {
    private $defaultPrecision = 20;
    private $precision = 0;

    private $probabilityOfsuccess = 0;
    private $amountOfDesiredCopies = 1;
    private $amountOfPulls = 1;
    private $distributionResults = array();

    private $binomialCache = array();
    private $exactDistributionCache = array();
    private $atLeastDistributionCache = array();

    public function __construct() {
        if(!extension_loaded('bcmath')) {
            throw new Exception('BCMath extension is not loaded');
        }

        $this->precision = $this->defaultPrecision;
        bcscale($this->precision);
    }

    public function setPrecision($precision) {
        $this->precision = $precision;
        bcscale($this->precision);
    }

    public function setProbabilityOfsuccess($genericResultChance = 1, $specificresultChance = 100) {
        $probabilityOfSuccess = bcdiv(bcmul($genericResultChance, $specificresultChance), "10000");
        $this->probabilityOfsuccess = $probabilityOfSuccess;
    }

    public function setAmountOfDesiredCopies($amountOfDesiredCopies) {
        $this->amountOfDesiredCopies = $amountOfDesiredCopies;
    }

    public function setAmountOfPulls($amountOfPulls) {
        $this->amountOfPulls = $amountOfPulls;
    }

    public function getResults() {
        return $this->distributionResults;
    }

    private function getBinomialCoefficient($n, $k) {        
        if ($k > $n) return "0";
        if ($k == 0 || $k == $n) return "1";
        
        $key = "$n:$k";
        if (isset($this->binomialCache[$key])) {
            return $this->binomialCache[$key];
        }
        
        $result = "1";
        
        for ($i = 1; $i <= $k; $i++) {
            $result = bcdiv(bcmul($result, ($n - $i + 1)), $i, $this->precision);
        }
        
        $this->binomialCache[$key] = $result;
        return $result;
    }

    public function getExactBinomialDistribution($amountDesiredCopies, $amountOfPulls, $probabilityOfSuccess) {        
        $key = "$amountDesiredCopies:$amountOfPulls:$probabilityOfSuccess";

        if (isset($exactDistributionCache[$key])) {
            return $exactDistributionCache[$key];
        }
        
        $logProb = 0;
        $logProb += log(floatval($this->getBinomialCoefficient($amountOfPulls, $amountDesiredCopies)));
        $logProb += $amountDesiredCopies * log(floatval($probabilityOfSuccess));
        $logProb += ($amountOfPulls - $amountDesiredCopies) * log(1 - floatval($probabilityOfSuccess));
        
        if (is_infinite($logProb) || is_nan($logProb)) {
            $result = ($logProb == -INF) ? "0" : "INF";
        } else {
            $result = number_format(exp($logProb), $this->precision, '.', '');
            
            // If the result is very small, use bcpow to represent it
            if (floatval($result) == 0) {
                $exponent = strval($logProb / log(10));

                if (is_numeric($exponent) && intval($exponent) == $exponent) {
                    $result = bcpow("10", $exponent, $this->precision);
                } else {
                    $result = "0";
                }
            }
        }
        
        $exactDistributionCache[$key] = $result;
        return $result;
    }

    function getAtLeastBinomialDistribution($amountDesiredCopies, $amountOfPulls, $probabilityOfSuccess) {        
        $key = "$amountDesiredCopies:$amountOfPulls:$probabilityOfSuccess";

        if (isset($atLeastDistributionCache[$key])) {
            return $atLeastDistributionCache[$key];
        }
        
        $probability = "0";

        for ($i = $amountDesiredCopies; $i <= $amountOfPulls; $i++) {
            $exactProb = $this->getExactBinomialDistribution($i, $amountOfPulls, $probabilityOfSuccess);
            $probability = bcadd($probability, $exactProb, $this->precision);
        }
        
        $atLeastDistributionCache[$key] = $probability;
        return $probability;
    }

    function formatPercentage($probability) {
        $percentage = bcmul($probability, "100", 8); // Increase precision to 8 decimal places

        if (bccomp($percentage, "0.0001", 8) == -1) { // If less than 0.0001%
            return "0.0000%"; // Cut-off point for relevancy
        }

        return number_format(floatval($percentage), 4) . '%';
    }

    public function generateResults() {
        $amountOfPulls = range(1, $this->amountOfPulls);
        $amountOfDesiredCopies = range(1, $this->amountOfDesiredCopies);

        foreach($amountOfPulls as $pulls) {
            foreach($amountOfDesiredCopies as $desiredCopies) {
                $exactResult = $this->getExactBinomialDistribution(
                    $desiredCopies,
                    $pulls,
                    $this->probabilityOfsuccess
                );
                $atLeastResult = $this->getAtLeastBinomialDistribution(
                    $desiredCopies,
                    $pulls,
                    $this->probabilityOfsuccess
                );
                $this->distributionResults[$pulls][$desiredCopies] = [
                    'exact' => $this->formatPercentage($exactResult),
                    'atLeast' => $this->formatPercentage($atLeastResult)
                ];
            }
        }
    }
}
?>