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
}
?>