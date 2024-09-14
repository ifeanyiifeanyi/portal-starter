<?php

namespace App\Http\Controllers\Admin;

use App\Models\Semester;
use App\Models\Department;
use App\Models\PaymentType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminPaymentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $paymentTypes = PaymentType::with('departments')->get();
        return view('admin.paymentTypes.index', compact('paymentTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::all();
        $academic_sessions = AcademicSession::all();
        $semesters = Semester::all();
        return view('admin.paymentTypes.create', compact(
            'departments',
            'academic_sessions',
            'semesters'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
            'department_id' => 'required|exists:departments,id',
            'department_id' => 'required|exists:departments,id',
            'levels' => 'required|array',
            'levels.*' => 'integer|min:100|max:600',
            'is_active' => 'required|boolean',
            'amount' => 'required|numeric',
            'description' => 'required|string',
        ]);
        $paymentType = PaymentType::create([
            'name' => $validated['name'],
            'academic_session_id' => $validated['academic_session_id'],
            'semester_id' => $validated['semester_id'],
            'is_active' => $request->has('is_active'),
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'slug' => Str::slug($validated['name'])
        ]);


        $departmentId = $validated['department_id'];
        $levels = $validated['levels'];

        foreach ($levels as $level) {
            DB::table('department_payment_type')->insert([
                'department_id' => $departmentId,
                'payment_type_id' => $paymentType->id,
                'level' => $level,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }



        return redirect()->route('admin.payment_type.index')->with([
            'message' => 'Payment Type Created Successfully!!',
            'alert-type' => 'success'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentType $paymentType)
    {
        // dd($paymentType);
        return view('admin.paymentTypes.show', compact('paymentType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentType $paymentType)
    {
        $departments = Department::all();
        $academicSessions = AcademicSession::all();
        $semesters = Semester::all();
        $paymentType->load('departments');
        return view('admin.paymentTypes.edit', compact('paymentType', 'departments', 'academicSessions', 'semesters'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentType $paymentType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'levels' => 'required|array',
            'levels.*' => 'integer|min:100|max:600',
            'is_active' => 'required|boolean',
            'amount' => 'required|numeric',
            'description' => 'required|string',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $paymentType->update([
            'name' => $validated['name'],
            'is_active' => $request->has('is_active'),
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'slug' => Str::slug($validated['name']),
            'academic_session_id' => $validated['academic_session_id'],
            'semester_id' => $validated['semester_id'],
        ]);

        $departmentId = $validated['department_id'];
        $levels = $validated['levels'];

        // Remove existing relationships for this payment type and department
        DB::table('department_payment_type')
            ->where('payment_type_id', $paymentType->id)
            ->where('department_id', $departmentId)
            ->delete();

        // Insert new relationships
        foreach ($levels as $level) {
            DB::table('department_payment_type')->insert([
                'department_id' => $departmentId,
                'payment_type_id' => $paymentType->id,
                'level' => $level,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Remove any old relationships that are no longer valid
        DB::table('department_payment_type')
            ->where('payment_type_id', $paymentType->id)
            ->where('department_id', '!=', $departmentId)
            ->delete();

        return redirect()->route('admin.payment_type.index')->with([
            'message' => 'Payment Type Updated Successfully!!',
            'alert-type' => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentType $paymentType)
    {
        if (Auth::check()) {
            $paymentType->delete();
            return redirect()->route('admin.payment_type.index')->with([
                'message' => 'Payment Type Deleted Successfully!!',
                'alert-type' => 'success'
            ]);
        }
    }
}
