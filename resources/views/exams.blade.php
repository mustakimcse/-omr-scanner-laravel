<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>All Exams - OMR Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pop-in {
            0% { transform: scale(.7) translateY(16px); opacity: 0; }
            60% { transform: scale(1.03) translateY(0); opacity: 1; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        @keyframes check-pop {
            0% { transform: scale(0); }
            60% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        @keyframes ring-pulse {
            0% { box-shadow: 0 0 0 0 rgba(34,197,94,.45); }
            100% { box-shadow: 0 0 0 18px rgba(34,197,94,0); }
        }
        .success-pop { animation: pop-in .35s ease-out; }
        .success-check { animation: check-pop .45s ease-out .1s both, ring-pulse 1.2s ease-out .2s; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
<div class="max-w-6xl mx-auto py-8 px-4">

    {{-- Toast --}}
    <div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white"></div>

    @if(session('success'))
        <div id="flash-success" data-message="{{ session('success') }}"></div>
    @endif

    {{-- Hero header --}}
    <div class="rounded-2xl shadow p-8 mb-8 text-white bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">OMR Exams</h1>
                <p class="text-blue-100 mt-1">সব পরীক্ষার তালিকা ও সামারি</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('omr.scan') }}"
                   class="px-5 py-2.5 bg-white/20 text-white font-semibold rounded-lg border border-white/40 hover:bg-white/30">📤 Scan OMR</a>
                <a href="{{ route('exam.create') }}"
                   class="px-5 py-2.5 bg-white text-blue-700 font-semibold rounded-lg shadow hover:bg-blue-50">+ New Exam</a>
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-6">
            <div class="bg-white/15 backdrop-blur rounded-xl px-5 py-4">
                <p class="text-xs uppercase tracking-wide text-blue-100">Total Exams</p>
                <p class="text-3xl font-bold">{{ $total_exams }}</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl px-5 py-4">
                <p class="text-xs uppercase tracking-wide text-blue-100">Uploaded Answers</p>
                <p class="text-3xl font-bold">{{ $total_uploaded_answers }}</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl px-5 py-4 col-span-2 sm:col-span-1">
                <p class="text-xs uppercase tracking-wide text-blue-100">Exams Shown</p>
                <p class="text-3xl font-bold">{{ count($exams ?? []) }}</p>
            </div>
        </div>
    </div>

    @if(!empty($load_error))
        <div class="bg-red-50 border border-red-300 text-red-700 rounded-lg p-4 mb-6 text-sm">
            {{ $load_error }}
        </div>
    @endif

    @if(empty($exams))
        <div class="bg-white rounded-xl shadow p-10 text-center">
            <p class="text-4xl mb-3">📝</p>
            <p class="text-gray-600 font-medium">কোনো exam পাওয়া যায়নি</p>
            <a href="{{ route('exam.create') }}" class="inline-block mt-4 px-5 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">প্রথম Exam তৈরি করুন</a>
        </div>
    @else
        <div class="grid md:grid-cols-2 gap-5">
            @foreach($exams as $exam)
                @php
                    $uploaded = $exam['total_uploaded_answers'] ?? 0;
                    $success = $exam['total_success'] ?? 0;
                    $failed = $exam['total_failed'] ?? 0;
                    $rate = $uploaded > 0 ? round($success / $uploaded * 100) : 0;
                @endphp
                <div id="exam-card-{{ $exam['exam_id'] ?? $loop->index }}" class="bg-white rounded-xl shadow hover:shadow-lg transition overflow-hidden">
                    {{-- Card top --}}
                    <div class="p-5 border-b">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">{{ $exam['exam_name'] ?? '-' }}</h2>
                                <p class="text-xs text-gray-400 mt-0.5">Exam ID: {{ $exam['exam_id'] ?? '-' }}</p>
                            </div>
                            <span class="px-3 py-1 text-sm font-bold rounded-full bg-purple-100 text-purple-700">
                                Set {{ $exam['set_code'] ?? '-' }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-3 text-xs">
                            <span class="px-2.5 py-1 rounded bg-gray-100 text-gray-600">
                                {{ $exam['total_questions'] ?? 0 }} Questions
                            </span>
                            <span class="px-2.5 py-1 rounded bg-blue-50 text-blue-700">
                                Default Negative: {{ $exam['default_negative_mark'] ?? 0 }}
                            </span>
                            @if(!empty($exam['question_negative_marks']) && is_array($exam['question_negative_marks']))
                                @foreach($exam['question_negative_marks'] as $q => $m)
                                    <span class="px-2.5 py-1 rounded bg-amber-50 text-amber-700 border border-amber-200">
                                        Q{{ $q }} → {{ $m }}
                                    </span>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="grid grid-cols-3 divide-x text-center">
                        <div class="py-4">
                            <p class="text-2xl font-bold text-gray-800">{{ $uploaded }}</p>
                            <p class="text-xs text-gray-400 uppercase">Uploaded</p>
                        </div>
                        <div class="py-4">
                            <p class="text-2xl font-bold text-green-600">{{ $success }}</p>
                            <p class="text-xs text-gray-400 uppercase">Success</p>
                        </div>
                        <div class="py-4">
                            <p class="text-2xl font-bold {{ $failed > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $failed }}</p>
                            <p class="text-xs text-gray-400 uppercase">Failed</p>
                        </div>
                    </div>

                    {{-- Success bar --}}
                    <div class="px-5 pb-2">
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $rate === 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ $rate }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ $rate }}% success</p>
                    </div>

                    {{-- Action --}}
                    <div class="p-4 grid grid-cols-2 gap-2">
                        <a href="{{ route('exam.results', $exam['exam_id']) }}"
                           class="block text-center px-4 py-2.5 bg-gray-800 text-white font-semibold rounded-lg hover:bg-gray-900">
                            View Results →
                        </a>
                        <a href="{{ route('omr.scan', ['exam_id' => $exam['exam_id']]) }}"
                           class="block text-center px-4 py-2.5 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700">
                            📤 Scan
                        </a>
                        <a href="{{ route('exam.scan_results', $exam['exam_id']) }}"
                           class="block text-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200">
                            Scan Results
                        </a>
                        @if(!empty($exam['exam_id']))
                            <button type="button" onclick="openDelete({{ $exam['exam_id'] }}, '{{ addslashes($exam['exam_name'] ?? '') }}')"
                                    class="block text-center px-4 py-2 bg-red-600 text-white text-sm font-bold rounded-lg hover:bg-red-700">
                                🗑️ Delete Exam
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- Beautiful success popup (exam create-এর toast-এর বদলে) --}}
<div id="success-modal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4 bg-black/50 backdrop-blur-sm" onclick="if(event.target===this) closeSuccess()">
    <div class="success-pop bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 via-emerald-500 to-teal-500 px-6 pt-8 pb-6 text-center">
            <div class="success-check mx-auto w-16 h-16 rounded-full bg-white flex items-center justify-center mb-3">
                <svg class="w-9 h-9 text-green-600" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white">সফল হয়েছে! 🎉</h3>
        </div>
        <div class="px-6 py-5 text-center">
            <p id="success-message" class="text-gray-700 font-medium"></p>
            <button type="button" onclick="closeSuccess()" class="mt-5 px-8 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-sm font-bold rounded-xl hover:from-green-700 hover:to-emerald-700 shadow-lg shadow-green-200">ঠিক আছে ✓</button>
        </div>
    </div>
