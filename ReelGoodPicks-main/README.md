**ReelGoodPicks — Setup & Notes**

**Overview:**

- **Project:** Movie discovery app with user auth, watchlist, random-suggestion, and a test/swipe recommendation flow.

**Quick Local Setup (XAMPP on Windows):**

- **Start services:** Open XAMPP Control Panel → start `Apache` and `MySQL`.
- **Import DB via CLI (from project root):**

```powershell
cd C:\xampp\htdocs\ReelGoodPicks
C:\xampp\mysql\bin\mysql.exe -u root < movie_db.sql
```

- **Or import with phpMyAdmin:** go to http://localhost/phpmyadmin → Import → upload `movie_db.sql`.

**Linting & quick checks (XAMPP PHP):**

```powershell
C:\xampp\php\php.exe -v
C:\xampp\php\php.exe -l signuplogic.php
# or lint other PHP files: C:\xampp\php\php.exe -l <filename>
```

**Smoke-test steps (manual / quick):**

- Open http://localhost/ReelGoodPicks/signup.php → create account.
- Login at http://localhost/ReelGoodPicks/login.html.
- From dashboard/random/trending/toprated: click "Add to Watchlist" and confirm watchlist at http://localhost/ReelGoodPicks/watchlist.php.
- Click "Remove from Watchlist" to confirm deletion.
- On Surprise Me page (random.php) use the "Get Another Movie" button (AJAX) and confirm JSON endpoint at `random.php?ajax=1` returns {success, movie, in_watchlist} when requested with the session.

**Important fixes included in this repo:**

- Database: added `movies_db` creation to `movie_db.sql` and ensured primary keys use `AUTO_INCREMENT` where appropriate.
- Resolved table-name mismatches: renamed DB table `user` → `users` (and adjusted PHP queries to `USERS`). If you import on Linux, ensure table name casing matches (MySQL table name case-sensitivity depends on OS and configuration).
- Fixed `users.ID` existing row with `0` and enabled `AUTO_INCREMENT` on import where needed.
- Backend: converted watchlist add/remove endpoints to return JSON for AJAX calls while still supporting non-AJAX redirects.
- Frontend: updated `js/script.js` to expect JSON responses and updated UI accordingly; ensured jQuery is included before `js/script.js` on pages using it.

**Developer notes / gotchas:**

- Table name casing: `MOVIES`, `USERS`, `WATCHLIST`, etc. are used in SQL; some INSERT statements in the seed use lowercase names — XAMPP on Windows is case-insensitive, but Linux can be case-sensitive. Normalize names if deploying to Linux.
- `REVIEWS` includes a `CHECK` constraint which older MySQL versions may ignore — it's harmless but be aware.

**Next recommended steps:**

- Add CSRF protection on forms and AJAX endpoints.
- Harden input validation and sanitize outputs further where needed.
- Add automated tests or a quick PHPUnit smoke-test script.

If you want, I can: update `movie_db.sql` table-name casing for portability, add a small `scripts/` folder with lint/run commands, or create a short `smoke-test.sh` / `smoke-test.ps1` to automate the verification steps.

---

Last updated: May 4, 2026 — repository maintenance: made DB/table fixes and completed a local smoke-test.

- The flow of control for the test-to-swipe transition.

Comments are included inside PHP files like test_and_swipe.php, add_to_watchlist.php, and remove_from_watchlist.php to explain:

- Database connection
- Handling of POST requests
- Response structure (success/fail JSON)
