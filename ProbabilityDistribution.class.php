<?php
class ProbabilityDistribution {
    const MIN_PRECISION = 0;
    const MAX_PRECISION = 100;
    const DEFAULT_PRECISION = 20;
    private $precision = 0;

    const MIN_PROBABILITY = 0;
    const MAX_PROBABILITY = 100;
    const PROBABILITY_DENOMINATOR = 10000;
 
    private $probabilityOfsuccess = 0;
    private $amountOfDesiredCopies = 1;
    private $amountOfPulls = 1;
    private $distributionResults = array();

    const MIN_AMOUNT_OF_DESIRED_COPIES = 1;
    const MIN_PULL_AMOUNT = 1;

    const CUT_OFF_PRECISION = '0.0001';
    const DISPLAY_DECIMAL_PLACES = 4;
    const PERCENTAGE_CONVERSION_FACTOR = '100';

    private $binomialCache = array();
    private $exactDistributionCache = array();
    private $atLeastDistributionCache = array();

    public function __construct() {
        if(!extension_loaded('bcmath')) {
            throw new Exception('BCMath extension is not loaded');
        }

        $this->precision = self::DEFAULT_PRECISION;
        bcscale($this->precision);
    }

    public function setPrecision(int $precision): void {
        if($precision < self::MIN_PRECISION || $precision > self::MAX_PRECISION) {
            throw new InvalidArgumentException(
                sprintf(
                    'Precision must be an integer between %d and %d, received %d',
                    self::MIN_PRECISION,
                    self::MAX_PRECISION,
                    $precision
                )
            );
        }

        $this->precision = $precision;
        bcscale($this->precision);
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

        $probabilityOfSuccess = bcdiv(bcmul($genericResultChance, $specificresultChance), self::PROBABILITY_DENOMINATOR, $this->precision);
        $this->probabilityOfsuccess = $probabilityOfSuccess;
    }

    public function setAmountOfDesiredCopies(int $amountOfDesiredCopies): void {
        if($amountOfDesiredCopies < self::MIN_AMOUNT_OF_DESIRED_COPIES) {
            throw new OutOfRangeException(
                sprintf(
                    'Amount of desired copies must be at least %d, received %d',
                    self::MIN_AMOUNT_OF_DESIRED_COPIES,
                    $amountOfDesiredCopies
                )
            );
        }

        $this->amountOfDesiredCopies = $amountOfDesiredCopies;
    }

    public function setAmountOfPulls(array $amountOfPulls): void {
        foreach($amountOfPulls as $pull) {
            if(!is_int($pull) || $pull < self::MIN_PULL_AMOUNT) {
                throw new OutOfRangeException(
                    sprintf(
                        'Amount of pulls must be an array of positive integers, received %s',
                        gettype($pull)
                    )
                );
            }
        }

        $this->amountOfPulls = $amountOfPulls;
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
            $result = bcdiv(bcmul($result, ($n - $i + 1)), $i, $this->precision);
        }

        $this->binomialCache[$key] = $result;
        return $result;
    }

    public function getExactBinomialDistribution(
        int $amountDesiredCopies,
        int $amountOfPulls,
        string $probabilityOfSuccess
    ): string {
        if(!is_numeric($probabilityOfSuccess)) {
            throw new InvalidArgumentException(
                sprintf('Probability of success must be a numeric value, received %s')
            );
        }

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

    function getAtLeastBinomialDistribution(int $amountDesiredCopies, int $amountOfPulls, string $probabilityOfSuccess): string {
        if(!is_numeric($probabilityOfSuccess)) {
            throw new InvalidArgumentException(
                sprintf('Probability of success must be a numeric value, received %s')
            );
        }

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

    function formatPercentage(string $probability): string {
        $percentage = bcmul($probability, self::PERCENTAGE_CONVERSION_FACTOR, $this->precision);

        if (bccomp($percentage, self::PERCENTAGE_CUTOFF, $this->precision) == -1) {
            return "0.0000%"; // Cut-off point for relevancy
        }

        return number_format(floatval($percentage), self::DISPLAY_DECIMAL_PLACES) . '%';
    }

    public function generateResults(): void {
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

    public function prettyPrintResults() {
        $html = '
        <style>
            #distributionTable {
                border-collapse: collapse;
            }

            #distributionTable th, #distributionTable td {
                border: 1px solid black;
                text-align: center;
                padding: 4px;
            }

            #distributionTable th {
                background-color: #94E797;
            }

            #distributionTable tr:nth-child(even) td {
                background-color: #FFD7BE;
            }

            #distributionTable tr:nth-child(odd) td {
                background-color: #F2F2F2;
            }

            #distributionTable tr:hover td {
                filter: contrast(0.8);
                2px solid black;
            }

            #distributionTable tr td.exact:first-of-type,
            #distributionTable tr td.atLeast:first-of-type {
                border-left: 2px solid black;
            }
        </style>

        <table id="distributionTable">
            <thead>
                <tr>
                    <th></th>
                    <th colspan="'.$this->amountOfDesiredCopies.'">Exact Copies</th>
                    <th colspan="'.$this->amountOfDesiredCopies.'">At Least Copies</th>
                </tr>
                <tr>
                    <th>Number of Pulls</th>';

 
        for ($i = 1; $i <= $this->amountOfDesiredCopies; $i++) {
            $html .= '<th>' . $i . '</th>';
            $html .= '<th>' . $i . '</th>';
        }

        $html .= '
                </tr>
            </thead>
            <tbody>';

        foreach($this->distributionResults as $pulls => $results) {
            $html .= '
                <tr>
                    <td class="pulls">' . $pulls . '</td>';
 
            for ($i = 1; $i <= $this->amountOfDesiredCopies; $i++) {
                $html .= '<td class="exact">' . $results[$i]['exact'] . '</td>';
            }

            for ($i = 1; $i <= $this->amountOfDesiredCopies; $i++) {
                $html .= '<td class="atLeast">' . $results[$i]['atLeast'] . '</td>';
            }

            $html .= '
                </tr>';
        }

        $html .= '
            </tbody>
        </table>';

        echo $html;
    }
}
?>