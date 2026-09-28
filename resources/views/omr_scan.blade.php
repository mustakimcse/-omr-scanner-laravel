<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan OMR - OMR Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<div class="max-w-3xl mx-auto py-8 px-4">

    <div class="rounded-2xl shadow p-6 mb-6 text-white bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-500">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Scan Answer Scripts</h1>
                <p class="text-purple-100 mt-1">Exam select করে student OMR ছবি upload করুন</p>
            </div>
            <a href="{{ route('home') }}" class="px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 text-sm font-semibold">← All Exams</a>
        </div>
    </div>

    @if(session('success'))
        <script>
            alert(@json(session('success')));
        </script>
    @endif

    @if(!empty($load_error))
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded-lg p-4 mb-6 text-sm">
            {{ $load_error }}
        </div>
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

    <form action="{{ route('omr.scan.upload') }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-xl shadow p-6 space-y-6" onsubmit="return confirmUpload()">
        @csrf

        {{-- Exam select --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Exam *</label>
            @if(!empty($exams))
                <select name="exam_id" required class="w-full border rounded px-3 py-2.5 outline-none focus:ring focus:ring-purple-200 bg-white">
                    <option value="">— Exam select করুন —</option>
                    @foreach($exams as $exam)
                        <option value="{{ $exam['exam_id'] }}"
                            {{ (string) old('exam_id', $selected_exam ?? '') === (string) $exam['exam_id'] ? 'selected' : '' }}>
                            #{{ $exam['exam_id'] }} — {{ $exam['exam_name'] }} (Set {{ $exam['set_code'] }})
                        </option>
                    @endforeach
                </select>
            @else
                <input type="number" name="exam_id" required value="{{ old('exam_id', $selected_exam ?? '') }}" min="1"
                       class="w-full border rounded px-3 py-2.5 outline-none focus:ring focus:ring-purple-200" placeholder="Exam ID লিখুন (যেমন 2)">
            @endif
        </div>

        {{-- Files --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">OMR Images * <span class="font-normal text-gray-400">(একসাথে একাধিক ছবি দেওয়া যাবে)</span></label>
            <label for="omr-files"
                   class="flex flex-col items-center justify-center border-2 border-dashed border-purple-300 rounded-xl p-8 cursor-pointer hover:border-purple-500 hover:bg-purple-50/50 transition">
                <p class="text-4xl mb-2">📤</p>
                <p class="text-sm font-semibold text-gray-700">ছবি select করুন বা এখানে drop করুন</p>
                <p class="text-xs text-gray-400 mt-1">JPG / PNG / WEBP — প্রতিটি সর্বোচ্চ 10MB</p>
                <p id="file-count" class="text-xs font-bold text-purple-600 mt-2"></p>
            </label>
            <input type="file" id="omr-files" name="files[]" multiple required accept="image/*" class="hidden">
            <div id="preview" class="grid grid-cols-3 sm:grid-cols-5 gap-2 mt-3"></div>
        </div>

        <div class="pt-1">
            <button type="submit" id="scan-btn" class="w-full px-6 py-3 bg-purple-600 text-white font-bold rounded-lg hover:bg-purple-700 disabled:opacity-50">
                Scan Start করুন
            </button>
        </div>
    </form>
</div>

<script>
    const fileInput = document.getElementById('omr-files');
    const preview = document.getElementById('preview');
    const fileCount = document.getElementById('file-count');
    const dt = new DataTransfer(); // file list (delete সমর্থনের জন্য)
    let previewUrls = [];

    fileInput.addEventListener('change', () => {
        // নতুন select করা ফাইলগুলো আগেরগুলোর সাথে যোগ হবে
        [...fileInput.files].forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
        renderPreview();
    });

    function renderPreview() {
        previewUrls.forEach(u => URL.revokeObjectURL(u));
        previewUrls = [];
        preview.innerHTML = '';
        const files = [...dt.files];
        fileCount.textContent = files.length ? files.length + 'টি ছবি select হয়েছে' : '';
        files.slice(0, 30).forEach((f, idx) => {
            const url = URL.createObjectURL(f);
            previewUrls.push(url);
            const div = document.createElement('div');
            div.className = 'relative border rounded overflow-hidden bg-gray-50 group';
            div.innerHTML = `<img src="${url}" class="w-full h-20 object-cover cursor-zoom-in" onclick="openLightbox('${url}')">
                <p class="text-[10px] text-gray-500 truncate px-1 py-0.5">${f.name}</p>
                <button type="button" onclick="removeFile(${idx})"
                    class="absolute top-0 right-0 w-6 h-6 bg-red-600 text-white text-sm font-bold rounded-bl hover:bg-red-700" title="Delete">×</button>`;
            preview.appendChild(div);
        });
        if (files.length > 30) {
            const more = document.createElement('p');
            more.className = 'text-xs text-gray-400 col-span-full';
            more.textContent = '+' + (files.length - 30) + 'টি আরও...';
            preview.appendChild(more);
        }
    }

    function removeFile(idx) {
        dt.items.remove(idx);
        fileInput.files = dt.files;
        renderPreview();
    }

    function confirmUpload() {
        const n = fileInput.files.length;
        const exam = document.querySelector('[name="exam_id"]').value;
        if (!exam || !n) return true;
        const btn = document.getElementById('scan-btn');
        btn.disabled = true;
        btn.textContent = 'Scanning... অপেক্ষা করুন';
        return true;
    }

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
</script>

{{-- Lightbox for full image --}}
<div id="lightbox" class="fixed inset-0 bg-black/85 hidden items-center justify-center z-50 p-4" onclick="closeLightbox()">
    <img id="lightbox-img" src="" alt="Full OMR" class="max-w-full rounded shadow-lg bg-white" style="max-height: 92vh">
    <button type="button" class="absolute top-4 right-6 text-white text-3xl font-bold" onclick="closeLightbox()">×</button>
</div>
</body>
</html>
