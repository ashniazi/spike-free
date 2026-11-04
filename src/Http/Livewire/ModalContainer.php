<?php

namespace Opcodes\Spike\Http\Livewire;

use LivewireUI\Modal\Modal;
use Opcodes\Spike\SpikeServiceProvider;

/**
 * Spike's modal container: wire-elements/modal's, resolving Spike's own modal names first.
 *
 * On Livewire 4, Modal::resolveComponentClass() asks Livewire's finder directly, and the finder only resolves a
 * "spike::" name through registered class namespaces, never through Livewire::component() or the missing-component
 * resolvers. Every Spike modal (subscribe, checkout, add payment method) then failed with a null class. Spike's own
 * names come from SpikeServiceProvider::LIVEWIRE_COMPONENTS; anything else goes to the package as before.
 */
class ModalContainer extends Modal
{
    protected function resolveComponentClass(string $component): string
    {
        return SpikeServiceProvider::LIVEWIRE_COMPONENTS[$component] ?? parent::resolveComponentClass($component);
    }
}
