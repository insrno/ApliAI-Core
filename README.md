# ApliAI

**Live Demo:** [https://apliai-core.onrender.com](https://apliai-core.onrender.com)

ApliAI is an AI-powered ATS simulator that evaluates resumes against job descriptions. It parses uploaded resume PDFs, performs semantic analysis through configurable AI providers, and returns match scores with actionable recommendations.

*(You can drag and drop a screenshot of your website here!)*

---

## Features

* **Resume upload** with PDF parsing and text extraction
* **Job description storage** and management
* **AI-based resume-to-job evaluation** with numeric match score
* **Structured evaluation output:** feedback, strengths, and weaknesses
* **ATS keyword coverage checker** (matched vs missing keywords)
* **AI-generated improvement tips** with priority levels
* **Multiple AI providers with runtime selection:**
  * Groq
  * Google Gemini
  * OpenAI
* **Rate limiting** on sensitive API routes for safer usage

## Tech Stack

* **Backend:** Laravel 12 (PHP 8.2+)
* **Database:** MySQL (Cloud DB via Aiven) or SQLite (supported)
* **Frontend tooling:** Vite + Tailwind CSS
* **Deployment & DevOps:** Docker, Render (Monolith setup)
* **PDF parsing:** `smalot/pdfparser`
* **AI integrations:**
  * Groq (`llama-3.3-70b-versatile`)
  * Gemini (`gemini-2.0-flash`)
  * OpenAI (`gpt-4o-mini`)

---

## API Endpoints

**Base URL (local):** `http://127.0.0.1:8000/api`  
**Base URL (production):** `https://apliai-core.onrender.com/api`

### 1) Upload Resume
* **Method:** `POST`
* **Path:** `/resumes`
* **Content type:** `multipart/form-data`
* **Fields:** `resume` (required, PDF, max 5MB)

**Example:**
bash
curl -X POST http://127.0.0.1:8000/api/resumes \
	-F "resume=@/path/to/resume.pdf"


### 2) Create Job Description
* **Method:** `POST`
* **Path:** `/job-descriptions`
* **JSON body:**
json
{
	"user_id": 1,
	"title": "Backend Developer",
	"company": "Acme Inc",
	"description": "We are looking for a Laravel developer with API and SQL experience..."
}


### 3) Evaluate Resume Against Job Description
* **Method:** `POST`
* **Path:** `/evaluate`
* **JSON body:**
json
{
	"resume_id": 1,
	"job_description_id": 1,
	"provider": "groq"
}

*`provider` is optional and can be `groq`, `gemini`, or `openai`.*

### 4) List Available Providers
* **Method:** `GET`
* **Path:** `/providers`
* Returns provider availability based on configured API keys.

### 5) ATS Keyword Check
* **Method:** `POST`
* **Path:** `/keywords`
* **JSON body:**
json
{
	"resume_id": 1,
	"job_description_id": 1
}


### 6) Generate Resume Improvement Tips
* **Method:** `POST`
* **Path:** `/tips`
* **JSON body:**
json
{
	"resume_id": 1,
	"provider": "groq"
}


---

## Sample Output Format

### Evaluation Response
json
{
	"status": "success",
	"message": "Resume evaluated successfully!",
	"data": {
		"id": 10,
		"resume_id": 1,
		"job_description_id": 1,
		"score": 84,
		"feedback": "Your resume aligns well with backend API responsibilities...",
		"strengths": ["Strong Laravel project experience", "Clear API work examples"],
		"weaknesses": ["Missing measurable outcomes", "Limited cloud deployment details"]
	}
}


### Tips Response
json
{
	"status": "success",
	"data": [
		{
			"title": "Quantify Impact",
			"description": "Add metrics to each major achievement (for example, reduced API latency by 35%).",
			"priority": "high"
		},
		{
			"title": "Improve Skills Section",
			"description": "Group skills by backend, database, and tooling to improve readability.",
			"priority": "medium"
		}
	]
}


---

## Local Setup

### Prerequisites
* PHP 8.2+
* Composer
* Node.js 18+ and npm
* MySQL (if using MySQL config)

### Installation

bash
git clone <your-repo-url>
cd ApliAI/backend

composer install
npm install

cp .env.example .env
php artisan key:generate


### Configure Environment
In `backend/.env`, set your database and at least one AI key:
* `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
* `GROQ_API_KEY` or `GEMINI_API_KEY` or `OPENAI_API_KEY`
* `DEFAULT_AI_PROVIDER=groq` (or `gemini` / `openai`)

### Run Migrations
bash
php artisan migrate


### Start the App

**Backend API server:**
bash
php artisan serve


**Frontend asset server** (optional during development):
bash
npm run dev


---

## Common Troubleshooting

### `Could not open input file: artisan`
Make sure you are inside the backend folder before running artisan commands:
bash
cd ApliAI/backend
php artisan serve


### `DB connection errors during migration`
* Confirm MySQL is running
* Verify DB credentials in `.env`
* Ensure the database already exists

---

## Security Notes
* Never commit `.env` to source control
* Keep API keys private and rotate them if accidentally exposed

## Roadmap Ideas
- [ ] Auth and per-user resume ownership
- [ ] Job description CRUD endpoints and UI
- [ ] Better NLP keyword extraction and phrase matching
- [ ] Historical evaluation analytics dashboard
