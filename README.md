# SMARTDTR — Smart Daily Time Record System

Production-ready DepEd Senior High School attendance and DTR system with Raspberry Pi face recognition integration.

## Features

- Admin dashboard (Blade + Alpine.js + Tailwind CSS)
- Personnel enrollment with face photo upload (3–10 images)
- Raspberry Pi attendance client (face + blink liveness)
- Real-time daily status: Present, Late, Absent, On Leave, Official Travel, Off-duty
- Weekly schedules and per-date overrides
- Leave/travel management
- DepEd CS Form No. 48 DTR export (Excel template fill + PDF)
- Date-range attendance reports (Excel + PDF)
- REST API for Pi device and admin integrations

## Tech Stack

- Laravel 12 (PHP 8.2+)
- MySQL 8
- PhpSpreadsheet (DTR Excel)
- barryvdh/laravel-dompdf (PDF)
- Session-based admin auth
- Database queue driver

## Quick Start (XAMPP)

### 1. Prerequisites

- XAMPP with PHP 8.2+, MySQL 8, Apache
- Node.js 18+ (for asset build)
- Composer

### 2. Database

Create MySQL database:

```sql
CREATE DATABASE smartdtr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Install

```bash
cd "C:\xampp\htdocs\INHS SMART-DTR"
copy .env.example .env
php composer.phar install
php artisan key:generate
php artisan migrate --seed
php artisan dtr:generate-template
npm install
npm run build
php artisan storage:link
```

### 4. Configure `.env`

```env
APP_NAME=SMARTDTR
APP_URL=http://localhost/INHS%20SMART-DTR/public
APP_TIMEZONE=Asia/Manila
DB_DATABASE=smartdtr
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Apache Virtual Host (optional)

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/INHS SMART-DTR/public"
    ServerName smartdtr.local
    <Directory "C:/xampp/htdocs/INHS SMART-DTR/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Or access via: `http://localhost/INHS%20SMART-DTR/public`

### 6. Login

- URL: `/login`
- Username: `admin`
- Password: `Admin@123`

## Deployment Checklist

- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Configure MySQL credentials in `.env`
- [ ] Run `php artisan migrate --seed`
- [ ] Run `php artisan dtr:generate-template`
- [ ] Run `npm run build`
- [ ] Set folder permissions: `storage/`, `bootstrap/cache/` writable
- [ ] Configure HTTPS (recommended for production)
- [ ] Upload official DepEd DTR template to `storage/app/templates/DTR-2025-2026.xlsx` (optional — starter template included)
- [ ] Sync `storage/app/known_faces/` to Raspberry Pi
- [ ] Configure Pi client `.env` with server API URL

## Face Enrollment Workflow

1. Admin → Personnel → Create personnel record
2. Upload 3–10 JPEG/PNG face photos per employee
3. Photos saved to `storage/app/known_faces/{personnel_id}/`
4. On Raspberry Pi: set `REBUILD_ENCODINGS=true` and restart client
5. Pi loads encodings and begins scanning

## DTR Export Workflow

1. Admin → DTR Export
2. Select employee and month
3. Click **Generate DTR Excel** or **Generate DTR PDF**
4. System loads official template and fills AM/PM times from `attendance_logs`

Artisan command to regenerate starter template:

```bash
php artisan dtr:generate-template
```

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/attendance/scan` | None (rate-limited) | Pi scan ingestion |
| POST | `/api/auth/login` | None | Admin login |
| POST | `/api/auth/logout` | Admin | Logout |
| GET | `/api/auth/me` | Admin | Session check |
| GET | `/api/admin/personnel` | Admin | List personnel |
| POST | `/api/admin/personnel` | Admin | Create personnel |
| POST | `/api/admin/personnel/{id}/faces` | Admin | Upload faces |
| POST | `/api/admin/leave` | Admin | Create leave/travel |
| POST | `/api/admin/schedule` | Admin | Weekly schedule or override |
| GET | `/api/admin/attendance?date=` | Admin | Attendance for date |
| GET | `/api/admin/status?date=` | Admin | Status for all personnel |
| GET | `/api/admin/reports/dtr/excel` | Admin | DTR Excel download |
| GET | `/api/admin/reports/dtr/pdf` | Admin | DTR PDF download |

See `docs/openapi.yaml` for full API specification.

## Raspberry Pi Client

See [raspberry-pi/README.md](raspberry-pi/README.md)

```bash
cd raspberry-pi
pip install -r requirements.txt
python attendance_client.py
```

## Testing

```bash
php artisan test
```

Tests cover:
- `computeDailyStatus()` all priority cases
- DTR scan mapping (2/3/4-scan days)
- Attendance upsert deduplication
- Blink rejection

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `FACE_CONFIDENCE_MIN` | 0.5 | Minimum face match confidence |
| `LATE_THRESHOLD_MINUTES` | 10 | Grace period before Late |
| `MAX_REPORT_RANGE_DAYS` | 31 | Max report date range |
| `DTR_TEMPLATE_PATH` | templates/DTR-2025-2026.xlsx | DTR template path |
| `KNOWN_FACES_DIR` | known_faces | Face photos directory |

## License

MIT — For DepEd SHS internal use.

