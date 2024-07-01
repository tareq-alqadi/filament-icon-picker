<?php

namespace TareqAlqadi\FilamentIconPicker;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentIconPickerServiceProvider extends PackageServiceProvider
{

    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-icon-picker')
            ->hasViews()
            ->hasConfigFile('icon-picker')
            ->hasTranslations()
        ;
    }

    public function bootingPackage()
    {
        parent::bootingPackage();

        FilamentAsset::register([
            Css::make('filament-icon-picker-stylesheet', __DIR__ . '/../dist/plugin.css'),
        ], package: 'TareqAlqadi/filament-icon-picker');
    }

}
