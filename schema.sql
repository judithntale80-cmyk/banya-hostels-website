-- ===== DATABASE INITIALIZATION =====
-- Complete fresh-install schema: tables, defaults, and starter news articles.
-- Import in phpMyAdmin (WAMP) to create the Banya Hostels database.
CREATE DATABASE IF NOT EXISTS banya_hostels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE banya_hostels;

-- ===== CORE TABLES: SETTINGS, ACCOUNTS, AND HOSTELS =====
CREATE TABLE settings (
    k VARCHAR(50) PRIMARY KEY,
    v TEXT NOT NULL
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    expires_at DATETIME NULL DEFAULT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE hostels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(80) NOT NULL,
    tagline VARCHAR(160),
    description TEXT,
    facilities TEXT,
    status ENUM('open', 'upcoming') DEFAULT 'open',
    image VARCHAR(100) NULL
);

CREATE TABLE pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_id INT NOT NULL,
    label VARCHAR(120) NOT NULL,
    amount DECIMAL(12, 0) NOT NULL,
    slots TINYINT DEFAULT 1,
    note VARCHAR(200) NULL,
    FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE CASCADE
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_id INT NOT NULL,
    room_no VARCHAR(20) NOT NULL,
    status ENUM('available', 'reserved', 'occupied') DEFAULT 'available',
    UNIQUE KEY uq_room (hostel_id, room_no),
    FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE CASCADE
);

-- ===== BOOKINGS, ENQUIRIES, GALLERY, AND LOGIN TRACKING =====
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(12) UNIQUE NOT NULL,
    room_id INT NOT NULL,
    pricing_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    reg_no VARCHAR(40) NOT NULL,
    institution VARCHAR(120) NOT NULL DEFAULT 'Mountains of the Moon University',
    course VARCHAR(100),
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    notes TEXT,
    check_in_date DATE NULL,
    stay_duration_months SMALLINT UNSIGNED NULL,
    occupants TINYINT UNSIGNED NOT NULL DEFAULT 1,
    payment_ref VARCHAR(80),
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bookings_room_status (room_id, status),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (pricing_id) REFERENCES pricing(id) ON DELETE CASCADE
);

CREATE TABLE enquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('enquiry', 'complaint') NOT NULL,
    category VARCHAR(50) NULL,
    name VARCHAR(100),
    reg_no VARCHAR(40),
    email VARCHAR(120),
    phone VARCHAR(30),
    hostel_id INT NULL,
    subject VARCHAR(150),
    body TEXT,
    status ENUM('new', 'in_progress', 'responded', 'resolved') NOT NULL DEFAULT 'new',
    reply TEXT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_id INT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Common Areas',
    caption VARCHAR(160),
    image VARCHAR(100) NOT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gallery_category_created (category, created)
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(190) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT NULL,
    body MEDIUMTEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    image VARCHAR(255) NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_posts_category_created (category, created)
);

