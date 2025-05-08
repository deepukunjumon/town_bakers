<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ItemQuantityExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;


class StockCOntroller extends Controller
{
    public function addStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'required|exists:employees,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        // Create trip
        $trip = Trip::create([
            'branch_id' => $request->branch_id,
            'employee_id' => $request->employee_id,
        ]);

        // Create stock items
        foreach ($request->items as $stock) {
            StockItem::create([
                'trip_id' => $trip->id,
                'item_id' => $stock['item_id'],
                'quantity' => $stock['quantity'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock added successfully',
            'trip_id' => $trip->id
        ]);
    }

    public function getTripDetails($trip_id)
    {
        $trip = Trip::with('stockItems.item')->findOrFail($trip_id);

        return response()->json($trip);
    }

    public function getItemsByDate(Request $request)
    {
        if ($request->has('export')) {
            $request->merge([
                'export' => filter_var($request->input('export'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'export' => 'sometimes|boolean',
            'type' => 'required_if:export,true|in:excel,pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = Carbon::parse($request->date);
        $branchId = Auth::user()->branch_id;

        $branch = DB::table('branches')->where('id', $branchId)->first();
        $branch_name = $branch ? $branch->name : 'Unknown Branch';

        $items = DB::table('stock_items')
            ->join('items', 'stock_items.item_id', '=', 'items.id')
            ->join('trips', 'stock_items.trip_id', '=', 'trips.id')
            ->whereDate('trips.date', $date)
            ->where('trips.branch_id', $branchId)
            ->select('items.name as item_name', DB::raw('SUM(stock_items.quantity) as total_quantity'))
            ->groupBy('items.name')
            ->get();

        // Check if export is requested
        if ($request->boolean('export')) {
            $type = $request->input('type');

            if ($type === 'excel') {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                $sheet->mergeCells('A1:B1');
                $sheet->setCellValue('A1', 'Branch: ' . $branch_name);
                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:B2');
                $sheet->setCellValue('A2', 'Date: ' . $date->format('d-m-Y'));
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A4', 'Item Name');
                $sheet->setCellValue('B4', 'Total Quantity');
                $sheet->getStyle('A4:B4')->getFont()->setBold(true);

                $row = 5;
                foreach ($items as $item) {
                    $sheet->setCellValue('A' . $row, $item->item_name);
                    $sheet->setCellValue('B' . $row, $item->total_quantity);
                    $row++;
                }

                if (ob_get_length()) ob_end_clean();

                $filename = 'Stock_Summary_' . $date->format('Y-m-d') . '.xlsx';
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header("Content-Disposition: attachment; filename=\"$filename\"");
                header('Cache-Control: max-age=0');

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                exit;
            }

            if ($type === 'pdf') {
                if (ob_get_length()) ob_end_clean();

                $safeBranch = htmlentities($branch_name, ENT_QUOTES, 'UTF-8');
                $safeAddress = htmlentities($branch->address ?? 'N/A', ENT_QUOTES, 'UTF-8');
                $safeMobile = htmlentities($branch->mobile ?? 'N/A', ENT_QUOTES, 'UTF-8');
                $safeDate = $date->format('d-m-Y');

                $html = '
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        @page {
                            margin: 30px;
                        }
            
                        body {
                            font-family: sans-serif;
                            margin: 0;
                            padding: 0;
                        }
            
                        .page-border {
                            position: absolute;
                            top: 15px;
                            left: 15px;
                            right: 15px;
                            bottom: 15px;
                            border: 2px solid #000;
                            padding: 20px;
                            box-sizing: border-box;
                        }
            
                        h3, p {
                            margin: 5px 0;
                            text-align: center;
                        }
            
                        .text-right {
                            text-align: right;
                            margin-top: 15px;
                            margin-bottom: 10px;
                        }
            
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 10px;
                        }
            
                        th, td {
                            border: 1px solid #000;
                            padding: 6px;
                            font-size: 12px;
                            text-align: center;
                        }
            
                        thead {
                            display: table-header-group;
                        }
                    </style>
                </head>
                <body>
                    <div class="page-border">
                        <h3>' . $safeBranch . '</h3>
                        <p>' . $safeAddress . '</p>
                        <p>Contact: ' . $safeMobile . '</p>
                        <p class="text-right"><strong>Date: ' . $safeDate . '</strong></p>
            
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 8%;">Sl.No</th>
                                    <th>Item Name</th>
                                    <th style="width: 20%;">Total Quantity</th>
                                </tr>
                            </thead>
                            <tbody>';

                $i = 1;
                foreach ($items as $item) {
                    $itemName = htmlentities($item->item_name, ENT_QUOTES, 'UTF-8');
                    $html .= '<tr>
                                <td>' . $i++ . '</td>
                                <td>' . $itemName . '</td>
                                <td>' . $item->total_quantity . '</td>
                              </tr>';
                }

                $html .= '
                            </tbody>
                        </table>
                    </div>
                </body>
                </html>';

                $dompdf = new Dompdf();
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                $filename = 'Stock_Summary_' . $date->format('Y-m-d') . '.pdf';
                $dompdf->stream($filename, ["Attachment" => true]);
                exit;
            }
        }

        // Return JSON if export is not requested
        return response()->json([
            'success' => true,
            'message' => 'Fetched stock summary',
            'date' => $date->format('d-m-Y'),
            'data' => $items
        ]);
    }

    public function branchwiseStockSummary(Request $request)
    {
        if ($request->has('export')) {
            $request->merge([
                'export' => filter_var($request->input('export'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required',
            'date' => 'required|date',
            'export' => 'sometimes|boolean',
            'type' => 'required_if:export,true|in:excel,pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $branch_id = $request->branch_id;
        $date = Carbon::parse($request->date);
        $branch = DB::table('branches')->where('id', $branch_id)->first();
        $branch_name = $branch->name ?? 'Unknown Branch';
        $branch_code = $branch->code ?? 'Unknown Branch';

        $items = DB::table('stock_items')
            ->join('items', 'stock_items.item_id', '=', 'items.id')
            ->join('trips', 'stock_items.trip_id', '=', 'trips.id')
            ->whereDate('trips.date', $date)
            ->where('trips.branch_id', $branch_id)
            ->select(
                'stock_items.item_id',
                'items.name as item_name',
                DB::raw('SUM(stock_items.quantity) as total_quantity')
            )
            ->groupBy('stock_items.item_id', 'items.name')
            ->get();

        if ($request->boolean('export')) {
            $type = $request->input('type');

            if ($type === 'excel') {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                $sheet->mergeCells('A1:C1');
                $sheet->setCellValue('A1', 'Branch: ' . $branch_name);
                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:C2');
                $sheet->setCellValue('A2', 'Date: ' . $date->format('d-m-Y'));
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A4', 'Sl. No');
                $sheet->setCellValue('B4', 'Item Name');
                $sheet->setCellValue('C4', 'Total Quantity');
                $sheet->getStyle('A4:C4')->getFont()->setBold(true);

                $row = 5;
                foreach ($items as $index => $item) {
                    $sheet->setCellValue("A{$row}", $index + 1);
                    $sheet->setCellValue("B{$row}", $item->item_name);
                    $sheet->setCellValue("C{$row}", $item->total_quantity);
                    $row++;
                }

                if (ob_get_length()) ob_end_clean();

                $filename = 'Stock_Summary_' . $branch_code . '_' . $date->format('d-m-Y') . '.xlsx';
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header("Content-Disposition: attachment; filename=\"$filename\"");
                header('Cache-Control: max-age=0');

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                exit;
            }

            if ($type === 'pdf') {
                if (ob_get_length()) ob_end_clean();

                $safeBranch = htmlentities($branch_name, ENT_QUOTES, 'UTF-8');
                $safeBranchCode = htmlentities($branch_code, ENT_QUOTES, 'UTF-8');
                $safeAddress = htmlentities($branch->address ?? 'N/A', ENT_QUOTES, 'UTF-8');
                $safeMobile = htmlentities($branch->mobile ?? 'N/A', ENT_QUOTES, 'UTF-8');
                $safeDate = $date->format('d-m-Y');

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
                        h3, p { margin: 5px 0; text-align: center; }
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
                        <h3>' . $safeBranch . '-' . $safeBranchCode . '</h3>
                        <p>' . $safeAddress . '</p>
                        <p>Contact: ' . $safeMobile . '</p>
                        <p class="text-right"><strong>Date: ' . $safeDate . '</strong></p>
    
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 8%;">Sl.No</th>
                                    <th>Item Name</th>
                                    <th style="width: 20%;">Total Quantity</th>
                                </tr>
                            </thead>
                            <tbody>';

                $i = 1;
                foreach ($items as $item) {
                    $itemName = htmlentities($item->item_name, ENT_QUOTES, 'UTF-8');
                    $html .= '<tr>
                                <td>' . $i++ . '</td>
                                <td>' . $itemName . '</td>
                                <td>' . $item->total_quantity . '</td>
                              </tr>';
                }

                $html .= '
                            </tbody>
                        </table>
                    </div>
                </body>
                </html>';

                $dompdf = new Dompdf();
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                $filename = 'Stock_Summary_' . $safeBranchCode . '_' . $date->format('d-m-Y') . '.pdf';
                $dompdf->stream($filename, ["Attachment" => true]);
                exit;
            }
        }

        // JSON fallback if not exporting
        $formattedData = [
            [
                'branch_id' => $branch_id,
                'items' => $items->map(function ($item) {
                    return [
                        'item_id' => $item->item_id,
                        'item_name' => $item->item_name,
                        'quantity' => $item->total_quantity,
                    ];
                }),
            ]
        ];

        return response()->json([
            'success' => true,
            'date' => $date->format('d-m-Y'),
            'data' => $formattedData
        ]);
    }
}
