import './bootstrap';

// ===== GLOBAL ERROR HANDLER =====
window.addEventListener('error', (e) => {
    console.error('Uncaught error:', e.error);
});
window.addEventListener('unhandledrejection', (e) => {
    console.error('Unhandled promise rejection:', e.reason);
});

// ===== SECURITY: HTML escaping to prevent XSS =====
function esc(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

// ===== DOM =====
const dropZone = document.getElementById('drop-zone');
const dropZoneContent = document.getElementById('drop-zone-content');
const resumeInput = document.getElementById('resume-input');
const resumeStatus = document.getElementById('resume-status');
const jobTitle = document.getElementById('job-title');
const jobCompany = document.getElementById('job-company');
const jobDescription = document.getElementById('job-description');
const evaluateBtn = document.getElementById('evaluate-btn');
const evaluateBtnText = document.getElementById('evaluate-btn-text');
const loadingSection = document.getElementById('loading-section');
const resultsSection = document.getElementById('results-section');
const scoreArc = document.getElementById('score-arc');
const scoreValue = document.getElementById('score-value');
const scoreLabel = document.getElementById('score-label');
const feedbackText = document.getElementById('feedback-text');
const strengthsList = document.getElementById('strengths-list');
const weaknessesList = document.getElementById('weaknesses-list');
const resetBtn = document.getElementById('reset-btn');
const aiProvider = document.getElementById('ai-provider');
const atsKeywordsList = document.getElementById('ats-keywords-list');
const atsMatchRate = document.getElementById('ats-match-rate');
const atsProgress = document.getElementById('ats-progress');
const generateTipsBtn = document.getElementById('generate-tips-btn');
const tipsList = document.getElementById('tips-list');
const tipsPlaceholder = document.getElementById('tips-placeholder');
const toast = document.getElementById('toast');
const jobTemplates = document.getElementById('job-templates');
const exportBtn = document.getElementById('export-btn');
const historyNavBtn = document.getElementById('history-nav-btn');
const historyModal = document.getElementById('history-modal');
const closeHistoryBtn = document.getElementById('close-history-btn');
const historyList = document.getElementById('history-list');

let currentResumeId = null;
let currentProvider = 'groq';
let lastUploadedFileKey = null;
let lastUploadedResumeId = null;

let selectedFile = null;

function normalizeProvider(provider) {
    // Gemini is intentionally locked for now.
    if (provider === 'gemini') {
        showToast('Google Gemini is temporarily locked. Switched to Groq.', 'success');
        return 'groq';
    }
    return provider;
}

// ===== TOAST =====
function showToast(message, type = 'error') {
    toast.textContent = message;
    toast.className = `toast toast-${type} show`;
    setTimeout(() => toast.classList.remove('show'), 5000);
}

// ===== DRAG & DROP =====
dropZone.addEventListener('click', () => resumeInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('drag-over');
});

dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file && file.type === 'application/pdf') {
        handleFile(file);
    } else {
        showToast('Please upload a PDF file');
    }
});

resumeInput.addEventListener('change', (e) => {
    if (e.target.files[0]) handleFile(e.target.files[0]);
});

function handleFile(file) {
    selectedFile = file;
    lastUploadedFileKey = null;
    lastUploadedResumeId = null;
    dropZone.classList.add('has-file');
    dropZoneContent.innerHTML = `
        <svg class="w-8 h-8 mx-auto mb-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <p class="text-sm text-green-700 font-medium">${esc(file.name)}</p>
        <p class="text-xs text-gray-400 mt-0.5">${(file.size / 1024).toFixed(0)} KB · Click to change</p>
    `;
    checkReady();
}

// ===== FORM =====
jobTitle.addEventListener('input', checkReady);
jobDescription.addEventListener('input', checkReady);

function checkReady() {
    evaluateBtn.disabled = !(selectedFile && jobTitle.value.trim() && jobDescription.value.trim());
}

// ===== CTRL+ENTER SHORTCUT =====
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && !evaluateBtn.disabled) {
        e.preventDefault();
        evaluateBtn.click();
    }
});

