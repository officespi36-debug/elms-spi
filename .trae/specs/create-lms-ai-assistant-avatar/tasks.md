# Tasks

- [ ] Task 1: Create the `lms-ai-assistant` avatar skill definition.
  - [ ] SubTask 1.1: Create directory `.trae/skills/lms-ai-assistant/`.
  - [ ] SubTask 1.2: Write `SKILL.md` with frontmatter: `name: "lms-ai-assistant"`, `description` describing LMS capabilities and ending with the Task-tool trigger clause, `mode: agent`, and `skills: [dynamic-ui]`.
  - [ ] SubTask 1.3: Write the body: avatar identity/expertise, a numbered workflow covering LMS context gathering (read-only), optional dynamic-UI visualization, optional Lark sharing, and a final `AskUserQuestion` clarification step.
  - [ ] SubTask 1.4: Include a "Prerequisites / Environment" note for `dynamic-ui` (pure inline visual contract) and the Lark plugin (authorization).

- [ ] Task 2: Generate the avatar icon.
  - [ ] SubTask 2.1: Generate a 512x512 icon via `GenerateImage` and save to `.trae/skills/lms-ai-assistant/icon.png`.

- [ ] Task 3: Add a reference dynamic-UI artifact.
  - [ ] SubTask 3.1: Create `.trae/skills/lms-ai-assistant/reference/lms-insight-widget.html` as a self-contained fragment (`<style>` + content, no page shell) showing one compact LMS insight visual.
  - [ ] SubTask 3.2: Apply dynamic-ui visual contract: neutral surfaces, brand/chart-series color for the focal point, light + `:root[data-widget-theme="dark"]` support, `data-dynamic-ui-widget` root attribute.

- [ ] Task 4: Document Lark usage in the avatar body.
  - [ ] SubTask 4.1: Add a "Lark Integration" section with >=1 concrete flow (e.g. `lark-im` send summary, `lark-doc`/`lark-sheets` push report) and the authorization prerequisite.

- [ ] Task 5: Validate the avatar skill.
  - [ ] SubTask 5.1: Confirm frontmatter has `name`, `description` (trigger clause), `mode: agent`.
  - [ ] SubTask 5.2: Confirm `icon.png` exists and the body language is consistent (English only).
  - [ ] SubTask 5.3: Confirm `reference/lms-insight-widget.html` opens standalone and renders without a page shell.

# Task Dependencies
- Task 2 depends on Task 1 (directory must exist).
- Task 3 depends on Task 1 (directory must exist).
- Task 4 depends on Task 1 (body must exist).
- Task 5 depends on Tasks 1, 2, 3, 4.
