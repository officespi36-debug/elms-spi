# Checklist

- [ ] `.trae/skills/lms-ai-assistant/SKILL.md` exists.
- [ ] Frontmatter contains `name: "lms-ai-assistant"`.
- [ ] `description` ends with the Task-tool trigger clause.
- [ ] Frontmatter contains `mode: agent`.
- [ ] `skills` list declares `dynamic-ui`.
- [ ] Body begins with the avatar identity/expertise.
- [ ] Workflow includes reading LMS context (read-only) and rendering an optional dynamic-UI visual.
- [ ] Workflow includes an optional Lark sharing step.
- [ ] Workflow ends with an `AskUserQuestion` clarification step.
- [ ] A "Prerequisites / Environment" note covers dynamic-ui and the Lark plugin authorization.
- [ ] `.trae/skills/lms-ai-assistant/icon.png` exists (512x512).
- [ ] `.trae/skills/lms-ai-assistant/reference/lms-insight-widget.html` exists and is a self-contained fragment (no `<!DOCTYPE>`, `<html>`, `<head>`, `<body>`).
- [ ] The reference widget uses neutral surfaces and brand/chart-series color, and supports light + `:root[data-widget-theme="dark"]`.
- [ ] The reference widget root carries `data-dynamic-ui-widget`.
- [ ] Lark usage section documents at least one concrete flow with its auth prerequisite.
- [ ] All avatar content is English with no mixed-language sections.
- [ ] No LMS application code (`app/`, `resources/js/`, `routes/`, DB) was modified.
