<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
class UserController extends Controller
{
    public function usersList()
    {
        $users = User::with('details')
            ->withoutRole('Admin')
            ->orderBy('created_at', 'desc')
            ->get();

        // dd($users);
        return view('lms.pages.users-list', compact('users'));
    }

    public function usersAdd($id = null)
    {
        $user = $id ? User::with('details')->findOrFail($id) : new User();

        $teamleaders = User::role('TeamLeader')->get();
        $managers = User::role('Manager')->get();
        $clusters = User::role('Cluster')->get();

        $roles = Role::whereNotIn('name', ['Admin'])->get();

        return view('lms.pages.users-add', compact(
            'user',
            'roles',
            'clusters',
            'managers',
            'teamleaders'
        ));
    }

    public function storeOrUpdate(Request $request)
    {
        $id = $request->user_id;
        $isUpdate = $id ? true : false;

        $request->validate([
            'name' => 'required|string|max:255',

            'email' => $isUpdate
                ? 'required|email|unique:users,email,' . $id
                : 'required|email|unique:users,email',

            'phone' => 'nullable|string|max:20',

            'password' => $isUpdate
                ? 'nullable|min:6'
                : 'required|min:6',

            'role' => 'required|exists:roles,name',

            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'employee_id' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'designation' => 'nullable|string|max:100',

            'joining_date' => 'nullable|date',

            'cluster_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'teamleader_id' => 'nullable|exists:users,id',

            'status' => 'nullable|in:0,1'
        ]);

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | USER TABLE
            |--------------------------------------------------------------------------
            */

            $user = $isUpdate ? User::findOrFail($id) : new User;

            $user->name = $request->name;
            $user->email = $request->email;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();


            /*
            |--------------------------------------------------------------------------
            | ROLE (SPATIE)
            |--------------------------------------------------------------------------
            */

            if ($isUpdate) {
                $user->syncRoles([$request->role]);
            } else {
                $user->assignRole($request->role);
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();


            /*
            |--------------------------------------------------------------------------
            | USER DETAILS
            |--------------------------------------------------------------------------
            */

            $details = UserDetails::firstOrNew([
                'user_id' => $user->id
            ]);

            $details->phone = $request->phone;
            $details->employee_id = $request->employee_id;
            $details->department = $request->department;
            $details->designation = $request->designation;
            $details->joining_date = $request->joining_date;

            $details->cluster_id = $request->cluster_id;
            $details->manager_id = $request->manager_id;
            $details->teamleader_id = $request->teamleader_id;

            $details->status = $request->status ?? 1;


            /*
            |--------------------------------------------------------------------------
            | PROFILE PHOTO
            |--------------------------------------------------------------------------
            */

            if ($request->hasFile('profile_photo')) {

                $path = "users/{$user->id}/profile";

                if ($details->profile_photo) {
                    Storage::disk('public')->delete($details->profile_photo);
                }

                $file = $request->file('profile_photo');

                $fileName = uniqid() . '.' . $file->getClientOriginalExtension();

                $file->storeAs($path, $fileName, 'public');

                $details->profile_photo = $path . '/' . $fileName;
            }

            $details->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isUpdate
                    ? 'User updated successfully'
                    : 'User created successfully',
                'redirect_url' => route('lms.users.list')
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        $id = $request->id;
        DB::beginTransaction();

        try {

            $user = User::with('details')->findOrFail($id);
            if ($user->details && $user->details->profile_photo) {
                Storage::disk('public')
                    ->delete('profiles/' . $user->details->profile_photo);
            }
            $user->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function dashboard()
    {
        return view('task.pages.user-dashboard');
    }

    public function profile()
    {
        $user = UserDetails::where('user_id', auth()->id())->first();

        return view('task.pages.user-profile', compact('user'));
    }

    public function completeProfile()
    {
        $user = UserDetails::where('user_id', auth()->id())->first();

        return view('task.pages.complete-profile', compact('user'));
    }

    public function saveKyc(Request $request)
    {
        /* ================= VALIDATION ================= */

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users_details,user_id',

            'aadhar_front' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'aadhar_back' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'pan_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'bank_passbook' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'aadhar_number' => [
                'nullable',
                'digits:12',
                'regex:/^[2-9]{1}[0-9]{11}$/',
            ],

            'pancard_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            ],
            'kyc_status' => 'nullable|in:pending,approved,rejected',
            'address' => 'nullable|string|max:500',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        /* ================= FETCH USER DETAILS ================= */

        $details = UserDetails::where('user_id', $request->user_id)->firstOrFail();

        DB::beginTransaction();

        try {

            $kycDocs = $details->kyc_docs
                ? json_decode($details->kyc_docs, true)
                : [];

            $basePath = "users/{$details->user_id}/kyc";

            $kycFields = [
                'aadhar_front',
                'aadhar_back',
                'pan_card',
                'bank_passbook',
            ];

            $fileUploaded = false;

            foreach ($kycFields as $field) {

                if ($request->hasFile($field)) {

                    $fileUploaded = true;

                    // Delete old file if exists
                    if (!empty($kycDocs[$field])) {
                        Storage::disk('public')
                            ->delete($basePath . '/' . $kycDocs[$field]);
                    }

                    $file = $request->file($field);

                    $fileName = uniqid($field . '_') . '.' .
                        $file->getClientOriginalExtension();

                    $file->storeAs($basePath, $fileName, 'public');

                    $kycDocs[$field] = $fileName;
                }
            }

            if (!$fileUploaded) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please upload at least one document.',
                ], 400);
            }

            /* ================= SAVE ================= */
            $kycDocs['aadhar_number'] = $request->aadhar_number;
            $kycDocs['pancard_number'] = $request->pancard_number;
            $kycDocs['address'] = $request->address;
            $details->kyc_docs = json_encode($kycDocs);
            $details->kyc_status = 'pending';
            $details->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'KYC saved successfully.',
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function saveQualification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users_details,user_id',

            'school_10' => 'required|string|max:255',
            'school_12' => 'nullable|string|max:255',
            'highest_qualification' => 'nullable|string|max:255',
            'institute' => 'nullable|string|max:255',

            'marksheet_10' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'marksheet_12' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'degree_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $details = UserDetails::where('user_id', $request->user_id)->firstOrFail();

        DB::beginTransaction();

        try {

            $qualificationDocs = $details->qualifications_docs
                ? json_decode($details->qualifications_docs, true)
                : [];
            $basePath = "users/{$details->user_id}/qualification";
            $qualificationDocs['school_10'] = $request->school_10;
            $qualificationDocs['school_12'] = $request->school_12;
            $qualificationDocs['highest_qualification'] = $request->highest_qualification;
            $qualificationDocs['institute'] = $request->institute;

            $fileFields = [
                'marksheet_10',
                'marksheet_12',
                'degree_certificate',
            ];

            $fileUploaded = false;

            foreach ($fileFields as $field) {

                if ($request->hasFile($field)) {

                    $fileUploaded = true;
                    if (!empty($qualificationDocs[$field])) {
                        Storage::disk('public')->delete(
                            $basePath . '/' . $qualificationDocs[$field]
                        );
                    }

                    $file = $request->file($field);

                    $fileName = uniqid($field . '_') . '.' .
                        $file->getClientOriginalExtension();

                    $file->storeAs($basePath, $fileName, 'public');

                    $qualificationDocs[$field] = $fileName;
                }
            }

            // If marksheet_10 not uploaded and not already exists
            if (empty($qualificationDocs['marksheet_10'])) {
                return response()->json([
                    'status' => false,
                    'message' => '10th marksheet is required.',
                ], 400);
            }

            /* ================= SAVE DATA ================= */

            $details->qualifications_docs = json_encode($qualificationDocs);
            $details->qualifications_status = 'pending';
            $details->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Qualification saved successfully',
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function saveJobDetails(Request $request)
    {
        /* ================= VALIDATION ================= */

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users_details,user_id',
            'job_type' => 'required|in:fresher,experienced',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:4096',
            'offer_letter' => 'nullable|file|mimes:pdf,doc,docx|max:4096',
            'experience_letter' => 'nullable|file|mimes:pdf,doc,docx|max:4096',
            'registration_letter' => 'nullable|file|mimes:pdf,doc,docx|max:4096',
            'company_name' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'salary' => 'nullable|string|max:255',
            'experience_years' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $details = UserDetails::where('user_id', $request->user_id)->firstOrFail();

        DB::beginTransaction();

        try {

            $jobDocs = $details->job_docs
                ? json_decode($details->job_docs, true)
                : [];

            $basePath = "users/{$details->user_id}/job";

            if ($request->job_type === 'fresher') {

                // Delete experienced files if switching
                foreach (['offer_letter', 'experience_letter', 'registration_letter'] as $field) {
                    if (!empty($jobDocs[$field])) {
                        Storage::disk('public')->delete($basePath . '/' . $jobDocs[$field]);
                    }
                }

                $newDocs = [
                    'job_type' => 'fresher',
                ];

                if ($request->hasFile('resume')) {

                    if (!empty($jobDocs['resume'])) {
                        Storage::disk('public')->delete($basePath . '/' . $jobDocs['resume']);
                    }

                    $file = $request->file('resume');
                    $fileName = uniqid('resume_') . '.' . $file->getClientOriginalExtension();
                    $file->storeAs($basePath, $fileName, 'public');

                    $newDocs['resume'] = $fileName;

                } elseif (empty($jobDocs['resume'])) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Resume is required for fresher.',
                    ], 400);
                }

                $jobDocs = array_merge($jobDocs, $newDocs);
            }

            /* ================= HANDLE EXPERIENCED ================= */

            if ($request->job_type === 'experienced') {

                // Delete resume if switching
                if (!empty($jobDocs['resume'])) {
                    Storage::disk('public')->delete($basePath . '/' . $jobDocs['resume']);
                }

                $jobDocs['job_type'] = 'experienced';
                $jobDocs['company_name'] = $request->company_name;
                $jobDocs['position'] = $request->position;
                $jobDocs['salary'] = $request->salary;
                $jobDocs['experience_years'] = $request->experience_years;

                $fileFields = ['offer_letter', 'experience_letter', 'registration_letter'];

                foreach ($fileFields as $field) {

                    if ($request->hasFile($field)) {

                        if (!empty($jobDocs[$field])) {
                            Storage::disk('public')->delete($basePath . '/' . $jobDocs[$field]);
                        }

                        $file = $request->file($field);
                        $fileName = uniqid($field . '_') . '.' . $file->getClientOriginalExtension();
                        $file->storeAs($basePath, $fileName, 'public');

                        $jobDocs[$field] = $fileName;
                    }
                }

                if (empty($jobDocs['experience_letter'])) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Experience letter is required for experienced candidates.',
                    ], 400);
                }
            }

            /* ================= SAVE ================= */

            $details->job_docs = json_encode($jobDocs);
            $details->jobs_status = 'pending';
            $details->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Job details saved successfully',
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {

            $request->validate([
                'current_password' => ['required'],
                'new_password' => ['required', 'string', 'min:6', 'confirmed'],
            ]);

            $user = Auth::user();

            // Check current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.',
                ], 422);
            }

            // Prevent same password reuse
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password cannot be same as current password.',
                ], 422);
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

            // Optional: Logout from other devices
            Auth::logoutOtherDevices($request->new_password);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully.',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $details = UserDetails::where('user_id', auth()->id())->first();

        if ($request->hasFile('profile_photo')) {

            $file = $request->file('profile_photo');
            $basePath = "users/{$details->user_id}/profile";
            if (
                !empty($details->profile_photo) &&
                Storage::disk('public')->exists("{$basePath}/{$details->profile_photo}")
            ) {

                Storage::disk('public')->delete("{$basePath}/{$details->profile_photo}");
            }

            // Generate unique filename
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Store new file
            $file->storeAs($basePath, $fileName, 'public');

            // Save in DB
            $details->profile_photo = $fileName;
            $details->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully.',
                'profile_photo_url' => asset("storage/{$basePath}/{$fileName}"),
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file uploaded.',
        ], 400);
    }
}
