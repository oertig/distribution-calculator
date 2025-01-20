<?php
class TableRenderer {
    public function renderTable(array $data, array $copyAmounts): string {
        $html = $this->getHtmlTemplate();

        $amountHeaders = count($copyAmounts);
        $amountCopiesHeader = '';

        foreach($copyAmounts as $copyAmount) {
            $amountCopiesHeader .= '<th>'.$copyAmount.'</th>';
        }

        $html = str_replace('%%AMOUNT_COPIES%%', $amountHeaders, $html);
        $html = str_replace('%%CELLS_AMOUNT_COPIES%%', $amountCopiesHeader, $html);
        // TODO: add the table rows

        return $html;
    }

    private function getHtmlTemplate(): string {
        return '
            <table>
                <thead>
                    <tr>
                        <th> <!-- deliberately empty --> </th>
                        <th colspan="%%AMOUNT_COPIES%%">Exact Copies</th>
                    </tr>
                    <tr>
                        <th>Number of Pulls</th>
                        // one cell per desired copy: %%CELLS_AMOUNT_COPIES%%
                    </tr>
                </thead>
                <tbody>
                    %%TABLE_ROWS%%
                </tbody>
            </table>
        ';
    }



    // old function from other class
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
                    <th colspan="'.$this->desiredCopiesAmounts.'">Exact Copies</th>
                    <th colspan="'.$this->desiredCopiesAmounts.'">At Least Copies</th>
                </tr>
                <tr>
                    <th>Number of Pulls</th>';

 
        for ($i = 1; $i <= $this->desiredCopiesAmounts; $i++) {
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
 
            for ($i = 1; $i <= $this->desiredCopiesAmounts; $i++) {
                $html .= '<td class="exact">' . $results[$i]['exact'] . '</td>';
            }

            for ($i = 1; $i <= $this->desiredCopiesAmounts; $i++) {
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