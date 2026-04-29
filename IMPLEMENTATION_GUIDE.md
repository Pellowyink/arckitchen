# ARC Kitchen Integrated Management System
## Master Technical Blueprint - Implementation Complete ✅

**Version:** 1.0  
**Date:** April 30, 2026  
**Status:** Ready for Testing

---

## 📋 Table of Contents
1. [System Architecture](#system-architecture)
2. [Database Setup](#database-setup)
3. [Security Implementation](#security-implementation)
4. [Admin Interface Features](#admin-interface-features)
5. [AJAX & Real-time Filtering](#ajax--real-time-filtering)
6. [State Machine Logic](#state-machine-logic)
7. [File Structure](#file-structure)
8. [Next Steps](#next-steps)

---

## 🏗️ System Architecture

### Core Components
This is a **full-stack PHP/MySQL catering system** built on a modular architecture:

- **Frontend**: Customer-facing pages (home, menu, booking, inquiry)
- **Admin Dashboard**: Real-time system overview with state-machine counters
- **Admin Panels**: Unified inquiries/bookings management with AJAX filtering
- **Database**: MySQL with normalized tables for users, inquiries, bookings, packages, menu items

### Visual Design System
- **Primary Color**: Deep Maroon (#4a1414)
- **Secondary Color**: Maroon (#8a2927)
- **Background**: Cream (#fffdf8)
- **Accent**: Gold (#d5a437)
- **Border Radius**: 25px (organic, artisanal feel)
- **Typography**: League Spartan (headers), Inter (body text)

---

## 🗄️ Database Setup

### Execute Migration
Run the database initialization scripts in this order:

```bash
# 1. Initial setup (creates all base tables)
mysql -u root -p arc_kitchen < database/arc_kitchen.sql

# 2. Add bookings table and update schema
mysql -u root -p arc_kitchen < database/arc_kitchen_migration_v2.sql
```

### Database Tables

#### `users` (Admin Authentication)
- Stores admin accounts with bcrypt-hashed passwords
- Default account: `admin` / `admin123` (⚠️ CHANGE IMMEDIATELY)

#### `inquiries` (Customer Booking Requests)
- Incoming inquiry submissions from customers
- Status: `pending` → `approved` → (create booking)
- Status: `pending` → `rejected` (conversely)

#### `bookings` (Confirmed Events)
- Created when inquiry is approved
- Stores items JSON, total amount, event details
- Status progression: `pending` → `confirmed` → `completed`
- Can be `cancelled` or `blocked` at any stage

#### `menu_items` & `packages` (Catalog)
- Includes `is_active` flag for visibility control
- Dashboard counts only active items

---

## 🔐 Security Implementation

### Session Management
- ✅ All admin pages call `requireAdminCheck()` at the top
- ✅ Automatic redirect to `/admin/login.php` if not authenticated
- ✅ Session variables: `$_SESSION['admin_id']`, `$_SESSION['admin_username']`

### Password Security
- Passwords hashed with PHP's `password_hash()` (bcrypt, cost=10)
- Verified with `password_verify()` on login
- Migration path to Argon2id available if needed

### CSRF & Input Validation
- All POST requests validated with `isPostRequest()`
- Input sanitized with `escape()` function (htmlspecialchars)
- Prepared statements prevent SQL injection

### API Endpoints
All `/api/` endpoints require `requireAdminCheck()`:
- `filter-inquiries.php` - Live search & filter
- `filter-bookings.php` - Live search & filter
- `get-inquiry.php` - Fetch single inquiry
- `get-booking.php` - Fetch single booking
- `update-inquiry.php` - Edit inquiry details
- `update-booking.php` - Edit booking details & items
- `update-inquiry-status.php` - Approve/Reject inquiry
- `update-booking-status.php` - Update booking status

---

## 📊 Admin Interface Features

### 1. Dashboard (dashboard.php)
**Five Dynamic Status Cards (State Machine)**

```
┌─────────────────────────────────────────────┐
│ 📋 Pending Inquiries  │ ✅ Confirmed Bookings │
│ (awaiting approval)   │ (ready to execute)   │
├───────────────────────┼──────────────────────┤
│ 🏆 Completed Bookings │ 📦 Active Packages    │
│ (successfully done)   │ (in catalog)         │
├──────────────────────────────────────────────┤
│ 🍽️ Active Menu Items  │
│ (available for sale)  │
└──────────────────────────────────────────────┘
```

**Counters Logic (State Machine)**
- **Pending Inquiries**: `COUNT(inquiries WHERE status = 'pending')`
  - Triggered: +1 on new submission, -1 on approve/reject
- **Confirmed Bookings**: `COUNT(bookings WHERE status = 'confirmed')`
  - Triggered: +1 when inquiry approved, -1 on complete/cancel
- **Completed Bookings**: `COUNT(bookings WHERE status = 'completed')`
  - Triggered: +1 when booking marked complete
- **Active Packages**: `COUNT(packages WHERE is_active = 1)`
- **Active Menu Items**: `COUNT(menu_items WHERE is_active = 1)`

### 2. Inquiries Panel (inquiries.php)
**Unified Inquiry Management**

**Filter Bar Components:**
- 🔍 Live Search (customer name/email)
- 📊 Status Dropdown (pending, approved, rejected)
- 📅 Date Range (from/to pickers)
- 📦 Package Filter
- ↑↓ Sort Toggle (newest/oldest)

**Editable Actions:**
- **Edit**: Opens modular sidebar to change event details
- **Approve**: Converts inquiry to confirmed booking (+1 to counters)
- **Reject**: Marks as rejected (-1 from pending count)

### 3. Bookings Panel (bookings.php)
**Same unified layout as Inquiries for consistency**

**Filter Bar Components:** (identical to inquiries)

**Editable Actions:**
- **Edit**: Opens modular sidebar to adjust items, dates, quantities
- **Confirm**: Moves booking from pending → confirmed
- **Complete**: Marks booking as completed (final status)

### 4. Modular Edit Sidebar (edit_sidebar.php)
**Right-side pop-up component with AJAX**

**Sections:**
1. **Customer Information** (read-only)
   - Name, Email, Phone

2. **Event Details** (editable)
   - Event Date
   - Event Type
   - Guest Count

3. **Order Items** (editable)
   - Add/Remove items
   - Adjust quantities
   - Edit prices
   - Real-time total calculation

4. **Special Requests** (editable)
   - Textarea for custom notes

5. **Status** (booking only)
   - Dropdown to change status

6. **Total Amount**
   - Subtotal, Tax (12%), Total
   - Updates in real-time as items change

---

## 🔄 AJAX & Real-time Filtering

### How Filtering Works

**Without Page Reload:**
1. User types in search box / selects filter
2. JavaScript event listener detects change
3. Sends AJAX request to `/api/filter-inquiries.php` or `/api/filter-bookings.php`
4. Server returns HTML table rows as JSON
5. DOM updates with new results

**Filter Parameters:**
- `search` - Customer name or email (LIKE search)
- `status` - Exact status match
- `date_from` - Event date >= specified date
- `date_to` - Event date <= specified date
- `package_id` - Package filter
- `sort` - Sort order (asc/desc)

**Example AJAX Call:**
```javascript
fetch(`../api/filter-inquiries.php?search=John&status=pending&sort=desc`)
  .then(response => response.json())
  .then(data => {
    document.getElementById('inquiries-table-body').innerHTML = data.html;
  });
```

---

## 🎯 State Machine Logic

### Inquiry State Diagram

```
[NEW INQUIRY] 
    ↓
  pending (awaiting admin review)
    ↓
  ┌─────────────┬──────────────┐
  ↓             ↓
approved      rejected
  ↓
[BOOKING CREATED]
  → bookings.status = 'pending'
```

### Booking State Diagram

```
bookings.status: 'pending'
    ↓
'confirmed' (admin approves)
    ↓
'completed' (event finished)
    ↓
[ARCHIVED]

OR

    → 'cancelled' (at any stage)
    → 'blocked' (at any stage)
```

### State Machine Functions

```php
// Approve inquiry and create booking
approveInquiry($inquiry_id);
// Trigger: -1 pending, +1 confirmed

// Reject inquiry
rejectInquiry($inquiry_id);
// Trigger: -1 pending

// Update booking status
updateBookingStatus($booking_id, $status);
// Can transition to: pending → confirmed → completed

// Dashboard counters
countPendingInquiries();       // → int
countConfirmedBookings();      // → int
countCompletedBookings();      // → int
countActivePackages();         // → int
countActiveMenuItems();        // → int
```

---

## 📁 File Structure

### New/Modified Files

```
arckitchen/
├── database/
│   └── arc_kitchen_migration_v2.sql        [NEW] Adds bookings table
├── includes/
│   ├── functions.php                       [UPDATED] +12 new state-machine functions
│   ├── filter_bar.php                      [NEW] Reusable filter component
│   └── edit_sidebar.php                    [NEW] Modular edit sidebar + CSS + JS
├── admin/
│   ├── dashboard.php                       [UPDATED] 5 dynamic status cards
│   ├── inquiries.php                       [UPDATED] Filter bar + AJAX + editable
│   ├── bookings.php                        [UPDATED] Filter bar + AJAX + editable
│   ├── login.php                           [UNCHANGED] ✅ Already secure
│   ├── logout.php                          [UNCHANGED] ✅ Already secure
│   ├── packages.php                        [UPDATED] Added requireAdminCheck()
│   ├── menu-manager.php                    [UPDATED] Added requireAdminCheck()
│   ├── calendar.php                        [UPDATED] Added requireAdminCheck()
│   ├── sales.php                           [UPDATED] Added requireAdminCheck()
│   ├── archives.php                        [UPDATED] Added requireAdminCheck()
│   └── setup_admin.php                     [UPDATED] Added requireAdminCheck()
├── api/                                    [NEW] 8 AJAX endpoints
│   ├── filter-inquiries.php
│   ├── filter-bookings.php
│   ├── get-inquiry.php
│   ├── get-booking.php
│   ├── update-inquiry.php
│   ├── update-booking.php
│   ├── update-inquiry-status.php
│   └── update-booking-status.php
└── assets/
    └── css/
        └── admin.css                       [UPDATED] +400 lines of new styles
```

---

## ✅ Features Implemented

- ✅ **Admin Login Portal** - Secure authentication with session checks
- ✅ **Dashboard** - 5 state-machine counters with color-coded cards
- ✅ **Inquiries Panel** - Live filtering, search, approve/reject workflow
- ✅ **Bookings Panel** - Live filtering, status transitions, inline editing
- ✅ **Modular Edit Sidebar** - AJAX-based order customization
- ✅ **State Machine** - Inquiry→Booking conversion with counter updates
- ✅ **AJAX Filtering** - No-refresh data updates with real-time results
- ✅ **Unified UI** - Consistent sidebar, cards, tables across all pages
- ✅ **Security** - Session checks, password hashing, prepared statements
- ✅ **Visual Design** - Maroon/cream/gold theme, 25px rounded corners
- ✅ **Responsive** - Mobile-friendly layout and components

---

## 🚀 Next Steps

### Before Going Live

1. **Run Database Migration**
   ```bash
   mysql -u root -p arc_kitchen < database/arc_kitchen_migration_v2.sql
   ```

2. **Change Default Admin Password**
   - Login with `admin` / `admin123`
   - Update in database via admin panel or SQL:
     ```sql
     UPDATE users SET password = PASSWORD_HASH('new_secure_password') WHERE username = 'admin';
     ```

3. **Test the State Machine**
   - Create test inquiry
   - Click "Approve" → verify booking is created
   - Check dashboard counters update correctly
   - Edit booking items and status
   - Verify all filters work without page reload

4. **Enable HTTPS**
   - Add to `.htaccess` for automatic redirect:
     ```apache
     RewriteEngine On
     RewriteCond %{HTTPS} off
     RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
     ```

5. **Setup Database Backups**
   - Schedule daily backups of `arc_kitchen` database
   - Store in secure location

6. **Deploy Customer-Facing Pages**
   - Update `booking.php` to use new bookings table
   - Update `inquiry.php` to use new inquiries table with status
   - Add calendar blocking for confirmed event dates
   - Add modular sidebar to customer menu for order customization

### Customer Workflow

**→ Customer submits inquiry**
```
booking.php (GET menu items/packages)
  ↓
inquiry.php (customer form)
  ↓
[INSERT inquiries table with status='pending']
```

**→ Admin approves inquiry**
```
inquiries.php (Admin clicks "Approve")
  ↓
approveInquiry() function
  ↓
[UPDATE inquiries status='approved']
[INSERT bookings from inquiry data]
  ↓
Dashboard counters: -1 pending, +1 confirmed
```

**→ Admin edits booking & updates status**
```
bookings.php (Admin clicks "Edit")
  ↓
edit_sidebar.php opens
  ↓
[UPDATE bookings items_json, total_amount, status]
  ↓
bookings.php reloads with new data
```

**→ Booking completes**
```
bookings.php (Admin clicks "Complete")
  ↓
updateBookingStatus($id, 'completed')
  ↓
[UPDATE bookings status='completed']
  ↓
Dashboard counters: -1 confirmed, +1 completed
```

---

## 📚 Database Queries Reference

### Dashboard Counters
```sql
-- Pending Inquiries
SELECT COUNT(*) FROM inquiries WHERE status = 'pending';

-- Confirmed Bookings
SELECT COUNT(*) FROM bookings WHERE status = 'confirmed';

-- Completed Bookings
SELECT COUNT(*) FROM bookings WHERE status = 'completed';

-- Active Packages
SELECT COUNT(*) FROM packages WHERE is_active = 1;

-- Active Menu Items
SELECT COUNT(*) FROM menu_items WHERE is_active = 1;
```

### Filtering Examples
```sql
-- Get pending inquiries for specific date range
SELECT * FROM inquiries 
WHERE status = 'pending' 
AND event_date BETWEEN '2026-05-01' AND '2026-06-30'
ORDER BY created_at DESC;

-- Search bookings by customer name
SELECT * FROM bookings 
WHERE customer_name LIKE '%John%' 
OR customer_email LIKE '%john%'
ORDER BY event_date;

-- Get all confirmed bookings ordered by event date
SELECT * FROM bookings 
WHERE status = 'confirmed'
ORDER BY event_date ASC;
```

---

## 🎓 Training Notes

### For Admins
- Dashboard shows real-time status of all inquiries and bookings
- Use filters to find specific records quickly
- Click "Edit" to change event details or add items
- "Approve" converts inquiry to booking automatically
- Status badges show current state (pending, confirmed, completed)

### For Developers
- All state-machine logic in `functions.php`
- AJAX endpoints in `/api/` folder
- Filter component reusable in other pages
- Edit sidebar is modular - can be used elsewhere
- CSS organized by component in `admin.css`

---

## 🐛 Troubleshooting

### "Access Denied" on admin pages
- **Issue**: User not authenticated
- **Solution**: Login at `/admin/login.php`
- **Check**: Session cookie is enabled in browser

### Filters not working
- **Issue**: AJAX request failing
- **Solution**: Check browser console for errors
- **Check**: API endpoints are accessible (`/api/filter-*.php`)
- **Check**: Database connection is working

### Edit sidebar not saving changes
- **Issue**: Form validation error
- **Solution**: Check all required fields are filled
- **Check**: Server error in `/api/update-*.php`

### Dashboard counters not updating
- **Issue**: Database query error
- **Solution**: Run migration SQL again
- **Check**: `bookings` table exists with correct schema
- **Check**: Functions in `functions.php` are called correctly

---

**Implementation Complete!** 🎉  
The system is ready for testing and deployment.
