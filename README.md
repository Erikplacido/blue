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

This project does not include detailed deployment scripts, but the above steps outline the general setup required to run the application locally or on a server.
