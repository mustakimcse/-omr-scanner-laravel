<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Result #{{ $student['result_id'] ?? '-' }} - {{ $exam['exam_name'] ?? 'Transcript' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .omr-img { cursor: zoom-in; transition: transform .2s; }
        .omr-img:hover { transform: scale(1.01); }
        #lightbox img { max-height: 92vh; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- Toast --}}
    <div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white"></div>

    @php
        $apiBase = $api_base ?? 'https://new-omr-scanner-with-fast-api.onrender.com';
        $imgUrl = !empty($student['saved_path']) ? rtrim($apiBase, '/') . '/' . ltrim($student['saved_path'], '/') : null;
        $examId = $exam['exam_id'] ?? ($student['exam_id'] ?? null);
        $resultId = $student['result_id'] ?? null;
    @endphp

    {{-- Header --}}
    <div class="rounded-2xl shadow p-6 mb-6 text-white bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">{{ $exam['exam_name'] ?? 'Exam' }}</h1>
                <p class="text-purple-100 mt-1 text-sm">
                    Exam #{{ $exam['exam_id'] ?? '-' }} | Set {{ $exam['set_code'] ?? ($student['set_code'] ?? '-') }} | {{ $exam['total_questions'] ?? ($summary['total_questions'] ?? '-') }} Questions
                </p>
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <span class="px-2 py-1 text-sm font-semibold rounded bg-white/20">Roll: {{ $student['roll'] ?? '-' }}</span>
                    <span class="px-2 py-1 text-sm font-semibold rounded bg-white/20">Set: {{ $student['set_code'] ?? '-' }}</span>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ ($student['status'] ?? '') === 'success' ? 'bg-green-400 text-green-900' : 'bg-red-400 text-red-900' }}">{{ $student['status'] ?? '-' }}</span>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($examId)
                    <a href="{{ route('exam.results', $examId) }}" class="px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 text-sm font-semibold">← Marks</a>
                    <a href="{{ route('exam.scan_results', $examId) }}" class="px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 text-sm font-semibold">Scan Results</a>
                @endif
                <a href="{{ route('home') }}" class="px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 text-sm font-semibold">All Exams</a>
                @if($resultId)
                    <button type="button" onclick="openCorrection()" class="px-4 py-2 bg-amber-500 rounded-lg hover:bg-amber-600 text-sm font-bold">✏️ Correction</button>
                    <button type="button" onclick="openDelete()" class="px-4 py-2 bg-red-600 rounded-lg hover:bg-red-700 text-sm font-bold">🗑️ Delete</button>
                @endif
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-5">
            <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-3 text-center">
                <p class="text-xs uppercase text-purple-100">Questions</p>
                <p class="text-2xl font-bold">{{ $summary['total_questions'] ?? 0 }}</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-3 text-center">
                <p class="text-xs uppercase text-green-200">Correct</p>
                <p class="text-2xl font-bold text-green-200">{{ $summary['total_correct'] ?? 0 }}</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-3 text-center">
                <p class="text-xs uppercase text-red-200">Wrong</p>
                <p class="text-2xl font-bold text-red-200">{{ $summary['total_wrong'] ?? 0 }}</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-3 text-center">
                <p class="text-xs uppercase text-yellow-200">Skipped</p>
                <p class="text-2xl font-bold text-yellow-200">{{ $summary['skipped'] ?? 0 }}</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-3 text-center">
                <p class="text-xs uppercase text-purple-100">Obtained</p>
                <p class="text-2xl font-bold">{{ $summary['obtained_marks'] ?? 0 }}</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-3 text-center">
                <p class="text-xs uppercase text-purple-100">Total</p>
                <p class="text-2xl font-bold">{{ $summary['total_marks'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Student + OMR image --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b bg-gray-50">
                <h2 class="font-bold text-gray-800">Student OMR</h2>
                <p class="text-xs text-gray-400">Result ID: {{ $resultId ?? '-' }} | File: {{ $student['filename'] ?? '-' }}</p>
            </div>
            <div class="p-4">
                @if($imgUrl)
                    <img src="{{ $imgUrl }}" alt="OMR {{ $student['filename'] ?? '' }}" loading="lazy"
                         class="omr-img w-full rounded border shadow-sm bg-white"
                         onclick="openLightbox('{{ $imgUrl }}')"
                         onerror="this.outerHTML='<div class=\'bg-red-50 border border-red-200 text-red-600 text-sm rounded p-4 text-center\'>Image load হয়নি</div>'">
                    <a href="{{ $imgUrl }}" target="_blank" class="inline-block mt-2 text-xs text-blue-600 hover:underline">Full size খুলুন ↗</a>
                @else
                    <div class="bg-gray-100 border rounded p-6 text-center text-sm text-gray-400">Image path পাওয়া যায়নি</div>
                @endif
                <div class="mt-4 text-sm space-y-1 text-gray-600">
                    <p><span class="font-semibold">Negative mark:</span> {{ $exam['default_negative_mark'] ?? '-' }}</p>
                    @if(!empty($student['error']))
                        <p class="text-red-500 text-xs">{{ $student['error'] }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Details table --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between">
                <h2 class="font-bold text-gray-800">Question-wise Transcript <span class="text-xs text-gray-400 font-normal">({{ count($details ?? []) }}টি)</span></h2>
                <div class="flex gap-2 text-xs">
                    <span class="px-2 py-1 rounded bg-green-100 text-green-700 font-semibold">correct</span>
                    <span class="px-2 py-1 rounded bg-red-100 text-red-700 font-semibold">wrong</span>
                    <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-700 font-semibold">skipped</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Q</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Correct</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Student</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Marks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($details ?? [] as $d)
                            <tr class="hover:bg-gray-50 {{ ($d['status'] ?? '') === 'correct' ? 'bg-green-50/40' : ((($d['status'] ?? '') === 'wrong') ? 'bg-red-50/40' : 'bg-yellow-50/40') }}">
                                <td class="px-4 py-2.5 text-sm font-bold text-gray-700">Q{{ $d['question_no'] ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-center text-sm font-bold text-gray-800">{{ $d['correct_answer'] ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-center text-sm font-bold {{ ($d['status'] ?? '') === 'correct' ? 'text-green-600' : ((($d['status'] ?? '') === 'wrong') ? 'text-red-500' : 'text-yellow-600') }}">{{ $d['student_answer'] ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ ($d['status'] ?? '') === 'correct' ? 'bg-green-100 text-green-700' : ((($d['status'] ?? '') === 'wrong') ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ $d['status'] ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-center text-sm font-bold {{ ($d['marks'] ?? 0) < 0 ? 'text-red-500' : 'text-green-600' }}">{{ $d['marks'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">Details পাওয়া যায়নি।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Correction modal --}}
@if($resultId)
<div id="correction-modal" class="hidden fixed inset-0 bg-black/60 z-50 items-center justify-center p-4" onclick="if(event.target===this) closeCorrection()">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b sticky top-0 bg-white">
            <h3 class="font-bold text-gray-800">Correction — {{ $student['filename'] ?? '' }} <span class="text-xs text-gray-400 font-normal">(ID: {{ $resultId }})</span></h3>
            <button type="button" onclick="closeCorrection()" class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none">&times;</button>
        </div>
        <form id="correction-form" action="{{ route('exam.result.correct', $resultId) }}" method="POST" class="p-5 space-y-4">
            @csrf
            @method('PATCH')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Roll</label>
                    <input type="text" name="roll" value="{{ $student['roll'] ?? '' }}" class="w-full border rounded px-3 py-2 text-sm outline-none focus:ring focus:ring-amber-200">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Set Code</label>
                    <input type="text" name="set_code" value="{{ $student['set_code'] ?? '' }}" class="w-full border rounded px-3 py-2 text-sm outline-none focus:ring focus:ring-amber-200">
                </div>
            </div>
            @if(!empty($details))
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">MCQ Answers <span class="font-normal text-gray-400">(ভুলটা ঠিক করে দিন — a/b/c/d)</span></label>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                        @foreach($details as $d)
                            @php $qNo = $d['question_no'] ?? ''; $sAns = $d['student_answer'] ?? ''; @endphp
                            <div class="flex items-center gap-1 border rounded px-1.5 py-1 {{ ($d['status'] ?? '') !== 'correct' ? 'bg-yellow-50 border-yellow-300' : 'bg-gray-50' }}">
                                <span class="text-[10px] font-bold text-gray-400 w-6">Q{{ $qNo }}</span>
                                <input type="text" name="mcq_answers[{{ $qNo }}]" value="{{ $sAns === 'Unanswered' ? '' : $sAns }}" maxlength="1" placeholder="-"
                                       class="w-full border rounded px-1 py-0.5 text-sm font-bold text-center outline-none focus:ring focus:ring-amber-200">
                            </div>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">খালি রাখলে সেই প্রশ্নের উত্তর পাঠানো হবে না।</p>
                </div>
            @endif
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" onclick="closeCorrection()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-300">Cancel</button>
                <button type="submit" id="correction-btn" class="px-5 py-2 bg-amber-500 text-white text-sm font-bold rounded-lg hover:bg-amber-600">Save Correction</button>
            </div>
            <p id="correction-error" class="hidden text-xs text-red-600 font-semibold"></p>
        </form>
    </div>
</div>
@endif

{{-- Delete confirm modal --}}
@if($resultId)
<div id="delete-modal" class="hidden fixed inset-0 bg-black/60 z-50 items-center justify-center p-4" onclick="if(event.target===this) closeDelete()">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6 text-center">
        <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-2xl mb-3">🗑️</div>
        <h3 class="font-bold text-gray-800 mb-1">Delete করবেন?</h3>
        <p class="text-sm text-gray-500 mb-1">{{ $student['filename'] ?? '' }} <span class="text-gray-400">(ID: {{ $resultId }})</span></p>
        <p class="text-xs text-red-500 mb-4">এই student-এর scan করা OMR স্থায়ীভাবে মুছে যাবে।</p>
        <div class="flex justify-center gap-2">
            <button type="button" onclick="closeDelete()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-300">Cancel</button>
            <button type="button" id="delete-btn" onclick="doDelete()" class="px-5 py-2 bg-red-600 text-white text-sm font-bold rounded-lg hover:bg-red-700">Yes, Delete</button>
        </div>
        <p id="delete-error" class="hidden text-xs text-red-600 font-semibold mt-3"></p>
    </div>
</div>
@endif

{{-- Lightbox --}}
<div id="lightbox" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4" onclick="closeLightbox()">
    <img id="lightbox-img" src="" alt="Full OMR" class="max-w-full rounded shadow-lg bg-white">
    <button class="absolute top-4 right-6 text-white text-3xl font-bold" onclick="closeLightbox()">&times;</button>
</div>

<script>
    function openLightbox(src) {
        const lb = document.getElementById('lightbox');
        document.getElementById('lightbox-img').src = src;
        lb.classList.remove('hidden');
        lb.classList.add('flex');
    }
    function closeLightbox() {
        const lb = document.getElementById('lightbox');
        lb.classList.add('hidden');
        lb.classList.remove('flex');
        document.getElementById('lightbox-img').src = '';
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeLightbox(); closeDelete(); closeCorrection(); } });

    let toastTimer = null;
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('hidden', 'bg-green-600', 'bg-red-600');
        toast.classList.add(type === 'error' ? 'bg-red-600' : 'bg-green-600');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 2500);
    }

    function openCorrection() {
        const m = document.getElementById('correction-modal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeCorrection() {
        const m = document.getElementById('correction-modal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    // Correction form → AJAX: popup close + reload (marks পুনরায় হিসাব হয় বলে)
    document.getElementById('correction-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('correction-btn');
        const errEl = document.getElementById('correction-error');
        if (errEl) errEl.classList.add('hidden');
        const originalText = btn ? btn.textContent : '';
        if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; btn.classList.add('opacity-60'); }

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
                const msg = data.message || ('Save হয়নি (HTTP ' + res.status + ')');
                if (errEl) { errEl.textContent = msg; errEl.classList.remove('hidden'); }
                showToast(msg, 'error');
                return;
            }
            closeCorrection();
            showToast(data.message || 'Correction saved!');
            // Summary + details server থেকে পুনরায় হিসাব হয় → fresh data দেখাতে reload
            setTimeout(() => window.location.reload(), 800);
        } catch (err) {
            const msg = 'Network error — আবার চেষ্টা করুন।';
            if (errEl) { errEl.textContent = msg; errEl.classList.remove('hidden'); }
            showToast(msg, 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = originalText; btn.classList.remove('opacity-60'); }
        }
    });

    function openDelete() {
        const m = document.getElementById('delete-modal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeDelete() {
        const m = document.getElementById('delete-modal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    async function doDelete() {
        const btn = document.getElementById('delete-btn');
        const errEl = document.getElementById('delete-error');
        if (errEl) errEl.classList.add('hidden');
        if (btn) { btn.disabled = true; btn.textContent = 'Deleting…'; btn.classList.add('opacity-60'); }
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const resultId = @json($resultId);
        const examId = @json($examId);

        try {
            const res = await fetch('/exam/result/' + resultId, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: (() => { const f = new FormData(); f.append('_method', 'DELETE'); f.append('_token', token); return f; })(),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
                const msg = data.message || ('Delete হয়নি (HTTP ' + res.status + ')');
                if (errEl) { errEl.textContent = msg; errEl.classList.remove('hidden'); }
                showToast(msg, 'error');
                return;
            }
            showToast(data.message || 'OMR delete hoise!');
            setTimeout(() => {
                if (examId) window.location.href = '/exam/' + examId + '/results';
                else window.location.href = '/';
            }, 800);
        } catch (err) {
            const msg = 'Network error — আবার চেষ্টা করুন।';
            if (errEl) { errEl.textContent = msg; errEl.classList.remove('hidden'); }
            showToast(msg, 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = 'Yes, Delete'; btn.classList.remove('opacity-60'); }
        }
    }
</script>
</body>
</html>