INSERT INTO posts (slug, title, excerpt, body, category, image) VALUES
(
    'banya-5-vs-banya-4-which-student-hostel-fits-you-best',
    'Banya 5 vs Banya 4: Which Student Hostel Fits You Best?',
    'Compare Banya 5 and Banya 4 on room type, bathrooms, price, Wi-Fi, cooking space and parking to find the best student hostel near MMU.',
    'Choosing between Banya 5 and Banya 4 is easier when you compare them side by side. Both hostels are part of Banya Hostels, both are very near Mountains of the Moon University, and both have water, electricity and CCTV cameras. The differences are in the type of room, the bathroom, the price and the extras.

## Banya 5: single rooms at a fixed price

Banya 5 has 50 rooms, and every room is a single room. The rooms are non-self-contained, so bathrooms are shared with other residents. The stated accommodation fee is UGX 600,000 per room. Wi-Fi is available, and although there is no formal kitchen, residents have a space where they can cook. Many CCTV cameras are placed around the hostel.

## Banya 4: self-contained rooms with flexible sharing

Banya 4 has 55 self-contained rooms, so every room has its own bathroom. Rooms are generally designed as double rooms. You can stay alone for UGX 800,000, share with one other student for UGX 500,000 each, or share with three or four students for UGX 400,000 each, depending on room size. Banya 4 also has some parking space.

## Side-by-side comparison

- Rooms: Banya 5 has 50 single rooms. Banya 4 has 55 self-contained rooms, generally designed as doubles.
- Bathroom: Banya 5 bathrooms are shared. Banya 4 rooms are self-contained.
- Price: Banya 5 is UGX 600,000 per room. Banya 4 is UGX 800,000 alone, UGX 500,000 each for two, or UGX 400,000 each for three or four.
- Wi-Fi: Banya 5 has Wi-Fi available. Banya 4 has Wi-Fi installed, paid for separately.
- Cooking: Banya 5 residents have a space where they can cook.
- Parking: Banya 4 has some parking space.

## Which one should you choose?

Choose Banya 5 if you want your own room at a fixed price and you do not mind shared bathrooms. Choose Banya 4 if you want a private bathroom, or if you and friends want to share a room and split the cost.

Whichever you pick, check the live room availability on the hostel page, then send your request on the Book & Pay page. Banya Hostels is for Mountains of the Moon University students, so keep your registration number ready.',
    'Student Guides',
    'assets/banya_images/Student''s room at banya 5.webp'
),
(
    'share-and-save-how-room-sharing-at-banya-4-lowers-your-cost',
    'Share and Save: How Room Sharing at Banya 4 Lowers Your Cost',
    'At Banya 4 the price per student drops as more students share a room. See the real numbers and tips for choosing roommates.',
    'Accommodation is one of the biggest costs of university life, and Banya 4 gives you a way to reduce it. The hostel has 55 self-contained rooms that are generally designed as double rooms, and the price you pay depends on how many students share the room.

## The Banya 4 price options

- Staying alone: UGX 800,000.
- Two students sharing: UGX 500,000 each, which makes UGX 1,000,000 for the room.
- Three or four students sharing: UGX 400,000 each, depending on room size.

## What you save

If you share with one other student instead of staying alone, you pay UGX 300,000 less each (UGX 800,000 compared with UGX 500,000). If three or four students share, each pays UGX 400,000, which is UGX 400,000 less than staying alone and UGX 100,000 less than sharing between two. The room size decides how many students can share, so ask the administration which option fits the room you want.

## Tips for choosing roommates

Share with people whose study habits and routines match yours. Agree early on quiet hours, cleaning, visitors and shared items. Remember that everyone in the room must be a Mountains of the Moon University student, because Banya Hostels is not for the general public or for students from other universities.

## How to request a shared room

Open the Book & Pay page, choose Banya 4, pick an available room and select the sharing option that matches your group. Write your roommates'' names in the Notes box so the administration can match your requests. Once the booking is approved, each student follows the payment steps and submits their own payment reference.',
    'Student Guides',
    'assets/banya_images/banya4-double-room.jpg'
),
(
    'how-to-book-and-pay-for-your-room-at-banya-hostels-step-by-step',
    'How to Book and Pay for Your Room at Banya Hostels, Step by Step',
    'Follow the simple steps to book a room online, wait for approval, pay to the official account and track your payment at Banya Hostels.',
    'Booking a hostel room should not be stressful. Banya Hostels lets Mountains of the Moon University students check available rooms, send a request and follow the progress online. Here is the full process.

## Step 1: Choose your hostel and room

Compare Banya 5 and Banya 4 on their pages. Each page shows the facilities, the accommodation charges and the live availability of rooms. Banya 3 is still under construction, so it cannot be booked yet.

## Step 2: Send your booking request

Open the Book & Pay page. Choose the hostel, an available room and your occupancy option, then enter your name, registration number, phone number and email. When you submit, you receive a booking reference that starts with the letters BH. Write it down.

## Step 3: Wait for approval

The administration reviews your request and approves or rejects it. Do not pay before your booking is approved. You can check the status at any time on the Track booking page using your reference and the phone number you used.

## Step 4: Pay to the official account

After approval, pay using the official bank or mobile money details shown on the Book & Pay page. Never pay to a number or account that is not shown on the official website, and keep your receipt.

## Step 5: Submit your payment reference

Go to Track booking, enter your reference and phone number, and submit the payment method, the amount and the transaction or receipt number. You can also upload a photo of your receipt. The administration compares it with the official statement and marks it verified. You will see your balance and a Fully paid message when everything is complete.

## Need help?

If something is not clear, use the Enquiries page or read the FAQ. The administration will respond.',
    'Booking & Payments',
    'assets/banya_images/hostel-corridor.jpg'
),
(
    'wi-fi-at-banya-hostels-what-residents-should-know',
    'Wi-Fi at Banya Hostels: What Residents Should Know',
    'Banya 5 has Wi-Fi available, while Banya 4 residents subscribe separately. Learn how daily, weekly and monthly Wi-Fi subscriptions work.',
    'Reliable internet matters for research, assignments, online lectures and staying in touch with family. Here is what you need to know about Wi-Fi at Banya Hostels.

## Wi-Fi at Banya 5

Banya 5 has Wi-Fi available for residents. If you choose a single room in Banya 5, ask the administration how you can connect and what rules apply.

## Wi-Fi at Banya 4

At Banya 4 the Wi-Fi infrastructure is already installed, but Wi-Fi is not included in the rent. Residents pay for it separately and may subscribe daily, weekly or monthly. This means you pay only for the time you need.

## Which subscription should you choose?

A daily subscription suits short periods, such as a day when you must submit an online assignment. A weekly subscription fits busy weeks with heavy research. A monthly subscription is usually best if you study online every day. Ask the administration for the current rates and compare them with the number of days you really need internet.

## Make your data last

Download large files only when you have a connection, close apps that update in the background, and keep your devices charged. Do not share your login details with people outside your room, because this may slow down the network for everyone.

## Where to find the latest information

The Wi-Fi information on this website is updated by the administration. If rates change, the new information will appear on the Book & Pay page. For any question, send an enquiry.',
    'Hostel Information',
    'assets/banya_images/veranda-ground-floor with water tap.jpg'
),
(
    'staying-safe-in-your-hostel-security-features-and-smart-habits',
    'Staying Safe in Your Hostel: Security Features and Smart Habits',
    'Banya 5 and Banya 4 are supported by CCTV cameras. Learn what this means and the daily habits that keep you and your belongings safe.',
    'Feeling safe is a basic need for every student. At Banya Hostels, security is supported by CCTV cameras at both Banya 5 and Banya 4, and the hostels are very near Mountains of the Moon University and the surrounding student area.

## What the hostels provide

Banya 5 has many CCTV cameras around the hostel. Banya 4 is also supported by CCTV cameras. Water and electricity are available in both hostels, which helps residents stay comfortable and avoid unsafe alternatives such as candles or unsafe wiring.

## Smart habits that protect you

Cameras help, but your own habits matter just as much. Always lock your room, even when you are only going to the bathroom or to a neighbour. Keep your laptop, phone and money out of sight. Do not lend your key or your room access to people you do not know. Tell a roommate or a friend when you will return late.

## Electrical safety

Do not overload sockets. Switch off irons, heaters and chargers when you leave the room. If you notice damaged wires or sockets, report them to the administration immediately rather than trying to repair them.

## Visitors and respect for others

Know who is coming into the hostel and follow the hostel rules on visitors. Respect quiet hours so that everyone can study and sleep.

## Report problems early

If something does not feel right, or if an item goes missing, tell the administration quickly. You can also send a complaint through the Enquiries page and management will review it.',
    'Safety & Tips',
    'assets/banya_images/banya5-corridor.jpg'
),
(
    'banya-3-is-on-the-way-what-we-know-so-far',
    'Banya 3 Is on the Way: What We Know So Far',
    'Banya 3 is under construction and will join Banya 5 and Banya 4. Find out what has been confirmed and how you will hear about its opening.',
    'Banya Hostels is growing. Banya 3 is the third hostel in the Banya family, and it is still under construction.

## What has been confirmed

Banya 3 is part of Banya Hostels and is managed by the same administration as Banya 5 and Banya 4. It is not open for bookings yet, which is why its page is marked Coming soon. The photographs on the Gallery page show the structure rising, with concrete columns, slabs and a staircase taking shape.

## What has not been announced yet

The number of rooms, the room types, the prices and the opening date have not been announced. We do not want to promise details that are not final. As soon as construction is complete and the administration approves the information, it will be added to the Banya 3 page.

## How you will hear about it

The administration can update the Banya 3 page directly from the admin dashboard, change its status from Coming soon to Open, add its rooms and publish its prices. Once that happens, the hostel will appear on the Book & Pay page automatically, and you will be able to see its live room availability.

## What you can do now

Check the Banya 3 page and the News section regularly. If you would like to ask about the opening date, send a message through the Enquiries page. If you cannot wait, Banya 5 and Banya 4 are open and have rooms you can request today.',
    'Banya 3 Updates',
    'assets/banya_images/banya3-construction-columns.jpg'
),
(
    'what-to-bring-and-plan-before-you-move-into-your-hostel',
    'What to Bring and Plan Before You Move Into Your Hostel',
    'A simple checklist of what to bring, what to confirm and what to plan before moving into a student hostel near Mountains of the Moon University.',
    'Moving into a hostel is exciting, and a little planning makes the first week much easier. Use this checklist before you arrive at Banya Hostels.

## Documents and payments

- Your booking reference and the approval from the administration.
- Your Mountains of the Moon University registration number and student identification.
- Your payment receipt, and your verified payment status from the Track booking page.
- Your phone number and the contact of a parent, guardian or sponsor.

## Things for your room

Check what is already in the room before you buy anything. Then bring your bedding, a lock if the administration asks for it, a lamp or torch, a power extension that is in good condition, hangers, a bucket or basin, and cleaning supplies. If you plan to cook, remember that Banya 5 has no formal kitchen but offers a space where residents can cook, so bring only simple cooking items.

## For studying

Bring a laptop or notebooks, chargers, a notebook for timetables and a small clock. At Banya 4, plan your Wi-Fi subscription (daily, weekly or monthly) based on how often you study online.

## Before you unpack

Take photographs of the room, bathroom and furniture when you arrive and report any damage to the administration. This protects you later. Learn where the water, electricity and the CCTV-supported areas are, and keep the administration''s contact details on your phone.

## Settle in

Introduce yourself to your neighbours, join the residents'' WhatsApp group if you are invited, and keep the Enquiries page in mind for questions or complaints. A calm, organised start helps you focus on what matters most: your studies.',
    'Safety & Tips',
    'assets/banya_images/hostel-corridor.jpg'
);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45),
    at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ===== DEFAULT SITE SETTINGS =====
