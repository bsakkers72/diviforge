# DiviForge v3.3.0 Documentation

## Release focus
DiviForge v3.3.0 implements the Roadmap 3.0 **AI Chat** deliverable.

The goal is to make AI work iterative per WordPress/Divi page. Instead of every AI request being a separate one-off prompt, each page now has its own chat thread.

## Added functionality

### AI Chat menu
A new **AI Chat** menu item is available inside the DiviForge Studio navigation.

### Per-page conversation memory
Each page has a stored chat thread. DiviForge stores:

- page id
- user messages
- assistant messages
- timestamps
- related AI Preview job id
- model/provider metadata

Threads are stored in the WordPress option `diviforge_ai_chat_threads`.

### AI Chat workflow
1. Select a page.
2. Write a follow-up instruction.
3. DiviForge stores the message on the page thread.
4. DiviForge builds AI context from the page export package.
5. The prompt is sent to the active AI provider.
6. The AI response is stored in the page thread.
7. If the response contains a valid DiviForge package, an AI Job is created.
8. The job can be reviewed in AI Preview before import.

### Safe import model
AI Chat does not directly overwrite pages. Generated output is routed through the existing AI Preview and approve/discard workflow.

## Notes
This release keeps the MVP scope focused. It does not add multi-user chat permissions, streaming responses, or provider-specific chat memory. Those remain backlog items for after the core AI workflow is stable.
