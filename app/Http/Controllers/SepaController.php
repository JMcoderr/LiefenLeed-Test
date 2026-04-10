<?php

namespace App\Http\Controllers;

use App\Enums\ExportPrefix;
use App\Enums\RequestStatus;
use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use SimpleXMLElement;
use Carbon\Carbon;

class SepaController extends Controller
{
    /**
     * Validates submitted debtor data & stores it in a session.
     * Afterward, if successful, redirects the user back to the requests page with a success notification
     * @author Ismael Winterman
     */
    public function validateDebtor(Request $request) {
        // Validate modal data on the back-end.
        $validatedData = $request->validate([
            'debtorName' => 'required|string|min:2|max:250',
            'debtorIban' => 'required|regex:/^NL\d{2}[A-Z]{4}\d{10}$/',
        ]);

        // Store validated data securely in session
        session(['debtor_data' => $validatedData]);

        return redirect()->route('admin.requests.index')->with('toast', [
            'type' => 'success',
            'message' => 'Informatie succesvol gevalideerd.'
        ]);
    }

    /**
     * Gives the instruction to generate the SEPA-batch, sets the status of the requests in the batch to 'EXPORTED' and sends it to the user through a streamDownload.
     * Validates if the session data for 'debtor_data' exists, if it does, pull the data so it's removed from the session. Else return with toast error.
     * Validates if any requests with the status 'ACCEPTED' exist, if it does not, return with toast error.
     * Validates if the request came with the validate parameter on true, if it does, return with toast success. Else continue
     * @author Ismael Winterman
     */
    public function sepa(Request $request) {
        $request->validate([
            'validate' => 'boolean',
        ]);

        // Retrieve debtor data && if no debtor data is found, return to index with toast error data.
        $validatedData = $request->has('validate') ? session()->get('debtor_data', null) : session()->pull('debtor_data');
        if(!$validatedData) {
            return redirect()->route('admin.requests.index')->withErrors([
                'type' => 'error',
                'message' => 'Geen betaler informatie beschikbaar.'
            ], 'toast');
        }

        // Retrieve requests with 'ACCEPTED' status && if no requests are found, return to index with toast error data.
        $requestData = RequestModel::where('status', RequestStatus::ACCEPTED)->with(['eventCost', 'event', 'member' => fn($q) => $q->withTrashed()])->get();
        if($requestData->count() == 0) {
            return redirect()->route('admin.requests.index')->withErrors([
                'type' => 'error',
                'message' => 'Geen ontvanger informatie beschikbaar.'
            ], 'toast');
        }

        // If debtor data is valid, return back with toast success data.
        if ($request->has('validate')) {
            return redirect()->back()->with('toast', [
                'type' => 'success',
                'message' => 'Informatie succesvol gevalideerd.'
            ]);
        }

        // Build the SEPA XML & set the export file name
        $xml = $this->buildSEPAXML($this->buildSEPAData($validatedData, $this->buildPaymentData($requestData)));
        $filename = 'SEPA_EXPORT_' . Carbon::now()->format('Y-m-d') . '.xml';

        // Convert SimpleXMLElement to DOMDocument for formatting
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        // Update request status data to exported
        foreach($requestData as $request) {
            $request->status = RequestStatus::EXPORTED;
            $request->save();
        }

        // Send the SEPA XML directly to the client without storing it on the server.
        return response()->streamDownload(function () use ($dom) {
            echo $dom->saveXML();
        }, $filename, [
            'Content-Type' => 'application/xml'
        ]);
    }

