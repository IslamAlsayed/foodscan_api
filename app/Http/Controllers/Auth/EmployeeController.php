<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Exception;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\AuthUpdateRequest;
use App\Http\Resources\Auth\AuthShowResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Handles employee authentication and authorization.
 *
 * @author IslamAlsayed eslamalsayed8133@gmail.com
 */
class EmployeeController extends Controller
{
    /**
     * Get the employee data.
     *
     * @param Request $request Request object from the admin controller or any other controller.
     * @return AuthShowResource object containing the employee data.
     */
    public function index()
    {
        try {
            $employees = Employee::where('status', '1')->get();

            if ($employees->isEmpty()) {
                return response()->json(['status' => 'failed', 'message' => 'No active employees found'], 404);
            }

            return response()->json(['status' => 'success', 'data' => AuthShowResource::collection($employees)], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'failed', 'message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the employee data.
     * 
     * @param Request $request Request object from the admin controller or any other controller.
     * @return AuthShowResource object containing the employee data.
     */
    public function show($id)
    {
        try {
            $employee = Employee::findOrFail($id);
            if ($employee->status != 1) {
                return response()->json(['status' => 'failed', 'message' => 'Your account is not active'], 403);
            }

            return response()->json(['status' => 'success', 'data' => new AuthShowResource($employee)], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'failed', 'message' => 'Employee not found'], 404);
        } catch (Exception $e) {
            return response()->json(['status' => 'failed', 'message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the employee data.
     * 
     * @param AuthShowResource $employee employee object.
     * @return AuthShowResource object containing the updated employee data.
     */
    public function update(AuthUpdateRequest $request,  $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            if ($employee->status != 1) {
                return response()->json(['status' => 'failed', 'message' => 'Your account is not active'], 403);
            }

            $updateData = $request->only(['name', 'email', 'phone', 'role', 'status']);

            if (empty($updateData)) {
                return response()->json(['status' => 'failed', 'message' => 'No valid data to update'], 400);
            }

            if ($request->filled('password')) {
                $employee->password = Hash::make($request->input('password'));
            }

            $employee->update($updateData);
            return response()->json(['status' => 'success', 'data' => new AuthShowResource($employee), 'message' => 'Employee updated successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'failed', 'message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Updates an existing employee's status.
     * 
     * @param Request $request Request object containing the updated status.
     * @return Response JSON response indicating the success or failure of the operation.
     */
    public function updateStatus(Request $request,  $id)
    {
        $request->validate(['status' => 'required|in:0,1']);

        try {
            $employee = Employee::findOrFail($id);

            if ($employee->status == 1) {
                return response()->json(['status' => 'failed', 'message' => 'Your account is active already'], 403);
            }

            $updateSuccessful = $employee->update($request->only(['status']));

            if ($updateSuccessful && $employee->wasChanged('status')) {
                return response()->json(['status' => 'success', 'data' => ['status' => $employee->status], 'message' => 'Employee status updated successfully'], 200);
            }

            return response()->json(['status' => 'failed', 'message' => 'Status has not changed.'], 400);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'failed', 'message' => 'Employee not found'], 404);
        } catch (Exception $e) {
            return response()->json(['status' => 'failed', 'message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * destroy employee if status account is activated.
     * 
     * @param int $id The ID of the employee to be updated.
     * @return Response JSON response indicating the success or failure of the operation.
     */
    public function destroy($id)
    {
        try {
            $employee = Employee::findOrFail($id);

            if ($employee->status != 1) {
                return response()->json(['status' => 'failed', 'message' => 'Employee account is not active'], 403);
            }

            $employee->delete();

            return response()->json(['status' => 'success', 'message' => 'Employee deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'failed', 'message' => 'Employee not found'], 404);
        } catch (Exception $e) {
            return response()->json(['status' => 'failed', 'message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }
}
