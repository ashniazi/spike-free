<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Opcodes\Spike\Facades\PaymentGateway;
use Opcodes\Spike\Facades\Spike;
use Opcodes\Spike\Http\Livewire\PurchaseCredits;
use Opcodes\Spike\Http\Livewire\Subscriptions;

uses(RefreshDatabase::class);

/*
 * Livewire 4 treats the named dispatch() parameters component, ref, el, self and to as the event's target, so
 * dispatch('openModal', component: ...) never reached the modal. These pin the positional form, which the
 * modal's openModal($component, $arguments) listener receives in order on Livewire 3 and 4.
 */

beforeEach(function () {
    $this->user = createBillable();
    Spike::resolve(fn () => $this->user);
    PaymentGateway::fake();
    config(['spike.stripe.checkout.enabled' => false]);
});

test('subscribing opens the subscribe modal with the chosen price', function () {
    setupMonthlySubscriptionPlan(30, 100, 'price_standard_monthly');

    Livewire::test(Subscriptions::class)
        ->call('subscribeTo', 'price_standard_monthly')
        ->assertDispatched('openModal', 'spike::subscribe', ['price_id' => 'price_standard_monthly']);
});

test('checking out credits opens the checkout modal', function () {
    Livewire::test(PurchaseCredits::class)
        ->call('checkout')
        ->assertDispatched('openModal', 'spike::checkout');
});
