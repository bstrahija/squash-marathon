# Squash Marathon Tracker

## Overview

This project will track a 24-hour squash marathon event where participants play continuously over a 24-hour window. The homepage is currently a static Blade landing page built from Blade components, while the main application UI will be a Vue 3 SPA using Inertia.

## Current Stack (Installed)

- Backend: Laravel 12, Fortify (auth), Inertia v2, Wayfinder, Livewire
- Frontend: Vue 3, Vite, Tailwind CSS v4, TypeScript
- Testing: Pest v4, PHPUnit v12
- Tooling: Pint, ESLint, Prettier

## Styling Status

Tailwind CSS is already installed and wired:

- `tailwindcss` and `@tailwindcss/vite` are in `package.json`.
- `resources/css/app.css` imports `tailwindcss` and defines a theme.
  No additional Tailwind setup is required at this stage.

## Livewire Status

- Livewire is installed and available, but the current homepage is fully static Blade.

## Product Goals

- Track players, matches, and courts across a continuous 24-hour event.
- Keep scheduling and reporting simple, fast, and clear during the event.
- Provide a public homepage for event info and live status highlights.

## Core Entities (Draft)

- Participant: player profile, contact, status, total minutes played.
- Match: start/end time, court, participants, score, result.
- Court: name/number, availability.
- Event: 24-hour window, rules, timezone, live status.

## MVP Features (Draft)

- Participant registration and roster management.
- Match creation, editing, and real-time status.
- Court occupancy view with current and upcoming matches.
- Player statistics: matches played, win/loss, total minutes.
- Event timeline dashboard: now/next, backlog queue, alerts.

## Rules and Constraints (Draft)

- Event lasts exactly 24 hours from start time (Squash Arena Cakovec, April 17, 19:00).
- A player cannot be scheduled for overlapping games.
- Courts can host only one game at a time; current plan uses 2 courts.
- Each game is a single race to 11 points.
- Wins count 2 points; losses count 1 point.
- Record only the finished-game timestamp (no durations).
- Participant limit: 15 players.

## UX Notes

- Homepage: Static Blade components for nav, hero, event info, scoring, participants, leaderboard, courts, timeline, and footer.
- Navigation: Fixed top bar with smooth-scroll anchors.
- Dark mode: Toggle with persisted preference.
- App: Inertia + Vue pages for admin and operations workflow.
- Keep data entry quick: keyboard-first, minimal clicks.

## Non-Functional Requirements

- Auditability: match edits are traceable.
- Reliability: resilient to quick edits during event peaks.
- Performance: dashboards should be fast for live use.

## Testing Guidelines

- Use Pest for new tests.
- Prefer feature tests for match scheduling and constraints.
- Keep test runs focused and fast.

## Next Planning Questions

- Do we need multiple events or a single event model?
- Is there a public live scoreboard view?
- Will we allow team play or only singles?
- What time zone is authoritative for the event?
