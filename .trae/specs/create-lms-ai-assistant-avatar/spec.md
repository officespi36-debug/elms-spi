# LMS AI Assistant Digital Avatar Spec

## Why
The ELMS platform already exposes many disconnected AI surfaces (Admin AI Recommendation module, teacher `ai/*` endpoints, student `ai-path`, `learn/ai-tutor`) but has no unified, invokable assistant persona. A reusable "digital avatar" SubAgent gives users one entry point that understands the LMS domain, can render compact inline visualizations, and can bridge to Lark for sharing/notifications.

## What Changes
- ADD a digital avatar SubAgent skill at `.trae/skills/lms-ai-assistant/SKILL.md` with valid frontmatter (`name`, `description` ending in the trigger clause, `mode: agent`).
- ADD an avatar icon at `.trae/skills/lms-ai-assistant/icon.png`.
- DECLARE the `dynamic-ui` skill as an avatar capability and document how the avatar renders compact inline visuals.
- DOCUMENT Lark plugin usage (messaging / docs / notifications) as an avatar capability with concrete flows.
- ADD one reference dynamic-UI artifact (static HTML/CSS) showing a representative LMS insight visual.
- No **BREAKING** changes. No database or app-code changes are in scope.

## Impact
- Affected specs: new capability `ai-assistant-avatar` (this spec).
- Affected code: `.trae/skills/lms-ai-assistant/` (new directory only). No Laravel/Inertia/Vue application files are modified.

## ADDED Requirements

### Requirement: Digital avatar skill definition
The system SHALL provide a digital avatar skill at `.trae/skills/lms-ai-assistant/SKILL.md` that conforms to the digital-avatar-creator format.

#### Scenario: Valid frontmatter
- **WHEN** the skill file is inspected
- **THEN** it contains `name: "lms-ai-assistant"`, a `description` whose final clause instructs driving the avatar via the Task tool, and `mode: agent`

#### Scenario: Identity and workflow body
- **WHEN** the body is read
- **THEN** it begins with the avatar identity/expertise and contains a numbered workflow that ends with an `AskUserQuestion` clarification step

### Requirement: Avatar icon
The system SHALL include a profile icon for the avatar at `.trae/skills/lms-ai-assistant/icon.png` (512x512).

#### Scenario: Icon present
- **WHEN** the skill directory is listed
- **THEN** a supported icon file (`icon.png`) exists

### Requirement: Dynamic-UI capability
The avatar SHALL declare the `dynamic-ui` skill and document how it produces compact inline visualizations.

#### Scenario: Declared skill
- **WHEN** the avatar frontmatter is inspected
- **THEN** the `skills` list contains `dynamic-ui`

#### Scenario: Reference visual artifact
- **WHEN** the reference artifact is opened
- **THEN** it is a self-contained HTML/CSS fragment (`<style>` + content, no page shell) that renders one compact LMS insight visual using neutral surfaces plus brand/chart-series color

### Requirement: Lark integration capability
The avatar SHALL document how it uses the `trae-remote-official:lark` plugin for outbound collaboration.

#### Scenario: Concrete Lark flows
- **WHEN** the Lark section of the avatar body is read
- **THEN** it lists at least one concrete flow (e.g. send an LMS summary to a Lark chat via `lark-im`, or push a report to Lark docs/sheets), including any auth prerequisite

### Requirement: Language consistency
The avatar content SHALL be written entirely in one language (English), with no mixed-language content.

#### Scenario: No mixed languages
- **WHEN** the skill file is read
- **THEN** all sections are in English

## Non-Goals
- Do not modify LMS application code (`app/`, `resources/js/`, `routes/`, DB schema).
- Do not add new Composer/npm dependencies.
- Do not implement Lark server-side credentials or webhooks in the app.
- Do not create fake/hardcoded LMS data inside application code.

## Assumptions
- The avatar targets the admin/teacher/student audience generically; the primary audience is admin analytics (fits the open `AdminLayout.vue` and existing `AiRecommendationModule`).
- `dynamic-ui`'s live `PureShowWidget` renderer is unavailable in this environment, so the reference artifact is delivered as static HTML/CSS.
- Lark capabilities are used at runtime through the plugin's skills; no app-level Lark integration is built.

## Open Questions
- Confirm the avatar's primary audience (admin vs teacher vs student) - implemented as a general LMS assistant leaning admin analytics.
- Confirm whether a real in-app Lark integration (Laravel service + credentials) is wanted as a follow-up spec.
