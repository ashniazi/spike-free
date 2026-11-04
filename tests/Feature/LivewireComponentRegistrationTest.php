<?php

use Livewire\Livewire;
use Opcodes\Spike\Http\Livewire\AddPaymentMethodModal;
use Opcodes\Spike\Http\Livewire\CheckoutModal;
use Opcodes\Spike\Http\Livewire\CreditTransactions;
use Opcodes\Spike\Http\Livewire\Invoices;
use Opcodes\Spike\Http\Livewire\PaddlePaymentMethod;
use Opcodes\Spike\Http\Livewire\PurchaseCredits;
use Opcodes\Spike\Http\Livewire\StripePaymentMethods;
use Opcodes\Spike\Http\Livewire\SubscribeModal;
use Opcodes\Spike\Http\Livewire\Subscriptions;
use Opcodes\Spike\Http\Livewire\ValidateCart;
use Opcodes\Spike\Livewire\PaddleCheckoutButton;
use Opcodes\Spike\Livewire\UpdatePaymentMethodPaddle;

/*
 * Livewire 4 reads a "spike::" prefix as a component namespace and only looks for it among registered class
 * namespaces, never among names registered with Livewire::component(). Every Spike component must still be
 * reachable by its "spike::" name, on Livewire 3 and 4, because the views and the modal open them by name.
 */
dataset('spike components', [
    'spike::credit-transactions' => ['spike::credit-transactions', CreditTransactions::class],
    'spike::subscriptions' => ['spike::subscriptions', Subscriptions::class],
    'spike::purchase-credits' => ['spike::purchase-credits', PurchaseCredits::class],
    'spike::validate-cart' => ['spike::validate-cart', ValidateCart::class],
    'spike::stripe-payment-methods' => ['spike::stripe-payment-methods', StripePaymentMethods::class],
    'spike::paddle-payment-method' => ['spike::paddle-payment-method', PaddlePaymentMethod::class],
    'spike::invoices' => ['spike::invoices', Invoices::class],
    'spike::checkout' => ['spike::checkout', CheckoutModal::class],
    'spike::subscribe' => ['spike::subscribe', SubscribeModal::class],
    'spike::add-payment-method' => ['spike::add-payment-method', AddPaymentMethodModal::class],
    'spike::update-payment-method-paddle' => ['spike::update-payment-method-paddle', UpdatePaymentMethodPaddle::class],
    'spike::product-checkout-button-paddle' => ['spike::product-checkout-button-paddle', PaddleCheckoutButton::class],
]);

test('each Spike component resolves from its spike:: name', function (string $name, string $class) {
    $component = Livewire::new($name);

    expect($component)->toBeInstanceOf($class)
        ->and($component->getName())->toBe($name);
})->with('spike components');
