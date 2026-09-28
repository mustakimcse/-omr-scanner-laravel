<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not Found - OMR Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-indigo-900 to-purple-900 px-4">
    <div class="max-w-md w-full text-center">
        <div class="bg-white/10 backdrop-blur rounded-3xl shadow-2xl p-10 border border-white/20">
            <p class="text-8xl font-black text-white/90">404</p>
            <div class="w-16 h-1 bg-gradient-to-r from-cyan-400 to-purple-400 rounded-full mx-auto my-5"></div>
            <h1 class="text-xl font-bold text-white">Data পাওয়া যায়নি</h1>
            <p class="text-indigo-200 text-sm mt-2">
                {{ $exception->getMessage() ?: 'আপনি যে পেজটি খুঁজছেন সেটি নেই বা সরিয়ে নেওয়া হয়েছে।' }}
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center mt-7">
                <a href="{{ route('home') }}"
                   class="px-6 py-2.5 bg-white text-indigo-700 font-semibold rounded-lg hover:bg-indigo-50">← All Exams</a>
                <button onclick="history.back()"
                        class="px-6 py-2.5 bg-white/15 text-white font-semibold rounded-lg border border-white/25 hover:bg-white/25">Go Back</button>
            </div>
        </div>
        <p class="text-indigo-300/60 text-xs mt-5">OMR Scanner</p>
    </div>
</body>
</html>
