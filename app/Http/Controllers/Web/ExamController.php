<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExamController extends Controller
{
    protected function apiBase(): string
    {
        return rtrim(config('services.omr.base_url', 'https://new-omr-scanner-with-fast-api.onrender.com'), '/');
    }

    protected function http(int $timeout = 10)
    {
        // index()-এর মতো SSL verify skip — local WAMP-এ cURL error 60 এড়াতে
        return Http::timeout($timeout)->withoutVerifying();
    }

    public function index()
    {
        $apiBase = $this->apiBase();

        try {
            $response = $this->http(10)->get("{$apiBase}/exams");
        } catch (\Exception $e) {
            return view('exams', [
                'total_exams' => 0,
                'total_uploaded_answers' => 0,
                'exams' => [],
                'api_base' => $apiBase,
                'load_error' => 'API connection failed: ' . $e->getMessage(),
            ]);
        }

        $data = $response->json();

        if ($response->successful() && isset($data['success']) && $data['success'] === true) {
            return view('exams', [
                'total_exams' => $data['total_exams'] ?? 0,
                'total_uploaded_answers' => $data['total_uploaded_answers'] ?? 0,
                'exams' => $data['exams'] ?? [],
                'api_base' => $apiBase,
                'load_error' => null,
            ]);
        }

        return view('exams', [
            'total_exams' => 0,
            'total_uploaded_answers' => 0,
            'exams' => [],
            'api_base' => $apiBase,
            'load_error' => 'Exam list load হয়নি (API status: ' . $response->status() . ')',
        ]);
    }

    public function scanForm(Request $request)
    {
        $apiBase = $this->apiBase();

        $exams = [];
        try {
            $res = $this->http(10)->get("{$apiBase}/exams");
            if ($res->successful()) {
                $exams = $res->json('exams') ?? [];
            }
        } catch (\Exception $e) {
            // dropdown khali thakbe, error box dekhabe
        }

        return view('omr_scan', [
            'exams' => $exams,
            'api_base' => $apiBase,
            'selected_exam' => $request->query('exam_id'),
            'load_error' => empty($exams) ? 'Exam list load হয়নি — exam_id হাতে লিখে দিতে হবে।' : null,
        ]);
    }

    public function scanUpload(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|integer',
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $apiBase = $this->apiBase();

        $http = $this->http(300);
        foreach ($request->file('files') as $file) {
            $http = $http->attach(
                'files',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            );
        }

        try {
            $response = $http->post("{$apiBase}/omr/scanner", [
                'exam_id' => (int) $validated['exam_id'],
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['api' => 'API connection failed: ' . $e->getMessage()])->withInput();
        }

        if ($response->successful()) {
            $json = $response->json();
            $message = $json['message'] ?? null;
            if (!is_string($message) || trim($message) === '') {
                $message = 'Scan complete hoise!';
            }
            return redirect()->route('exam.scan_results', ['id' => $validated['exam_id']])->with('success', $message);
        }

        $errorMsg = $response->body();
        $json = $response->json();
        if (isset($json['detail'])) {
            $errorMsg = is_string($json['detail']) ? $json['detail'] : json_encode($json['detail']);
        }

        return back()->withErrors(['api' => 'API Error (' . $response->status() . '): ' . $errorMsg])->withInput();
    }

    public function create()
    {
        return view('exam_create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_name' => 'required|string|max:255',
            'set_code' => 'required|string|max:50',
            'default_negative_mark' => 'nullable|numeric|min:0',
            'answers' => 'required|array|min:1',
            'answers.*' => 'required|string',
            'question_negative_marks' => 'nullable|array',
            'question_negative_marks.*.question' => 'required_with:question_negative_marks|string',
            'question_negative_marks.*.mark' => 'required_with:question_negative_marks|numeric|min:0',
        ]);

        // Build answers: ["1" => "a", ...] — drop empty rows
        $answers = [];
        foreach ($validated['answers'] as $qNo => $ans) {
            $qNo = trim((string) $qNo);
            $ans = strtolower(trim((string) $ans));
            if ($qNo !== '' && $ans !== '') {
                $answers[$qNo] = $ans;
            }
        }

        if (empty($answers)) {
            return back()->withErrors(['answers' => 'At least one answer is required.'])->withInput();
        }

        // Build question_negative_marks: {"1": 1.0, "5": 0.5} or null
        $questionNegativeMarks = null;
        if (!empty($validated['question_negative_marks'])) {
            $marks = [];
            foreach ($validated['question_negative_marks'] as $row) {
                $q = trim((string) ($row['question'] ?? ''));
                $m = $row['mark'] ?? null;
                if ($q !== '' && $m !== null && $m !== '') {
                    $marks[$q] = (float) $m;
                }
            }
            if (!empty($marks)) {
                $questionNegativeMarks = $marks;
            }
        }

        $payload = [
            'exam_name' => $validated['exam_name'],
            'set_code' => $validated['set_code'],
            'default_negative_mark' => isset($validated['default_negative_mark']) && $validated['default_negative_mark'] !== null
                ? (float) $validated['default_negative_mark'] : 0.25,
            'question_negative_marks' => $questionNegativeMarks,
            'answers' => $answers,
        ];

        $apiBase = $this->apiBase();

        try {
            $response = $this->http(30)->post("{$apiBase}/omr/answer", $payload);
        } catch (\Exception $e) {
            return back()->withErrors(['api' => 'API connection failed: ' . $e->getMessage()])->withInput();
        }

        if ($response->successful()) {
            $json = $response->json();
            $message = $json['message'] ?? null;
            if (!is_string($message) || trim($message) === '') {
                $message = 'Exam create hoise!';
            }
            return redirect()->route('home')->with('success', $message);
        }

        // 422 validation error from FastAPI → show detail
        $errorMsg = $response->body();
        $json = $response->json();
        if (isset($json['detail'])) {
            $errorMsg = is_string($json['detail']) ? $json['detail'] : json_encode($json['detail']);
        }

        return back()->withErrors(['api' => 'API Error (' . $response->status() . '): ' . $errorMsg])->withInput();
    }

    public function scanResults($examId)
    {
        $apiBase = $this->apiBase();

        try {
            $response = $this->http(10)->get("{$apiBase}/exam/{$examId}/results");
        } catch (\Exception $e) {
            abort(404, "Exam #{$examId} এর সার্ভারে সংযোগ করা যায়নি।");
        }

        $data = $response->json();

        if (isset($data['success']) && $data['success'] === true) {
            $results = $data['results'] ?? [];

            // নতুন scan আগে (id বড় → আগে)
            usort($results, fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));

            return view('scan_results', [
                'exam_id' => $data['exam_id'] ?? $examId,
                'total_submissions' => $data['total_submissions'] ?? count($results),
                'results' => $results,
                'api_base' => $apiBase,
            ]);
        }

        abort(404, "Exam #{$examId} এর কোনো scan data পাওয়া যায়নি।");
    }

    public function correctResult(Request $request, $resultId)
    {
        $validated = $request->validate([
            'roll' => 'nullable|string|max:50',
            'set_code' => 'nullable|string|max:50',
            'mcq_answers' => 'nullable|array',
            'mcq_answers.*' => 'nullable|string|max:20',
        ]);

        // শুধু দেওয়া field গুলোই PATCH body তে যাবে
        $payload = [];
        if ($request->filled('roll')) {
            $payload['roll'] = trim($validated['roll']);
        }
        if ($request->filled('set_code')) {
            $payload['set_code'] = trim($validated['set_code']);
        }
        if (!empty($validated['mcq_answers'])) {
            $answers = [];
            foreach ($validated['mcq_answers'] as $qNo => $ans) {
                $ans = strtolower(trim((string) $ans));
                if ($ans !== '') {
                    $answers[(string) $qNo] = $ans;
                }
            }
            if (!empty($answers)) {
                $payload['mcq_answers'] = $answers;
            }
        }

        if (empty($payload)) {
            $msg = 'কিছু পরিবর্তন করেননি — অন্তত একটি field দিন।';
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['api' => $msg]);
        }

        $apiBase = $this->apiBase();

        try {
            $response = $this->http(30)->patch("{$apiBase}/exam/result/{$resultId}", $payload);
        } catch (\Exception $e) {
            $msg = 'API connection failed: ' . $e->getMessage();
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return back()->withErrors(['api' => $msg]);
        }

        if ($response->successful()) {
            $json = $response->json();
            $message = $json['message'] ?? null;
            if (!is_string($message) || trim($message) === '') {
                $message = 'Correction save hoise!';
            }
            // AJAX submit → no redirect, no alert. Frontend closes popup + updates data instantly.
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $json['data'] ?? $json['result'] ?? null,
                    'echo' => $payload, // fallback so UI can update instantly
                ]);
            }
            return back()->with('success', $message);
        }

        $errorMsg = $response->body();
        $json = $response->json();
        if (isset($json['detail'])) {
            $errorMsg = is_string($json['detail']) ? $json['detail'] : json_encode($json['detail']);
        }

        $msg = 'API Error (' . $response->status() . '): ' . $errorMsg;
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $msg], $response->status() ?: 500);
        }
        return back()->withErrors(['api' => $msg]);
    }

    public function deleteResult(Request $request, $resultId)
    {
        $apiBase = $this->apiBase();
        $isAjax = $request->expectsJson() || $request->ajax() || $request->wantsJson();

        try {
            $response = $this->http(30)->delete("{$apiBase}/exam/result/{$resultId}");
        } catch (\Exception $e) {
            $msg = 'API connection failed: ' . $e->getMessage();
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return back()->withErrors(['api' => $msg]);
        }

        if ($response->successful()) {
            $json = $response->json();
            $message = $json['message'] ?? null;
            if (!is_string($message) || trim($message) === '') {
                $message = 'OMR delete hoise!';
            }
            if ($isAjax) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return back()->with('success', $message);
        }

        $errorMsg = $response->body();
        $json = $response->json();
        if (isset($json['detail'])) {
            $errorMsg = is_string($json['detail']) ? $json['detail'] : json_encode($json['detail']);
        }

        $msg = 'API Error (' . $response->status() . '): ' . $errorMsg;
        if ($isAjax) {
            return response()->json(['success' => false, 'message' => $msg], $response->status() ?: 500);
        }
        return back()->withErrors(['api' => $msg]);
    }

    public function destroyExam(Request $request, $examId)
    {
        $apiBase = $this->apiBase();
        $isAjax = $request->expectsJson() || $request->ajax() || $request->wantsJson();

        try {
            $response = $this->http(30)->delete("{$apiBase}/exam/{$examId}");
        } catch (\Exception $e) {
            $msg = 'API connection failed: ' . $e->getMessage();
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return back()->withErrors(['api' => $msg]);
        }

        if ($response->successful()) {
            $json = $response->json();
            $message = $json['message'] ?? null;
            if (!is_string($message) || trim($message) === '') {
                $message = 'Exam delete hoise!';
            }
            if ($isAjax) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return back()->with('success', $message);
        }

        $errorMsg = $response->body();
        $json = $response->json();
        if (isset($json['detail'])) {
            $errorMsg = is_string($json['detail']) ? $json['detail'] : json_encode($json['detail']);
        }

        $msg = 'API Error (' . $response->status() . '): ' . $errorMsg;
        if ($isAjax) {
            return response()->json(['success' => false, 'message' => $msg], $response->status() ?: 500);
        }
        return back()->withErrors(['api' => $msg]);
    }

    public function singleResult($resultId)
    {
        $apiBase = $this->apiBase();

        try {
            $response = $this->http(10)->get("{$apiBase}/exam/result/{$resultId}");
        } catch (\Exception $e) {
            abort(404, "Result #{$resultId} এর সার্ভারে সংযোগ করা যায়নি।");
        }

        $data = $response->json();

        if ($response->successful() && isset($data['success']) && $data['success'] === true) {
            return view('single_result', [
                'exam' => $data['exam'] ?? [],
                'student' => $data['student'] ?? [],
                'summary' => $data['summary'] ?? [],
                'details' => $data['details'] ?? [],
                'api_base' => $apiBase,
            ]);
        }

        $msg = $data['detail'] ?? "Result #{$resultId} পাওয়া যায়নি।";
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        abort(404, $msg);
    }

    public function showResults($examId)
    {
        $apiBase = $this->apiBase();

        try {
            $response = $this->http(10)->get("{$apiBase}/exam/{$examId}/calculate-marks-by-set");
        } catch (\Exception $e) {
            abort(404, "Exam #{$examId} এর সার্ভারে সংযোগ করা যায়নি।");
        }

        $data = $response->json();

        if (isset($data['success']) && $data['success'] === true) {
            $resultsBySet = $data['results_by_set'] ?? [];

            // Sort each set by obtained_marks (highest first)
            foreach ($resultsBySet as $set => $rows) {
                usort($rows, fn($a, $b) => ($b['obtained_marks'] ?? 0) <=> ($a['obtained_marks'] ?? 0));
                $resultsBySet[$set] = $rows;
            }

            return view('exam_results', [
                'exam_id' => $data['exam_id'] ?? $examId,
                'total_students' => $data['total_students'] ?? 0,
                'results_by_set' => $resultsBySet,
                'api_base' => $apiBase,
            ]);
        }

        abort(404, "Exam #{$examId} এর কোনো data পাওয়া যায়নি।");
    }
}
