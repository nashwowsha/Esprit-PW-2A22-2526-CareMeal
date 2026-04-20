# CareMeal Module Integration Notes

This file documents how to merge the current Preference + Restaurant work with teammates safely.

## 1) Scope Owned By This Module

Primary feature files:

- `Model/Preference.php`
- `Controller/PreferenceController.php`
- `Controller/preference.php`
- `js/student-preferences-validation.js`
- `js/admin-preferences-validation.js`
- `student/preferences.php`
- `admin/preferences.php`

Restaurant entity files:

- `Model/Restaurant.php`
- `Controller/RestaurantController.php`
- `Controller/restaurant.php`
- `js/partner-restaurants-validation.js`
- `js/admin-restaurants-validation.js`
- `partner/restaurants.php`
- `admin/restaurants.php`

Assets used by restaurant upload:

- `assets/restaurant-signs/`

## 2) Shared/Global Files Touched

These are shared with team templates and may conflict during merge:

- `js/components.js`
- `admin/_admin_sidebar.php`
- `View/BackOffice/admin/*.php` (redirect wrappers)
- `admin/{dashboard,users,partners,events,logs,user-detail}.php` (compatibility redirects)

## 3) Navigation Compatibility Strategy

To reduce merge risk:

- Canonical editable admin pages remain in `admin/*.html`.
- `admin/*.php` (except `preferences.php`, `restaurants.php`) are compatibility redirects to matching `.html`.
- BackOffice wrappers in `View/BackOffice/admin/*.php` redirect to admin pages and keep old links working.
- `admin/_admin_sidebar.php` uses route fallback (`.html` first, then `.php`).

## 4) Team Merge Checklist

1. Keep teammate UI updates in `admin/*.html` (not redirect wrappers).
2. Keep dynamic modules in:
   - `admin/preferences.php`
   - `admin/restaurants.php`
   - `partner/restaurants.php`
   - `student/preferences.php`
3. If auth/user IDs change, update only:
   - `Controller/preference.php`
   - `Controller/restaurant.php`
4. If DB schema changes, update model methods only (controller signatures stable).
5. Re-test these URLs after merge:
   - `/caremeal/admin/preferences.php`
   - `/caremeal/admin/restaurants.php`
   - `/caremeal/partner/restaurants.php`
   - `/caremeal/student/preferences.php`

## 5) Important Note About Legacy Copy Folder

Folder `html que je veux/` is a legacy copy and should not be treated as production source during merge.

## 6) Next Planned Work (Not Yet Final)

- Matching API integration
- New `planning_collecte` logic
- Additional entities around delivery flow

These should be added in new files where possible to keep current merge surface small.
