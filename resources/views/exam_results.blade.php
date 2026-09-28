<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Exam Marks - Exam #{{ $exam_id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .omr-thumb { cursor: zoom-in; transition: transform .2s; }
        .omr-thumb:hover { transform: scale(1.05); }
        #lightbox img { max-height: 92vh; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- Toast (non-blocking, replaces alert()) --}}
    <div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white bg-green-600"></div>

    @if(session('success'))
        <div id="flash-success" data-message="{{ session('success') }}"></div>
    @endif

    @php
        $apiBase = $api_base ?? 'https://new-omr-scanner-with-fast-api.onrender.com';
        $allRows = collect($results_by_set ?? [])->flatten(1);
        $highest = $allRows->max('obtained_marks') ?? 0;
        $average = $allRows->count() ? round($allRows->avg('obtained_marks'), 2) : 0;
    @endphp

    {{-- Header --}}
    <div class="rounded-2xl shadow p-6 mb-6 text-white bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-500">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Exam #{{ $exam_id }} - Marks</h1>
                <p class="text-teal-100 mt-1">Set-wise result sheet</p>
            </div>
            <a href="{{ route('home') }}" class="px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 text-sm font-semibold">← All Exams</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5">
            <div class="bg-white/15 backdrop-blur rounded-xl px-5 py-3 text-center">
                <p class="text-xs uppercase text-teal-100">Students</p>
                <p class="text-2xl font-bold">{{ $total_students }}</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl px-5 py-3 text-center">
                <p class="text-xs uppercase text-teal-100">Sets</p>
                <p class="text-2xl font-bold">{{ count($results_by_set ?? []) }}</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl px-5 py-3 text-center">
                <p class="text-xs uppercase text-teal-100">Highest</p>
                <p class="text-2xl font-bold">{{ $highest }}</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl px-5 py-3 text-center">
                <p class="text-xs uppercase text-teal-100">Average</p>
                <p class="text-2xl font-bold">{{ $average }}</p>
            </div>
        </div>
    </div>

    @if(empty($results_by_set))
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded-lg p-6 text-center">
            No marks found for this exam.
        </div>
    @else
        <div class="space-y-8">
            @foreach($results_by_set as $set => $rows)
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    {{-- Set header --}}
                    <div class="flex items-center gap-3 px-5 py-4 bg-gray-800">
                        <span class="px-3 py-1 text-sm font-bold rounded-full {{ $set === 'Blank' ? 'bg-red-500 text-white' : 'bg-purple-500 text-white' }}">
                            Set {{ $set }}
                        </span>
                        <span class="text-gray-300 text-sm">{{ count($rows) }} students</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sheet</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Roll</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-green-600 uppercase">Correct</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-red-500 uppercase">Wrong</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-yellow-600 uppercase">Skipped</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Marks</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($rows as $i => $st)
                                    @php
                                        $imgUrl = !empty($st['saved_path']) ? rtrim($apiBase, '/') . '/' . ltrim($st['saved_path'], '/') : null;
                                        $isTop = ($st['obtained_marks'] ?? 0) == $highest && $highest > 0;
                                    @endphp
                                    <tr id="result-row-{{ $st['id'] ?? ($i + 1) }}" class="hover:bg-gray-50 {{ $isTop ? 'bg-green-50/60' : '' }}">
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $i + 1 }}
                                            @if($isTop) 🏆 @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                @if($imgUrl)
                                                    <img src="{{ $imgUrl }}" alt="{{ $st['filename'] ?? '' }}" loading="lazy"
                                                         class="omr-thumb w-14 h-14 object-cover rounded border shadow-sm bg-gray-50"
                                                         onclick="openLightbox('{{ $imgUrl }}')"
                                                         onerror="this.style.display='none'">
                                                @endif
                                                <div>
                                                    <p class="text-sm font-medium text-gray-800">{{ $st['filename'] ?? '-' }}</p>
                                                    <a href="{{ $imgUrl }}" target="_blank" class="text-xs text-blue-500 hover:underline">Full image ↗</a>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-block px-2 py-1 text-sm font-semibold rounded {{ ($st['roll'] ?? '') === 'Blank' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                                {{ $st['roll'] ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm font-bold text-green-600">{{ $st['total_correct'] ?? 0 }}</td>
                                        <td class="px-4 py-3 text-center text-sm font-bold text-red-500">{{ $st['total_wrong'] ?? 0 }}</td>
                                        <td class="px-4 py-3 text-center text-sm font-bold text-yellow-600">{{ $st['skipped'] ?? 0 }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block px-3 py-1 text-base font-bold rounded-lg {{ ($st['obtained_marks'] ?? 0) > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $st['obtained_marks'] ?? 0 }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            @if(!empty($st['id']))
                                                <a href="{{ route('exam.result.view', $st['id']) }}" class="inline-block px-3 py-1.5 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-700">👁️ View</a>
                                                <button type="button" onclick="openDelete({{ $st['id'] }}, '{{ addslashes($st['filename'] ?? '') }}')" class="px-3 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700">🗑️</button>
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <p class="text-center text-gray-400 text-sm mt-6">{{ $total_students }} students across {{ count($results_by_set ?? []) }} sets</p>
</div>

{{-- Delete confirm modal (shared) --}}
<div id="delete-modal" class="hidden fixed inset-0 bg-black/60 z-50 items-center justify-center p-4" onclick="if(event.target===this) closeDelete()">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6 text-center">
        <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-2xl mb-3">🗑️</div>
        <h3 class="font-bold text-gray-800 mb-1">Delete করবেন?</h3>
        <p class="text-sm text-gray-500 mb-1"><span id="delete-filename"></span> <span id="delete-id-label" class="text-gray-400"></span></p>
        <p class="text-xs text-red-500 mb-4">এই student-এর scan করা OMR স্থায়ীভাবে মুছে যাবে।</p>
        <div class="flex justify-center gap-2">
            <button type="button" onclick="closeDelete()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-300">Cancel</button>
            <button type="button" id="delete-btn" class="px-5 py-2 bg-red-600 text-white text-sm font-bold rounded-lg hover:bg-red-700">Yes, Delete</button>
        </div>
        <p id="delete-error" class="hidden text-xs text-red-600 font-semibold mt-3"></p>
    </div>
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

    let toastTimer = null;
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.remove('hidden', 'bg-green-600', 'bg-red-600');
        toast.classList.add(type === 'error' ? 'bg-red-600' : 'bg-green-600');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 2500);
    }

    // Flash message toast আকারে (alert() নয়)
    (function () {
        const flash = document.getElementById('flash-success');
        if (flash && flash.dataset.message) showToast(flash.dataset.message, 'success');
    })();

    let pendingDeleteId = null;
    function openDelete(id, filename = '') {
        pendingDeleteId = id;
        document.getElementById('delete-filename').textContent = filename;
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
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDelete(); });
    document.getElementById('delete-btn').addEventListener('click', async () => {
        if (!pendingDeleteId) return;
        const id = pendingDeleteId;
        const btn = document.getElementById('delete-btn');
        const errEl = document.getElementById('delete-error');
        btn.disabled = true; btn.textContent = 'Deleting…'; btn.classList.add('opacity-60');
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
                errEl.textContent = msg; errEl.classList.remove('hidden');
                showToast(msg, 'error');
                return;
            }
            const row = document.getElementById('result-row-' + id);
            if (row) row.remove();
            closeDelete();
            showToast(data.message || 'OMR delete hoise!');
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