// ===== EVALUATE =====
evaluateBtn.addEventListener('click', async () => {
    if (evaluateBtn.disabled) return;

    evaluateBtn.disabled = true;
    evaluateBtnText.textContent = 'Analyzing...';
    loadingSection.classList.remove('hidden');
    resultsSection.classList.add('hidden');
    loadingSection.scrollIntoView({ behavior: 'smooth', block: 'center' });

    try {
        // 1. Upload resume (skip if same file already uploaded)
        const fileKey = `${selectedFile.name}_${selectedFile.size}_${selectedFile.lastModified}`;
        let resumeId;

        if (lastUploadedFileKey === fileKey && lastUploadedResumeId) {
            resumeId = lastUploadedResumeId;
        } else {
            const form = new FormData();
            form.append('resume', selectedFile);
            form.append('user_id', '1');

            const r1 = await fetch('/api/resumes', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: form,
            });
            if (!r1.ok) throw new Error((await r1.json()).message || 'Resume upload failed');
            const d1 = await r1.json();
            resumeId = d1.data.id;
            lastUploadedFileKey = fileKey;
            lastUploadedResumeId = resumeId;
        }

        // 2. Save job description
        const r2 = await fetch('/api/job-descriptions', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                user_id: 1,
                title: jobTitle.value.trim(),
                company: jobCompany.value.trim() || null,
                description: jobDescription.value.trim(),
            }),
        });
        if (!r2.ok) throw new Error((await r2.json()).message || 'Failed to save job description');
        const d2 = await r2.json();

        // 3. Evaluate
        const r3 = await fetch('/api/evaluate', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                resume_id: resumeId,
                job_description_id: d2.data.id,
                provider: normalizeProvider(aiProvider.value),
            }),
        });

        const d3 = await r3.json();
        if (!r3.ok) throw new Error(d3.message || 'Evaluation failed');

        currentResumeId = resumeId;
        currentProvider = normalizeProvider(aiProvider.value);

        // 4. Fetch ATS Keywords Match (with loading indicator)
        atsKeywordsList.innerHTML = '<span class="text-xs text-gray-400 animate-pulse">Analyzing keywords...</span>';
        atsMatchRate.textContent = '...';

        const r4 = await fetch('/api/keywords', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                resume_id: resumeId,
                job_description_id: d2.data.id,
            }),
        });

        const d4 = r4.ok ? (await r4.json()).data : null;

        loadingSection.classList.add('hidden');
        showResults(d3.data, d4);
        saveToHistory(selectedFile.name, jobTitle.value || 'Untitled Job', d3.data.score);
        showToast('Evaluation complete!', 'success');

    } catch (err) {
        loadingSection.classList.add('hidden');
        showToast(err.message);
    } finally {
        evaluateBtnText.textContent = 'Evaluate with AI';
        checkReady();
    }
});

