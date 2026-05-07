<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Checkout;
use RuntimeException;

class CheckoutService
{
    public function proCheckout(): Checkout
    {
        $user = Auth::user();

        if (! $user) {
            throw new RuntimeException('User is required to start checkout.');
        }

        if (! config('services.stripe.billing_enabled')) {
            throw new RuntimeException('Billing is not enabled.');
        }

        $priceId = config('services.stripe.pro_price_id');

        if (! $priceId) {
            throw new RuntimeException('Stripe Pro price ID is not configured.');
        }

        if (! config('services.stripe.key') || ! config('services.stripe.secret')) {
            throw new RuntimeException('Stripe keys are not configured.');
        }

        return $user
            ->newSubscription('default', $priceId)
            ->checkout([
                /* 'success_url' => route('billing.success'),
                'cancel_url' => route('billing.cancel'), */
            ]);
    }
}