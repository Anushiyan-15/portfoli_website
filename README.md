# Modern 3D Portfolio (PHP + MySQL + Three.js)

Existing project upgraded in place: same `db.php` (`$conn`) conventions, same
`portfolio_db` database, plus a secure admin area, PHPMailer contact form and an
interactive 3D robot.

## Quick start (UniServerZ)

1. Start Apache + MySQL in UniServerZ.
2. Visit `http://localhost/project/portfolio/install.php`
   - creates the database/tables from `schema.sql`,
   - seeds profile/skills/timeline/demo project,
   - creates admin `admin / ChangeMe123!`.
3. **Delete or restrict `install.php`** after first run.
4. Log in at `admin/login.php`, change the password, edit **Profile & Photo**.
5. Copy `config.mail.example.php → config.mail.php` and fill SMTP values
   (or set env vars — see below). Without SMTP the contact form still saves
   every message to `contact_submissions` and shows success.

## Email (PHPMailer, no secrets in code)

`process-contact.php` uses `lib/PHPMailer` over SMTP with:

| Key | Env var | Meaning |
|---|---|---|
| `MAIL_HOST` | `MAIL_HOST` | e.g. `smtp.gmail.com` |
| `MAIL_PORT` | `MAIL_PORT` | `587` (tls) / `465` (ssl) |
| `MAIL_SECURE` | `MAIL_SECURE` | `tls` / `ssl` / empty |
| `MAIL_USER` / `MAIL_PASS` | same | SMTP auth |
| `MAIL_FROM` / `MAIL_FROM_NAME` | same | sender (must match provider) |
| `MAIL_TO` / `MAIL_TO_NAME` | same | inbox for contact mail |

Set via real environment variables (take precedence) **or** `config.mail.php`
(private, git-ignored). Never commit real credentials.

Validation: required name/email/message, `FILTER_VALIDATE_EMAIL`, length caps,
CRLF-injection guard, honeypot + CSRF. Failures are logged server-side; visitors
only see generic messages.

## Managing content

- **Profile/photo/resume:** Admin → Profile & Photo. Tap the 📷 badge on the
  avatar (messaging-app pattern) for instant preview, then Save.
- **Projects:** Admin → Projects → New/Edit, then **Files** to upload source
  (`php/html/css/js/…` + images/pdf/zip). Text files get an on-site **View**
  button (`project.php?slug=…&view=…`); all files are downloadable.
- **Skills:** Admin → Skills (Frontend/Backend/Tools + % bars).
- **Timeline:** Admin → Experience (type = experience/education).
- **Testimonials / Blog:** hidden automatically when empty or invisible/draft.
- **Messages:** Admin → Messages (read/delete; also stored in DB).

## Customising look

- Palette in `assets/css/style.css` (`:root` vars `--brand`, `--accent`, …).
  Layout style is card-based minimal; `data-theme="light"` overrides included.
- Fonts: Sora + Inter via Google Fonts (`index.php` `<head>`); swap the link
  to change. Placeholders for name/role/skills/tone/palette/font/layout are
  editable in Admin → Profile.
- 3D robot: `assets/js/robot-three.js` (procedural Three.js, no model download).
  Click/Enter = wave + speech; follows pointer; scroll parallax; disabled with
  `prefers-reduced-motion` or no WebGL (static accessible fallback).

## Structure

```
index.php               public site        project.php      project detail + code browser
process-contact.php     validated form → DB + PHPMailer
db.php / config.php     MySQLi $conn + env/private-config loader
includes/helpers.php    escaping, csrf, auth, uploads
schema.sql / install.php DB create + seed
admin/                  login, dashboard, profile, projects(+files),
                        skills, experience, testimonials, posts, messages
assets/css/style.css    theme (dark/light, responsive, reduced-motion)
assets/js/main.js       theme/nav/reveal/filter/tilt
assets/js/robot-three.js interactive robot
lib/PHPMailer/          vendored mail library (no composer needed)
uploads/                profile/resumes/projects/blog (php execution denied)
```

## Accessibility / performance / SEO

- Mobile-first CSS, sticky nav, smooth scroll, lazy-loaded images, no heavy
  framework (Bootstrap removed for speed; Three.js loads deferred from CDN).
- Semantic landmarks, skip link, alt text, focus-visible, keyboard-operable
  robot, `prefers-reduced-motion` handling, contrast-tested palette.
- SEO + Open Graph + JSON-LD Person tags in `index.php`.
