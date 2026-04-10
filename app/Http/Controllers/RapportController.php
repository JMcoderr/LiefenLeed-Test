<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Exports\RequestsExport;
use App\Models\Request as RequestModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class RapportController extends Controller
{
    /**
     * Validates submitted date range & stores it in a session.
     * Afterward, if successful, redirects the user back to the requests page with a success notification
     * @author Ismael Winterman
     */
    public function validateRapportDate(Request $request){
        // Validate modal data on the back-end.
        $validatedData = $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);

        // Store validated data securely in session
        session(['rapport_data' => $validatedData]);

        return redirect()->route('admin.requests.index')->with('toast', [
            'type' => 'success',
            'message' => 'Informatie succesvol gevalideerd.'
        ]);
    }

    /**
     * Creates an Excel export with requests that have the 'PAID' status and fall within the provided date range.
     * Validates if the session data for 'rapport_data' exists, if it does, pull the data so it's removed from the session. Else return with toast error.
     * Validates if any requests with the status 'PAID' exist, if it does not, return with toast error.
     * Validates if the request came with the validate parameter on true, if it does, return with toast success. Else continue
     * @author Ismael Winterman
     */
    public function rapport(Request $request) {
        $request->validate([
            'validate' => 'boolean',
        ]);

        // Retrieve rapport data && if no rapport data is found, return to index with toast error data.
        $validatedData = $request->has('validate') ? session()->get('rapport_data', null) : session()->pull('rapport_data');
        if(!$validatedData){
            return redirect()->route('admin.requests.index')->withErrors([
                'type' => 'error',
                'message' => 'Geen rapport informatie beschikbaar.'
            ], 'toast');
        }

        // Set start & end date since the data exist.
        $startDate = $validatedData['startDate'];
        $endDate = $validatedData['endDate'];

        // Retrieve requests with 'PAID' status && if no requests are found, return to index with toast error data.
        $requestData = RequestModel::where('status', RequestStatus::PAID)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->with(['eventCost', 'event', 'member' => fn($q) => $q->withTrashed()])->get();

        if($requestData->count() == 0) {
            return redirect()->route('admin.requests.index')->withErrors([
                'type' => 'error',
                'message' => 'Geen aanvragen beschikbaar tussen ' . $startDate . ' en ' . $endDate,
            ], 'toast');
        }

        // If rapport data is valid, return back with toast success data.
        if ($request->has('validate'))
            return redirect()->back()->with('toast', [
                'type' => 'success',
                'message' => 'Informatie succesvol gevalideerd.'
            ]);

        $filename = 'PAID_REQUESTS_EXPORT-'. Carbon::now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new RequestsExport($startDate, $endDate), $filename, ExcelFormat::XLSX);
    }
}
