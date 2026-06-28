<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Donation;
use App\Models\Notice;
use App\Models\ProgressReport;
use App\Models\Student;
use App\Models\StudentMaterial;
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
        ]);
    }

    public function company(): JsonResponse
    {
        return response()->json([
            'data' => Company::query()->first(),
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

        return response()->json([
            'data' => Notice::query()
                ->where('student_id', $student->id)
                ->latest('created_at')
                ->paginate($request->integer('per_page', 20)),
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

        return response()->json([
            'data' => $this->materialPayload($material),
            'related' => StudentMaterial::query()
                ->where('id', '!=', $material->id)
                ->where('subject', $material->subject)
                ->limit(4)
                ->get()
                ->map(fn (StudentMaterial $related) => $this->materialPayload($related))
                ->values(),
            'can_access' => $this->canAccessMaterial($student, $material),
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
            ->where('student_registration_code', $student->registration_code);

        if ($request->filled('month')) {
            $payments->where('payment_reason', $request->input('month'));
        }

        return response()->json([
            'summary' => [
                'total_fees' => $student->total_fees,
                'paid_fees' => $student->paid_fees,
                'due_fees' => $student->due_fees,
            ],
            'months' => Donation::query()
                ->where('student_registration_code', $student->registration_code)
                ->distinct()
                ->orderBy('payment_reason')
                ->pluck('payment_reason')
                ->values(),
            'payments' => $payments
                ->latest('created_at')
                ->paginate($request->integer('per_page', 20)),
        ]);
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

    public function attendance(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to = $data['to'] ?? now()->toDateString();

        return response()->json([
            'data' => $this->table('attendance')
                ->where('student_id', $student->id)
                ->whereBetween('attendance_date', [$from, $to])
                ->orderByDesc('attendance_date')
                ->paginate($request->integer('per_page', 31)),
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

        return response()->json([
            'data' => $this->table('enquiries')
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->paginate($request->integer('per_page', 20)),
        ]);
    }

    public function createEnquiry(Request $request): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'enquiry_type' => ['required', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $attachment = $this->saveUploadedFile($request->file('attachment'), base_path('../pages/uploads'), '');
        }

        $id = $this->table('enquiries')->insertGetId([
            'student_id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'phone' => $student->mobile_number,
            'enquiry_type' => $data['enquiry_type'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'attachment' => $attachment ? basename($attachment) : null,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Enquiry submitted.',
            'data' => $this->table('enquiries')->where('id', $id)->first(),
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
        $polls = $this->table('polls')
            ->where(function ($query) use ($student) {
                $query->where('send_type', 'all')
                    ->orWhere(function ($single) use ($student) {
                        $single->where('send_type', 'single')->where('student_id', $student->id);
                    })
                    ->orWhere(function ($class) use ($student) {
                        $class->where('send_type', 'class')
                            ->where('class_name', $student->class)
                            ->where('session', $student->session);
                    });
            })
            ->where(function ($query) {
                $query->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->orderByDesc('id')
            ->get();

        $pollIds = $polls->pluck('id');
        $options = $this->table('poll_options')->whereIn('poll_id', $pollIds)->get()->groupBy('poll_id');
        $votes = $this->table('poll_votes')->where('student_id', $student->id)->whereIn('poll_id', $pollIds)->get()->keyBy('poll_id');

        return response()->json([
            'data' => $polls->map(fn ($poll) => [
                'poll' => $poll,
                'options' => $options->get($poll->id, collect())->values(),
                'my_vote' => $votes->get($poll->id),
            ])->values(),
        ]);
    }

    public function votePoll(Request $request, int $id): JsonResponse
    {
        $student = $this->studentFromRequest($request);
        $data = $request->validate([
            'option_id' => ['required', 'integer'],
        ]);

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

        return response()->json(['message' => 'Vote saved.']);
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
        if (array_key_exists('image', $row)) {
            $row['image_url'] = $this->assetUrl($row['image']);
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
        $months = [
            now()->subMonth()->format('F'),
            now()->format('F'),
        ];

        $paid = Donation::query()
            ->where('student_registration_code', $student->registration_code)
            ->whereIn('payment_reason', $months)
            ->where('status', 'success')
            ->distinct()
            ->pluck('payment_reason')
            ->all();

        return count(array_diff($months, $paid)) === 2;
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

    private function transformPaginator($paginator)
    {
        $paginator->getCollection()->transform(fn (StudentMaterial $material) => $this->materialPayload($material));

        return $paginator;
    }

    private function materialPayload(StudentMaterial $material): array
    {
        $data = $material->toArray();
        $data['playback'] = $this->videoPlayback($material->file_path, $material->material_type);

        if (! empty($data['playback'])) {
            $data['source_url'] = $material->file_path;
            unset($data['file_path']);
            $data['permission'] = 'no';
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
                'share_url' => null,
            ];
        }

        if (preg_match('~player\.vimeo\.com/video/(\d+)~i', $source, $matches)) {
            return [
                'provider' => 'vimeo',
                'video_id' => $matches[1],
                'embed_url' => $this->appendUrlParams($source, 'title=0&byline=0&portrait=0&sidedock=0'),
                'share_url' => null,
            ];
        }

        return null;
    }

    private function appendUrlParams(string $url, string $params): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').$params;
    }
}
