<?php

namespace App\Exports;

use App\Enums\RequestStatus;
use App\Models\Request;
use App\Services\AfasEmployeesService;
use App\Services\AfasMembersService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RequestsExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    private $startDate;
    private $endDate;

    public function __construct($startDate, $endDate) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     * @author Ismael Winterman
     */
    public function collection()
    {
        // Retrieve all paid requests between the given start and end date.
        // Join requests.employee_recipient on members table (if data is available otherwise go back to requests.employee_requester
        return Request::with(['member' => fn($q) => $q->withTrashed(), 'eventCost.event'])
            ->where('status', RequestStatus::PAID)
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->get()
            ->map(function ($request) { // Eager load
                return [
                    'id' => $request->id,
                    'employee_requester' => $request->employee_requester,
                    'employee_recipient' => empty($request->member->full_name) || is_null($request->member->full_name) ? $request->employee_recipient : $request->member->full_name,
                    'title' => $request->eventCost?->event?->title,
                    'amount' => $request->eventCost?->amount,
                    'start_date' => $request->eventCost?->start_date,
                    'end_date' => $request->eventCost?->end_date,
                    'paid_at' => $request->paid_at,
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at,
                ];
            });
    }

    /**
     * @author Ismael Winterman
     */
    public function headings(): array
    {
        return [                        // Excel Rows
            'Id',                       // A
            'Employee_requester',       // B
            'Employee_Recipient',       // C
            'Event_Name',               // D
            'Cost',                     // E // If you edit this headings name, change 'searchHeading' in registerEvents
            'Event_cost_start_date',    // F
            'Event_cost_end_date',      // G
            'Paid_at',                  // H
            'Created_at',               // I
            'Updated_at',               // J
        ];
    }

    /**
     * @author Ismael Winterman
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Get worksheet for full access.
                $sheet = $event->sheet->getDelegate();

                // Get worksheet headings, last column, last row.
                $headings = $this->headings();
                $lastColumn = Coordinate::stringFromColumnIndex(count($headings));
                $lastRow = $sheet->getHighestRow();

                // Set header range & searchHeading.
                $headingsRange = 'A1:' . $lastColumn . '1';
                $searchHeading = 'Cost';

                // Total Cost section
                // Add 'Total Cost' heading below the last row with empty separator row (+2 after last row).
                $totalCostHeadingCell = 'A' . ($lastRow + 2);
                $sheet->setCellValue($totalCostHeadingCell, 'Total Cost');

                // Get 'Cost' column letter, +1 as array works on 0 index, but excel starts from 1.
                $costColumn = Coordinate::stringFromColumnIndex((array_search($searchHeading, $headings) + 1));

                // Set column range, example range of column A if lastRow is 7 => A2:A7.
                $costColumnRange = $costColumn . '2:' . $costColumn . $lastRow;

                // Build & set the sum formula for total cost cell.
                $totalCostCell = 'A' . ($lastRow + 3);
                $sheet->setCellValue($totalCostCell, '=SUM(' . $costColumnRange . ')');

                // Styling section
                $headingsToStyle = [ $headingsRange, $totalCostHeadingCell];
                $costsToStyle = [ $costColumn, $totalCostCell];

                // Set heading styles
                foreach ($headingsToStyle as $cell) {
                    // Font
                    $sheet->getStyle($cell)->getFont()
                        ->setBold(true)
                        ->setColor(new Color('000000'));
                    // Background
                    $sheet->getStyle($cell)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('DAFBC9');
                    // Border
                    $sheet->getStyle($cell)->getBorders()->getBottom()
                        ->setBorderStyle(Border::BORDER_THIN)
                        ->getColor()->setRGB('000000');
                }

                // Set currency styles
                foreach ($costsToStyle as $cell) {
                    // Currency format for cost column & total cost cell.
                    $sheet->getStyle($cell)
                        ->getNumberFormat()
                        ->setFormatCode('"€"#0.00');
                }
            }
        ];
    }
}
