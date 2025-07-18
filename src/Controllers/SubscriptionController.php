<?php
namespace Src\Controllers;

use Stripe\Stripe;
use Stripe\Subscription;

class SubscriptionController
{
    public function pause(string $subscriptionId, int $months = 1): Subscription
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        return Subscription::update($subscriptionId, [
            'pause_collection' => [
                'behavior'  => 'void',
                'resumes_at' => strtotime("+{$months} month"),
            ],
        ]);
    }

    public function resume(string $subscriptionId): Subscription
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        // Para retomar antes do resumes_at, basta limpar pause_collection
        return Subscription::update($subscriptionId, [
            'pause_collection' => '',
        ]);
    }
}