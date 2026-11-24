<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Bill;
use App\Models\Drug;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
// use Illuminate\Support\;

class BillController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Bill::class, 'bill');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Bill::query()->select('bills.*');
        $lastDays = $request->input('lastDays', '30');
        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereRaw('bills.name LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('bills.last_name LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('bills.date_of_birth LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('bills.insurance LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('bills.place_of_birth LIKE ?', ['%' . $search . '%']);
            });
        }   
        if ($lastDays && $lastDays !== 'all') {
            if (is_numeric($lastDays)) {
                $dateFrom = Carbon::now()->subDays((int) $lastDays)->startOfDay();
                $query->where('created_at', '>=', $dateFrom);
            } elseif ($lastDays === 'month') {
                $dateFrom = Carbon::now()->startOfMonth();
                $query->where('created_at', '>=', $dateFrom);
            } elseif ($lastDays === 'year') {
                $dateFrom = Carbon::now()->startOfYear();
                $query->where('created_at', '>=', $dateFrom);
            }
        }
        $bills = $query->with('drugs', 'patient', 'user', 'BillDetails')->paginate(10);
        return Inertia::render('Bill/index', ['bills' => $bills, 'filters' => ['search' => $search,
        'lastDays' => $lastDays],]);
    }

    public function create()
    {
        $patients = Patient::query()->get(['id', 'name', 'last_name']);
        $users = User::query()->get(['id', 'name', 'last_name']);
        $drugs = Drug::query()->get(['id', 'name']);
        return Inertia::render('Bill/create',['patients' => $patients, 'users' => $users, 'drugs' => $drugs]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
        'form.drug_id' => 'required|exists:drugs,id',
        'form.itbis' => 'required|string|max:255',
        'form.sub_total' => 'required|string|max:255',
        'form.total' => 'required|string|max:255',
        'form.payment_type' => 'required|string|max:255',
        'form.status' => 'boolean',
        'form.patient_id' => 'required|exists:patients,id',
        'form.users_id' => 'required|exists:users,id',

        'details' => 'required|array',
        'details.*.service' => 'required|string|max:255',
        'details.*.hour' => 'required',
        'details.*.price' => 'required|numeric|min:0',
        ]);
        $bill = $validated['form'];
        $created_bill = Bill::create($bill);
        $details = array_map(
            fn (array $detail) => $detail + ['patients_id' => $bill['patient_id']],
            $validated['details']
        );
        $created_bill->BillDetails()->createMany($details);
        return redirect()->route('bills.index')->with('success', 'Factura creada correctamente.');
    }

    public function show(Bill $bill)
    {
        $bill->load('drugs', 'patient', 'user', 'BillDetails');
        return Inertia::render("Bill/show", ['bills' => $bill]);
    }

    public function edit(Bill $bill)
    {
        $users = User::query()->get(['id', 'name', 'last_name']);
        $drugs = Drug::query()->get(['id', 'name']);
        $bill->load('drugs', 'user', 'BillDetails');
        return Inertia::render('Bill/edit', ['bills' => $bill, 'users' => $users, 'drugs' => $drugs]);
    }

    public function update(Request $request, Bill $bill)
    {
        $validated = $request->validate([
        'form.drug_id' => 'required|exists:drugs,id',
        'form.itbis' => 'required|string|max:255',
        'form.sub_total' => 'required|string|max:255',
        'form.total' => 'required|string|max:255',
        'form.payment_type' => 'required|string|max:255',
        'form.status' => 'boolean',
        'form.patient_id' => 'required|exists:patients,id',
        'form.users_id' => 'required|exists:users,id',

        'details' => 'required|array',
        'details.*.service' => 'required|string|max:255',
        'details.*.hour' => 'required',
        'details.*.price' => 'required|numeric|min:0',
        ]); 
        $bill->update($validated['form']);

        $bill->BillDetails()->delete();
        $details = array_map(
            fn (array $detail) => $detail + ['patients_id' => $validated['form']['patient_id']],
            $validated['details']
        );
        $bill->BillDetails()->createMany($details);
        return redirect()->route('bills.index')->with('success', 'Factura actualizada correctamente.');
}

    public function destroy(Bill $bill)
    {
        //
    }
}
