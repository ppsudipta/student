# Project Setup

## What this project is

This is a classic PHP + MySQL project intended to run inside XAMPP.

- Public/student side lives in `/Applications/XAMPP/xamppfiles/htdocs/admin/pages`
- Admin side lives in `/Applications/XAMPP/xamppfiles/htdocs/admin/admin`
- Laravel API lives in `/Applications/XAMPP/xamppfiles/htdocs/admin/laravel-api`
- New root launcher lives at `/Applications/XAMPP/xamppfiles/htdocs/admin/index.php`

## Open URLs

After Apache starts, use:

- `http://localhost/admin/`
- `http://localhost/admin/pages/`
- `http://localhost/admin/admin/`
- `http://localhost/admin/laravel-api/public/api/health`

## Database expected by code

The code currently connects to:

- host: `localhost`
- user: `root`
- password: empty
- database: `a1773756_app`

These values are present in:

- `/Applications/XAMPP/xamppfiles/htdocs/admin/pages/config.php`
- `/Applications/XAMPP/xamppfiles/htdocs/admin/pages/config/config.php`
- `/Applications/XAMPP/xamppfiles/htdocs/admin/admin/config.php`

## Current blocker

The local database is now available and the Laravel API is configured to use it.

The API documentation is in:

- `/Applications/XAMPP/xamppfiles/htdocs/admin/laravel-api/API.md`

## XAMPP

When XAMPP is stopped, Apache and MySQL will both fail locally. Start them with the XAMPP manager or command line, then import the correct database dump if you have it.
