<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Donation;
use App\Models\Notice;
use App\Models\ProgressReport;
use App\Models\Student;
use App\Models\StudentDeviceToken;
use App\Models\StudentMaterial;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

class StudentApiController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'database' => DB::connection()->getDatabaseName(),
        ]);
    }

    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'The Laravel API is connected and working!',
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile_number' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $mobileNumber = preg_replace('/\D+/', '', $this->clean($data['mobile_number']));
        $password = $this->clean($data['password']);

        $student = Student::query()
            ->where('mobile_number', $mobileNumber)
            ->where('password', $password)
            ->first();

        if (! $student) {
            return response()->json([
                'message' => 'Invalid mobile number or password.',
            ], 401);
        }

        if ($student->status !== 'ongoing') {
            return response()->json([
                'message' => 'Your account is not active.',
                'status' => $student->status,
            ], 403);
        }

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $this->makeToken($student),
            'student' => $this->studentPayload($student),
        ]);
    }

    public function registrationOptions(): JsonResponse
    {
        return response()->json([
            'data' => [
                'classes' => $this->table('class_session')
                    ->where('class', '!=', '')
                    ->distinct()
                    ->orderBy('class')
                    ->pluck('class')
                    ->unique()
                    ->values(),
                'sessions' => $this->table('class_session')
                    ->where('session', '!=', '')
                    ->distinct()
                    ->orderByDesc('session')
                    ->pluck('session')
                    ->unique()
                    ->values(),
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:15'],
            'password' => ['required', 'string', 'min:4'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'school_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'class' => ['required'],
            'session' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $mobileNumber = preg_replace('/\D+/', '', $this->clean($data['mobile_number']));
        $classes = is_array($data['class']) ? implode(', ', $data['class']) : $data['class'];

        if (Student::query()->where('mobile_number', $mobileNumber)->orWhere('email', $data['email'])->exists()) {
            return response()->json([
                'message' => 'Phone number or email already registered.',
            ], 422);
        }

        $image = null;
        if ($request->hasFile('image')) {
            $image = $this->saveUploadedFile($request->file('image'), base_path('../img'), '../img');
        }

        $student = new Student();
        $student->name = $this->clean($data['name']);
        $student->mobile_number = $mobileNumber;
        $student->password = $this->clean($data['password']);
        $student->email = $this->clean($data['email']);
        $student->address = $this->clean($data['address']);
        $student->father_name = $this->clean($data['father_name']);
        $student->school_name = $this->clean($data['school_name']);
        $student->date_of_birth = $request->input('date_of_birth');
        $student->last_percentage = '00';
        $student->course = $request->input('course', 'no');
        $student->class = $classes;
        $student->session = $this->clean($data['session']);
        $student->total_fees = $request->input('total_fees', 0);
        $student->paid_fees = 0;
        $student->registration_code = $this->uniqueRegistrationCode();
        $student->image = $image ?? '';
        $student->status = 'suspended';
        $student->date = now()->toDateString();
        $student->save();

        return response()->json([
            'message' => 'Registration submitted. Please contact admin for approval.',
            'student' => $this->studentPayload($student),
        ], 201);
    }

    public function home(Request $request): JsonResponse
    {
        $student = $this->optionalStudentFromRequest($request);
        $company = Company::query()->first();

        return response()->json([
            'company' => $company ? $this->withImageUrl($company->toArray()) : null,
            'student' => $student ? $this->studentPayload($student) : null,
            'sliders' => $this->mapImageUrls($this->table('slider')->latest('id')->limit(10)->get()),
            'events' => $this->mapImageUrls($this->table('event')->latest('id')->limit(10)->get()),
            'promotions' => $this->mapImageUrls(
                $this->table('gallery')
                    ->whereIn('type', ['promotional', 'Promotional'])
                    ->latest('id')
                    ->limit(10)
                    ->get()
            ),
            'notices_count' => $student ? Notice::query()->where('student_id', $student->id)->where('seen', 0)->count() : 0,
            'has_pending_fees' => $student ? $this->hasPendingFees($student) : false,
            'unvoted_polls' => $student ? $this->unvotedPollsForStudent($student) : [],
        ]);
    }

    public function company(): JsonResponse
    {
        $company = Company::query()->first();

        return response()->json([
            'data' => $company ? $this->withImageUrl($company->toArray()) : null,
        ]);
    }

    public function about(): JsonResponse
    {
        $company = Company::query()->first();
        $blog = $this->table('blog')->orderByDesc('id')->first();
        $companyData = $company ? $this->withImageUrl($company->toArray()) : null;

        if ($companyData && ! empty($companyData['address'])) {
            $companyData['map_url'] = 'https://www.google.com/maps/search/?api=1&query='.urlencode($companyData['address']);
        }

        return response()->json([
            'data' => [
                'company' => $companyData,
                'about' => $blog ? [
                    'title' => $blog->name ?? 'About Us',
                    'details' => $blog->details ?? '',
                    'image' => $blog->image ?? null,
                    'image_url' => $this->assetUrl($blog->image ?? null),
                    'date' => $blog->date ?? null,
                ] : null,
            ],
        ]);
    }

    public function legalPolicies(): JsonResponse
    {
        return response()->json([
            'data' => [
                'title' => 'Legal and Policies',
                'sections' => [
                    [
                        'title' => 'Terms of Use',
                        'items' => [
                            'By using the Dey Education app and services, you agree to follow academy rules and guidelines.',
                            'Course materials, videos, and documents are for enrolled students only and must not be shared publicly.',
                            'Misuse of login credentials or sharing account access may lead to suspension.',
                            'Fees and payments must be completed as per the schedule communicated by the academy.',
                        ],
                    ],
                    [
                        'title' => 'Privacy Policy',
                        'items' => [
                            'We collect student name, contact details, class information, and academic records to provide our services.',
                            'Profile photos and submitted documents are stored securely and used only for academy purposes.',
                            'We do not sell personal data to third parties.',
                            'You may contact the academy to update or correct your personal information.',
                        ],
                    ],
                    [
                        'title' => 'Refund & Cancellation',
                        'items' => [
                            'Admission and fee refund policies follow the rules stated at the time of enrollment.',
                            'Partial refunds, if applicable, are processed only after admin review.',
                            'Course transfers or batch changes are subject to seat availability and admin approval.',
                        ],
                    ],
                    [
                        'title' => 'Code of Conduct',
                        'items' => [
                            'Students must maintain respectful behaviour with teachers, staff, and fellow students.',
                            'Harassment, cheating, or disruptive conduct in class or online platforms is not permitted.',
                            'The academy reserves the right to take disciplinary action when rules are violated.',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function enquiryCategories(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['id' => 'academic', 'label' => 'Academic'],
                ['id' => 'financial', 'label' => 'Financial'],
                ['id' => 'technical', 'label' => 'Technical Support'],
                ['id' => 'facilities', 'label' => 'Facilities'],
                ['id' => 'other', 'label' => 'Others'],
            ],
        ]);
    }

    public function classes(): JsonResponse
    {
        return response()->json([
            'data' => $this->table('class_session')
                ->select('class', 'session', 'subject', 'status')
                ->where('status', 'active')
                ->orderBy('class')
                ->get(),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'student' => $this->studentPayload($this->studentFromRequest($request)),
            'company' => Company::query()->first(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'address' => ['sometimes', 'string', 'max:255'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:10'],
            'father_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'school_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $student->fill($data);
        $student->save();

        return response()->json([
            'message' => 'Profile updated.',
            'student' => $this->studentPayload($student->fresh()),
        ]);
    }

    public function uploadProfilePhoto(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $student->image = $this->saveUploadedFile($request->file('image'), base_path('../img'), '../img');
        $student->save();

        return response()->json([
            'message' => 'Profile photo updated.',
            'student' => $this->studentPayload($student),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:4'],
        ]);

        if ($student->password !== $this->clean($data['current_password'])) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $student->password = $this->clean($data['new_password']);
        $student->save();

        return response()->json([
            'message' => 'Password changed.',
        ]);
    }

    public function notices(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);

        $paginator = Notice::query()
            ->where('student_id', $student->id)
            ->latest('created_at')
            ->paginate($request->integer('per_page', 20));

        $paginator->getCollection()->transform(fn (Notice $notice) => $this->noticePayload($notice));

        return response()->json([
            'unread_count' => Notice::query()
                ->where('student_id', $student->id)
                ->where('seen', 0)
                ->count(),
            'data' => $paginator,
        ]);
    }

    public function materials(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);

        $query = StudentMaterial::query();
        $this->applyMaterialAccessFilter($query, $student);

        return response()->json([
            'data' => $this->transformPaginator(
                $query->latest('upload_date')->paginate($request->integer('per_page', 20))
            ),
        ]);
    }

    public function material(Request $request, int $id): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $material = StudentMaterial::query()->findOrFail($id);

        if (! $this->canAccessMaterial($student, $material)) {
            return response()->json([
                'message' => 'You do not have access to this material.',
            ], 403);
        }

        return response()->json([
            'data' => $this->materialPayload($material, detailed: true),
            'related' => StudentMaterial::query()
                ->where('id', '!=', $material->id)
                ->where('subject', $material->subject)
                ->limit(4)
                ->get()
                ->map(fn (StudentMaterial $related) => $this->materialPayload($related))
                ->values(),
            'can_access' => true,
        ]);
    }

    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'is_favorite' => ['required', 'boolean'],
        ]);

        $updated = StudentMaterial::query()
            ->where('id', $id)
            ->where('student_id', $student->id)
            ->update(['is_favorite' => $data['is_favorite']]);

        return response()->json([
            'message' => $updated ? 'Favorite updated.' : 'Material is not assigned to this student.',
            'updated' => (bool) $updated,
        ], $updated ? 200 : 404);
    }

    public function progress(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);

        return response()->json([
            'data' => ProgressReport::query()
                ->where('student_id', $student->id)
                ->latest('report_date')
                ->paginate($request->integer('per_page', 20)),
        ]);
    }

    public function fees(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $payments = Donation::query()
            ->where('student_registration_code', $student->registration_code)
            ->select(['donation_date', 'payment_reason', 'status', 'created_at']);

        if ($request->filled('year')) {
            $payments->whereYear('donation_date', $request->integer('year'));
        }

        if ($request->filled('month')) {
            $payments->where('payment_reason', $request->input('month'));
        }

        $paginated = $payments
            ->latest('created_at')
            ->paginate($request->integer('per_page', 50));

        $paginated->getCollection()->transform(function ($payment) {
            $date = $payment->donation_date;
            $year = $date ? $date->format('Y') : '';
            $month = trim((string) ($payment->payment_reason ?? ''));
            $payment->fee_period = $month !== '' && $year !== ''
                ? $month.' '.$year
                : ($month !== '' ? $month : $year);

            return $payment;
        });

        return response()->json([
            'student' => [
                'name' => $student->name,
                'registration_code' => $student->registration_code,
                'mobile_number' => $student->mobile_number,
                'email' => $student->email,
            ],
            'months_by_year' => $this->feeMonthsByYear($student->registration_code),
            'record_count' => $paginated->total(),
            'payments' => $paginated,
        ]);
    }

    private function feeMonthsByYear(string $registrationCode): array
    {
        $rows = Donation::query()
            ->where('student_registration_code', $registrationCode)
            ->whereNotNull('donation_date')
            ->orderByDesc('donation_date')
            ->get(['donation_date', 'payment_reason']);

        $grouped = [];
        foreach ($rows as $row) {
            $year = (int) $row->donation_date->format('Y');
            $month = trim((string) ($row->payment_reason ?? ''));
            if ($month === '') {
                continue;
            }
            $grouped[$year][$month] = true;
        }

        krsort($grouped);
        $result = [];
        foreach ($grouped as $year => $months) {
            $monthNames = array_keys($months);
            usort($monthNames, function (string $a, string $b) use ($year) {
                $aNum = (int) date('n', strtotime($a.' 1 '.$year));
                $bNum = (int) date('n', strtotime($b.' 1 '.$year));

                return $bNum <=> $aNum;
            });
            $result[] = [
                'year' => (int) $year,
                'months' => array_values($monthNames),
            ];
        }

        return $result;
    }

    public function markNoticeSeen(Request $request, int $id): JsonResponse
    {
        $student = $this->studentFromRequest($request);

        $updated = Notice::query()
            ->where('id', $id)
            ->where('student_id', $student->id)
            ->update(['seen' => 1]);

        return response()->json([
            'message' => $updated ? 'Notice marked as seen.' : 'Notice not found.',
            'updated' => (bool) $updated,
        ], $updated ? 200 : 404);
    }

    public function registerDeviceToken(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['nullable', 'string', 'max:32'],
        ]);

        StudentDeviceToken::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'student_id' => $student->id,
                'platform' => $data['platform'] ?? 'android',
            ]
        );

        return response()->json([
            'message' => 'Device token registered.',
            'student_id' => $student->id,
        ]);
    }

    public function unregisterDeviceToken(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        StudentDeviceToken::query()
            ->where('student_id', $student->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json([
            'message' => 'Device token removed.',
        ]);
    }

    public function pushNotices(Request $request, FcmService $fcm): JsonResponse
    {
        $secret = (string) $request->header('X-Push-Secret', $request->input('secret', ''));
        $expected = (string) config('services.fcm.push_secret');
        if ($expected === '' || ! hash_equals($expected, $secret)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:500'],
            'notice_type' => ['nullable', 'string', 'max:32'],
        ]);

        $title = trim((string) ($data['title'] ?? 'New notice'));
        $body = trim((string) ($data['body'] ?? 'You have a new notice.'));
        if ($title === '') {
            $title = 'New notice';
        }
        if ($body === '') {
            $body = 'You have a new notice.';
        }

        $result = $fcm->sendToStudents(
            $data['student_ids'],
            $title,
            $body,
            [
                'type' => 'notice',
                'screen' => 'notices',
                'notice_type' => (string) ($data['notice_type'] ?? 'text'),
            ]
        );

        return response()->json([
            'message' => 'Push dispatch completed.',
            'result' => $result,
            'configured' => $fcm->isConfigured(),
        ]);
    }

    public function attendance(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        if (! empty($data['month'])) {
            $from = \Carbon\Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth()->toDateString();
            $to = \Carbon\Carbon::createFromFormat('Y-m', $data['month'])->endOfMonth()->toDateString();
        } else {
            $from = $data['from'] ?? now()->startOfMonth()->toDateString();
            $to = $data['to'] ?? now()->toDateString();
        }

        $records = $this->table('attendance')
            ->where('student_id', $student->id)
            ->whereBetween('attendance_date', [$from, $to])
            ->orderByDesc('attendance_date')
            ->get();

        $totalDays = $records->count();
        $presentDays = $records->where('status', 'Present')->count();
        $absentDays = $totalDays - $presentDays;
        $attendancePercentage = $totalDays > 0 ? (int) round(($presentDays / $totalDays) * 100) : 0;

        $perPage = $request->integer('per_page', 31);
        $page = max(1, $request->integer('page', 1));
        $paginated = $records->forPage($page, $perPage)->values();

        return response()->json([
            'summary' => [
                'total_days' => $totalDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'attendance_percentage' => $attendancePercentage,
                'month' => substr($from, 0, 7),
            ],
            'student' => [
                'name' => $student->name,
                'registration_code' => $student->registration_code,
                'class' => $student->class,
            ],
            'data' => [
                'current_page' => $page,
                'data' => $paginated,
                'per_page' => $perPage,
                'total' => $totalDays,
            ],
        ]);
    }

    public function homework(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $homework = $this->table('homework_assignments')
            ->where('class', $student->class)
            ->where('session', $student->session)
            ->orderByDesc('deadline')
            ->paginate($request->integer('per_page', 20));

        $submissions = $this->table('homework_submissions')
            ->where('student_id', $student->id)
            ->orderByDesc('submission_date')
            ->get()
            ->groupBy('homework_id')
            ->map(fn ($items) => $items->first());

        return response()->json([
            'data' => $homework,
            'submissions' => $submissions,
        ]);
    }

    public function submitHomework(Request $request, int $id): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $request->validate([
            'homework_file' => ['required', 'file', 'max:5120'],
            'comments' => ['nullable', 'string'],
        ]);

        $homework = $this->table('homework_assignments')
            ->where('id', $id)
            ->where('class', $student->class)
            ->where('session', $student->session)
            ->first();

        if (! $homework) {
            return response()->json([
                'message' => 'Homework not found for this student.',
            ], 404);
        }

        $filePath = $this->saveUploadedFile($request->file('homework_file'), base_path('../pages/uploads'), '');
        $submissionId = $this->table('homework_submissions')->insertGetId([
            'student_id' => $student->id,
            'homework_id' => $id,
            'file_path' => basename($filePath),
            'comments' => $request->input('comments'),
            'submission_date' => now(),
        ]);

        return response()->json([
            'message' => 'Homework submitted.',
            'submission' => $this->table('homework_submissions')->where('id', $submissionId)->first(),
        ], 201);
    }

    public function enquiries(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $paginator = $this->enquiriesQuery($student)
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        $paginator->setCollection(
            $paginator->getCollection()->map(fn ($row) => $this->enquiryPayload((array) $row))
        );

        return response()->json([
            'data' => $paginator,
        ]);
    }

    public function showEnquiry(Request $request, int $id): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $enquiry = $this->enquiriesQuery($student)->where('id', $id)->first();

        if (! $enquiry) {
            return response()->json(['message' => 'Enquiry not found.'], 404);
        }

        return response()->json([
            'data' => $this->enquiryPayload((array) $enquiry, true),
        ]);
    }

    public function createEnquiry(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'enquiry_type' => ['required', 'string', Rule::in(['academic', 'financial', 'technical', 'facilities', 'other'])],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
        ]);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $attachment = basename($this->saveUploadedFile($request->file('attachment'), base_path('../pages/uploads'), ''));
        }

        $id = $this->table('enquiries')->insertGetId([
            'student_id' => $student->registration_code,
            'name' => $student->name,
            'email' => $student->email,
            'phone' => $student->mobile_number,
            'enquiry_type' => $data['enquiry_type'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'attachment' => $attachment,
            'created_at' => now(),
        ]);

        $this->insertEnquiryMessage((int) $id, 'student', $student->name, $data['message'], $attachment);

        $enquiry = $this->table('enquiries')->where('id', $id)->first();

        return response()->json([
            'message' => 'Enquiry submitted.',
            'data' => $this->enquiryPayload((array) $enquiry, true),
        ], 201);
    }

    public function addEnquiryMessage(Request $request, int $id): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $enquiry = $this->enquiriesQuery($student)->where('id', $id)->first();

        if (! $enquiry) {
            return response()->json(['message' => 'Enquiry not found.'], 404);
        }

        $data = $request->validate([
            'message' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
        ]);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $attachment = basename($this->saveUploadedFile($request->file('attachment'), base_path('../pages/uploads'), ''));
        }

        $this->insertEnquiryMessage($id, 'student', $student->name, $data['message'], $attachment);

        return response()->json([
            'message' => 'Message sent.',
            'data' => $this->enquiryPayload((array) $this->table('enquiries')->where('id', $id)->first(), true),
        ], 201);
    }

    public function paymentMethods(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);

        return response()->json([
            'data' => $this->table('student_payments')
                ->where('student_id', $student->id)
                ->orderByDesc('is_default')
                ->orderBy('is_qr_code')
                ->get(),
        ]);
    }

    public function addPaymentMethod(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['card', 'qr'])],
            'card_type' => ['nullable', 'string', 'max:50'],
            'card_number' => ['nullable', 'string', 'max:50'],
            'card_holder_name' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'string', 'max:10'],
            'qr_code_image' => ['nullable', 'image', 'max:5120'],
            'qr_code_details' => ['nullable', 'string'],
        ]);

        $payload = [
            'student_id' => $student->id,
            'payment_method' => $data['payment_method'],
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($data['payment_method'] === 'card') {
            $cardNumber = preg_replace('/\D+/', '', $data['card_number'] ?? '');
            $payload += [
                'card_type' => $data['card_type'] ?? null,
                'card_number' => $cardNumber,
                'card_last_four' => substr($cardNumber, -4),
                'card_holder_name' => $data['card_holder_name'] ?? '',
                'expiry_date' => $data['expiry_date'] ?? null,
                'is_qr_code' => 0,
            ];
        } else {
            $payload += [
                'card_number' => '',
                'card_last_four' => '',
                'card_holder_name' => '',
                'is_qr_code' => 1,
                'qr_code_details' => $data['qr_code_details'] ?? null,
                'qr_code_image' => $request->hasFile('qr_code_image')
                    ? $this->saveUploadedFile($request->file('qr_code_image'), base_path('../pages/uploads'), '')
                    : null,
            ];
        }

        $id = $this->table('student_payments')->insertGetId($payload);

        return response()->json([
            'message' => 'Payment method added.',
            'data' => $this->table('student_payments')->where('id', $id)->first(),
        ], 201);
    }

    public function reminders(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);

        return response()->json([
            'exam' => $this->table('exam_reminder')
                ->where('student_code', $student->registration_code)
                ->orWhere('student_name', $student->name)
                ->orderByDesc('id')
                ->get(),
            'fees' => $this->table('fees_reminder')
                ->where('student_code', $student->registration_code)
                ->orWhere('student_name', $student->name)
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function admission(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);

        return response()->json([
            'data' => $this->table('admission')
                ->where('code_no', $student->registration_code)
                ->orWhere('phone', $student->mobile_number)
                ->orWhere('student_name', $student->name)
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function polls(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $polls = $this->pollsVisibleToStudent($student);

        $pollIds = $polls->pluck('id');
        $options = $this->table('poll_options')->whereIn('poll_id', $pollIds)->get()->groupBy('poll_id');
        $votes = $this->table('poll_votes')->where('student_id', $student->id)->whereIn('poll_id', $pollIds)->get()->keyBy('poll_id');

        return response()->json([
            'data' => $polls->map(function ($poll) use ($options, $votes) {
                $myVote = $votes->get($poll->id);
                $payload = [
                    'poll' => $poll,
                    'options' => $options->get($poll->id, collect())->values(),
                    'my_vote' => $myVote,
                    'has_voted' => $myVote !== null,
                ];
                if ($myVote !== null) {
                    $payload['results'] = $this->pollResults((int) $poll->id);
                }

                return $payload;
            })->values(),
        ]);
    }

    public function showPoll(Request $request, int $id): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $poll = $this->pollsVisibleToStudent($student)->firstWhere('id', $id);

        if (! $poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $myVote = $this->table('poll_votes')
            ->where('poll_id', $id)
            ->where('student_id', $student->id)
            ->first();

        $payload = [
            'poll' => $poll,
            'options' => $this->table('poll_options')->where('poll_id', $id)->get()->values(),
            'my_vote' => $myVote,
            'has_voted' => $myVote !== null,
        ];

        if ($myVote !== null) {
            $payload['results'] = $this->pollResults($id);
        }

        return response()->json(['data' => $payload]);
    }

    public function votePoll(Request $request, int $id): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'option_id' => ['required', 'integer'],
        ]);

        $poll = $this->pollsVisibleToStudent($student)->firstWhere('id', $id);
        if (! $poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $option = $this->table('poll_options')
            ->where('id', $data['option_id'])
            ->where('poll_id', $id)
            ->first();

        if (! $option) {
            return response()->json(['message' => 'Invalid poll option.'], 422);
        }

        $existing = $this->table('poll_votes')
            ->where('poll_id', $id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            $this->table('poll_votes')->where('id', $existing->id)->update([
                'option_id' => $data['option_id'],
                'voted_at' => now(),
            ]);
        } else {
            $this->table('poll_votes')->insert([
                'poll_id' => $id,
                'student_id' => $student->id,
                'option_id' => $data['option_id'],
                'voted_at' => now(),
            ]);
        }

        $myVote = $this->table('poll_votes')
            ->where('poll_id', $id)
            ->where('student_id', $student->id)
            ->first();

        return response()->json([
            'message' => 'Vote saved.',
            'data' => [
                'poll' => $poll,
                'options' => $this->table('poll_options')->where('poll_id', $id)->get()->values(),
                'my_vote' => $myVote,
                'has_voted' => true,
                'results' => $this->pollResults($id),
            ],
        ]);
    }

    public function contact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fname' => ['required', 'string', 'max:255'],
            'lname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $id = $this->table('date')->insertGetId([
            'fname' => $data['fname'],
            'lname' => $data['lname'] ?? '',
            'email' => $data['email'],
            'subject' => $data['subject'],
            'phone' => $data['phone'],
            'city' => $data['city'] ?? '',
            'message' => $data['message'],
            'date' => now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Contact request submitted.',
            'data' => $this->table('date')->where('id', $id)->first(),
        ], 201);
    }

    public function listTable(Request $request, string $resource): JsonResponse
    {
        [$table, $order] = $this->resourceMap($resource);
        $paginator = $this->table($table)
            ->orderByDesc($order)
            ->paginate($request->integer('per_page', 20));
        $paginator->setCollection(
            $paginator->getCollection()->map(fn ($row) => $this->withImageUrl((array) $row))
        );

        return response()->json([
            'data' => $paginator,
        ]);
    }

    public function showTable(string $resource, int $id): JsonResponse
    {
        [$table] = $this->resourceMap($resource);
        $row = $this->table($table)->where('id', $id)->firstOrFail();

        return response()->json([
            'data' => $this->withImageUrl((array) $row),
        ]);
    }

    public function questionMeta(): JsonResponse
    {
        return response()->json([
            'boards' => $this->safeTable('boards'),
            'semesters' => $this->safeTable('semesters'),
            'subjects' => $this->safeTable('subjects'),
        ]);
    }

    public function questions(Request $request): JsonResponse
    {
        if (! Schema::hasTable('questions')) {
            return response()->json(['data' => []]);
        }

        $query = $this->table('questions');

        foreach (['board_id', 'semester_id', 'subject_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 20)),
        ]);
    }

    public function submitQuestionAnswer(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);

        if (! Schema::hasTable('answers')) {
            return response()->json(['message' => 'Question answer table is not available.'], 404);
        }

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'answer_id' => ['required', 'integer'],
        ]);

        $answer = $this->table('answers')
            ->where('question_id', $data['question_id'])
            ->where('answer_id', $data['answer_id'])
            ->first();

        if (! $answer) {
            return response()->json(['message' => 'Invalid answer.'], 422);
        }

        if (Schema::hasTable('user_progress')) {
            $this->table('user_progress')->updateOrInsert(
                [
                    'user_id' => $student->id,
                    'question_id' => $data['question_id'],
                ],
                [
                    'selected_answer_id' => $data['answer_id'],
                    'is_correct' => (int) $answer->is_correct,
                ]
            );
        }

        return response()->json([
            'is_correct' => (bool) $answer->is_correct,
        ]);
    }

    public function exams(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->table('exam')
                ->orderByDesc('date')
                ->paginate($request->integer('per_page', 20)),
        ]);
    }

    public function mockQuestions(Request $request): JsonResponse
    {
        if (! Schema::hasTable('mock_question')) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $this->table('mock_question')
                ->limit($request->integer('limit', 10))
                ->get(),
        ]);
    }

    public function submitMockAnswer(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);

        if (! Schema::hasTable('mock_question') || ! Schema::hasTable('mock_answer')) {
            return response()->json(['message' => 'Mock test tables are not available.'], 404);
        }

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'selected_option' => ['required', Rule::in(['a', 'b', 'c', 'd'])],
        ]);

        $question = $this->table('mock_question')->where('question_id', $data['question_id'])->first();
        if (! $question) {
            return response()->json(['message' => 'Question not found.'], 404);
        }

        $isCorrect = $data['selected_option'] === $question->correct_answer;
        $this->table('mock_answer')->insert([
            'question_id' => $data['question_id'],
            'selected_option' => $data['selected_option'],
            'is_correct' => $isCorrect ? 1 : 0,
            'session_id' => $student->id,
        ]);

        return response()->json([
            'correct' => $isCorrect,
            'correct_answer' => $question->correct_answer,
        ]);
    }

    private function studentFromRequest(Request $request): Student
    {
        try {
            $payload = json_decode(Crypt::decryptString((string) $request->bearerToken()), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            abort(401, 'Invalid or missing bearer token.');
        }

        $student = Student::query()->find($payload['student_id'] ?? null);

        if (! $student || $student->status !== 'ongoing') {
            abort(401, 'Student is not active.');
        }

        return $student;
    }

    private function optionalStudentFromRequest(Request $request): ?Student
    {
        if (! $request->bearerToken()) {
            return null;
        }

        try {
            return $this->studentFromRequest($request);
        } catch (Throwable) {
            return null;
        }
    }

    private function makeToken(Student $student): string
    {
        return Crypt::encryptString(json_encode([
            'student_id' => $student->id,
            'issued_at' => time(),
        ], JSON_THROW_ON_ERROR));
    }

    private function studentPayload(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'mobile_number' => $student->mobile_number,
            'email' => $student->email,
            'address' => $student->address,
            'registration_code' => $student->registration_code,
            'course' => $student->course,
            'class' => $student->class,
            'session' => $student->session,
            'image' => $student->image,
            'image_url' => $this->assetUrl($student->image),
            'gender' => $student->gender,
            'father_name' => $student->father_name,
            'date_of_birth' => $this->validDate($student->date_of_birth),
            'school_name' => $student->school_name,
            'status' => $student->status,
            'total_fees' => $student->total_fees,
            'paid_fees' => $student->paid_fees,
            'due_fees' => $student->due_fees,
        ];
    }

    private function clean(string $value): string
    {
        $value = trim($value);

        return trim(preg_replace('/[\p{Cf}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u', '', $value));
    }

    private function validDate(?string $value): ?string
    {
        if (! $value || $value === '0000-00-00') {
            return null;
        }

        return $value;
    }

    private function table(string $table)
    {
        return DB::table($table);
    }

    private function safeTable(string $table)
    {
        return Schema::hasTable($table) ? $this->table($table)->get() : [];
    }

    private function resourceMap(string $resource): array
    {
        $resources = [
            'blogs' => ['blog', 'id'],
            'categories' => ['category', 'id'],
            'courses' => ['course', 'id'],
            'events' => ['event', 'id'],
            'gallery' => ['gallery', 'id'],
            'news' => ['news', 'id'],
            'pdfs' => ['pdf', 'id'],
            'services' => ['service', 'id'],
            'sliders' => ['slider', 'id'],
            'staff' => ['staff', 'id'],
            'subcategories' => ['subcategory', 'id'],
            'teachers' => ['teachers', 'id'],
            'testimonials' => ['testimonial', 'id'],
        ];

        abort_unless(isset($resources[$resource]), 404, 'Unknown API resource.');

        return $resources[$resource];
    }

    private function mapImageUrls($rows)
    {
        return collect($rows)->map(fn ($row) => $this->withImageUrl((array) $row))->values();
    }

    private function withImageUrl(array $row): array
    {
        foreach (['image', 'logo', 'photo'] as $field) {
            if (! empty($row[$field])) {
                $row['image_url'] = $this->assetUrl($row[$field]);

                return $row;
            }
        }

        return $row;
    }

    /**
     * Build a public URL the same way pages/home.php does: ../admin/{path} from pages/.
     */
    private function assetUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = str_replace('\\/', '/', $path);
        $base = $this->publicAssetBase();

        if (str_starts_with($path, '../img/')) {
            return $base.'/img/'.$this->encodePath(substr($path, 7));
        }

        if (str_starts_with($path, 'img/')) {
            return $base.'/'.$this->encodePath($path);
        }

        while (str_starts_with($path, '../')) {
            $path = substr($path, 3);
        }
        while (str_starts_with($path, './')) {
            $path = substr($path, 2);
        }

        return $base.'/admin/'.$this->encodePath(ltrim($path, '/'));
    }

    /** Absolute site root for legacy PHP assets (falls back to request host if .env is empty). */
    private function publicAssetBase(): string
    {
        $base = rtrim((string) config('app.public_asset_base'), '/');

        if ($base !== '' && (str_starts_with($base, 'http://') || str_starts_with($base, 'https://'))) {
            return $base;
        }

        $host = rtrim((string) request()->getSchemeAndHttpHost(), '/');

        if ($base !== '' && str_starts_with($base, '/')) {
            return $host !== '' ? $host.$base : $base;
        }

        return $host !== '' ? $host : 'http://127.0.0.1';
    }

    private function encodePath(string $path): string
    {
        $segments = explode('/', $path);

        return implode('/', array_map('rawurlencode', $segments));
    }

    private function enquiriesQuery(Student $student)
    {
        return $this->table('enquiries')->where(function ($query) use ($student) {
            $query->where('student_id', (string) $student->registration_code)
                ->orWhere('student_id', (string) $student->id);
        });
    }

    private function enquiryPayload(array $row, bool $withMessages = false): array
    {
        $id = (int) ($row['id'] ?? 0);
        $messages = array_map(
            fn (array $message) => $this->formatEnquiryMessage($message),
            $this->enquiryMessages($id)
        );
        $row['attachment_url'] = $this->enquiryAttachmentUrl($row['attachment'] ?? null);
        $row['message_count'] = count($messages);
        $row['messages'] = $withMessages ? $messages : [];
        $row['has_admin_reply'] = collect($messages)->contains(fn ($message) => ($message['sender_type'] ?? '') === 'admin')
            || ! empty($row['reply_message']);
        $row['status'] = $row['has_admin_reply'] ? 'replied' : 'pending';
        $row['last_message'] = $messages !== [] ? end($messages) : null;

        return $row;
    }

    private function formatEnquiryMessage(array $message): array
    {
        $message['attachment_url'] = $this->enquiryAttachmentUrl($message['attachment'] ?? null);

        return $message;
    }

    private function enquiryAttachmentUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = str_replace('\\/', '/', $path);
        $base = $this->publicAssetBase();

        if (str_starts_with($path, 'pages/')) {
            return $base.'/'.$this->encodePath($path);
        }

        if (str_starts_with($path, 'uploads/')) {
            return $base.'/pages/'.$this->encodePath($path);
        }

        if (! str_contains($path, '/')) {
            return $base.'/pages/uploads/'.$this->encodePath($path);
        }

        return $this->assetUrl($path);
    }

    private function enquiryMessages(int $enquiryId): array
    {
        if ($enquiryId <= 0) {
            return [];
        }

        if (Schema::hasTable('enquiry_messages')) {
            $messages = $this->table('enquiry_messages')
                ->where('enquiry_id', $enquiryId)
                ->orderBy('created_at')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            if ($messages !== []) {
                return $messages;
            }
        }

        $enquiry = $this->table('enquiries')->where('id', $enquiryId)->first();
        if (! $enquiry) {
            return [];
        }

        $legacy = [];
        if (! empty($enquiry->message)) {
            $legacy[] = [
                'id' => 0,
                'enquiry_id' => $enquiryId,
                'sender_type' => 'student',
                'sender_name' => $enquiry->name,
                'message' => $enquiry->message,
                'attachment' => $enquiry->attachment,
                'created_at' => $enquiry->created_at,
            ];
        }
        if (! empty($enquiry->reply_message)) {
            $legacy[] = [
                'id' => 0,
                'enquiry_id' => $enquiryId,
                'sender_type' => 'admin',
                'sender_name' => $enquiry->replied_by ?? 'Admin',
                'message' => $enquiry->reply_message,
                'attachment' => null,
                'created_at' => $enquiry->replied_at ?? $enquiry->created_at,
            ];
        }

        return $legacy;
    }

    private function insertEnquiryMessage(int $enquiryId, string $senderType, ?string $senderName, string $message, ?string $attachment = null): void
    {
        if (! Schema::hasTable('enquiry_messages')) {
            if ($senderType === 'admin') {
                $this->table('enquiries')->where('id', $enquiryId)->update([
                    'reply_message' => $message,
                    'replied_at' => now(),
                    'replied_by' => $senderName,
                ]);
            }

            return;
        }

        $this->table('enquiry_messages')->insert([
            'enquiry_id' => $enquiryId,
            'sender_type' => $senderType,
            'sender_name' => $senderName,
            'message' => $message,
            'attachment' => $attachment,
            'created_at' => now(),
        ]);

        if ($senderType === 'admin') {
            $this->table('enquiries')->where('id', $enquiryId)->update([
                'reply_message' => $message,
                'replied_at' => now(),
                'replied_by' => $senderName,
            ]);
        }
    }

    private function saveUploadedFile($file, string $directory, string $databasePrefix): string
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $name = uniqid('api_', true).'.'.$extension;
        $file->move($directory, $name);

        return trim($databasePrefix.'/'.$name, '/');
    }

    private function uniqueRegistrationCode(): string
    {
        do {
            $code = (string) random_int(10000, 99999);
        } while (Student::query()->where('registration_code', $code)->exists());

        return $code;
    }

    private function hasPendingFees(Student $student): bool
    {
        $periods = [
            [
                'month' => now()->subMonth()->format('F'),
                'year' => now()->subMonth()->year,
            ],
            [
                'month' => now()->format('F'),
                'year' => now()->year,
            ],
        ];

        foreach ($periods as $period) {
            $paid = Donation::query()
                ->where('student_registration_code', $student->registration_code)
                ->where('payment_reason', $period['month'])
                ->whereYear('donation_date', $period['year'])
                ->where('status', 'success')
                ->exists();

            if (! $paid) {
                return true;
            }
        }

        return false;
    }

    private function canAccessMaterial(Student $student, StudentMaterial $material): bool
    {
        if ($material->access_level === 'public') {
            return true;
        }

        if ((int) $material->student_id === (int) $student->id) {
            return true;
        }

        if ($material->access_level !== 'class') {
            return false;
        }

        if (trim((string) $material->session) !== trim((string) $student->session)) {
            return false;
        }

        return $this->classesOverlap($student->class, $material->class);
    }

    /** Match pages/explore.php: student may have comma-separated classes. */
    private function applyMaterialAccessFilter($query, Student $student): void
    {
        $studentClasses = $this->splitClasses($student->class);
        $session = trim((string) ($student->session ?? ''));

        $query->where(function ($outer) use ($student, $session, $studentClasses) {
            $outer->where('student_id', $student->id)
                ->orWhere('access_level', 'public');

            if ($session === '') {
                return;
            }

            $outer->orWhere(function ($classQuery) use ($session, $studentClasses) {
                $classQuery->where('access_level', 'class')
                    ->where('session', $session);

                if ($studentClasses === []) {
                    return;
                }

                $classQuery->where(function ($match) use ($studentClasses) {
                    foreach ($studentClasses as $cls) {
                        $match->orWhere('class', $cls)
                            ->orWhereRaw('FIND_IN_SET(?, REPLACE(`class`, ", ", ",")) > 0', [$cls])
                            ->orWhereRaw('FIND_IN_SET(`class`, REPLACE(?, ", ", ",")) > 0', [$cls]);
                    }
                });
            });
        });
    }

    private function splitClasses(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', str_replace(', ', ',', $value)));

        return array_values(array_filter($parts, fn ($part) => $part !== ''));
    }

    private function classesOverlap(?string $studentClass, ?string $materialClass): bool
    {
        $studentClasses = $this->splitClasses($studentClass);
        $materialClasses = $this->splitClasses($materialClass);

        if ($studentClasses === [] || $materialClasses === []) {
            return false;
        }

        return count(array_intersect($studentClasses, $materialClasses)) > 0;
    }

    private function pollsVisibleToStudent(Student $student)
    {
        $studentClasses = $this->splitClasses($student->class);
        $studentSessions = $this->splitClasses($student->session);

        return $this->table('polls')
            ->where(function ($query) use ($student, $studentClasses, $studentSessions) {
                $query->where('send_type', 'all')
                    ->orWhere(function ($single) use ($student) {
                        $single->where('send_type', 'single')->where('student_id', $student->id);
                    })
                    ->orWhere(function ($class) use ($studentClasses, $studentSessions) {
                        $class->where('send_type', 'class')
                            ->where(function ($match) use ($studentClasses, $studentSessions) {
                                $match->where(function ($classFilter) use ($studentClasses) {
                                    $classFilter->whereNull('class_name')
                                        ->orWhere('class_name', '');
                                    if ($studentClasses !== []) {
                                        $classFilter->orWhereIn('class_name', $studentClasses);
                                    }
                                })->where(function ($sessionFilter) use ($studentSessions) {
                                    $sessionFilter->whereNull('session')
                                        ->orWhere('session', '');
                                    if ($studentSessions !== []) {
                                        $sessionFilter->orWhereIn('session', $studentSessions);
                                    }
                                });
                            });
                    });
            })
            ->where(function ($query) {
                $query->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->orderByDesc('id')
            ->get();
    }

    private function pollResults(int $pollId): array
    {
        $options = $this->table('poll_options')->where('poll_id', $pollId)->get();
        $counts = $this->table('poll_votes')
            ->where('poll_id', $pollId)
            ->selectRaw('option_id, COUNT(*) as votes')
            ->groupBy('option_id')
            ->pluck('votes', 'option_id');

        $total = (int) $counts->sum();

        return $options->map(function ($option) use ($counts, $total) {
            $votes = (int) ($counts[$option->id] ?? 0);

            return [
                'id' => $option->id,
                'option_text' => $option->option_text,
                'votes' => $votes,
                'percentage' => $total > 0 ? (int) round(($votes / $total) * 100) : 0,
            ];
        })->values()->all();
    }

    private function unvotedPollsForStudent(Student $student): array
    {
        $polls = $this->pollsVisibleToStudent($student);
        if ($polls->isEmpty()) {
            return [];
        }

        $votedIds = $this->table('poll_votes')
            ->where('student_id', $student->id)
            ->whereIn('poll_id', $polls->pluck('id'))
            ->pluck('poll_id')
            ->all();

        return $polls
            ->filter(fn ($poll) => ! in_array($poll->id, $votedIds, true))
            ->map(fn ($poll) => [
                'id' => $poll->id,
                'question' => $poll->question,
            ])
            ->values()
            ->all();
    }

    private function transformPaginator($paginator)
    {
        $paginator->getCollection()->transform(fn (StudentMaterial $material) => $this->materialPayload($material));

        return $paginator;
    }

    private function noticePayload(Notice $notice): array
    {
        $data = $notice->toArray();

        if (in_array($notice->notice_type, ['image', 'video'], true) && ! empty($notice->notice_content)) {
            $data['media_url'] = $this->assetUrl($notice->notice_content);
        }

        return $data;
    }

    private function materialPayload(StudentMaterial $material, bool $detailed = false): array
    {
        $data = $material->toArray();
        $playback = $this->videoPlayback($material->file_path, $material->material_type);

        if (! empty($playback)) {
            unset($data['file_path']);
            $data['permission'] = 'no';
            $data['is_video'] = true;
            if ($detailed) {
                $data['playback'] = [
                    'embed_url' => $playback['embed_url'],
                ];
            }
        } elseif (! empty($material->file_path)) {
            $data['file_url'] = $this->assetUrl($material->file_path);
        }

        return $data;
    }

    private function videoPlayback(?string $source, ?string $type): ?array
    {
        if (! $source || ! filter_var($source, FILTER_VALIDATE_URL)) {
            return null;
        }

        if (strtolower((string) $type) !== 'video' && ! preg_match('/(vimeo\.com|player\.vimeo\.com)/i', $source)) {
            return null;
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)(?:/([A-Za-z0-9]+))?~i', $source, $matches)) {
            $embedUrl = 'https://player.vimeo.com/video/'.$matches[1];
            $query = parse_url($source, PHP_URL_QUERY);

            if ($query) {
                parse_str($query, $params);
                if (! empty($params['h'])) {
                    $embedUrl .= '?h='.$params['h'];
                }
            } elseif (! empty($matches[2])) {
                $embedUrl .= '?h='.$matches[2];
            }

            $embedUrl = $this->appendUrlParams($embedUrl, 'title=0&byline=0&portrait=0&sidedock=0');

            return [
                'provider' => 'vimeo',
                'video_id' => $matches[1],
                'embed_url' => $embedUrl,
            ];
        }

        if (preg_match('~player\.vimeo\.com/video/(\d+)~i', $source, $matches)) {
            return [
                'provider' => 'vimeo',
                'video_id' => $matches[1],
                'embed_url' => $this->appendUrlParams($source, 'title=0&byline=0&portrait=0&sidedock=0'),
            ];
        }

        return null;
    }

    private function appendUrlParams(string $url, string $params): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').$params;
    }
}