INSERT INTO settings (k,v) VALUES
('site_name','Banya Hostels'),
('tagline','Student accommodation next to Mountains of the Moon University'),
('brand_color','#1b4965'),
('phone','0775626618'),('whatsapp','256787270383'),('email','info@banyahostels.com'),
('address','Saaka Rd, kasindikwa Village, Fort Portal'),
('hours','Mon - Sat: 8:00am - 6:00pm'),
('about','Banya Hostels is one organisation with three hostel facilities under one management: Banya 5, Banya 4 and the upcoming Banya 3. We provide safe, affordable accommodation very close to Mountains of the Moon University, for MMU students.'),
('security_info','Banya 5 and Banya 4 are monitored by CCTV cameras. Water and electricity are available in all hostels.'),
('wifi_info','Banya 4 has Wi-Fi infrastructure installed, but Wi-Fi is not included in rent. Residents pay separately and may subscribe daily, weekly or monthly. Ask the administration for the current subscription rates. Banya 5 has Wi-Fi available.'),
('bank_name','Centenary Bank'),('bank_account_name','Banya Hostels'),('bank_account_no','5620004830'),
('mobile_money','0787270383'),('cash_instructions','Hostel management office');

-- ===== STARTER HOSTELS AND ROOM PRICES =====
INSERT INTO hostels (slug,name,tagline,description,facilities,status) VALUES
('banya-5','Banya 5','50 single rooms with shared bathrooms','Banya 5 offers 50 single, non-self-contained rooms for students who want an affordable room close to campus. Bathrooms are shared. Residents have a space where they can cook, although there is no formal kitchen.','50 single rooms\nNon-self-contained rooms\nShared bathrooms\nWater and electricity\nWi-Fi available\nSpace for residents to cook\nMany CCTV cameras around the hostel','open'),
('banya-4','Banya 4','55 self-contained rooms with flexible sharing','Banya 4 has 55 self-contained rooms, generally designed as double rooms. You can stay alone or share with one other student.','55 self-contained rooms\nDouble rooms, sharing by 2 students\nWater and electricity\nWi-Fi installed (paid separately, daily/weekly/monthly)\nSome parking space\nCCTV cameras','open'),
('banya-3','Banya 3','Coming soon','Banya 3 is still under construction and is part of Banya Hostels. Full details, rooms and prices will be published here once construction is complete. Contact us to ask about the opening date.','Details to be announced after construction','upcoming');
INSERT INTO gallery (hostel_id, category, caption, image) VALUES
(1,'Single Rooms','A furnished single room at Banya 5','assets/banya_images/Student''s room at banya 5.webp'),
(2,'Single Rooms','Single room at Banya 4','assets/banya_images/banya4-single-room.jpg'),
(2,'Single Rooms','Student room setup at Banya 4','assets/banya_images/student room setup at banya4.jpg'),
(2,'Single Rooms','Banya 4 room with a wall-mounted TV','assets/banya_images/student-room-wall-tv at banya 4.jpg'),
(1,'Single Rooms','Storage space inside a Banya 5 room','assets/banya_images/student''s space for things at banya5.jpg'),
(2,'Double Rooms','Double room at Banya 4','assets/banya_images/banya4-double-room.jpg'),
(2,'Bathrooms','Bathroom in a Banya 4 double room','assets/banya_images/banya4-double-bathroom.jpg'),
(2,'Bathrooms','Bathroom in a Banya 4 single room','assets/banya_images/banya4-single-bathroom.jpg'),
(1,'Bathrooms','Shared bathrooms at Banya 5','assets/banya_images/shared bathrooms.png'),
(2,'Exterior','Banya 4 hostel showcase','assets/banya_images/Banya4 Showcase.png'),
(1,'Exterior','Exterior view of Banya 5','assets/banya_images/Outside Banya5.jpg'),
(NULL,'Exterior','Front view of the hostel building','assets/banya_images/main-building-front.jpg'),
(1,'Exterior','Banya 5 exterior with its red roof','assets/banya_images/yellow-block-red-roof.jpg'),
(2,'Exterior','Banya 4 rooftop water tanks','assets/banya_images/banya4 with water tanks on the roof.jpeg'),
(3,'Exterior','Banya 3 construction columns','assets/banya_images/banya3-construction-columns.jpg'),
(3,'Exterior','Banya 3 staircase under construction','assets/banya_images/banya3-construction-staircase.jpg'),
(2,'Surroundings','Parking area at Banya 4','assets/banya_images/Parking area at banya4.jpeg'),
(1,'Common Areas','Banya 5 corridor','assets/banya_images/banya5-corridor.jpg'),
(NULL,'Common Areas','Shared hostel corridor','assets/banya_images/hostel-corridor.jpg'),
(NULL,'Common Areas','Ground-floor veranda with a water tap','assets/banya_images/veranda-ground-floor with water tap.jpg'),
(NULL,'Common Areas','Water tank and hanging area','assets/banya_images/water tank with hanging area.jpeg'),
(1,'Single Rooms','Single room at Banya 5','assets/banya_images/single room at banya5.jpg'),
(2,'Exterior','Water tank on the Banya 4 roof','assets/banya_images/banya4-roof-water-tank.jpg'),
(1,'Common Areas','CCTV cameras around the hostel compound','assets/banya_images/CCTV Cameras on the compound .jpeg'),
(1,'Bathrooms','Pit latrine at Banya 5','assets/banya_images/Pit latrine at banya5.png');
INSERT INTO pricing (hostel_id,label,amount,slots,note) VALUES
(1,'Single room (1 student)',600000,1,'Per room, per accommodation period'),
(2,'Staying alone',800000,1,'One student in a double room'),
(2,'Two students sharing',500000,2,'Per student (UGX 1,000,000 per room)');

