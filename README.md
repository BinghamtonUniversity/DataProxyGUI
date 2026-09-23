# BITS Proxy (Binghamton ITS Proxy)

BITS Proxy is a modern web application for integrating with GrapheneAPIGateway and BUDjangoProxy. It is built using Laravel 12, Inertia.js, and Vue.js.

Repository folder name remains DataProxyGUI.

## Prerequisites

- PHP >= 8.2
- Composer
- Node.js >= 16.x
- NPM

## Quick Start

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

Update your `.env` file with your database credentials. Set `APP_NAME="BITS Proxy"` (already the default in `.env.example`).

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Start Development Server

```bash
composer run dev
```

This single command will start:
- Laravel development server 
- Queue worker
- Vite dev server with HMR

## Available Commands

### Production

```bash
npm run build        # Build assets for production
```

## Tech Stack

- **Backend:** Laravel 12
- **Frontend:** Vue.js with Inertia.js
