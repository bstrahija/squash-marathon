---
name: filament-development
description: "Use for any task or question involving Laravel Filament admin panels. Activate when the user mentions Filament, resources, forms, tables, filters, actions, relation managers, widgets, panel providers, or plugin setup. Covers creating and refactoring Filament resources, table and form behavior, authorization in admin screens, navigation and UX in panels, and performance tuning for admin data queries. Do not use for non-Filament frontend tasks or backend code that does not touch Filament."
license: MIT
metadata:
  author: laravel
---

# Filament Development

## Documentation

Use `search-docs` for Filament v5 examples and current API behavior.

## When To Use

Use this skill when working on:
- `app/Filament/**` resources, pages, schemas, tables, widgets, and providers
- Filament form components and validation behavior
- Filament table columns, filters, sorting, pagination, and bulk actions
- Relation managers, custom resource pages, infolists, and navigation

## Core Conventions

- Match existing project structure before introducing new patterns.
- Keep table and form logic in dedicated classes (for example `.../Tables/*Table.php` and `.../Schemas/*Form.php`) when the project already does so.
- Keep labels, helper text, and action names clear and user-facing.
- Prefer explicit authorization checks and Laravel policies for admin actions.

## Tables

- Mark only meaningful columns as searchable/sortable.
- Use eager loading or counts to avoid N+1 issues in admin listings.
- Set sensible pagination defaults for large resources.

Example:

```php
use Filament\Tables\Table;

public static function configure(Table $table): Table
{
    return $table
        ->defaultPaginationPageOption(25)
        ->paginated([10, 25, 50, 100]);
}
```

## Forms

- Keep validation rules aligned with domain rules and model casts.
- For file uploads, keep disk/collection names consistent with media handling code.
- Prefer small, composable field groups over very large inline schemas.

## Actions And UX

- Use row actions for single-record operations and bulk actions for batch workflows.
- Add confirmation for destructive actions.
- Keep destructive actions role-restricted and visible only when relevant.

## Testing

- Cover resource create/edit flows with focused feature tests.
- For Livewire-backed pages, assert form submissions, validation, and persistence.
- Run only the affected test files first, then expand if needed.

## Verification Checklist

1. Confirm resources render without errors.
2. Confirm form submission and validation behavior.
3. Confirm table filters/sorting/pagination behavior.
4. Confirm authorization and action visibility.
5. Run relevant Pest tests for edited resources.
