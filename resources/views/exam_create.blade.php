<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam - OMR Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .ans-filled { border-color: #34d399 !important; background: #ecfdf5 !important; }
        .ans-filled .ans-q { color: #059669 !important; }
        input[type="number"]::-webkit-inner-spin-button { opacity: 1; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-slate-100 to-cyan-50 min-h-screen">
<div class="max-w-4xl mx-auto py-8 px-4 pb-28">

    {{-- Hero header --}}
    <div class="rounded-2xl shadow-lg p-6 sm:p-8 mb-6 text-white bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-14 -left-6 w-40 h-40 rounded-full bg-white/10"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-blue-200">OMR Scanner</p>
                <h1 class="text-2xl sm:text-3xl font-bold mt-1">📝 New Exam Create</h1>
                <p class="text-blue-100 mt-1 text-sm">প্রশ্নপত্রের সঠিক উত্তর + নেগেটিভ মার্ক সেট করুন</p>
            </div>
            <a href="{{ route('home') }}" class="px-4 py-2 bg-white/20 border border-white/40 rounded-xl text-sm font-semibold hover:bg-white/30">← All Exams</a>
        </div>
        {{-- Steps --}}
        <div class="relative flex flex-wrap gap-2 mt-5 text-xs font-semibold">
            <span class="px-3 py-1.5 rounded-full bg-white text-indigo-700 shadow">1 · Basic Info</span>
            <span class="px-3 py-1.5 rounded-full bg-white/20 border border-white/30">2 · Negative Marks</span>
            <span class="px-3 py-1.5 rounded-full bg-white/20 border border-white/30">3 · Answers</span>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 mb-6 text-sm shadow-sm">
            <p class="font-bold mb-1">⚠️ কিছু ঠিক করতে হবে</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('exam.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Step 1: Basic info --}}
        <div class="bg-white rounded-2xl shadow p-6">
            <div class="flex items-center gap-3 mb-5">
                <span class="w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-bold flex items-center justify-center shadow shadow-indigo-200">1</span>
                <div>
                    <h2 class="font-bold text-gray-800">Basic Info</h2>
                    <p class="text-xs text-gray-400">পরীক্ষার নাম, সেট ও ডিফল্ট নেগেটিভ মার্ক</p>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">📘 Exam Name <span class="text-red-500">*</span></label>
                    <input type="text" name="exam_name" required value="{{ old('exam_name', 'Physics Final Exam') }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 bg-gray-50 focus:bg-white transition" placeholder="Physics Final Exam">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">🔖 Set Code <span class="text-red-500">*</span></label>
                    <input type="text" name="set_code" required value="{{ old('set_code', 'A') }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 bg-gray-50 focus:bg-white transition" placeholder="A">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">➖ Default Negative Mark</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" name="default_negative_mark"
                               value="{{ old('default_negative_mark', '0.25') }}"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 bg-gray-50 focus:bg-white transition">
                        <span class="absolute right-4 top-2.5 text-xs text-gray-400">per wrong</span>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">ভুল উত্তরে কত কাটা যাবে (ডিফল্ট 0.25)</p>
                </div>
            </div>
        </div>

        {{-- Step 2: Question specific negative marks --}}
        <div class="bg-white rounded-2xl shadow p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-1">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-amber-500 text-white text-sm font-bold flex items-center justify-center shadow shadow-amber-200">2</span>
                    <div>
                        <h2 class="font-bold text-gray-800">Question-wise Negative Marks</h2>
                        <p class="text-xs text-gray-400">optional — যেমন Q1 → 1.0, Q5 → 0.5</p>
                    </div>
                </div>
                <button type="button" onclick="addNegRow()" class="text-xs font-bold px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 shadow shadow-purple-200">+ Add Row</button>
            </div>
            <div id="neg-rows" class="space-y-2 mt-4">
                @php $oldNeg = old('question_negative_marks', [['question' => '1', 'mark' => '1.0'], ['question' => '5', 'mark' => '0.5']]); @endphp
                @foreach($oldNeg as $i => $nm)
                    <div class="flex gap-2 neg-row items-center bg-amber-50/60 border border-amber-100 rounded-xl px-2 py-2">
                        <span class="text-xs font-bold text-amber-600 pl-2">Q</span>
                        <input type="text" name="question_negative_marks[{{ $i }}][question]" value="{{ $nm['question'] ?? '' }}" placeholder="Q no (1)"
                               class="w-1/2 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-amber-200 bg-white">
                        <span class="text-gray-300 font-bold">→</span>
                        <input type="number" step="0.01" min="0" name="question_negative_marks[{{ $i }}][mark]" value="{{ $nm['mark'] ?? '' }}" placeholder="mark (1.0)"
                               class="w-1/2 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-amber-200 bg-white">
                        <button type="button" onclick="this.parentElement.remove()" title="Remove" class="w-9 h-9 shrink-0 rounded-lg bg-red-50 text-red-500 font-bold hover:bg-red-100">&times;</button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Step 3: Answers --}}
        <div class="bg-white rounded-2xl shadow p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold flex items-center justify-center shadow shadow-emerald-200">3</span>
                    <div>
                        <h2 class="font-bold text-gray-800">Answers <span class="text-red-500">*</span></h2>
                        <p class="text-xs text-gray-400">সঠিক উত্তর লিখুন — a / b / c / d</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span id="ans-counter" class="text-xs font-bold px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">0 filled</span>
                    <label for="question-count" class="text-xs text-gray-500 font-semibold">Total</label>
                    <input type="number" id="question-count" value="{{ count($oldAns ?? [1]) }}" min="1" max="200"
                           class="w-20 border border-gray-200 rounded-xl px-2 py-1.5 text-sm outline-none focus:ring-2 focus:ring-emerald-200 text-center font-bold">
                </div>
            </div>
            @php
                $oldAns = old('answers');
                if (empty($oldAns)) {
                    $oldAns = array_fill_keys(range(1, 45), '');
                }
            @endphp
            <div id="answer-rows" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                @foreach($oldAns as $qNo => $ans)
                    <div class="answer-row flex gap-2 items-center border border-gray-200 rounded-xl px-2 py-1.5 bg-gray-50 transition {{ $ans !== '' ? 'ans-filled' : '' }}">
                        <span class="ans-q text-xs font-bold text-gray-500 w-10">Q{{ $qNo }}</span>
                        <input type="text" name="answers[{{ $qNo }}]" data-q="{{ $qNo }}" value="{{ $ans }}"
                               maxlength="1" class="ans-input w-full border border-gray-200 rounded-lg px-2 py-1 text-sm font-bold text-center uppercase outline-none focus:ring-2 focus:ring-emerald-200 bg-white" placeholder="–">
                    </div>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-2 mt-4">
                <button type="button" onclick="clearAnswers()" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200">Clear all</button>
                <p class="text-[11px] text-gray-400 self-center">💡 Total বদলালে আগের লেখা উত্তর সংরক্ষিত থাকবে</p>
            </div>
        </div>

        {{-- Sticky submit bar --}}
        <div class="fixed bottom-4 inset-x-4 sm:inset-x-auto sm:right-8 sm:left-auto z-40">
            <div class="bg-gray-900/95 backdrop-blur text-white rounded-2xl shadow-2xl px-5 py-3.5 flex items-center gap-4">
                <div class="text-xs">
                    <p class="text-gray-400">Filled</p>
                    <p class="font-bold text-base leading-none"><span id="ans-counter-2">0</span> answers</p>
                </div>
                <div class="w-px h-9 bg-white/15"></div>
                <a href="{{ route('home') }}" class="text-xs font-semibold text-gray-300 hover:text-white px-2">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-green-500 to-emerald-500 font-bold rounded-xl hover:from-green-600 hover:to-emerald-600 shadow-lg shadow-green-900/40 text-sm">✓ Save Exam</button>
            </div>
        </div>
    </form>
</div>

<script>
    let negIndex = {{ count($oldNeg ?? []) }};
    function addNegRow() {
        const wrap = document.getElementById('neg-rows');
        const div = document.createElement('div');
        div.className = 'flex gap-2 neg-row items-center bg-amber-50/60 border border-amber-100 rounded-xl px-2 py-2';
        div.innerHTML = `<span class="text-xs font-bold text-amber-600 pl-2">Q</span>
            <input type="text" name="question_negative_marks[${negIndex}][question]" placeholder="Q no (1)" class="w-1/2 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-amber-200 bg-white">
            <span class="text-gray-300 font-bold">→</span>
            <input type="number" step="0.01" min="0" name="question_negative_marks[${negIndex}][mark]" placeholder="mark (1.0)" class="w-1/2 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-amber-200 bg-white">
            <button type="button" onclick="this.parentElement.remove()" title="Remove" class="w-9 h-9 shrink-0 rounded-lg bg-red-50 text-red-500 font-bold hover:bg-red-100">&times;</button>`;
        wrap.appendChild(div);
        negIndex++;
    }

    function buildAnswerRow(qNo, val = '') {
        const div = document.createElement('div');
        div.className = 'answer-row flex gap-2 items-center border border-gray-200 rounded-xl px-2 py-1.5 bg-gray-50 transition' + (val !== '' ? ' ans-filled' : '');
        div.innerHTML = `<span class="ans-q text-xs font-bold text-gray-500 w-10">Q${qNo}</span>
            <input type="text" name="answers[${qNo}]" data-q="${qNo}" value="${val}" maxlength="1" class="ans-input w-full border border-gray-200 rounded-lg px-2 py-1 text-sm font-bold text-center uppercase outline-none focus:ring-2 focus:ring-emerald-200 bg-white" placeholder="–">`;
        return div;
    }

    function updateCounter() {
        const inputs = document.querySelectorAll('.ans-input');
        let filled = 0;
        inputs.forEach(i => {
            const has = i.value.trim() !== '';
            i.closest('.answer-row').classList.toggle('ans-filled', has);
            if (has) filled++;
        });
        document.getElementById('ans-counter').textContent = filled + ' / ' + inputs.length + ' filled';
        document.getElementById('ans-counter-2').textContent = filled;
    }

    function clearAnswers() {
        document.querySelectorAll('.ans-input').forEach(i => { i.value = ''; });
        updateCounter();
    }

    function syncAnswerRows() {
        let n = parseInt(document.getElementById('question-count').value) || 0;
        n = Math.max(1, Math.min(200, n));
        const wrap = document.getElementById('answer-rows');
        const current = {};
        wrap.querySelectorAll('.ans-input').forEach(i => { current[i.dataset.q] = i.value; });
        wrap.innerHTML = '';
        for (let i = 1; i <= n; i++) {
            wrap.appendChild(buildAnswerRow(i, current[i] || ''));
        }
        updateCounter();
    }

    document.getElementById('question-count').addEventListener('input', syncAnswerRows);
    document.getElementById('answer-rows').addEventListener('input', e => {
        if (e.target.classList.contains('ans-input')) {
            e.target.value = e.target.value.toLowerCase();
            updateCounter();
        }
    });
    updateCounter();
</script>
</body>
</html>
