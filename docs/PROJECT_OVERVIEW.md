# Banya Hostels – Project Overview

## Purpose
The Banya Hostels website presents accommodation information and supports online booking requests and administration of website content and accommodation records.

## Main public routes/files
- `index.php` – homepage.
- `about.php` – about information.
- `hostel.php` – hostel details; friendly hostel paths are rewritten by Apache.
- `gallery.php` – photo gallery.
- `booking.php` – booking request workflow.
- `track.php` – booking status lookup.
- `enquiries.php` / `enquires.php` – enquiry-related page files present in the supplied project archive; verify which route is linked and retain compatibility until confirmed.
- `news.php` and `post.php` – news listing and individual post display.
- `contact.php` – contact page.
- `sitemap.php` – XML sitemap endpoint.

## Administration files
The `admin/` directory contains login/logout and management pages for bookings, enquiries, gallery, hostels, rooms and settings.

## Database
The supplied `schema.sql` defines tables for settings, users, hostels, pricing, rooms, bookings, enquiries, gallery, posts and login attempts. Import it only into a fresh database or after making a verified backup and reviewing the SQL.

## Important configuration
`config.php` contains the database connection and site URL settings. Configure it for the local or hosted environment. Never commit production database passwords or private client information.

## Important deployment checks
- Confirm the hosting provider supports the Apache directives/modules used in `.htaccess`.
- Set the correct `SITE_URL` and `BASE_URL`.
- Update the sitemap URL in `robots.txt`.
- Enable HTTPS only after the certificate is installed and tested.
- Test the booking and admin workflows after deployment.