    /**
     * Builds the structured SEPA-batch file with the provided data.
     * @author Ismael Winterman
     */
    private function buildSEPAXML($data): SimpleXMLElement {
        // Set document root
        $xml = new SimpleXMLElement('<?xml version = "1.0" encoding = "UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.09"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></Document>');

        // General information section
        $customerTransfer = $xml->addChild('CstmrCdtTrfInitn');

        // Group Header section
        $groupHeader = $customerTransfer->addChild('GrpHdr');
        $groupHeader->addChild('MsgId', $data['msg_id']);
        $groupHeader->addChild('CreDtTm', Carbon::now()->toAtomString());
        $groupHeader->addChild('NbOfTxs', count($data['payments']));
        $groupHeader->addChild('CtrlSum', number_format(collect($data['payments'])->sum('amount'), 2, '.', ''));
        $initParty = $groupHeader->addChild('InitgPty');
        $initParty->addChild('Nm', $data['debtor_name']);

        // Payment Information Debtor
        $paymentInfo = $customerTransfer->addChild('PmtInf');
        $paymentInfo->addChild('PmtInfId', $data['payment_info_id']);
        $paymentInfo->addChild('PmtMtd', 'TRF');
        $paymentInfo->addChild('NbOfTxs', count($data['payments']));
        $paymentInfo->addChild('CtrlSum', number_format(collect($data['payments'])->sum('amount'), 2, '.', ''));

        $paymentTypeInfo = $paymentInfo->addChild('PmtTpInf');
        $svcLvl = $paymentTypeInfo->addChild('SvcLvl');
        $svcLvl->addChild('Cd', 'SEPA');

        $paymentExecutionDate = $paymentInfo->addChild('ReqdExctnDt');
        $paymentExecutionDate->addChild('Dt', $data['execution_date']);

        $debtor = $paymentInfo->addChild('Dbtr');
        $debtor->addChild('Nm', $data['debtor_name']);

        $debtorAccount = $paymentInfo->addChild('DbtrAcct');
        $debtorAccountId = $debtorAccount->addChild('Id');
        $debtorAccountId->addChild('IBAN', $data['debtor_iban']);

        $debtorAgent = $paymentInfo->addChild('DbtrAgt');
        $debtorAgentInstitute = $debtorAgent->addChild('FinInstnId');
        $debtorAgentInstitute->addChild('BICFI', $data['debtor_bicfi']);

        // Individual Payment Information Creditor(s)
        foreach($data['payments'] as $payment){
            $creditTransferTransactionInfo = $paymentInfo->addChild('CdtTrfTxInf');

            $creditTransferId = $creditTransferTransactionInfo->addChild('PmtId');
            $creditTransferId->addChild('EndToEndId', $payment['end_to_end_id']);

            $creditTransferAmount = $creditTransferTransactionInfo->addChild('Amt');
            $creditTransferInstructedAmount = $creditTransferAmount->addChild('InstdAmt', $payment['amount']);
            $creditTransferInstructedAmount->addAttribute('Ccy', 'EUR');

            $creditorAgent = $creditTransferTransactionInfo->addChild('CdtrAgt');
            $creditorAgentInstitute = $creditorAgent->addChild('FinInstnId');
            $creditorAgentInstitute->addChild('BICFI', $payment['creditor_bicfi']);

            $creditor = $creditTransferTransactionInfo->addChild('Cdtr');
            $creditor->addChild('Nm', $payment['creditor_name']);

            $creditorAccount = $creditTransferTransactionInfo->addChild('CdtrAcct');
            $creditorAccountId = $creditorAccount->addChild('Id');
            $creditorAccountId->addChild('IBAN', $payment['creditor_iban']);

            $description = $creditTransferTransactionInfo->addChild('RmtInf');
            $description->addChild('Ustrd', $payment['remittance_information']);
        }

        return $xml;
    }

    /**
     * Builds the SEPA-data required to build the batch file.
     * @author Ismael Winterman
     */
    private function buildSEPAData($debtorData, $requestData): array {
        $data = [
            'msg_id' => $this->generateId(ExportPrefix::MSG, 1),
            'payment_info_id' => $this->generateId(ExportPrefix::PMNTINFO, 1),
            'execution_date' => Carbon::now()->addDays(3)->format('Y-m-d'), //->format required to be SEPA compliant.
            'debtor_name' => $debtorData['debtorName'],
            'debtor_iban' => $debtorData['debtorIban'],
            'debtor_bicfi' => $this->deriveBICFIfromIBAN($debtorData['debtorIban']),
            'payments' => $requestData,
        ];

        return $data;
    }

