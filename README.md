# Banya Hostels website - PHP + MySQL + Bootstrap (BIT 2103)

## Run on WAMP
Admin: http://localhost/banya/admin/login.php. Use Settings to enter the real phone, WhatsApp, bank/mobile money details and Wi-Fi rates.

## Pages
Home, About, Gallery, Enquiries, Book a room (including payment information), Track booking, News / Blog, Contact, sitemap.xml.

## Where to edit
- `config.php`: database connection, site URLs and shared PHP helpers.
- `schema.sql`: complete fresh-install database, including tables, starter hostel data, settings and news articles.
- Root PHP pages: public website pages and their page-specific server-side logic.
- `includes/`: shared public-site header and footer.
- `admin/`: administrator login and management pages.
- `api/rooms.php`: room, occupancy capacity and pricing data used by the booking form.
- `assets/css/style.css` and `assets/css/admin.css`: public-site and admin styling.
- `assets/js/app.js`: shared form validation, date limits and action confirmations.
- `uploads/` and `assets/banya_images/`: uploaded and bundled site images, including the website favicon. The bundled hostel photos are seeded into the categorized gallery when importing `schema.sql`.

## Admin
Hostels & prices (incl. Banya 3 coming soon -> open), Rooms & availability, Bookings (approve/reject/cancel/complete), Enquiries & complaints, Gallery (upload, categorize, edit and delete photos), Blog posts (create, categorize, publish, edit and delete articles), Settings. Temporary admins are identified by an expiry date, shown a badge in the admin header, and denied access after expiry.

## Data from the client report
- Banya 5: 50 single non-self-contained rooms, UGX 600,000, shared bathrooms, Wi-Fi, CCTV.
- Banya 4: 55 self-contained rooms: alone 800,000; two students sharing 500,000 each; Wi-Fi paid separately.
- Banya 3: under construction, editable from admin.
- ASSUMPTIONS to confirm with client: room numbers (B5-01.., B4-01..), Wi-Fi rates, phone/WhatsApp, logo, brand colour, photos.

## Booking logic
Student picks an open hostel, available room and occupancy/price, then enters MMU registration, contact and intended-stay details. Every request starts Pending and does not reserve a room until an administrator approves it. Approval checks room capacity; shared occupancy options remain available only until their capacity is reached. Administrators can mark approved stays Completed to release capacity. Students track status with reference + phone.

## Deploy to cPanel
Create DB + user, import schema.sql (remove CREATE DATABASE/USE lines), edit config.php (DB_*, BASE_URL='', SITE_URL='https://yourdomain'), upload, run install.php and delete it, install SSL and uncomment HTTPS lines in .htaccess, edit robots.txt, submit sitemap in Search Console.
Security (done in code): hashed passwords, login limit, CSRF, prepared statements, upload checks, headers. Still to do on server: SSL, malware/firewall tool, scheduled backups + restore demo.
