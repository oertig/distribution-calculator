<?php
namespace DistributionCalculator;

class TableRenderer {
    public function renderTable(
        array $data, 
        array $copyAmounts, 
        int $amountForMulti = 10,
        string $amountLabel = 'Copies',
        bool $isMultiesShown = false
    ): string {
        $copiesColumnCount = count($copyAmounts);
        
        $html = $this->getHtmlTemplate($copiesColumnCount, true);

        $html = str_replace('%%AMOUNT_LABEL%%', $amountLabel, $html);
        $html = str_replace('%%TABLE_ROWS%%', $this->getHtmlTableRows($data, $amountForMulti, $isMultiesShown), $html);

        for($i = 1; $i <= $copiesColumnCount; $i++) {
            $html = str_replace('%%AMOUNT_LABEL_'.$i.'%%', $copyAmounts[$i-1], $html);
        }

        return $html;
    }

    public function renderHtmlSkeleton(string $content = ''): string {
        $html = '
            <html>
                <head>
                    <title>distribution Calculator</title>
                    <link rel="stylesheet" type="text/css" href="styles.css">
                </head>
                <body>
                    %%CONTENT%%
                </body>
            </html>
        ';

        return str_replace('%%CONTENT%%', $content, $html);
    }

    private function getHtmlTemplate(int $copiesColumnCount = 1, bool $isMultiesShown = false): string {
        $multiesColumn = $isMultiesShown ? '<th>Multies</th>' : '';
        $pullsColspan = $isMultiesShown ? '2' : '1';
        $headerAmountCopies = '';

        for($i = 1; $i <= $copiesColumnCount; $i++) {
            $headerAmountCopies .= '<th>%%AMOUNT_LABEL_'.$i.'%%</th>';
        }

        return '
            <div class="table-container">
                <table class="distributionTable">
                    <thead>
                        <tr>
                            <th colspan="'.$pullsColspan.'">Pulls</th>
                            <th colspan="'.$copiesColumnCount.'">%%AMOUNT_LABEL%%</th>
                        </tr>
                        <tr>
                            <th>Singles</th>
                            '.$multiesColumn.'
                            '.$headerAmountCopies.'
                        </tr>
                    </thead>
                    <tbody>
                        %%TABLE_ROWS%%
                    </tbody>
                </table>
            </div>
        ';
    }

    private function getHtmlTableRows(array $data, int $amountForMulti = 10, bool $isMultiesShown = false): string {
        $html = '';

        foreach($data as $amountSingles => $successChances) {
            $html .= '<tr>';
            $html .= '<td>'.$amountSingles.'</td>';

            if($isMultiesShown) {
                $cellMultiples = '<td></td>';

                if($amountSingles % $amountForMulti === 0) { // if perfect multi without remainder
                    $quotient = intdiv($amountSingles, $amountForMulti); // amount of multies
                    $cellMultiples = '<td>'.$quotient.'</td>';
                }

                $html .= $cellMultiples;
            }

            foreach($successChances as $successChance) {
                $html .= '<td>'.$successChance.'</td>';
            }

            $html .= '</tr>';
        }

        return $html;
    }

    public function renderChart(array $data, string $chartTitle = 'Chart Title'): string {
        $html = '<div class="chart-container">';
        $html .= <<<EOT
            <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
            <script type="text/javascript">
            google.charts.load('current', {'packages':['corechart']});
            google.charts.setOnLoadCallback(drawChart);

            function drawChart() {
                var data = google.visualization.arrayToDataTable([
        EOT;

        $dataHeaders = "['Singles', ";

        reset($data);
        $firstKey = key($data);

        foreach($data[$firstKey] as $amountCopies => $successChance) {
            $dataHeaders .= "'".$amountCopies." Copies', ";
        }

        $dataHeaders = substr($dataHeaders, 0, -2);
        $dataHeaders .= "],";

        $html .= $dataHeaders;
        $dataPoints = '';

        foreach($data as $amountSingles => $successChances) {
            $dataString = "['".$amountSingles."'";

            foreach($successChances as $successChance) {
                // $dataString .= ", '".$successChance."'";
                $dataString .= ", ".floatval(str_replace('%', '', $successChance));
            }

            $dataString .= "],";
            $dataPoints .= $dataString;
        }

        $dataPoints= substr($dataPoints, 0, -1);
        $html .= $dataPoints;

        $amountDataPoints = count($data);
        $chartWidth = $amountDataPoints * 50;
        $chartHeight = ceil($chartWidth / 2);

        $html .= <<<EOT
                ]);

                var options = {
                title: '{$chartTitle}',
                curveType: 'function',
                legend: { position: 'bottom' },
                chartArea: { width: '80%', height: '70%' },
                fontSize: 10,
                hAxis: {title: 'amount pulls' },
                vAxis: {title: 'chance', format: '#%' }
                };

                var chart = new google.visualization.LineChart(document.getElementById('curve_chart_{$chartTitle}'));
                chart.draw(data, options);
            }
            </script>

            <div id="curve_chart_{$chartTitle}" style="width: {$chartWidth}px; height: {$chartHeight}px"></div>
        EOT;

        $html .= '</div>';

        return $html;
    }
}
?>