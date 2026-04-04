import './bootstrap';

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
const toast = document.getElementById('toast');

let selectedFile = null;

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

// ===== EVALUATE =====
evaluateBtn.addEventListener('click', async () => {
    if (evaluateBtn.disabled) return;

    evaluateBtn.disabled = true;
    evaluateBtnText.textContent = 'Analyzing...';
    loadingSection.classList.remove('hidden');
    resultsSection.classList.add('hidden');
    loadingSection.scrollIntoView({ behavior: 'smooth', block: 'center' });

    try {
        // 1. Upload resume
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
                resume_id: d1.data.id,
                job_description_id: d2.data.id,
                provider: aiProvider.value,
            }),
        });

        const d3 = await r3.json();
        if (!r3.ok) throw new Error(d3.message || 'Evaluation failed');

        loadingSection.classList.add('hidden');
        showResults(d3.data);

    } catch (err) {
        loadingSection.classList.add('hidden');
        showToast(err.message);
        console.error('Evaluation error:', err);
    } finally {
        evaluateBtnText.textContent = 'Evaluate with AI';
        checkReady();
    }
});

// ===== RESULTS =====
function showResults(data) {
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
    (data.weaknesses || []).forEach((w, i) => {
        const el = document.createElement('div');
        el.className = 'flex items-start gap-2 animate-fade-in';
        el.style.animationDelay = `${i * 0.08}s`;
        el.innerHTML = `<span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span><span class="text-sm text-gray-600">${esc(w)}</span>`;
        weaknessesList.appendChild(el);
    });
}

function animateNum(el, from, to, dur) {
    const start = performance.now();
    (function tick(now) {
        const p = Math.min((now - start) / dur, 1);
        el.textContent = Math.round(from + (to - from) * (1 - Math.pow(1 - p, 3)));
        if (p < 1) requestAnimationFrame(tick);
    })(start);
}

// ===== RESET =====
resetBtn.addEventListener('click', () => {
    selectedFile = null;
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
