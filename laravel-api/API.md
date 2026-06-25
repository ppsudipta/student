# Student API

This Laravel app is separate from the old PHP admin app and uses the same MySQL database: `a1773756_app`.

## Local URLs

When served through XAMPP:

- `http://localhost/admin/laravel-api/public/api/health`
- `http://localhost/admin/laravel-api/public/api/login`

When served with Laravel Artisan:

- `http://127.0.0.1:8001/api/health`
- `http://127.0.0.1:8001/api/login`

## Login

`POST /api/login`

```json
{
  "mobile_number": "9038495748",
  "password": "12345677"
}
```

Use the returned token as:

```http
Authorization: Bearer <access_token>
```

## Authenticated Endpoints

- `GET /api/me`
- `PATCH /api/me`
- `POST /api/me/photo`
- `POST /api/change-password`
- `GET /api/notices`
- `PATCH /api/notices/{id}/seen`
- `GET /api/materials`
- `GET /api/materials/{id}`
- `PATCH /api/materials/{id}/favorite`

Video materials return a `playback` object for Android. For Vimeo links, use `playback.embed_url` in an in-app WebView/player and do not show a share button.
- `GET /api/progress`
- `GET /api/fees`
- `GET /api/reminders`
- `GET /api/admission`
- `GET /api/polls`
- `POST /api/polls/{id}/vote`
- `GET /api/attendance`
- `GET /api/homework`
- `POST /api/homework/{id}/submit`
- `GET /api/enquiries`
- `POST /api/enquiries`
- `GET /api/payment-methods`
- `POST /api/payment-methods`
- `POST /api/mock-questions/answer`

All endpoints return JSON and read from the legacy database tables.

## Public App Endpoints

- `GET /api/health`
- `GET /api/home`
- `GET /api/dashboard`
- `GET /api/company`
- `GET /api/classes`
- `POST /api/contact`
- `GET /api/exams`
- `GET /api/mock-questions`
- `POST /api/register`
- `GET /api/question-meta`
- `GET /api/questions`
- `POST /api/questions/answer`

## Public Resource Endpoints

These support pagination with `?page=1&per_page=20`.

- `GET /api/blogs`
- `GET /api/blogs/{id}`
- `GET /api/categories`
- `GET /api/categories/{id}`
- `GET /api/courses`
- `GET /api/courses/{id}`
- `GET /api/events`
- `GET /api/events/{id}`
- `GET /api/gallery`
- `GET /api/gallery/{id}`
- `GET /api/news`
- `GET /api/news/{id}`
- `GET /api/pdfs`
- `GET /api/pdfs/{id}`
- `GET /api/services`
- `GET /api/services/{id}`
- `GET /api/sliders`
- `GET /api/sliders/{id}`
- `GET /api/staff`
- `GET /api/staff/{id}`
- `GET /api/subcategories`
- `GET /api/subcategories/{id}`
- `GET /api/teachers`
- `GET /api/teachers/{id}`
- `GET /api/testimonials`
- `GET /api/testimonials/{id}`

## Android Flow

1. Call `POST /api/login`.
2. Store `access_token`.
3. Send `Authorization: Bearer <access_token>` for protected endpoints.
4. Use `GET /api/home` for the first dashboard payload.
