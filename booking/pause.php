<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (empty($_POST['booking_id'])) {
    header('Location: profile.php?error=no_booking_id');
    exit;
}
$bookingId = intval($_POST['booking_id']);

require __DIR__ . '/../src/config.php';
// Composer autoloader for Stripe PHP SDK
require __DIR__ . '/vendor/autoload.php';

use Src\Models\Booking;
use Stripe\Stripe;
use Stripe\Subscription;

$bookingModel = new Booking();
$booking = $bookingModel->getById($bookingId);
if (empty($booking['stripe_subscription_id'])) {
    header('Location: profile.php?error=no_subscription');
    exit;
}
$subId = (string) $booking['stripe_subscription_id'];

Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

try {
    Subscription::update($subId, [
        'pause_collection' => [
            'behavior'  => 'void',
            'resumes_at' => strtotime('+1 month'),
        ],
    ]);

    // Aqui você pode disparar e-mail ou gravar um log
    header('Location: profile.php?booking_id=' . $bookingId . '&paused=1');
} catch (\Exception $e) {
    header('Location: profile.php?booking_id=' . $bookingId . '&error=' . urlencode($e->getMessage()));
}
exit;