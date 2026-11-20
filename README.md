# University Knowledge Network (UKN)

A campus peer-learning and mentoring platform. Students learn skills from other students,
mentors teach what they know, and everyone earns points and shares knowledge in a community feed.

Built with **PHP 8**, **MySQL/MariaDB (PDO)**, **Bootstrap 5** and vanilla JavaScript, for a local
**XAMPP** setup.

## Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Project Structure](#project-structure)
- [Database](#database)
- [Roles](#roles)
- [Mentoring Sessions](#mentoring-sessions)
- [Private Contact Details](#private-contact-details)
- [Security](#security)
- [Known Limitations](#known-limitations)
- [Credits](#credits)

---

## Features

| Area | What it does |
|---|---|
| **Accounts** | Registering signs you in automatically (no email verification). Passwords are hashed; login is rate limited. |
| **Roles** | Every account starts as a Learner. A learner can apply to become a mentor; an admin approves it. |
| **Profiles** | Edit name, department, year, bio, skills and profile photo (JPG/PNG, max 2 MB). |
| **Skills & goals** | Skill directory with categories; learning and teaching skills; learning goals with progress. |
| **Availability** | Mentors set weekly time slots (up to four per day). |
| **Recommendations** | Mentors are scored 0–100 for a learner by skill match, availability, rating, experience and points. |
| **Sessions** | Learners request sessions; mentors accept, reject and complete them; learners rate them. |
| **Points** | Completed session: learner +25, mentor +30. Late cancellation: −10. Stored in a points ledger. |
| **Community** | Posts with skill tags, comments and replies, votes, saved posts, follows, sharing and reports. |
| **Notifications** | For comments, replies and new followers. |
| **Search & leaderboard** | Search skills, mentors, learners and posts; leaderboards for the week, month and all time. |
| **Admin panel** | Manage departments, skill categories, skills and users; moderate posts, comments and reports; review mentor applications; dashboard charts. |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Bootstrap 5.3, vanilla JavaScript, Chart.js 4 (admin dashboard) |
| Backend | PHP 8 (tested on 8.2), server-rendered pages and form POST actions |
| Database | MariaDB / MySQL via PDO (tested on MariaDB 10.4), InnoDB, `utf8mb4` |
| Server | Apache (XAMPP) with `.htaccess` and `mod_headers` |

Bootstrap, Chart.js and fonts load from CDNs. There is no build step and no package manager.

---

## Installation

**Requirements:** XAMPP (Apache, MySQL/MariaDB, PHP 8) and Git. The browser needs internet access
for the CDN files.

1. **Start Apache and MySQL** in the XAMPP Control Panel.
2. **Get the code** into XAMPP's `htdocs` folder:

   ```bash
   cd C:\xampp\htdocs
   git clone <repository-url> university-knowledge-network-frontend
   ```

   *Using a folder outside `htdocs`?* Add an `Alias` to XAMPP's `apache\conf\httpd.conf` and
   restart Apache:

   ```apache
   Alias "/university-knowledge-network-frontend" "D:/path/to/project"
   <Directory "D:/path/to/project">
       Options FollowSymLinks
       AllowOverride All
       Require all granted
       DirectoryIndex index.php
   </Directory>
   ```

3. **Create the database** in phpMyAdmin (`http://localhost/phpmyadmin` → SQL):

   ```sql
   CREATE DATABASE ukn_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Import the SQL files in this order** (phpMyAdmin → `ukn_database` → Import, or the command
   line):

   ```bash
   mysql -u root ukn_database < database/schema.sql
   mysql -u root ukn_database < database/seed.sql
   mysql -u root ukn_database < database/seed_demo_data.sql   # optional demo content
   ```

   If `mysql` is not found, use `C:\xampp\mysql\bin\mysql.exe`. `seed_demo_data.sql` replaces
   the demo rows, so never run it on a database with real data.

5. **Check the database login** in `backend/config/database.php` (default: `localhost`, `root`,
   no password).
6. **Open the site** and register an account. You are signed in straight away.
7. **Make yourself an admin** (optional):

   ```sql
   UPDATE users SET is_admin = 1 WHERE email = 'your-email@example.com';
   ```

The seeded demo users cannot log in (they have a placeholder password); they only fill the demo
data.

---

## Usage

| Page | URL |
|---|---|
| Site | `http://localhost/university-knowledge-network-frontend/` |
| Register | `…/index.php?page=register` |
| Log in | `…/index.php?page=login` |
| Admin panel | `…/admin/dashboard.php` (admin accounts only) |

Pages are addressed as `index.php?page=<route>`. To test the database connection from the
command line: `C:\xampp\php\php.exe backend\test-db.php`.

---

## Configuration

**Database:** `backend/config/database.php` (host, database name, user, password).

**Optional environment variables** (set with `SetEnv` in the Apache config):

| Variable | Purpose |
|---|---|
| `UKN_APP_URL` | Full base URL used in shared post links, e.g. `http://localhost/university-knowledge-network-frontend`. Set it in production. |
| `UKN_ENV` | `production` hides PHP errors and shows a generic error page. |

> Never commit passwords, API keys or other secrets. Keep machine-specific settings in the Apache
> config, not in the repository.

---

## Project Structure

```text
├── index.php          Router: every page is index.php?page=<route>
├── pages/             Page templates (auth, dashboard, profile, skills, sessions, community, …)
├── components/        Reusable render functions (cards, list items, states)
├── includes/          Shared layout: header, sidebars, dropdowns, mobile nav, footer
├── modals/            Bootstrap modals (create post, session request, rating, …)
├── admin/             Admin panel pages
├── backend/
│   ├── auth/          Register, login, logout, role switch
│   ├── admin/         Admin actions
│   ├── helpers/       Shared logic (auth, session, CSRF, validation, points, search, …)
│   ├── models/        User model
│   ├── config/        Database connection and app settings
│   └── …              One folder per feature (sessions, posts, comments, skills, goals, …)
├── assets/            CSS, JavaScript, icons and images
├── database/
│   ├── schema.sql     Full schema (drops and recreates every table)
│   ├── seed.sql       Base data (departments, categories, skills, users)
│   ├── seed_demo_data.sql   Optional demo content
│   └── patches/       Upgrade scripts for an existing database
├── uploads/           Uploaded profile photos (scripts cannot run here)
└── docs/              ER diagram, database design, project report, UI references
```

---

## Database

- **24 tables and 1 view** (`mentor_rating_summary`), InnoDB, `utf8mb4`.
- Foreign keys, unique keys and `CHECK` constraints protect the data; multi-step writes run in
  transactions; the connection uses strict SQL mode.
- The `point_transactions` ledger is the source of truth for points and leaderboards.

| Area | Tables |
|---|---|
| People | `users`, `user_settings`, `user_private_contacts`, `departments`, `mentor_applications` |
| Skills | `skill_categories`, `skills`, `skill_relations`, `user_skills` |
| Learning | `learning_goals`, `mentor_availability`, `mentoring_sessions`, `session_ratings` |
| Points | `point_transactions` |
| Community | `posts`, `post_skills`, `comments`, `post_votes`, `saved_posts`, `follows`, `learner_recommendations` |
| Other | `notifications`, `reports`, `login_attempts` |

**Full design documentation** (ER diagram, relational schema, 3NF, indexes, transactions, joins)
is in [`docs/DATABASE-DESIGN.md`](docs/DATABASE-DESIGN.md). The project report is in
[`docs/UKN-DBMS-Project-Report.md`](docs/UKN-DBMS-Project-Report.md).

**Upgrading an older database:** a fresh install from `schema.sql` needs no patches. For an older
database, run the scripts in `database/patches/` that match the features it is missing. Each one
is safe to run more than once, for example:

```bash
mysql -u root ukn_database < database/patches/remove-email-verification-and-password-reset.sql
```

---

## Roles

| Account | Can do |
|---|---|
| **Learner** | Learn skills, set goals, find mentors, request and rate sessions, use the community |
| **Learner & Mentor** | Everything a learner can, plus teach skills, set availability and handle session requests (switch mode from the profile menu) |
| **Admin** | A separate flag on any account; opens the admin panel |

---

## Mentoring Sessions

```text
pending ──accept──► accepted ──complete──► completed ──► rated (once)
   ├──reject──► rejected
   └──cancel──► cancelled        accepted ──cancel──► cancelled
```

- `rejected`, `cancelled` and `completed` are final.
- Only the two people in a session can see or change it.
- Cancelling an accepted session less than 6 hours before it starts costs 10 points.

---

## Private Contact Details

- Registration asks for a Bangladesh mobile number (`01XXXXXXXXX` or `+8801XXXXXXXXX`).
- It is stored separately in `user_private_contacts` and changed in **Settings → Account**.
- Only the mentor of a `pending` or `accepted` session sees the learner's number, on Session
  Details. It never appears in lists, profiles, search or admin pages.
- Numbers are not encrypted in the database.

---

## Security

- CSRF tokens on every action; actions accept `POST` only.
- Server-side validation, prepared statements and output escaping everywhere.
- Route guards and ownership checks; roles are re-read from the database on every request.
- Secure sessions (`HttpOnly`, `SameSite=Lax`, new ID at login) and login rate limiting.
- Security headers (CSP, `X-Frame-Options`, `nosniff`, …) set in `.htaccess`.
- Database, docs and include-only folders are blocked from direct web access.

For a public deployment, also use HTTPS, set `UKN_ENV=production`, and use a dedicated database
user instead of `root`.

---

## Known Limitations

- No **Forgot Password** feature and no email sending.
- The **Settings** page saves only the mobile number; the other options and the change-password
  dialog are not connected to the backend yet.
- The admin **Sessions** and **Settings** pages are read-only demo screens.
- Notifications cover community activity only, not session events.
- Some profile statistics come from seeded counter columns and can differ from the leaderboard.
- Profile photos appear on profiles and in the header; cards and lists still show initials.

---

## Credits

Add the author, course, institution and group members here. Design references and the original
UI hand-off are in `docs/`.
