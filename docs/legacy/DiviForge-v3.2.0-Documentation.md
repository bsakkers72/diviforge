# DiviForge v3.2.0 Documentation

## Release theme
AI Preview & Jobs.

This release turns the v3.1 AI Improve response into a controlled workflow:

1. Create an AI request from AI Studio.
2. Store the response as an AI Job.
3. Review the job in AI Preview.
4. Validate the returned package structure.
5. Approve & update the page, or discard the job.

## New screens

### AI Preview
A new admin screen at `admin.php?page=diviforge-ai-preview`.

The screen shows:

- target page;
- job status;
- model/provider;
- generated package score;
- desktop/tablet/phone preview frames;
- validation checks;
- prompt and AI response summary;
- Approve & update page action;
- Discard action.

### AI Jobs
A new admin screen at `admin.php?page=diviforge-ai-jobs`.

The screen lists local AI jobs with:

- status;
- target page;
- provider and model;
- prompt summary;
- review link.

## Data model

AI jobs are stored in the WordPress option `diviforge_ai_jobs`.

Each job contains:

- `id`
- `created_at`
- `updated_at`
- `status`
- `page_id`
- `title`
- `prompt`
- `provider`
- `model`
- `response`
- `parsed`
- `validation`
- `error`
- `elapsed`
- `imported_at`

## Validation

The first validator checks for:

- valid JSON;
- `manifest`;
- `layout`;
- `page.css` / `page_css`;
- `change_summary`.

The score is intentionally simple in v3.2.0. It becomes stricter in later releases.

## Import behaviour

When a job is approved:

- the generated `layout` is converted to Divi shortcodes;
- the page content is updated;
- the Divi Builder flag is enabled;
- generated CSS is applied through the DiviForge CSS importer;
- job status becomes `imported`;
- request history is updated.

## Limitations

- Preview is a structured MVP preview, not yet a pixel-perfect rendered Divi preview.
- AI package parsing expects JSON output from the model.
- Direct live generation remains implemented for OpenAI first.