</div>

{{-- Delete confirm modal (shared) --}}
<div id="delete-modal" class="hidden fixed inset-0 bg-black/60 z-50 items-center justify-center p-4" onclick="if(event.target===this) closeDelete()">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6 text-center">
        <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-2xl mb-3">⚠️</div>
        <h3 class="font-bold text-gray-800 mb-1">Exam delete করবেন?</h3>
        <p class="text-sm text-gray-500 mb-1"><span id="delete-filename"></span> <span id="delete-id-label" class="text-gray-400"></span></p>
        <p class="text-xs text-red-500 mb-4">এই exam + এর সব scan করা OMR স্থায়ীভাবে মুছে যাবে।</p>
        <div class="flex justify-center gap-2">
            <button type="button" onclick="closeDelete()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-300">Cancel</button>
            <button type="button" id="delete-btn" class="px-5 py-2 bg-red-600 text-white text-sm font-bold rounded-lg hover:bg-red-700">Yes, Delete</button>
        </div>
        <p id="delete-error" class="hidden text-xs text-red-600 font-semibold mt-3"></p>
    </div>
</div>

<script>
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
    (function () {
        const flash = document.getElementById('flash-success');
        if (flash && flash.dataset.message) openSuccess(flash.dataset.message);
    })();

    function openSuccess(message) {
        document.getElementById('success-message').textContent = message;
        const m = document.getElementById('success-modal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        // re-trigger animation
        const card = m.querySelector('.success-pop');
        card.classList.remove('success-pop');
        void card.offsetWidth;
        card.classList.add('success-pop');
    }
    function closeSuccess() {
        const m = document.getElementById('success-modal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    let pendingDeleteId = null;
    function openDelete(id, name = '') {
        pendingDeleteId = id;
        document.getElementById('delete-filename').textContent = name;
        document.getElementById('delete-id-label').textContent = '(ID: ' + id + ')';
        document.getElementById('delete-error').classList.add('hidden');
        const m = document.getElementById('delete-modal');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeDelete() {
        pendingDeleteId = null;
        const m = document.getElementById('delete-modal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeDelete(); closeSuccess(); } });
    document.getElementById('delete-btn').addEventListener('click', async () => {
        if (!pendingDeleteId) return;
        const id = pendingDeleteId;
        const btn = document.getElementById('delete-btn');
        const errEl = document.getElementById('delete-error');
        btn.disabled = true; btn.textContent = 'Deleting…'; btn.classList.add('opacity-60');
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch('/exam/' + id, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: (() => { const f = new FormData(); f.append('_method', 'DELETE'); f.append('_token', token); return f; })(),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
                const msg = data.message || ('Delete হয়নি (HTTP ' + res.status + ')');
                errEl.textContent = msg; errEl.classList.remove('hidden');
                showToast(msg, 'error');
                return;
            }
            const card = document.getElementById('exam-card-' + id);
            if (card) card.remove();
            closeDelete();
            showToast(data.message || 'Exam delete hoise!');
            if (!document.querySelector('[id^="exam-card-"]')) setTimeout(() => window.location.reload(), 800);
        } catch (err) {
            errEl.textContent = 'Network error — আবার চেষ্টা করুন।'; errEl.classList.remove('hidden');
            showToast('Network error — আবার চেষ্টা করুন।', 'error');
        } finally {
            btn.disabled = false; btn.textContent = 'Yes, Delete'; btn.classList.remove('opacity-60');
        }
    });
</script>
</body>
</html>