// ===== RESULTS =====
function showResults(data, keywordsData) {
    resultsSection.classList.remove('hidden');
    setTimeout(() => resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);

    const score = data.score || 0;
    const circ = 534.07;
    const offset = circ - (score / 100) * circ;

    let color = '#111827';
    if (score >= 70) color = '#16a34a';
    else if (score >= 50) color = '#d97706';
    else if (score > 0) color = '#dc2626';

    scoreArc.setAttribute('stroke', color);
    setTimeout(() => { scoreArc.style.strokeDashoffset = offset; }, 50);
    animateNum(scoreValue, 0, score, 1000);

    const labels = [[90, 'Excellent Match'], [70, 'Strong Match'], [50, 'Moderate Match'], [30, 'Weak Match'], [0, 'Poor Match']];
    scoreLabel.textContent = labels.find(([m]) => score >= m)?.[1] || '';

    feedbackText.textContent = data.feedback || 'No feedback available.';

    // Strengths as list items
    strengthsList.innerHTML = '';
    (data.strengths || []).forEach((s, i) => {
        const el = document.createElement('div');
        el.className = 'flex items-start gap-2 animate-fade-in';
        el.style.animationDelay = `${i * 0.08}s`;
        el.innerHTML = `<span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-green-500 shrink-0"></span><span class="text-sm text-gray-600">${esc(s)}</span>`;
        strengthsList.appendChild(el);
    });

    weaknessesList.innerHTML = '';
    (data.weaknesses || []).forEach(item => {
        weaknessesList.innerHTML += `<div class="flex items-start gap-2.5">
            <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            <p class="text-sm text-gray-700 leading-relaxed">${esc(item)}</p>
        </div>`;
    });

    // Handle ATS Keywords
    if (keywordsData) {
        atsMatchRate.textContent = `${keywordsData.match_rate}%`;
        atsProgress.style.width = `${keywordsData.match_rate}%`;
        
        if (keywordsData.match_rate >= 70) atsProgress.className = 'bg-green-500 h-1.5 rounded-full';
        else if (keywordsData.match_rate >= 50) atsProgress.className = 'bg-amber-500 h-1.5 rounded-full';
        else atsProgress.className = 'bg-red-500 h-1.5 rounded-full';

        atsKeywordsList.innerHTML = '';
        
        keywordsData.matched.forEach(kw => {
            atsKeywordsList.innerHTML += `<span class="px-2.5 py-1 bg-green-50 text-green-700 rounded text-xs font-medium border border-green-100 flex items-center gap-1"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>${esc(kw)}</span>`;
        });
        
        keywordsData.missing.forEach(kw => {
            atsKeywordsList.innerHTML += `<span class="px-2.5 py-1 bg-red-50 text-red-700 rounded text-xs font-medium border border-red-100 flex items-center gap-1"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>${esc(kw)}</span>`;
        });
    }

    // Reset Tips section
    tipsList.classList.add('hidden');
    tipsList.innerHTML = '';
    tipsPlaceholder.classList.remove('hidden');
    generateTipsBtn.classList.remove('hidden');
    generateTipsBtn.disabled = false;
    generateTipsBtn.innerHTML = `
        <svg class="w-3.5 h-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0 3.09 3.09Z" /></svg>
        Generate Tips
    `;
}

// ===== TIPS COMPONENT =====
generateTipsBtn.addEventListener('click', async () => {
    if (!currentResumeId) return;

    const defaultBtnContent = `
        <svg class="w-3.5 h-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
        Generate Tips
    `;

    generateTipsBtn.disabled = true;
    generateTipsBtn.innerHTML = `<div class="w-3.5 h-3.5 rounded-full border-2 border-gray-200 border-t-purple-500 animate-spin"></div> Generating...`;
    
    let completed = false;

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 25000);

        const res = await fetch('/api/tips', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ resume_id: currentResumeId, provider: currentProvider }),
            signal: controller.signal,
        });

        clearTimeout(timeoutId);

        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Failed to generate tips');

        if (!Array.isArray(data.data)) {
            throw new Error('Tips response is invalid. Please try again.');
        }

        const priorityOrder = { high: 0, medium: 1, low: 2 };
        const sortedTips = [...data.data].sort((a, b) => {
            const ap = priorityOrder[a?.priority] ?? 99;
            const bp = priorityOrder[b?.priority] ?? 99;
            if (ap !== bp) return ap - bp;
            return (a?.title || '').localeCompare(b?.title || '');
        });

        generateTipsBtn.classList.add('hidden');
        tipsPlaceholder.classList.add('hidden');
        tipsList.classList.remove('hidden');
        tipsList.innerHTML = '';

        sortedTips.forEach((tip, idx) => {
            const priorityColor = tip.priority === 'high' ? 'bg-red-50 text-red-700 border-red-100' : (tip.priority === 'medium' ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-gray-50 text-gray-600 border-gray-100');
            const priorityIcon = tip.priority === 'high' ? '🔴' : (tip.priority === 'medium' ? '🟡' : '⚪');
            
            const card = document.createElement('div');
            card.className = 'bg-white border border-gray-100 p-4 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 animate-fade-in';
            card.style.animationDelay = `${idx * 0.06}s`;
            card.innerHTML = `
                <div class="flex justify-between items-start mb-2">
                    <span class="font-semibold text-gray-900 text-sm leading-snug">${priorityIcon} ${esc(tip.title)}</span>
                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full border ${priorityColor} shrink-0 ml-2">${esc(tip.priority)}</span>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">${esc(tip.description)}</p>
            `;
            tipsList.appendChild(card);
        });

        completed = true;
    } catch (err) {
        if (err.name === 'AbortError') {
            showToast('Generating tips took too long. Please try again.');
        } else {
            showToast(err.message);
        }
        generateTipsBtn.disabled = false;
        generateTipsBtn.innerHTML = `
            <svg class="w-3.5 h-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
            Retry
        `;
    } finally {
        if (!completed && !generateTipsBtn.classList.contains('hidden')) {
            // Ensure button never remains in an indefinite loading state.
            const isRetry = generateTipsBtn.textContent.includes('Retry');
            if (!isRetry) {
                generateTipsBtn.disabled = false;
                generateTipsBtn.innerHTML = defaultBtnContent;
            }
        }
    }
});

