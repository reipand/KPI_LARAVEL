# Notification System Design
Date: 2026-04-17  
Project: KPI BASS (KPI_LARAVEL)  
Status: Approved

## Overview

Full-stack notification system for KPI BASS using Pusher/Laravel Echo for real-time in-app delivery and Firebase Cloud Messaging (FCM) for push notifications when the browser tab is closed or the user is offline. Notifications are stored permanently in the database and deleted manually by the user.

## Scope

This spec covers sprint B (Notification System) only. Later sprints:
- A: Security & Auth (auto logout, token refresh, multi-device)
- C: KPI UX Refactor
- D: UI/Visual Overhaul

Native mobile app (Android/iOS) FCM integration is explicitly out of scope for this sprint. The backend will be FCM-ready so a future native app can register tokens.

## Architecture

```
Backend Event
(task.assigned, kpi.updated, dll)
      │
      ├──► Laravel Notification ──► DB notifications table (permanent)
      │
      ├──► Pusher private-user.{id} ──► Laravel Echo (real-time in-app)
      │
      └──► FCM HTTP v1 ──► Firebase ──► Browser Service Worker / Native
```

**Stack:**
- Backend: Laravel + Pusher (existing) + FCM HTTP v1 API
- Frontend: Vue 3 + Pinia + Firebase JS SDK + Service Worker
- Real-time: Laravel Echo + Pusher (existing infrastructure, no changes)
- Push: Firebase Cloud Messaging (new)

## Backend Design

### Database

New table `fcm_tokens`:
```sql
id            bigint unsigned PK auto_increment
user_id       bigint unsigned FK → users.id (cascade delete)
token         text NOT NULL
device_type   enum('web', 'android', 'ios') DEFAULT 'web'
created_at    timestamp
updated_at    timestamp
UNIQUE KEY unique_user_token (user_id, token(255))
```

The existing `notifications` table (Laravel default) is used as-is. No schema changes needed.

### Notification Classes

All located in `app/Notifications/`. Each implements `toArray()` (database), `toBroadcast()` (Pusher), and `toFcm()` (push).

| Class | Trigger point | Notified user |
|---|---|---|
| `TaskAssignedNotification` | `TaskController::assign()` | Pegawai yang di-assign |
| `KpiUpdatedNotification` | `KpiController::update()` | Pegawai yang di-nilai |
| `ReportSubmittedNotification` | `KpiController::submitReport()` | HR Manager |
| `DeadlineReminderNotification` | Scheduler daily 07:00 | Pegawai dengan task H-1/H-3 |
| `ReportApprovedNotification` | `KpiController::approveReport()` | Pegawai yang submit |
| `ReportRejectedNotification` | `KpiController::rejectReport()` | Pegawai yang submit |

### API Endpoints

```
POST   /api/fcm/token           Store or update FCM token for current user
DELETE /api/fcm/token           Remove FCM token on logout
DELETE /api/notifications/{id}  Delete a single notification (user-owned only)
```

Existing endpoints remain unchanged:
```
GET /api/notifications           List all notifications (paginated)
PUT /api/notifications/{id}/read Mark as read
PUT /api/notifications/read-all  Mark all as read
```

### Deadline Reminder Scheduler

```php
// app/Console/Kernel.php
$schedule->command('notify:deadline-reminder')->dailyAt('07:00');
```

Command `app/Console/Commands/NotifyDeadlineReminder.php` queries tasks where `deadline = today + 1 day` or `today + 3 days`, dispatches `DeadlineReminderNotification` for each owner.

### Broadcasting

Pusher channel: `private-user.{userId}`  
Event: `NotificationSent`  
Payload: `{ id, type, title, body, data, created_at }`

Auth via existing Laravel broadcasting auth endpoint (`/api/broadcasting/auth`).

## Frontend Design

### New Files

| File | Purpose |
|---|---|
| `resources/js/services/firebase.js` | Firebase app init + getMessaging() |
| `public/firebase-messaging-sw.js` | Service worker for background push |
| `resources/js/composables/useNotification.js` | Orchestrates FCM + Echo subscription |

### Updated Files

| File | Changes |
|---|---|
| `resources/js/stores/notification.js` | Add `deleteNotification(id)`, `addRealtime(notif)` |
| `resources/js/components/shared/NotificationBell.vue` | Upgrade dropdown UI, add delete, bounce badge |
| `resources/js/main.js` | Call `useNotification().init()` after auth |

### `useNotification.js` API

```js
init()          // Request FCM permission → send token to backend
onForeground()  // Firebase onMessage() → toast + store update
subscribeEcho() // Echo.private('user.{id}').listen('NotificationSent', ...)
cleanup()       // Unsubscribe Echo, delete FCM token via API
```

Called from auth store's `login()` success handler (after token is set). `cleanup()` called inside auth store's `logout()` before clearing state.

### NotificationBell UI Spec

- Bell icon (Lucide `Bell`)
- Badge: red circle, `animate-bounce` on new unread, shows count (max "99+")
- Dropdown: `w-[360px]`, `rounded-xl`, `shadow-lg`, slide-fade animation
- Each notification item: icon by type, title, body (truncated 2 lines), relative time, mark-read on click, delete button (×)
- Empty state: illustration + "Belum ada notifikasi"
- Footer: "Tandai semua dibaca" button

### Service Worker (`public/firebase-messaging-sw.js`)

Handles background push using Firebase compat SDK. Shows native browser notification with title, body, and icon from FCM payload. Clicking notification focuses the KPI BASS tab (or opens a new one).

## Error Handling

| Scenario | Handling |
|---|---|
| User denies FCM permission | Graceful — in-app realtime still works, push silently disabled, no error shown |
| FCM token expired/invalid | Backend catches `InvalidArgument` from Firebase → deletes token from DB |
| Pusher disconnect | Echo auto-reconnects; badge count re-fetched via 30-second polling fallback |
| FCM send failure | Server-side log only; notification still saved to DB and visible on next app open |
| Delete notification API failure | Optimistic update in store; rollback to original list on error |
| User not logged in | `init()` is a no-op; cleanup not called |

## Security

- Pusher channels are `private-*` — each user can only subscribe to `private-user.{own_id}`
- Broadcasting auth endpoint validates Sanctum token before authorizing channel subscription
- FCM tokens are scoped to `user_id` — a user cannot register a token for another user
- FCM tokens are deleted from DB on logout (`DELETE /api/fcm/token`)
- `DELETE /api/notifications/{id}` enforces ownership: `where user_id = auth()->id()`

## Out of Scope

- Native Android/iOS app FCM registration (backend FCM-ready; app does not exist yet)
- Email notifications
- Notification preferences / per-type mute settings
- Admin broadcast to all users