-- ===== STARTER ROOMS FOR BANYA 5 =====
INSERT INTO rooms (hostel_id,room_no) VALUES
(1,'B5-01'),
(1,'B5-02'),
(1,'B5-03'),
(1,'B5-04'),
(1,'B5-05'),
(1,'B5-06'),
(1,'B5-07'),
(1,'B5-08'),
(1,'B5-09'),
(1,'B5-10'),
(1,'B5-11'),
(1,'B5-12'),
(1,'B5-13'),
(1,'B5-14'),
(1,'B5-15'),
(1,'B5-16'),
(1,'B5-17'),
(1,'B5-18'),
(1,'B5-19'),
(1,'B5-20'),
(1,'B5-21'),
(1,'B5-22'),
(1,'B5-23'),
(1,'B5-24'),
(1,'B5-25'),
(1,'B5-26'),
(1,'B5-27'),
(1,'B5-28'),
(1,'B5-29'),
(1,'B5-30'),
(1,'B5-31'),
(1,'B5-32'),
(1,'B5-33'),
(1,'B5-34'),
(1,'B5-35'),
(1,'B5-36'),
(1,'B5-37'),
(1,'B5-38'),
(1,'B5-39'),
(1,'B5-40'),
(1,'B5-41'),
(1,'B5-42'),
(1,'B5-43'),
(1,'B5-44'),
(1,'B5-45'),
(1,'B5-46'),
(1,'B5-47'),
(1,'B5-48'),
(1,'B5-49'),
(1,'B5-50');

