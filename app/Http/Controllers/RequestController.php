<?php

namespace App\Http\Controllers;

use App\Enums\EventCostStatus;
use App\Enums\RequestStatus;
use App\Models\Event;
use App\Models\EventCost;
use App\Models\Member;
use App\Models\Request as RequestModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Mail\RequestCreatedMail;
use App\Mail\RequestAcceptedMail;
use App\Mail\RequestRejectedMail;
use App\Mail\RequestPaidMail;

class RequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $events = Event::select('id', 'title')->orderBy('id')->get();

        $isSuper = $request->session()->get('magic.admin.isSuper') ?? false;

        // Sanitize statuses if not super
        $statuses = $request->query('statuses', []);
        if (!$isSuper && is_array($statuses))
            $statuses = array_values(array_intersect($statuses, [RequestStatus::ACCEPTED->value, RequestStatus::REJECTED->value, RequestStatus::PENDING->value]));

        if (!$isSuper && empty($statuses))
            $statuses = [RequestStatus::ACCEPTED->value, RequestStatus::PENDING->value, RequestStatus::REJECTED->value];
//            return redirect()->route('admin.requests.index', ['statuses' => [RequestStatus::ACCEPTED->value, RequestStatus::REJECTED->value, RequestStatus::PENDING->value]]);

            $search = $request->query('search');

        return Inertia::render('admin/Requests', [
            'requests' => RequestModel::query()
                ->with([
                    'eventCost.event',
                    'member' => fn($query) => $query->withTrashed()
                ])
                ->when($request->filled('event_id'), fn ($query) =>
                    $query->whereHas('eventCost', fn ($query) =>
                        $query->where('event_id', $request->get('event_id'))
                    )
                )
                ->when(!empty($statuses), function ($query) use ($statuses) {
                    $query->whereIn('status', $statuses);
                })
                ->when(!empty($search), fn ($query) =>
                    $query->where('id', 'like', "%$search%")
                        ->orWhere('employee_requester', 'like', "%$search%")
                        ->orWhereHas('member', fn ($q) =>
                            $q->where('email', 'like', "%$search%")
                                ->orWhere('full_name', 'like', "%$search%")
                        )
                )
                ->orderByRaw('
                    CASE
                        WHEN status = ? THEN 0
                        WHEN status = ? THEN 2
                        ELSE 1
                    END
                ', [RequestStatus::PENDING->value, RequestStatus::REJECTED->value])
                ->orderByDesc('updated_at')
                ->paginate(15)
                ->onEachSide(1)
                ->withQueryString(),
            'events' => $events,
            'selectedEvent' => $request->query('event_id'),
            'selectedStatuses' => $statuses,
            'search' => $request->query('search'),
            'defaultSepa' => $isSuper ? config('services.sepa') : null
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_requester' => ['required', 'email', new \App\Rules\ValidDomainEmail],
            'employee_recipient' => ['required', 'email', new \App\Rules\ValidDomainEmail],
            'event_cost_id' => ['required', 'exists:event_costs,id'],
            'account_name' => ['required', 'string', 'max:100'],
            'iban' => ['required', 'regex:/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/i'],
        ]);

        if (strcasecmp($validated['employee_requester'], $request->session()->get('magic.email')) !== 0)
            throw ValidationException::withMessages([
                'employee_requester' => 'Ingevulde aanvrager is niet het zelfde als ingelogde gebruiker.'
            ]);

        // Validate that the requester and recipient aren't the same
        if (strcasecmp($validated['employee_requester'], $validated['employee_recipient']) === 0)
            throw ValidationException::withMessages([
                'employee_requester' => 'De aanvrager mag niet het zelfde zijn als de ontvanger.'
            ]);

        // Validate that member exists
        if (!$member = Member::query()->allowedMembers()->where('email', '=', $validated['employee_recipient'])->first())
            throw ValidationException::withMessages([
                'employee_recipient' => 'De opgegeven ontvanger kon niet worden gevonden.'
            ]);

        $eventCost = EventCost::findOrFail($validated['event_cost_id']);

        // Validate that the status of eventCost is currently active
        if ($eventCost->status !== EventCostStatus::Active)
            throw ValidationException::withMessages([
                'event_cost_id' => 'Het geselecteerde gebeurtenis is niet actief.'
            ]);

        if ($eventCost->event_id == 15 || $eventCost->event_id == 16) {
            $latestRequest = RequestModel::query()
                ->where('employee_recipient', $member->id)
                ->where('event_cost_id', $eventCost->id)
                ->withoutStatus(RequestStatus::REJECTED)
                ->orderByDesc('created_at')
                ->first();

            if ($latestRequest) {
                $threeMonthsAgo = Carbon::now()->subMonths(3);

                if ($latestRequest->created_at->greaterThan($threeMonthsAgo)) {
                    $nextAllowedDate = $latestRequest->created_at->addMonths(3);
                    $daysRemaining = (int) Carbon::now()->diffInDays($nextAllowedDate);

                    throw ValidationException::withMessages([
                        'event_cost_id' => "Er is al een aanvraag voor ziek-(enhuisopname) ingediend. Er kan pas over $daysRemaining dagen opnieuw een aanvraag ingediend worden."
                    ]);
                }
            }
        }
        else {
            $eventCostIds = Event::with('eventCosts')
                ->find($eventCost->event_id)
                ->eventCosts
                ->pluck('id')
                ->toArray();

            $requestExists = RequestModel::query()
                ->where('employee_recipient', $member->id)
                ->whereIn('event_cost_id', $eventCostIds)
                ->withoutStatus(RequestStatus::REJECTED)
                ->exists();

            // Validate that request doesn't already exist (except if the request was rejected)
            if ($requestExists)
                throw ValidationException::withMessages([
                    'employee_recipient' => 'De ontvanger heeft al een aanvraag voor dit typen gebeurtenis.'
                ]);
        }

        $validated['employee_requester'] = $request->session()->get('magic.email');
        $validated['employee_recipient'] = $member->id; // Check to call via model hasMany method (implements foreign key, should be carefull if member is ever deleted due to no longer being active)

        // Look into making modular and not hard coded.
        $milestoneDate = match ($eventCost->event_id) {
            // Date of Birth
            1 => Carbon::parse($member->dob)->addYears(30),
            2 => Carbon::parse($member->dob)->addYears(40),
            3 => Carbon::parse($member->dob)->addYears(50),
            4 => Carbon::parse($member->dob)->addYears(60),
            5 => Carbon::parse($member->dob)->addYears(65),

            // Years of Service
            6 => Carbon::parse($member->years_of_service)->addYears(12.5),
            7 => Carbon::parse($member->years_of_service)->addYears(25),
            8 => Carbon::parse($member->years_of_service)->addYears(40),
            default => null
        };
////                $milestoneDate = Carbon::now(); // work on back calulation of 'dob' to determine when someone would go with pension
////                $milestoneDate = Carbon::parse($member->date_of_pension); // Should check if data is deliverid otherwise remove or make a function that calulates based on DOB and remaining years until pension for age braket (i.e. 67 or 67 and 2 months.)

        if (isset($milestoneDate)) {
            $startWindow = $milestoneDate->copy()->subMonths(3);
            $endWindow = $milestoneDate->copy()->addMonths(3);
            if (!Carbon::today()->between($startWindow, $endWindow)) {
                throw ValidationException::withMessages([
                    'event_cost_id' => 'Het geselecteerde gebeurtenis valt niet binnen acceptabele periode. (3 maanden voor of na)'
                ]);
            }
            $validated['status'] = RequestStatus::ACCEPTED;
        }

        $requestCreate = RequestModel::create($validated);

        if ($requestCreate->status === RequestStatus::ACCEPTED)
            Mail::to($requestCreate->employee_requester)->send(
                new RequestAcceptedMail($requestCreate)
            );
        else
            Mail::to($requestCreate->employee_requester)->send(
                new RequestCreatedMail(
                    $member,
                    $request->input('iban'),
                    $request->input('account_name'),
                    $eventCost
                )
            );

        return redirect()->back()->with('toast',[
            'type' => 'success',
            'message' => 'Aanvraag succesvol ingediend.'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(RequestModel $requestModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RequestModel $requestModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RequestModel $requestModel)
    {
        //
        $validated = $request->validate([
            'status' => ['required', Rule::in([RequestStatus::ACCEPTED->value, RequestStatus::REJECTED->value, RequestStatus::PAID->value])],
            'reason' => ['nullable', 'string'],
        ]);

//        $response = $this->employees->get([
//            'take' => 1,
//            'filterfieldids' => 'Medewerker',
//            'filtervalues' => $requestModel->employee_requester,
//            'operatortypes' => 1
//        ]);

        $requestModel->load(['member' => fn($query) => $query->withTrashed()]);
        switch ($validated['status']) {
            case RequestStatus::ACCEPTED->value:
            case RequestStatus::REJECTED->value:
                if (!$requestModel->updateIfPending($validated))
                    return redirect()->back()->with('toast',[
                        'type' => 'error',
                        'message' => 'Alleen afwachtende (pending) aanvragen kunnen worden aangepast.'
                    ]);

                if ($requestModel->status === RequestStatus::REJECTED)
                    Mail::to($requestModel->employee_requester)->send(
                        new RequestRejectedMail($requestModel)
                    );
                elseif ($requestModel->status === RequestStatus::ACCEPTED)
                    Mail::to($requestModel->employee_requester)->send(
                        new RequestAcceptedMail($requestModel)
                    );

                return redirect()->back()->with('toast', [
                    'type' => 'success',
                    'message' => 'Aanvraag status was aangepast naar ' . $validated['status'] .'.',
                ]);
            case RequestStatus::PAID->value:
                if (!$request->session()->get('magic.admin.isSuper', false))
                    return redirect()->back()->withErrors([
                        'type' => 'error',
                        'message' => 'Niet een super administrator.'
                    ],'toast');

                $iban = $requestModel->iban;
                $account_name = $requestModel->account_name;

                if (!$requestModel->updateIfExported($validated))
                    return redirect()->back()->withErrors([
                        'type' => 'error',
                        'message' => 'Alleen geëxporteerde (exported) aanvragen kunnen worden aangepast.'
                    ],'toast');

                Mail::to($requestModel->employee_requester)->send(
                    new RequestPaidMail($requestModel, $iban, $account_name)
                );

                return redirect()->back()->with('toast', [
                    'type' => 'success',
                    'message' => 'Aanvraag status was aangepast naar ' . $validated['status'] . '.',
                ]);
            default:
                return redirect()->back()->withErrors([
                    'type' => 'error',
                    'message' => 'Onverwachten status was meegegeven'
                    ], 'toast');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RequestModel $requestModel)
    {
        //
    }
}
