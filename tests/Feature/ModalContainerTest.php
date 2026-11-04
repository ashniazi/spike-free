<?php

use Opcodes\Spike\Http\Livewire\AddPaymentMethodModal;
use Opcodes\Spike\Http\Livewire\CheckoutModal;
use Opcodes\Spike\Http\Livewire\ModalContainer;
use Opcodes\Spike\Http\Livewire\SubscribeModal;

/*
 * On Livewire 4, wire-elements/modal resolves a modal's class through Livewire's finder, which never sees
 * "spike::" names, so every Spike modal failed to open. Spike's container resolves its own names first.
 */
dataset('spike modals', [
    'spike::subscribe' => ['spike::subscribe', SubscribeModal::class],
    'spike::checkout' => ['spike::checkout', CheckoutModal::class],
    'spike::add-payment-method' => ['spike::add-payment-method', AddPaymentMethodModal::class],
]);

test('the modal container resolves each Spike modal by its spike:: name', function (string $name, string $class) {
    $resolve = (new ReflectionMethod(ModalContainer::class, 'resolveComponentClass'))->getClosure(new ModalContainer());

    expect($resolve($name))->toBe($class);
})->with('spike modals');

test('the layout renders Spike\'s modal container', function () {
    expect(file_get_contents(__DIR__.'/../../resources/views/components/layout.blade.php'))
        ->toContain("@livewire('spike::modal')")
        ->not->toContain("@livewire('livewire-ui-modal')");
});