-- ===== STARTER ROOMS FOR BANYA 4 =====
INSERT INTO rooms (hostel_id,room_no) VALUES
(2,'B4-01'),
(2,'B4-02'),
(2,'B4-03'),
(2,'B4-04'),
(2,'B4-05'),
(2,'B4-06'),
(2,'B4-07'),
(2,'B4-08'),
(2,'B4-09'),
(2,'B4-10'),
(2,'B4-11'),
(2,'B4-12'),
(2,'B4-13'),
(2,'B4-14'),
(2,'B4-15'),
(2,'B4-16'),
(2,'B4-17'),
(2,'B4-18'),
(2,'B4-19'),
(2,'B4-20'),
(2,'B4-21'),
(2,'B4-22'),
(2,'B4-23'),
(2,'B4-24'),
(2,'B4-25'),
(2,'B4-26'),
(2,'B4-27'),
(2,'B4-28'),
(2,'B4-29'),
(2,'B4-30'),
(2,'B4-31'),
(2,'B4-32'),
(2,'B4-33'),
(2,'B4-34'),
(2,'B4-35'),
(2,'B4-36'),
(2,'B4-37'),
(2,'B4-38'),
(2,'B4-39'),
(2,'B4-40'),
(2,'B4-41'),
(2,'B4-42'),
(2,'B4-43'),
(2,'B4-44'),
(2,'B4-45'),
(2,'B4-46'),
(2,'B4-47'),
(2,'B4-48'),
(2,'B4-49'),
(2,'B4-50'),
(2,'B4-51'),
(2,'B4-52'),
(2,'B4-53'),
(2,'B4-54'),
(2,'B4-55');

-- ===== PAYMENTS TABLE =====
CREATE TABLE IF NOT EXISTS payments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    booking_ref     VARCHAR(12)   NOT NULL,
    student_name    VARCHAR(100)  NOT NULL,
    phone           VARCHAR(30)   NOT NULL,
    amount          DECIMAL(12,0) NOT NULL,
    method          ENUM('mobile_money','bank_transfer','cash') NOT NULL,
    transaction_ref VARCHAR(100)  NOT NULL,
    payment_date    DATE          NOT NULL,
    receipt         VARCHAR(100)  NULL,
    status          ENUM('pending','verified','rejected','refunded') NOT NULL DEFAULT 'pending',
    notes           TEXT          NULL,
    created         DATETIME      DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_booking_ref (booking_ref),
    INDEX idx_status      (status)
);
