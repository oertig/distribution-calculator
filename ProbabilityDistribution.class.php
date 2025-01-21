<?php
namespace DistributionCalculator;

class ProbabilityDistribution {
    private const MIN_INTERNAL_MATH_PRECISION = 0;
    private const MAX_INTERNAL_MATH_PRECISION = 100;
    private const DEFAULT_INTERNAL_MATH_PRECISION = 20;
    private $internalMathPrecision = 0;

    private const MIN_PROBABILITY = 0;
    private const MAX_PROBABILITY = 100;
    private const PROBABILITY_DENOMINATOR = 10000;
 
    private $probabilityOfsuccess = 0;
    private $desiredCopiesAmounts = [1];
    private $pullAmounts = [1];
    private $distributionResults = array();

    private const MIN_AMOUNT_OF_DESIRED_COPIES = 1;
    private const MIN_PULL_AMOUNT = 1;

    private const PERCENTAGE_CONVERSION_FACTOR = '100';
    private const DEFAULT_CUT_OFF_DISPLAY_PRECISION = '0.0001';
    private const DEFAULT_DECIMAL_PLACES_TO_DISPLAY = 4;
    private const MIN_CUT_OFF_DISPLAY_PRECISION = 0;
    private const MAX_CUT_OFF_DISPLAY_PRECISION = 10;

    private $cutOffPrecision = self::DEFAULT_DECIMAL_PLACES_TO_DISPLAY;
    private $displayDecimalPlaces = self::DEFAULT_CUT_OFF_DISPLAY_PRECISION;

    private $binomialCache = array();
    private $exactDistributionCache = array();
    private $atLeastDistributionCache = array();

    // TODO: create functions to clear cache

    public function __construct() {
        if(!extension_loaded('bcmath')) {
            throw new Exception('BCMath extension is not loaded');
        }

        $this->internalMathPrecision = self::DEFAULT_INTERNAL_MATH_PRECISION;
        bcscale($this->internalMathPrecision);
    }

    public function setInternalMathPrecision(int $internalMathPrecision): void {
        if(
            $internalMathPrecision < self::MIN_INTERNAL_MATH_PRECISION || 
            $internalMathPrecision > self::MAX_PRECISION
        ) {
            throw new OutOfRangeException(
                sprintf(
                    'Internal math precision must be an integer between %d and %d, received %d',
                    self::MIN_INTERNAL_MATH_PRECISION,
                    self::MAX_INTERNAL_MATH_PRECISION,
                    $internalMathPrecision
                )
            );
        }

        $this->internalMathPrecision = $internalMathPrecision;
        bcscale($this->internalMathPrecision);
    }

    public function setCutOffDisplayPrecision(int $cutOffDisplayPrecision): void {
        if(
            $cutOffDisplayPrecision < self::MIN_CUT_OFF_DISPLAY_PRECISION ||
            $cutOffDisplayPrecision > self::MAX_CUT_OFF_DISPLAY_PRECISION
        ) {
            throw new OutOfRangeException(
                sprintf(
                    'Cut off display precision must be between %d and %d, received %d',
                    self::MIN_CUT_OFF_DISPLAY_PRECISION,
                    self::MAX_CUT_OFF_DISPLAY_PRECISION,
                    $cutOffDisplayPrecision
                )
            );
        }

        $this->cutOffPrecision = $cutOffDisplayPrecision;
        $this->displayDecimalPlaces = sprintf('0.%0' . max(($value - 1), 0) .'d1', 0);
    }

    public function setProbabilityOfsuccess(
        float $genericResultChance = 1,
        float $specificresultChance = 100
    ): void {
        if(
            $genericResultChance < self::MIN_PROBABILITY ||
            $genericResultChance > self::MAX_PROBABILITY ||
            $specificresultChance < self::MIN_PROBABILITY ||
            $specificresultChance > self::MAX_PROBABILITY
        ) {
            throw new OutOfRangeException(
                sprintf(
                    'Inputs must be between %d and %d, received %d and %d',
                    self::MIN_PROBABILITY,
                    self::MAX_PROBABILITY,
                    $genericResultChance,
                    $specificresultChance
                )
            );
        }

        $probabilityOfSuccess = bcdiv(bcmul($genericResultChance, $specificresultChance), self::PROBABILITY_DENOMINATOR, $this->internalMathPrecision);
        $this->probabilityOfsuccess = $probabilityOfSuccess;
    }