    /**
     * Builds the payment data for each individual payment in the batch of requests.
     * @author Ismael Winterman
     */
    private function buildPaymentData($requestData): array {
        $payments = [];

        foreach($requestData as $request){
            $member = is_null($request->member->full_name) || empty($request->member->full_name) ? $request->employee_recipient : $request->member->full_name . ' (' . $request->member->email . ')';
            $payments[] =
                [
                    'end_to_end_id' => $this->generateId(ExportPrefix::ENDTOEND, $request->id),
                    'amount' => $request->eventCost->amount,
                    'creditor_name' => $request->account_name,
                    'creditor_iban' =>  $request->iban,
                    'creditor_bicfi' => $this->deriveBICFIfromIBAN($request->iban),
                    'remittance_information' => "Aanvraag voor: $member, bijzonderheid: {$request->event->title}",
                ];
        }

        return $payments;
    }

    /**
     * Generates a unique ID based on the provided prefix, data and ID of a request.
     * @author Ismael Winterman
     */
    private function generateId(ExportPrefix $type, int $RequestID): string {
        /** Format =>
         *      message_id: PREFIX-DATE-TIME-EXPORT_ID
         *      payment_info_id: PREFIX-DATE-EXPORT_ID
         *      end_to_end_id: PREFIX-REQUEST_ID
         * PREFIX => MSG (MESSAGE) || PMT (PAYMENT) || INV (INVOICE)
         * DATE => Year-Month-Date
         * TIME => Hour-Minute-Second
         */
        $date = date('Ymd');
        $time = date('His');
        $separator = '-';

        return match ($type) {
            ExportPrefix::MSG => ExportPrefix::MSG->value . $separator . $date . $separator . $time . $separator . $this->returnIdWithPadding($RequestID),
            ExportPrefix::PMNTINFO => ExportPrefix::PMNTINFO->value . $separator . $date . $separator . $time . $separator . $this->returnIdWithPadding($RequestID),
            ExportPrefix::ENDTOEND => ExportPrefix::ENDTOEND->value . $separator . $this->returnIdWithPadding($RequestID),
        };
    }

    /**
     * Returns a number after turning it into a 6-digit number.
     * If the provided number is longer, returns the same number back.
     * @author Ismael Winterman
     */
    private function returnIdWithPadding ($number): string {
        return str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Derives the corresponding bank identification code because on the provided IBAN.
     * If the corresponding code cannot be found, returns unknown.
     * @author Ismael Winterman
     */
    private function deriveBICFIfromIBAN($iban): string {
        /** IBAN format =>
         *  NLAA BBBB CCCCCCCCCC
         *  4 characters, followed by 4, followed by 10
         *  We want the second set of 4 as that identifies which bank it comes from.
         */
        // Bank BIC dataset
        $dutchBankBICNums = [
            'ABNA' => 'ABNANL2A', // ABN AMRO
            'ASNB' => 'ASNBNL21', // ASN
            'BUNQ' => 'BUNQNL2A', // BUNQ
            'BUUT' => 'BUUTNL2A', // BUUT
            'INGB' => 'INGBNL2A', // ING
            'KNAB' => 'KNABNL2H', // KNAB
            'NNBA' => 'NNBANL2G',// Nationale-Nederlanden
            'RABO' => 'RABONL2U', // Rabobank
            'RBRB' => 'RBRBNL21', // Regiobank
            'REVON' => 'REVONL22', // Revolut
            'SNSB' => 'SNSBNL2A', // SNS
            'TRIO' => 'TRIONL2U', // Triodos
            'FVLB' => 'FVLBNL22', // Van Lanschot
            'YOUR' => 'YOURNL2A', // YourSafe
        ];

        $bankIndentifier = substr($iban, 4, 4);
        $bicfi = '';

        if(array_key_exists($bankIndentifier, $dutchBankBICNums)) {
            $bicfi = $dutchBankBICNums[$bankIndentifier];
        } else {
            $bicfi = 'unknown';
        }

        return $bicfi;
    }
}
