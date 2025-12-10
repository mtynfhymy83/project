# Database Structure Documentation

This document provides a comprehensive overview of the database schema for the Madras Laravel application.

## Table of Contents

1. [Core Tables](#core-tables)
2. [Authentication & Security](#authentication--security)
3. [Content Management](#content-management)
4. [User Management](#user-management)
5. [E-Learning System](#e-learning-system)
6. [Commerce & Payments](#commerce--payments)
7. [Comments & Interactions](#comments--interactions)
8. [System Tables](#system-tables)

---

## Core Tables

### `users`

Primary user table with UUID-based identification.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID (Primary Key) | Unique identifier for the user |
| `username` | VARCHAR(50) | Unique username (nullable) |
| `email` | VARCHAR(255) | Unique email address (nullable) |
| `mobile` | VARCHAR(20) | Unique mobile number (nullable) |
| `password` | VARCHAR | Hashed password (nullable) |
| `first_name` | VARCHAR(50) | User's first name (nullable) |
| `last_name` | VARCHAR(50) | User's last name (nullable) |
| `display_name` | VARCHAR(100) | Display name (nullable) |
| `active` | BOOLEAN | Account active status (default: true) |
| `approved` | BOOLEAN | Account approval status (default: false) |
| `role` | VARCHAR(50) | User role (default: 'user') |
| `last_seen` | TIMESTAMPTZ | Last seen timestamp (nullable) |
| `meta` | JSONB | Additional metadata stored as JSON (nullable) |
| `created_at` | TIMESTAMPTZ | Creation timestamp |
| `updated_at` | TIMESTAMPTZ | Last update timestamp |

**Indexes:**
- `username`
- `email`
- `mobile`
- `role`

**Relationships:**
- Has many `jwt_tokens`
- Has many `user_devices`
- Has many `ci_user_meta`
- Has many `ci_posts` (as author)

---

## Authentication & Security

### `jwt_tokens`

Stores JWT refresh tokens for user authentication.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `user_id` | UUID (Foreign Key) | Reference to `users.id` |
| `refresh_token` | VARCHAR(255) | Unique refresh token |
| `mini_app_uuid` | VARCHAR | Mini app identifier (nullable) |
| `roles` | JSON | User roles (nullable) |
| `permissions` | JSON | User permissions (nullable) |
| `expires_at` | TIMESTAMP | Token expiration time |
| `last_used_at` | TIMESTAMP | Last usage timestamp (nullable) |
| `device_info` | VARCHAR | Device information (nullable) |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**Indexes:**
- `user_id`, `mini_app_uuid` (composite)
- `refresh_token`

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)

### `user_devices`

Tracks user devices for authentication and security.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `user_id` | UUID (Foreign Key) | Reference to `users.id` |
| `device_name` | VARCHAR(255) | Device name (e.g., "Samsung S23") (nullable) |
| `platform` | VARCHAR(50) | Platform (android, ios, web) (nullable) |
| `ip` | VARCHAR(50) | IP address (nullable) |
| `user_agent` | VARCHAR(500) | User agent string (nullable) |
| `token_version` | INTEGER | Token version for global logout (default: 1) |
| `last_login` | TIMESTAMPTZ | Last login timestamp (nullable) |
| `created_at` | TIMESTAMPTZ | Creation timestamp |
| `updated_at` | TIMESTAMPTZ | Last update timestamp |

**Indexes:**
- `user_id`
- `platform`

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)

---

## Content Management

### `ci_posts`

Main content table for posts, books, articles, videos, lessons, etc.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `type` | VARCHAR(50) | Content type (default: 'post') |
| `title` | VARCHAR(300) | Post title |
| `slug` | VARCHAR(350) | Unique URL slug |
| `excerpt` | TEXT | Short excerpt (nullable) |
| `content` | LONGTEXT | Full content (nullable) |
| `media` | JSONB | Media files (cover, audio, video, pdf, gallery) (nullable) |
| `published` | BOOLEAN | Publication status (default: false) |
| `published_at` | TIMESTAMPTZ | Publication date (nullable) |
| `price` | INTEGER | Price (nullable) |
| `discount` | INTEGER | Discount amount (nullable) |
| `views` | INTEGER | View count (default: 0) |
| `likes` | INTEGER | Like count (default: 0) |
| `author_id` | UUID | Reference to `users.id` (nullable) |
| `created_at` | TIMESTAMPTZ | Creation timestamp |
| `updated_at` | TIMESTAMPTZ | Last update timestamp |

**Indexes:**
- `type`
- `published`
- `author_id`

**Relationships:**
- Belongs to `users` (author)
- Has many `ci_post_meta`
- Belongs to many `ci_categori` (via `post_category`)

### `ci_categori`

Category table for organizing content.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `title` | VARCHAR(200) | Category title |
| `slug` | VARCHAR(200) | Unique URL slug |
| `parent_id` | BIGINT (Foreign Key) | Parent category ID (nullable) |
| `description` | TEXT | Category description (nullable) |
| `created_at` | TIMESTAMPTZ | Creation timestamp |
| `updated_at` | TIMESTAMPTZ | Last update timestamp |

**Indexes:**
- `parent_id`

**Foreign Keys:**
- `parent_id` → `ci_categori.id` (NULL ON DELETE)

**Relationships:**
- Self-referential (parent-child)
- Belongs to many `ci_posts` (via `post_category`)

### `post_category`

Pivot table for many-to-many relationship between posts and categories.

| Column | Type | Description |
|--------|------|-------------|
| `post_id` | BIGINT (Foreign Key) | Reference to `ci_posts.id` |
| `category_id` | BIGINT (Foreign Key) | Reference to `ci_categori.id` |
| `created_at` | TIMESTAMPTZ | Creation timestamp |
| `updated_at` | TIMESTAMPTZ | Last update timestamp |

**Primary Key:**
- Composite: `post_id`, `category_id`

**Foreign Keys:**
- `post_id` → `ci_posts.id` (CASCADE DELETE)
- `category_id` → `ci_categori.id` (CASCADE DELETE)

**Indexes:**
- `category_id`

### `ci_post_meta`

Metadata for posts (key-value pairs).

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `post_id` | BIGINT (Foreign Key) | Reference to `ci_posts.id` |
| `key` | VARCHAR(255) | Metadata key |
| `value` | TEXT | Metadata value (nullable) |
| `created_at` | TIMESTAMPTZ | Creation timestamp |
| `updated_at` | TIMESTAMPTZ | Last update timestamp |

**Indexes:**
- `post_id`
- `key`

**Foreign Keys:**
- `post_id` → `ci_posts.id` (CASCADE DELETE)

### `books`

Books table for managing book content.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `title` | VARCHAR(300) | Book title |
| `slug` | VARCHAR(350) | Unique URL slug (nullable) |
| `description` | TEXT | Book description (nullable) |
| `isbn` | VARCHAR(50) | ISBN number (nullable) |
| `author` | VARCHAR(255) | Author name (nullable) |
| `publisher` | VARCHAR(255) | Publisher name (nullable) |
| `price` | INTEGER | Price (nullable) |
| `discount` | INTEGER | Discount amount (nullable) |
| `active` | BOOLEAN | Active status (default: true) |
| `created_at` | TIMESTAMPTZ | Creation timestamp |
| `updated_at` | TIMESTAMPTZ | Last update timestamp |

**Indexes:**
- `slug`
- `active`

---

## User Management

### `ci_user_meta`

User metadata table (key-value pairs).

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `user_id` | UUID (Foreign Key) | Reference to `users.id` |
| `key` | VARCHAR(255) | Metadata key |
| `value` | TEXT | Metadata value (nullable) |
| `created_at` | TIMESTAMPTZ | Creation timestamp |
| `updated_at` | TIMESTAMPTZ | Last update timestamp |

**Indexes:**
- `user_id`
- `key`

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)

### `ci_user_books`

User-book relationships (user purchases/access).

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `user_id` | UUID (Foreign Key) | Reference to `users.id` |
| `book_id` | BIGINT (Foreign Key) | Reference to `books.id` |
| `created_at` | TIMESTAMPTZ | Creation timestamp |
| `updated_at` | TIMESTAMPTZ | Last update timestamp |

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)
- `book_id` → `books.id` (CASCADE DELETE)

---

## E-Learning System

### `ci_doreh`

Course/period table for e-learning.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `published` | BOOLEAN | Publication status (default: false) |
| `classcount` | UNSIGNED INTEGER | Number of classes (default: 0) |
| `tecatid` | UNSIGNED INTEGER | Category ID |
| `supplierid` | UNSIGNED INTEGER | Supplier ID |
| `placeid` | UNSIGNED INTEGER | Place ID |
| `user_id` | UNSIGNED INTEGER | User/Instructor ID |
| `image` | VARCHAR(255) | Course image |
| `description` | TEXT | Course description |
| `createdate` | UNSIGNED BIGINT | Creation date (Unix timestamp) |
| `upddate` | UNSIGNED BIGINT | Update date (Unix timestamp) |
| `tahsili_year` | UNSIGNED INTEGER | Academic year |
| `offer` | UNSIGNED TINYINT | Offer flag (default: 0) |

**Indexes:**
- `published`
- `tecatid`
- `supplierid`
- `user_id`
- `createdate`
- `published`, `tecatid` (composite)
- `user_id`, `createdate` (composite)

### `ci_dorehclass`

Course classes/sessions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `dorehclassid` | INTEGER | Reference to course |
| Additional fields... | | (Structure from migration) |

### `ci_jalasat`

Sessions/meetings table.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `dorehclassid` | INTEGER | Reference to course class |
| `title` | VARCHAR(255) | Session title |
| `startdate` | INTEGER | Start date (Unix timestamp) |
| `starttime` | VARCHAR(255) | Start time |
| `description` | TEXT | Session description |
| `subjalase` | INTEGER | Sub-session ID |
| `user_id` | INTEGER | User/Instructor ID |
| `published` | BOOLEAN | Publication status |
| `createdate` | INTEGER | Creation date (Unix timestamp) |
| `upddate` | INTEGER | Update date (Unix timestamp) |
| `image` | INTEGER | Image ID |
| `pdf` | INTEGER | PDF ID |
| `audio` | INTEGER | Audio ID |
| `video` | INTEGER | Video ID |

### `ci_user_doreh`

User-course enrollment.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `user_id` | UUID | Reference to `users.id` |
| `doreh_id` | BIGINT | Reference to `ci_doreh.id` |
| Additional fields... | | (Structure from migration) |

---

## Commerce & Payments

### `ci_factors`

Invoice/factor table for purchases.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `user_id` | UNSIGNED INTEGER | User ID (nullable) |
| `status` | UNSIGNED TINYINT | Payment status (nullable) |
| `state` | VARCHAR(1000) | State information (nullable) |
| `cprice` | UNSIGNED INTEGER | Calculated price (nullable) |
| `price` | UNSIGNED INTEGER | Original price (nullable) |
| `discount` | UNSIGNED TINYINT | Discount percentage (default: 0) |
| `discount_id` | UNSIGNED INTEGER | Discount ID (nullable) |
| `paid` | UNSIGNED INTEGER | Paid amount (default: 0) |
| `ref_id` | VARCHAR(255) | Reference ID (nullable) |
| `cdate` | UNSIGNED INTEGER | Creation date (Unix timestamp) (nullable) |
| `pdate` | UNSIGNED INTEGER | Payment date (Unix timestamp) (nullable) |
| `owner` | UNSIGNED INTEGER | Owner ID |
| `section` | VARCHAR(255) | Section/type |
| `data_id` | VARCHAR(255) | Related data ID |

**Indexes:**
- `user_id`
- `status`
- `paid`
- `cdate`
- `pdate`
- `section`
- `ref_id`
- `user_id`, `status` (composite)
- `status`, `paid` (composite)
- `user_id`, `cdate` (composite)

### `ci_factor_detail`

Invoice line items/details.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `factor_id` | BIGINT | Reference to `ci_factors.id` |
| Additional fields... | | (Structure from migration) |

### `ci_discounts`

Discount codes table.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| Additional fields... | | (Structure from migration) |

### `ci_discount_used`

Tracks used discount codes.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `user_id` | UUID | Reference to `users.id` |
| `discount_id` | BIGINT | Reference to `ci_discounts.id` |
| Additional fields... | | (Structure from migration) |

---

## Comments & Interactions

### `ci_comments`

Comments table for posts and other content.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `parent` | UNSIGNED INTEGER | Parent comment ID (default: 0) |
| `table` | VARCHAR(20) | Target table name |
| `row_id` | UNSIGNED INTEGER | Target row ID |
| `submitted` | BOOLEAN | Approval status (default: false) |
| `user_id` | UNSIGNED INTEGER | User ID |
| `name` | VARCHAR(255) | Commenter name |
| `email` | VARCHAR(255) | Commenter email (nullable) |
| `text` | TEXT | Comment text |
| `ip` | VARCHAR(50) | IP address |
| `date` | TIMESTAMP | Comment date (default: current) |

**Indexes:**
- `parent`
- `user_id`
- `row_id`
- `submitted`
- `date`
- `table`, `row_id` (composite)
- `table`, `row_id`, `submitted` (composite)
- `user_id`, `date` (composite)

### `ci_comment_rate`

Comment ratings.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| Additional fields... | | (Structure from migration) |

### `ci_favorites`

User favorites/bookmarks.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| `user_id` | UUID | Reference to `users.id` |
| Additional fields... | | (Structure from migration) |

---

## System Tables

### `ci_settings`

Application settings.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| Additional fields... | | (Structure from migration) |

### `ci_logs`

Application logs.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| Additional fields... | | (Structure from migration) |

### `ci_captcha`

CAPTCHA records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (Primary Key) | Auto-incrementing ID |
| Additional fields... | | (Structure from migration) |

---

## Additional Tables

The database also includes many other tables for specific features:

- **Geographic Data:** `ci_city`, `ci_province`, `ci_country`, `ci_geo_section`, `ci_geo_type`
- **Content Types:** `ci_ostad` (instructors), `ci_writer` (writers), `ci_publisher` (publishers)
- **Learning Tools:** `ci_leitner`, `ci_leitbox`, `ci_dictionary`, `ci_userdictionary`
- **Membership:** `ci_membership`, `ci_user_membership`, `ci_user_level`
- **Online Classes:** `ci_classonline`, `ci_classonline_data`, `ci_classroom`, `ci_classroom_data`
- **Tests & Exams:** `ci_tests`, `ci_questions`, `ci_azmoon_result`, `ci_tashrihi`
- **Other:** `ci_notes`, `ci_highlights`, `ci_news_redirects`, `ci_short_links`, `ci_advertise`, `ci_admin_inbox`

---

## Database Relationships Summary

### User Relationships
- `users` → `jwt_tokens` (One-to-Many)
- `users` → `user_devices` (One-to-Many)
- `users` → `ci_user_meta` (One-to-Many)
- `users` → `ci_posts` (One-to-Many, as author)
- `users` → `ci_user_books` (One-to-Many)
- `users` → `ci_factors` (One-to-Many)
- `users` → `ci_favorites` (One-to-Many)

### Content Relationships
- `ci_posts` → `ci_post_meta` (One-to-Many)
- `ci_posts` ↔ `ci_categori` (Many-to-Many via `post_category`)
- `ci_posts` → `ci_comments` (One-to-Many, via `table` and `row_id`)
- `books` → `ci_user_books` (One-to-Many)

### Category Relationships
- `ci_categori` → `ci_categori` (Self-referential, parent-child)

### E-Learning Relationships
- `ci_doreh` → `ci_dorehclass` (One-to-Many)
- `ci_dorehclass` → `ci_jalasat` (One-to-Many)
- `users` → `ci_user_doreh` (One-to-Many)

### Commerce Relationships
- `ci_factors` → `ci_factor_detail` (One-to-Many)
- `ci_discounts` → `ci_discount_used` (One-to-Many)

---

## Notes

- The database uses **PostgreSQL** (indicated by `jsonb`, `timestamptz` types)
- Primary keys use either **UUID** (for `users`) or **BIGINT auto-increment** (for most other tables)
- Many tables use Unix timestamps (INTEGER) instead of TIMESTAMP for date fields
- Foreign key constraints use CASCADE DELETE for most relationships
- JSON/JSONB fields are used for flexible metadata storage
- Indexes are created on frequently queried columns and composite keys

---

## Migration Files

All database structure is defined in Laravel migration files located in:
```
database/migrations/
```

Key migration files:
- `0001_01_01_000000_create_users_table.php` - Core user table
- `2025_11_26_091125_create_jwt_tokens_table.php` - JWT authentication
- `2025_09_30_130002_create_ci_posts_table.php` - Content posts
- `2025_10_01_054240_create_ci_categori.php` - Categories
- `2025_11_29_091223_create_user_devices_table.php` - User devices
- `2025_11_29_092516_create_post__categories_table.php` - Post-Category pivot
- And 80+ additional migration files for other features

---

*Last Updated: Generated from migration files analysis*
