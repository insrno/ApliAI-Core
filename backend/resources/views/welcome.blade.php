<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="ApliAI - AI-powered resume evaluation. Get instant feedback on how well your resume matches any job description.">
    <title>ApliAI – AI Resume Evaluator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#fafafa] text-gray-900 font-sans antialiased">

    {{-- Nav --}}
    <nav class="sticky top-0 z-50 bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <div class="w-7 h-7 bg-gray-900 rounded-md flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                    </svg>
                </div>
                <span class="font-bold text-sm">ApliAI</span>
            </a>
            <div class="hidden sm:flex items-center gap-6 text-sm text-gray-500">
                <a href="#how-it-works" class="hover:text-gray-900 transition-colors">How it works</a>
                <a href="#evaluate" class="hover:text-gray-900 transition-colors">Evaluate</a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="pt-24 pb-20 px-6">
        <div class="max-w-2xl mx-auto text-center">
            <p class="text-sm font-medium text-gray-400 mb-4 tracking-wide">AI-POWERED RESUME ANALYSIS</p>
            <h1 class="text-4xl sm:text-5xl font-extrabold leading-tight tracking-tight text-gray-900 mb-5">
                See how your resume matches the job
            </h1>
            <p class="text-base text-gray-500 max-w-lg mx-auto mb-8 leading-relaxed">
                Upload your resume and paste the job description. Get a match score, key strengths, and areas to improve — in seconds.
            </p>
            <a href="#evaluate" class="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-800 transition-colors">
                Try it now
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 5.25 7.5 7.5 7.5-7.5m-15 6 7.5 7.5 7.5-7.5" /></svg>
            </a>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="pb-20 px-6">
        <div class="max-w-4xl mx-auto">
            <div class="grid sm:grid-cols-3 gap-6">
                <div class="text-center p-6">
                    <div class="w-10 h-10 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center text-gray-600 font-bold text-sm">1</div>
                    <h3 class="font-semibold text-sm mb-1">Upload Resume</h3>
                    <p class="text-sm text-gray-400">Drop your PDF resume or click to browse</p>
                </div>
                <div class="text-center p-6">
                    <div class="w-10 h-10 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center text-gray-600 font-bold text-sm">2</div>
                    <h3 class="font-semibold text-sm mb-1">Add Job Description</h3>
                    <p class="text-sm text-gray-400">Paste the job posting you want to apply for</p>
                </div>
                <div class="text-center p-6">
                    <div class="w-10 h-10 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center text-gray-600 font-bold text-sm">3</div>
                    <h3 class="font-semibold text-sm mb-1">Get AI Feedback</h3>
                    <p class="text-sm text-gray-400">Receive a score, strengths, and improvement areas</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Evaluate --}}
    <section id="evaluate" class="py-16 px-6">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-2xl font-bold text-center mb-10">Evaluate Your Resume</h2>

            <div class="grid lg:grid-cols-5 gap-8 mb-10">
                {{-- Resume Upload (2 cols) --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Resume</label>
                    <div id="drop-zone" class="drop-zone p-8 text-center h-56 flex flex-col items-center justify-center">
                        <div id="drop-zone-content">
                            <svg class="w-8 h-8 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                            <p class="text-sm text-gray-500 mb-1">Drop PDF here</p>
                            <p class="text-xs text-gray-400">or click to browse</p>
                        </div>
                    </div>
                    <input type="file" id="resume-input" accept=".pdf" class="hidden">
                    <p id="resume-status" class="mt-2 text-xs text-gray-400"></p>
                </div>

                {{-- Job Description (3 cols) --}}
                <div class="lg:col-span-3">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Job Description</label>
                    <div class="space-y-3">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <input type="text" id="job-title" placeholder="Job Title"
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300 transition-all">
                            <input type="text" id="job-company" placeholder="Company (optional)"
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300 transition-all">
                        </div>
                        <textarea id="job-description" rows="7" placeholder="Paste the full job description here..."
                            class="w-full px-4 py-3 bg-white border border-gray-200 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300 transition-all resize-none"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center gap-4 flex-wrap">
                <div class="flex items-center gap-2">
                    <label for="ai-provider" class="text-xs font-medium text-gray-500">AI Model:</label>
                    <select id="ai-provider" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-200">
                        <option value="groq">Groq (Llama 3.3)</option>
                        <option value="gemini">Google Gemini</option>
                        <option value="openai">OpenAI (GPT-4o)</option>
                    </select>
                </div>
                <button id="evaluate-btn" disabled
                    class="inline-flex items-center gap-2 px-8 py-3 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-800 transition-all disabled:opacity-25 disabled:cursor-not-allowed disabled:hover:bg-gray-900">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                    </svg>
                    <span id="evaluate-btn-text">Evaluate with AI</span>
                </button>
            </div>
        </div>
    </section>

    {{-- Loading --}}
    <section id="loading-section" class="hidden py-20 px-6">
        <div class="max-w-sm mx-auto text-center">
            <div class="w-10 h-10 mx-auto mb-4 rounded-full border-[3px] border-gray-200 border-t-gray-900 animate-spin"></div>
            <p class="text-sm font-medium text-gray-600">Analyzing your resume...</p>
            <p class="text-xs text-gray-400 mt-1">This may take up to 15 seconds</p>
        </div>
    </section>

    {{-- Results --}}
    <section id="results-section" class="hidden py-16 px-6">
        <div class="max-w-4xl mx-auto">

            {{-- Score + Summary row --}}
            <div class="flex flex-col md:flex-row items-center gap-10 mb-12">
                <div class="shrink-0">
                    <div class="score-ring">
                        <svg viewBox="0 0 200 200">
                            <circle class="track" cx="100" cy="100" r="85"></circle>
                            <circle id="score-arc" class="progress" cx="100" cy="100" r="85"
                                stroke-dasharray="534.07" stroke-dashoffset="534.07" stroke="#111827"></circle>
                        </svg>
                        <div class="text-center">
                            <span id="score-value" class="text-4xl font-extrabold text-gray-900">0</span>
                            <span class="text-xs text-gray-400 block">/100</span>
                        </div>
                    </div>
                    <p id="score-label" class="text-center text-xs font-medium text-gray-500 mt-3"></p>
                </div>

                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">AI Feedback</h3>
                    <p id="feedback-text" class="text-sm text-gray-600 leading-relaxed whitespace-pre-line"></p>
                </div>
            </div>

            {{-- Strengths & Weaknesses --}}
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        Strengths
                    </h3>
                    <div id="strengths-list" class="space-y-2"></div>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Areas to improve
                    </h3>
                    <div id="weaknesses-list" class="space-y-2"></div>
                </div>
            </div>

            <div class="text-center pt-4 border-t border-gray-100">
                <button id="reset-btn"
                    class="text-sm text-gray-500 hover:text-gray-900 font-medium transition-colors inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                    </svg>
                    Evaluate another resume
                </button>
            </div>
        </div>
    </section>

    {{-- Toast container --}}
    <div id="toast" class="toast"></div>

    {{-- Footer --}}
    <footer class="py-6 px-6 text-center">
        <p class="text-xs text-gray-300">ApliAI · Powered by AI</p>
    </footer>

</body>
</html>
