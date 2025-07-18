<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/vendor/stripe/stripe-php/init.php';

use Stripe\Stripe;
use Src\Controllers\WebhookController;

Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

$controller = new WebhookController();
$controller->handle();