    public function setDesiredCopiesAmounts(array $desiredCopiesAmounts): void {
        if (empty($desiredCopiesAmounts)) {
            throw new InvalidArgumentException(
                'Desired copies amounts must be a non-empty array of positive integers'
            );
        }
    
        foreach ($desiredCopiesAmounts as $desiredCopies) {
            if (!is_int($desiredCopies) || $desiredCopies < self::MIN_AMOUNT_OF_DESIRED_COPIES) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Desired copies amounts must be an array of positive integers, received %s',
                        gettype($desiredCopies)
                    )
                );
            }
        }
    
        $this->desiredCopiesAmounts = $desiredCopiesAmounts;
    }

    public function setPullAmounts(array $pullAmounts): void {
        if(empty($pullAmounts)) {
            throw new OutOfRangeException(
                sprintf(
                    'Pull amounts must be an array of positive integers, empty array received',
                )
            );
        }

        foreach($pullAmounts as $pullAmount) {
            if(!is_int($pullAmount) || $pullAmount < self::MIN_PULL_AMOUNT) {
                throw new OutOfRangeException(
                    sprintf(
                        'Amount of pulls must be an array of positive integers, received %s',
                        gettype($pullAmount)
                    )
                );
            }
        }

        $this->pullAmounts = $pullAmounts;
    }

    public function getResults(): array {
        return $this->distributionResults;
    }

    private function getBinomialCoefficient(int $n, int $k): string {        
        if ($k > $n) return "0";
        if ($k == 0 || $k == $n) return "1";

        $key = "$n:$k";

        if (isset($this->binomialCache[$key])) {
            return $this->binomialCache[$key];
        }

        $result = "1";

        for ($i = 1; $i <= $k; $i++) {
            $result = bcdiv(bcmul($result, ($n - $i + 1)), $i, $this->internalMathPrecision);
        }

        $this->binomialCache[$key] = $result;
        return $result;
    }

    public function calculateExactBinomialDistribution(
        int $desiredCopiesAmount,
        int $amountOfPulls,
        string $probabilityOfSuccess
    ): string {
        if(!is_numeric($probabilityOfSuccess)) {
            throw new InvalidArgumentException(
                sprintf('Probability of success must be a numeric value, received %s')
            );
        }

        $key = "$desiredCopiesAmount:$amountOfPulls:$probabilityOfSuccess";

        if (isset($exactDistributionCache[$key])) {
            return $exactDistributionCache[$key];
        }

        $logProb = 0;
        $logProb += log(floatval($this->getBinomialCoefficient($amountOfPulls, $desiredCopiesAmount)));
        $logProb += $desiredCopiesAmount * log(floatval($probabilityOfSuccess));
        $logProb += ($amountOfPulls - $desiredCopiesAmount) * log(1 - floatval($probabilityOfSuccess));

        if (is_infinite($logProb) || is_nan($logProb)) {
            $result = ($logProb == -INF) ? "0" : "INF";
        } else {
            $result = number_format(exp($logProb), $this->internalMathPrecision, '.', '');

            // If the result is very small, use bcpow to represent it
            if (floatval($result) == 0) {
                $exponent = strval($logProb / log(10));

                if (is_numeric($exponent) && intval($exponent) == $exponent) {
                    $result = bcpow("10", $exponent, $this->internalMathPrecision);
                } else {
                    $result = "0";
                }
            }
        }

        $exactDistributionCache[$key] = $result;
        return $result;
    }

    // uses calculateExactBinomialDistribution, therefore it is better to use this functin second (to use the cache)
    function calculateAtLeastBinomialDistribution(int $desiredCopiesAmount, int $amountOfPulls, string $probabilityOfSuccess): string {
        if(!is_numeric($probabilityOfSuccess)) {
            throw new InvalidArgumentException(
                sprintf('Probability of success must be a numeric value, received %s')
            );
        }

        $key = "$desiredCopiesAmount:$amountOfPulls:$probabilityOfSuccess";

        if (isset($atLeastDistributionCache[$key])) {
            return $atLeastDistributionCache[$key];
        }       

        $probability = "0";

        for ($i = $desiredCopiesAmount; $i <= $amountOfPulls; $i++) {
            $exactProb = $this->calculateExactBinomialDistribution($i, $amountOfPulls, $probabilityOfSuccess);
            $probability = bcadd($probability, $exactProb, $this->internalMathPrecision);
        }

        $atLeastDistributionCache[$key] = $probability;
        return $probability;
    }

    function formatPercentage(string &$probability): void {
        $percentage = bcmul($probability, self::PERCENTAGE_CONVERSION_FACTOR, $this->internalMathPrecision);

        // Cut-off point for relevancy
        if (bccomp($percentage, self::DEFAULT_CUT_OFF_DISPLAY_PRECISION, $this->internalMathPrecision) == -1) {
            $probability = str_replace('1', '0', $this->displayDecimalPlaces);
        }

        $probability = number_format(floatval($percentage), self::DEFAULT_DECIMAL_PLACES_TO_DISPLAY) . '%';
    }

    private function getBinomialDistribution(callable $callback) {
        $results = [];

        foreach($this->pullAmounts as $pullAmount) {
            foreach($this->desiredCopiesAmounts as $desiredCopiesAmount) {
                $results[$pullAmount][$desiredCopiesAmount] = $callback(
                    $desiredCopiesAmount, 
                    $pullAmount, 
                    $this->probabilityOfsuccess
                );
            }
        }

        return $results;
    }

    public function getExactBinomialDistribution($isFormatOutput = true) {
        $results = $this->getBinomialDistribution([$this, 'calculateExactBinomialDistribution']);

        if($isFormatOutput) {
            array_walk_recursive($results, [$this, 'formatPercentage']);
        }

        return $results;
    }

    public function getAtLeastBinomialDistribution($isFormatOutput = true) {
        $results = $this->getBinomialDistribution([$this, 'calculateAtLeastBinomialDistribution']);

        if($isFormatOutput) {
            array_walk_recursive($results, [$this, 'formatPercentage']);
        }

        return $results;
    }
}
?>