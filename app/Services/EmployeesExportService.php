<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

class EmployeesExportService
{
    public static function exportExcel($employees, $branch_name, $branch_code, $date, $columns)
    {
        ob_start();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->mergeCells('A1:' . chr(64 + count($columns)) . '1');
        $sheet->setCellValue('A1', 'Branch: ' . $branch_name);
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:' . chr(64 + count($columns)) . '2');
        $sheet->setCellValue('A2', 'Date: ' . $date->format('d-m-Y'));
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set column headers
        $col = 'A';
        foreach ($columns as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }
        $sheet->getStyle('A4:' . chr(64 + count($columns)) . '4')->getFont()->setBold(true);

        // Insert data
        $row = 5;
        foreach ($employees as $index => $item) {
            $col = 'A';
            foreach ($item as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        if (ob_get_length()) ob_end_clean();

        $filename = 'Employees_List_' . $branch_code . '_' . $date->format('d-m-Y') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public static function exportPdf($employees, $branch_name, $branch_code, $branch_address, $date, $columns, $extra = [])
    {
        if (ob_get_length()) ob_end_clean();

        ob_start();

        $safeBranch = htmlentities($branch_name, ENT_QUOTES, 'UTF-8');
        $safeBranchCode = htmlentities($branch_code, ENT_QUOTES, 'UTF-8');
        $safeBranchAddress = htmlentities($branch_address, ENT_QUOTES, 'UTF-8');
        $safeDate = $date->format('d-m-Y');

        $logoPath = public_path('images/logo_alt.png');
        $logoBase64 = base64_encode(file_get_contents($logoPath));

        $html = '
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 30px; }
                body { font-family: sans-serif; margin: 0; padding: 0; }
                .page-border {
                    position: absolute;
                    top: 15px; left: 15px; right: 15px; bottom: 15px;
                    border: 2px solid #000; padding: 20px; box-sizing: border-box;
                }
                .header {
                    display: flex;
                    flex-direction: row;
                    align-items: center;
                    margin-bottom: 20px;
                    position: relative;
                }
                .logo {
                    width: 80px;
                    height: 80px;
                    position: absolute;
                    left: 0;
                }
                .header-text {
                    text-align: center;
                    width: 100%;
                }
                h3, h4, p { margin: 5px 0; }
                h3 { font-size: 16px; }
                h4 { font-size: 14px; }
                .text-right { text-align: right; margin-top: 15px; margin-bottom: 10px; }
                table {
                    width: 100%; border-collapse: collapse; margin-top: 10px;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 6px;
                    font-size: 12px;
                    text-align: center;
                }
                thead { display: table-header-group; }
            </style>
        </head>
        <body>
            <div class="page-border">
                <div class="header">
                    <img src="data:image/png;base64,' . $logoBase64 . '" class="logo" alt="Company Logo">
                    <div class="header-text">
                        <h3>' . $safeBranch . '-' . $safeBranchCode . '</h3>
                        <h4>' . $safeBranchAddress . '</h4>
                        <br>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h4>Employees List</h4>
                            <h4 style="text-align: right; mt=-6">Date: ' . $safeDate . '</h4>
                        </div>
                    </div>
                </div>
                <table>
                    <colgroup>
                        <col style="width: 12%;"> <!-- Employee Code -->
                        <col style="width: 30%;"> <!-- Name -->
                        <col style="width: 11%;"> <!-- Mobile -->
                        <col style="width: 11%;"> <!-- Designation -->
                        <col style="width: 12%;"> <!-- Branch Code -->
                        <col style="width: 11%;"> <!-- Branch Name -->
                        <col style="width: 11%;"> <!-- Status -->
                    </colgroup>
                    <thead>
                        <tr>';

        foreach ($columns as $header) {
            $html .= '<th>' . htmlentities($header, ENT_QUOTES, 'UTF-8') . '</th>';
        }

        $html .= '</tr>
                    </thead>
                    <tbody>';

        foreach ($employees as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= '<td>' . htmlentities($value, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '
                    </tbody>
                </table>
            </div>
        </body>
        </html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');

        // Set to A4 portrait
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = 'Employees_List_' . $safeBranchCode . '_' . $safeDate . '.pdf';
        $dompdf->stream($filename, ["Attachment" => true]);
        exit;
    }
}
