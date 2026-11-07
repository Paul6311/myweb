PORTFOLIO WEBSITE WITH ADMIN AREA (PHP + MySQL/MariaDB)

FILES
  index.html        public website (form posts to api/contact.php, CV button uses cv.php)
  api/contact.php   validates + saves each message and emails you an alert
  cv.php            serves the CV you last uploaded
  admin/            private dashboard: messages, CV upload/replace, change password
  inc/bootstrap.php settings (database login, admin email, setup code) + database code
  database.sql      OPTIONAL: the database and tables as SQL (the site also creates them itself)
  data/             uploaded CV (blocked from the web by .htaccess; inc/ is blocked too)

RUN ON XAMPP (Windows)
  1. Start Apache AND MySQL in the XAMPP control panel.
  2. Extract this zip into C:\xampp\htdocs\myweb\  (keep the folders; do not flatten them).
  3. Open inc\bootstrap.php. Set SETUP_CODE to your own secret. XAMPP's default DB login
     (root, empty password) is already filled in.
  4. Visit http://localhost/myweb/ . The first request creates the "portfolio" database and tables.
     (Or paste database.sql into phpMyAdmin > SQL if you want to create them by hand.)
  5. Visit http://localhost/myweb/admin/login.php, create your username and password (needs the setup code).
  6. Upload your CV PDF in the dashboard. Send a test message from the contact form.
  Note: XAMPP cannot send email unless sendmail is configured; locally, messages appear in the dashboard only.

GOING LIVE
  Use a PHP 7.4+ host with MySQL and HTTPS. Create a database + user in the host's control panel, then put
  DB_HOST / DB_NAME / DB_USER / DB_PASS in inc/bootstrap.php. Never use root with an empty password online.
  Apache/LiteSpeed honour the .htaccess files; on Nginx add rules denying /data and /inc.
  Netlify/Vercel/GitHub Pages cannot run PHP.

NOTIFICATIONS
  Every message is stored and shown in the dashboard (unread highlighted, count in the tab title).
  Email alerts use PHP mail(); some hosts send them to spam or disable it. The dashboard always has the message.

BACKUPS
  Export the database from phpMyAdmin now and then, and keep a copy of your CV.
