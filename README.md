# Spike - easy user billing for Laravel projects

![Spike example image](https://spike.opcodes.io/images/purchase-credits-emerald.webp)

Spike is a package for [Laravel](https://laravel.com/) allowing you to easily set up billing for your users. You can start charging subscriptions and one-off purchases easily with just a few lines of configuration. The user interface is already built for you, as well as payment and invoice handling.

- **[Official website](https://spike.opcodes.io)**
- **[Documentation](https://spike.opcodes.io/docs)**

## Maintained fork

This is the maintained fork of [`opcodesio/spike-free`](https://github.com/opcodesio/spike-free), used by Branding Now. Its `main` branch is the working version; upstream changes are merged in from the `upstream` remote when needed. It installs under the same package name, `opcodesio/spike`, through a Composer `vcs` repository.

What differs from upstream:

- **Livewire 4.** `livewire/livewire` is `^3.8.1|^4.0` and `wire-elements/modal` is `^2.0|^3.0` (modal 2.x does not boot on Livewire 4; 4.x renames its `component` argument to `modalComponent`).
- **Positional `openModal` dispatches.** Livewire 4 treats the named `dispatch()` parameters `component`, `ref`, `el`, `self` and `to` as the event's target, so `dispatch('openModal', component: ...)` never reaches the modal. Pass the modal name and arguments positionally: `dispatch('openModal', 'spike::subscribe', ['price_id' => $id])`. Fix by [idealerror](https://github.com/idealerror/spike-free) (opcodesio/spike-free#11).
- **Spike components resolve on Livewire 4.** Livewire 4 reads the `spike::` prefix as a component namespace and only looks for it among registered class namespaces, never among names registered with `Livewire::component()`, so every Spike component was "not found". `SpikeServiceProvider::LIVEWIRE_COMPONENTS` maps each name once; it is registered as before and also answered through `Livewire::resolveMissingComponent()`.
- **Billable key type.** Every migration column that points at a billable (the carts, credit transactions and provide history morphs, `stripe_subscriptions` and the Paddle tables) follows `Opcodes\Spike\BillableKeyType`: set `spike.billable_key_type` to `int`, `uuid` or `ulid`, or leave it null to follow the first billable model (`HasUuids` is uuid, `HasUlids` is ulid, otherwise an unsigned big integer, as upstream assumed).
- **Spike's own modal container.** On Livewire 4, wire-elements/modal resolves a modal's class through Livewire's finder, which never sees `spike::` names, so every Spike modal failed to open. The layout renders `spike::modal` (`ModalContainer`, a subclass of the package's `Modal`) which resolves Spike's names from `LIVEWIRE_COMPONENTS` first.
- **No `arukompas/livewire-charts`.** It pinned Livewire 3 and only drew the usage page's one static chart. `UsageChart` now builds the same ApexCharts options itself (a port of that package's defaults and its `deepmerge` 2.2.1), and the page loads ApexCharts directly from `resources/dist`.
- **ApexCharts 3.41.0, bundled locally** as `resources/dist/apexcharts.min.js` with its MIT notice in `resources/dist/apexcharts.LICENSE.txt`. Never upgrade it past 4.7.0: ApexCharts 5.0 and later are not MIT licensed. Moving to 4.7.0 needs a visual check of the usage chart first.

Requirements for this fork: Laravel 10+, PHP 8.1+, Livewire 3.8+ or 4.

## Installing

The package can be installed into an existing Laravel project. Here are the requirements before installing Spike:

- Laravel 10+
- PHP 8.1+
- Livewire 3+

To install Spike into your project, simply use the Composer command:

```shell
composer require opcodesio/spike
```

After that, just follow the [Getting started guide](https://spike.opcodes.io/docs/v3/getting-started) in our official documentation.

> **Note:** the above is slightly different from the previous paid version which was installed using `opcodes/spike` (notice the missing "io").

## Testing

```bash
composer test
```

## Security Vulnerabilities

Please report any security vulnerabilities directly to [arunas@arunas.dev](mailto:arunas@arunas.dev).

## Credits

- [Arunas Skirius](https://github.com/arukompas)
- [All Contributors](../../contributors)