function animateNum(el, from, to, dur) {
    const start = performance.now();
    function step(timestamp) {
        const p = Math.min((timestamp - start) / dur, 1);
        el.innerHTML = Math.floor(from + p * (to - from));
        if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

// ===== JOB TEMPLATES =====
const templates = {
    // Technology
    frontend: "We are looking for a Frontend Developer proficient in React, Vue, HTML, CSS, and modern JavaScript. You must understand responsive design, REST APIs, Git, and agile workflows. Experience with TypeScript and testing frameworks like Jest is a plus.",
    backend: "Seeking a Backend Developer with strong PHP/Laravel, Node.js, or Java skills. Must have experience building secure RESTful APIs, managing PostgreSQL/MySQL databases, and working with Docker/AWS. Understanding of CI/CD and software architecture is required.",
    data_analyst: "Looking for a Data Analyst to gather, analyze, and interpret complex data sets. Strong proficiency in SQL, Python/R, and Data Visualization tools (Tableau/Power BI) is required. You will work closely with stakeholders to provide actionable business insights.",
    cloud_architect: "Seeking a Cloud Solutions Architect with deep expertise in AWS, Azure, or Google Cloud. You will design secure, scalable, and highly available cloud infrastructure. Proficiency in Infrastructure as Code (Terraform) and Kubernetes is essential.",
    
    // Business
    project_manager: "We need an Agile Project Manager to lead cross-functional teams. You must have a PMP or Scrum Master certification, excellent communication skills, and expertise with Jira/Asana. Managing timelines, budgets, and stakeholder expectations is key.",
    hr_manager: "Seeking an HR Manager to handle recruitment, employee relations, and performance management. Must have strong knowledge of labor laws, experience with HRIS systems, and a track record of building positive company culture.",
    business_analyst: "Looking for a Business Analyst to bridge the gap between IT and the business. You will gather requirements, create process flowcharts, and write technical specifications. Experience with Agile methodology and tools like Visio is required.",
    
    // Healthcare
    registered_nurse: "Currently hiring a Registered Nurse (RN) for our intensive care unit. Must hold a valid multi-state nursing license, BLS/ACLS certification, and possess strong clinical judgment. Ability to work in a fast-paced environment and provide compassionate patient care is mandatory.",
    medical_assistant: "Seeking a Certified Medical Assistant to perform clinical and administrative duties. Responsibilities include taking vitals, assisting with procedures, and managing electronic health records (EHR). Strong organizational and interpersonal skills are needed.",
    healthcare_admin: "Healthcare Administrator needed to manage hospital operations. Requires a Master's degree in Healthcare Administration (MHA), experience with medical billing compliance, facility management, and budget oversight.",
    
    // Marketing
    marketing: "Digital Marketing Specialist needed to run SEO, SEM, and social media campaigns. You should have strong analytical skills (Google Analytics), copywriting ability, and experience with ad platforms (Facebook Ads, Google Ads). Data-driven mindset is essential.",
    sales_executive: "Driven Sales Executive required for B2B enterprise software sales. You must have a proven track record of meeting quotas, excellent negotiation skills, and proficiency using CRM software (Salesforce). Building and maintaining client relationships is critical.",
    content_writer: "Looking for a creative Content Writer to produce engaging blog posts, website copy, and marketing materials. Must have flawless grammar, SEO writing skills, and the ability to adapt tone for different audiences.",
    
    // Engineering & Design
    design: "UI/UX Designer required to create intuitive, beautiful interfaces. Proficient in Figma, wireframing, and prototyping. Must understand user research, design systems, and collaborate closely with engineering teams to bring products to life.",
    mechanical_engineer: "Seeking a Mechanical Engineer to design and test mechanical systems. Proficiency in CAD software (SolidWorks/AutoCAD), understanding of thermodynamics and materials science, and ability to manage product lifecycle from concept to production is required.",
    graphic_designer: "Looking for a Graphic Designer to create visual concepts for digital and print media. Must be an expert in Adobe Creative Suite (Photoshop, Illustrator, InDesign) with a strong portfolio demonstrating typography, layout, and color theory skills."
};

jobTemplates.addEventListener('change', (e) => {
    const val = e.target.value;
    if (templates[val]) {
        jobDescription.value = templates[val];
        jobTitle.value = jobTemplates.options[jobTemplates.selectedIndex].text;
        checkReady();
    }
});

// ===== EXPORT PDF =====
exportBtn.addEventListener('click', () => {
    // Let CSS print styles control a clean PDF-like layout.
    window.print();
});

aiProvider.addEventListener('change', () => {
    if (aiProvider.value === 'gemini') {
        aiProvider.value = 'groq';
        showToast('Google Gemini is temporarily locked.', 'error');
    }
});

// ===== EVALUATION HISTORY =====
function saveToHistory(resumeName, jobName, score) {
    const history = JSON.parse(localStorage.getItem('apliai_history') || '[]');
    history.unshift({
        id: Date.now(),
        date: new Date().toLocaleDateString(),
        resume: resumeName,
        job: jobName,
        score: score
    });
    // Keep only last 10
    if (history.length > 10) history.pop();
    localStorage.setItem('apliai_history', JSON.stringify(history));
}

function renderHistory() {
    const history = JSON.parse(localStorage.getItem('apliai_history') || '[]');
    if (history.length === 0) {
        historyList.innerHTML = 'No past evaluations found.';
        return;
    }
    
    historyList.innerHTML = history.map(item => `
        <div class="bg-white p-4 rounded-xl border border-gray-100 flex items-center justify-between text-left shadow-sm">
            <div>
                <h4 class="font-bold text-gray-900 text-sm mb-1">${esc(item.job)}</h4>
                <p class="text-xs text-gray-500 line-clamp-1">${esc(item.resume)}</p>
                <p class="text-[10px] text-gray-400 mt-1">${item.date}</p>
            </div>
            <div class="shrink-0 w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg ${
                item.score >= 70 ? 'bg-green-50 text-green-700' : (item.score >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700')
            }">
                ${item.score}
            </div>
        </div>
    `).join('');
}

historyNavBtn.addEventListener('click', () => {
    renderHistory();
    historyModal.classList.remove('hidden');
});

closeHistoryBtn.addEventListener('click', () => {
    historyModal.classList.add('hidden');
});

historyModal.addEventListener('click', (e) => {
    if (e.target === historyModal) historyModal.classList.add('hidden');
});

// ===== RESET =====
resetBtn.addEventListener('click', () => {
    selectedFile = null;
    lastUploadedFileKey = null;
    lastUploadedResumeId = null;
    resumeInput.value = '';
    jobTitle.value = '';
    jobCompany.value = '';
    jobDescription.value = '';
    evaluateBtn.disabled = true;

    dropZone.classList.remove('has-file');
    dropZoneContent.innerHTML = `
        <svg class="w-8 h-8 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
        </svg>
        <p class="text-sm text-gray-500 mb-1">Drop PDF here</p>
        <p class="text-xs text-gray-400">or click to browse</p>
    `;

    resultsSection.classList.add('hidden');
    scoreArc.style.strokeDashoffset = '534.07';
    scoreValue.textContent = '0';
    document.getElementById('evaluate').scrollIntoView({ behavior: 'smooth' });
});
