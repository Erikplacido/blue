# Blue Web Application

This repository contains a PHP-based web application for managing bookings and referral programs. The codebase is split across several directories that separate the administrative tools, referral club features and core application logic.

## Directory layout

- `admin/` &ndash; administrative scripts and interfaces.
- `bluereferralclub/` &ndash; referral program pages, assets and related scripts.
- `booking/` &ndash; user booking flow and supporting pages.
- `src/` &ndash; main application code such as controllers, models and services.

## Setup basics

1. Ensure you have PHP and a supported web server installed (e.g. Apache or Nginx).
2. Create a `.env` file with the required database credentials and configuration options.
3. Configure your web server to serve the PHP files from the repository directories.
4. Install any PHP dependencies into the `vendor/` directory if using Composer.

## Currency configuration

All Stripe transactions use the currency defined by the `DEFAULT_CURRENCY` constant in `src/config.php`. Update that constant to change the application's default currency. If you need to support different currencies for specific flows, introduce additional constants in `config.php` and reference them where required.

This project does not include detailed deployment scripts, but the above steps outline the general setup required to run the application locally or on a server.
