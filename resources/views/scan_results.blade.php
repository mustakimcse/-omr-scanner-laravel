<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scan Results - Exam #{{ $exam_id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .answer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(52px, 1fr)); gap: 6px; }
        .omr-img { cursor: zoom-in; transition: transform .2s; }
        .omr-img:hover { transform: scale(1.01); }
        #lightbox img { max-height: 92vh; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- Toast (non-blocking, replaces alert()) --}}
    <div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white"></div>

    @if(session('success'))
        <div id="flash-success" data-message="{{ session('success') }}"></div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-300 text-red-700 rounded-lg p-4 mb-6 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Exam #{{ $exam_id }} - Scan Results</h1>
                <p class="text-gray-500 mt-1">Image দেখে detected answer যাচাই করুন</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-5 py-3 text-center">
                    <p class="text-xs text-blue-500 font-semibold uppercase">Submissions</p>
                    <p class="text-xl font-bold text-blue-700">{{ $total_submissions }}</p>
                </div>
                <a href="{{ route('exam.results', $exam_id) }}"
                   class="px-4 py-2.5 bg-gray-800 text-white text-sm font-semibold rounded-lg hover:bg-gray-900">View Marks →</a>
                <a href="{{ route('omr.scan', ['exam_id' => $exam_id]) }}"
                   class="px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700">📤 Scan More</a>
                <a href="{{ route('home') }}" class="px-4 py-2.5 bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-300">← All Exams</a>
            </div>
        </div>
    </div>

    @php $apiBase = $api_base ?? 'https://new-omr-scanner-with-fast-api.onrender.com'; @endphp

    @if(empty($results))
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded-lg p-6 text-center">
            কোনো scan data পাওয়া যায়নি।
        </div>
    @else
        <div class="space-y-6">
            @foreach($results as $index => $row)
                @php
                    $imgUrl = !empty($row['saved_path']) ? rtrim($apiBase, '/') . '/' . ltrim($row['saved_path'], '/') : null;
                @endphp
                <div id="result-card-{{ $row['id'] ?? $index }}" class="bg-white rounded-xl shadow overflow-hidden">
                    {{-- Card header --}}
                    <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b bg-gray-50">
                        <span class="text-lg font-bold text-gray-700">#{{ $index + 1 }}</span>
                        <div class="flex-1 min-w-[180px]">
                            <p class="font-semibold text-gray-800">{{ $row['filename'] ?? '-' }}</p>
                            <p class="text-xs text-gray-400">ID: {{ $row['id'] ?? '-' }} | Path: {{ $row['saved_path'] ?? '-' }}</p>
                        </div>
                        <span id="roll-badge-{{ $row['id'] ?? $index }}" class="px-2 py-1 text-sm font-semibold rounded {{ ($row['roll'] ?? '') === 'Blank' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                            Roll: {{ $row['roll'] ?? '-' }}
                        </span>
                        <span id="set-badge-{{ $row['id'] ?? $index }}" class="px-2 py-1 text-sm font-semibold rounded {{ ($row['set_code'] ?? '') === 'Blank' ? 'bg-red-100 text-red-700' : 'bg-purple-100 text-purple-700' }}">
                            Set: {{ $row['set_code'] ?? '-' }}
                        </span>
                        @if(($row['status'] ?? '') === 'success')
                            <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded-full">success</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded-full">{{ $row['status'] ?? 'failed' }}</span>
                        @endif
                        @if(!empty($row['error']))
                            <p class="w-full text-xs text-red-500">{{ $row['error'] }}</p>
                        @endif
                        @if(!empty($row['id']))
                            <button type="button" onclick="openCorrection({{ $row['id'] }})"
                                    class="px-3 py-1.5 bg-amber-500 text-white text-xs font-bold rounded-lg hover:bg-amber-600">✏️ Correction</button>
                            <button type="button" onclick="openDelete({{ $row['id'] }})"
                                    class="px-3 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700">🗑️ Delete</button>
                        @endif
                    </div>

                    {{-- Card body: image + answers --}}
                    <div class="grid md:grid-cols-2 gap-0">
                        <div class="p-4 border-b md:border-b-0 md:border-r bg-gray-50">
                            <p class="text-sm font-semibold text-gray-600 mb-2">Scanned OMR Sheet</p>
                            @if($imgUrl)
                                <img src="{{ $imgUrl }}" alt="OMR {{ $row['filename'] ?? '' }}" loading="lazy"
                                     class="omr-img w-full rounded border shadow-sm bg-white"
                                     onclick="openLightbox('{{ $imgUrl }}')"
                                     onerror="this.outerHTML='<div class=\'bg-red-50 border border-red-200 text-red-600 text-sm rounded p-4 text-center\'>Image load হয়নি</div>'">
                                <a href="{{ $imgUrl }}" target="_blank" class="inline-block mt-2 text-xs text-blue-600 hover:underline">Full size খুলুন ↗</a>
                            @else
                                <div class="bg-gray-100 border rounded p-6 text-center text-sm text-gray-400">Image path পাওয়া যায়নি</div>
                            @endif
                        </div>

                        <div class="p-4">
                            <p class="text-sm font-semibold text-gray-600 mb-2">
                                Detected Answers
                                @if(!empty($row['mcq_answers']) && is_array($row['mcq_answers']))
                                    <span class="text-gray-400 font-normal">({{ count($row['mcq_answers']) }}টি)</span>
                                @endif
                            </p>
                            @if(!empty($row['mcq_answers']) && is_array($row['mcq_answers']))
                                <div class="answer-grid" id="answers-{{ $row['id'] ?? $index }}">
                                    @foreach($row['mcq_answers'] as $qNo => $ans)
                                        <div id="ans-box-{{ $row['id'] ?? $index }}-{{ $qNo }}" class="border rounded px-1 py-1.5 text-center {{ $ans === 'Unanswered' ? 'bg-yellow-50 border-yellow-400' : 'bg-white border-gray-200' }}">
                                            <p class="text-[10px] text-gray-400 font-semibold leading-none mb-1">Q{{ $qNo }}</p>
                                            <p id="ans-{{ $row['id'] ?? $index }}-{{ $qNo }}" class="text-sm font-bold leading-none {{ $ans === 'Unanswered' ? 'text-yellow-600' : 'text-gray-800' }}">{{ $ans }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </div>
                    </div>

                    {{-- Correction modal --}}
                    @if(!empty($row['id']))
                    <div id="correction-{{ $row['id'] }}" class="hidden fixed inset-0 bg-black/60 z-50 items-center justify-center p-4" onclick="if(event.target===this) closeCorrection({{ $row['id'] }})">
                        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                            <div class="flex items-center justify-between px-5 py-4 border-b sticky top-0 bg-white">
                                <h3 class="font-bold text-gray-800">Correction — {{ $row['filename'] ?? '' }} <span class="text-xs text-gray-400 font-normal">(ID: {{ $row['id'] }})</span></h3>
                                <button type="button" onclick="closeCorrection({{ $row['id'] }})" class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none">&times;</button>
                            </div>
                            <form action="{{ route('exam.result.correct', $row['id']) }}" method="POST" class="p-5 space-y-4" data-correction-form data-result-id="{{ $row['id'] }}">
                                @csrf
                                @method('PATCH')
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Roll</label>
                                        <input type="text" name="roll" value="{{ $row['roll'] ?? '' }}" class="w-full border rounded px-3 py-2 text-sm outline-none focus:ring focus:ring-amber-200">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Set Code</label>
                                        <input type="text" name="set_code" value="{{ $row['set_code'] ?? '' }}" class="w-full border rounded px-3 py-2 text-sm outline-none focus:ring focus:ring-amber-200">
                                    </div>
                                </div>
                                @if(!empty($row['mcq_answers']) && is_array($row['mcq_answers']))
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-2">MCQ Answers <span class="font-normal text-gray-400">(ভুলটা ঠিক করে দিন — a/b/c/d)</span></label>
                                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                                            @foreach($row['mcq_answers'] as $qNo => $ans)
                                                <div class="flex items-center gap-1 border rounded px-1.5 py-1 {{ $ans === 'Unanswered' ? 'bg-yellow-50 border-yellow-300' : 'bg-gray-50' }}">
                                                    <span class="text-[10px] font-bold text-gray-400 w-6">Q{{ $qNo }}</span>
                                                    <input type="text" name="mcq_answers[{{ $qNo }}]" value="{{ $ans === 'Unanswered' ? '' : $ans }}" maxlength="1" placeholder="-"
                                                           class="w-full border rounded px-1 py-0.5 text-sm font-bold text-center outline-none focus:ring focus:ring-amber-200">
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="text-[11px] text-gray-400 mt-1">খালি রাখলে সেই প্রশ্নের উত্তর পাঠানো হবে না।</p>
                                    </div>
                                @endif
                                <div class="flex justify-end gap-2 pt-1">
                                    <button type="button" onclick="closeCorrection({{ $row['id'] }})" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-300">Cancel</button>
                                    <button type="submit" data-submit-btn class="px-5 py-2 bg-amber-500 text-white text-sm font-bold rounded-lg hover:bg-amber-600">Save Correction</button>
                                </div>
                                <p data-form-error class="hidden text-xs text-red-600 font-semibold"></p>
                            </form>
                        </div>
                    </div>
                    @endif
                    {{-- Delete confirm modal (alert/confirm এর বদলে) --}}
                    @if(!empty($row['id']))
                    <div id="delete-{{ $row['id'] }}" class="hidden fixed inset-0 bg-black/60 z-50 items-center justify-center p-4" onclick="if(event.target===this) closeDelete({{ $row['id'] }})">
                        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6 text-center">
                            <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-2xl mb-3">🗑️</div>
                            <h3 class="font-bold text-gray-800 mb-1">Delete করবেন?</h3>
                            <p class="text-sm text-gray-500 mb-1">{{ $row['filename'] ?? '' }} <span class="text-gray-400">(ID: {{ $row['id'] }})</span></p>
                            <p class="text-xs text-red-500 mb-4">এই student-এর scan করা OMR স্থায়ীভাবে মুছে যাবে।</p>
                            <div class="flex justify-center gap-2">
                                <button type="button" onclick="closeDelete({{ $row['id'] }})" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-300">Cancel</button>
                                <button type="button" data-delete-btn="{{ $row['id'] }}" onclick="doDelete({{ $row['id'] }})" class="px-5 py-2 bg-red-600 text-white text-sm font-bold rounded-lg hover:bg-red-700">Yes, Delete</button>
                            </div>
                            <p data-delete-error="{{ $row['id'] }}" class="hidden text-xs text-red-600 font-semibold mt-3"></p>
                        </div>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <p class="text-center text-gray-400 text-sm mt-6">Showing {{ count($results ?? []) }} of {{ $total_submissions }} submissions</p>
</div>

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
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
    function openCorrection(id) {
        const m = document.getElementById('correction-' + id);
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeCorrection(id) {
        const m = document.getElementById('correction-' + id);
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') document.querySelectorAll('[id^="correction-"]').forEach(m => { m.classList.add('hidden'); m.classList.remove('flex'); });
    });
    function openDelete(id) {
        const m = document.getElementById('delete-' + id);
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeDelete(id) {
        const m = document.getElementById('delete-' + id);
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') document.querySelectorAll('[id^="delete-"]').forEach(m => { m.classList.add('hidden'); m.classList.remove('flex'); });
    });

    async function doDelete(id) {
        const btn = document.querySelector('[data-delete-btn="' + id + '"]');
        const errEl = document.querySelector('[data-delete-error="' + id + '"]');
        if (errEl) errEl.classList.add('hidden');
        const originalText = btn ? btn.textContent : '';
        if (btn) { btn.disabled = true; btn.textContent = 'Deleting…'; btn.classList.add('opacity-60'); }
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

        try {
            const res = await fetch('/exam/result/' + id, {
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
            // Card instant remove (reload ছাড়াই)
            const card = document.getElementById('result-card-' + id);
            if (card) card.remove();
            const corr = document.getElementById('correction-' + id);
            if (corr) corr.remove();
            const del = document.getElementById('delete-' + id);
            if (del) del.remove();
            showToast(data.message || 'OMR delete hoise!');
        } catch (err) {
            const msg = 'Network error — আবার চেষ্টা করুন।';
            if (errEl) { errEl.textContent = msg; errEl.classList.remove('hidden'); }
            showToast(msg, 'error');
        } finally {
            if (btn && document.body.contains(btn)) { btn.disabled = false; btn.textContent = originalText; btn.classList.remove('opacity-60'); }
        }
    }

    // Non-blocking toast (alert() এর বদলে)
    let toastTimer = null;
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('hidden', 'bg-green-600', 'bg-red-600', 'bg-gray-800');
        toast.classList.add(type === 'error' ? 'bg-red-600' : 'bg-green-600');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 2500);
    }

    // Flash message (যেমন Scan upload) toast আকারে দেখাও — alert() নয়
    (function () {
        const flash = document.getElementById('flash-success');
        if (flash && flash.dataset.message) showToast(flash.dataset.message, 'success');
    })();

    function updateRowUI(resultId, form) {
        const rollInput = form.querySelector('input[name="roll"]');
        const setInput = form.querySelector('input[name="set_code"]');
        if (rollInput && rollInput.value.trim() !== '') {
            const badge = document.getElementById('roll-badge-' + resultId);
            if (badge) {
                const val = rollInput.value.trim();
                badge.textContent = 'Roll: ' + val;
                const isBlank = val.toLowerCase() === 'blank';
                badge.className = 'px-2 py-1 text-sm font-semibold rounded ' + (isBlank ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700');
            }
        }
        if (setInput && setInput.value.trim() !== '') {
            const badge = document.getElementById('set-badge-' + resultId);
            if (badge) {
                const val = setInput.value.trim();
                badge.textContent = 'Set: ' + val;
                const isBlank = val.toLowerCase() === 'blank';
                badge.className = 'px-2 py-1 text-sm font-semibold rounded ' + (isBlank ? 'bg-red-100 text-red-700' : 'bg-purple-100 text-purple-700');
            }
        }
        // MCQ answers instant update
        form.querySelectorAll('input[name^="mcq_answers["]').forEach(input => {
            const m = input.name.match(/mcq_answers\[(.+)\]/);
            if (!m) return;
            const qNo = m[1];
            const val = input.value.trim().toLowerCase();
            if (val === '') return; // খালি মানে সার্ভারে পাঠানো হয়নি, UI অপরিবর্তিত
            const el = document.getElementById('ans-' + resultId + '-' + qNo);
            const box = document.getElementById('ans-box-' + resultId + '-' + qNo);
            if (el) {
                el.textContent = val;
                el.classList.remove('text-yellow-600');
                el.classList.add('text-gray-800');
            }
            if (box) {
                box.classList.remove('bg-yellow-50', 'border-yellow-400');
                box.classList.add('bg-white', 'border-gray-200');
            }
        });
    }

    // Correction form → AJAX: popup close + data instant update, কোনো alert() নেই
    document.querySelectorAll('[data-correction-form]').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const resultId = form.dataset.resultId;
            const btn = form.querySelector('[data-submit-btn]');
            const errEl = form.querySelector('[data-form-error]');
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
                closeCorrection(resultId);      // popup instant close
                updateRowUI(resultId, form);    // data instant update (reload ছাড়াই)
                showToast(data.message || 'Correction saved!');
            } catch (err) {
                const msg = 'Network error — আবার চেষ্টা করুন।';
                if (errEl) { errEl.textContent = msg; errEl.classList.remove('hidden'); }
                showToast(msg, 'error');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = originalText; btn.classList.remove('opacity-60'); }
            }
        });
    });
</script>
</body>
</html>
