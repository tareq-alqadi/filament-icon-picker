<?php

namespace TareqAlqadi\FilamentIconPicker\Forms;


use BladeUI\Icons\Factory as IconFactory;
use Closure;
use Filament\Forms\Components\Select;
use TareqAlqadi\FilamentIconPicker\Forms\Concerns\CanBeCacheable;
use TareqAlqadi\FilamentIconPicker\Layout;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\Finder\SplFileInfo;

class IconPicker extends Select
{
    use CanBeCacheable;

    protected string $view = 'filament-icon-picker::forms.icon-picker';

    protected array|Closure|null $sets = null;
    protected array|Closure|null $allowedIcons = null;
    protected array|Closure|null $disallowedIcons = null;

    protected bool|Closure $isHtmlAllowed = true;
    protected bool|Closure $isSearchable = true;

    protected Closure|string|Htmlable|null $itemTemplate = null;

    protected string $layout = Layout::FLOATING;

    protected bool|Closure $show;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sets(config('icon-picker.sets', null));
        $this->columns(6);
        // $this->columns(config('icon-picker.columns', 1));
        $this->layout(config('icon-picker.layout', Layout::FLOATING));
        $this->preload();

        $this->getSearchResultsUsing = function (IconPicker $component, string $search, Collection $icons) {

            $iconsHash = md5(serialize($icons));
            $key = "icon-picker.results.$iconsHash.$search";

            return $this->tryCache($key, function () use ($component, $search, $icons) {
                return collect($icons)
                    ->filter(fn (string $icon) => str_contains($icon, $search))
                    ->take(50)
                    ->mapWithKeys(function (string $icon) use ($component) {
                        return [$icon => $component->getItemTemplate([
                            'icon' => $icon,
                            // 'iconName' => $iconName,
                        ])];
                    })
                    ->toArray();
            });
        };

        $this->options(function (IconPicker $component, Collection $icons) {
            return collect($icons)
                ->take(50)
                ->mapWithKeys(function (string $icon) use ($component) {
                    return [$icon => $component->getItemTemplate([
                        'icon' => $icon,
                        // 'iconName' => $iconName,
                    ])];
                })
                ->toArray();
        });

        $this->getOptionLabelUsing = function (IconPicker $component, $value) {
            if ($value) {

                return $component->getItemTemplate([
                    // 'icon' => svg($value),
                    'icon' => $value
                ]);
            }
        };

        $this
            ->itemTemplate(function (IconPicker $component, string $icon) {
                $key = "icon-picker.template.$icon";
                return $this->tryCache($key, function () use ($icon) {

                    // return <<<Html
                    //             <div class="flex justify-start items-center gap-2">
                    //                 <div class="w-10 h-10 p-2 border border-gary-200 dark:border-gray-700 rounded-lg flex justify-center items-center">
                    //                     {$icon}
                    //                 </div>
                    //                 <div class="flex flex-col gap-1">
                    //                     <h1>{$iconName}</h1>
                    //                     <p class="text-gray-600 dark:text-gray-400">{$iconName}</p>
                    //                 </div>
                    //             </div>
                    //         Html;

                    // return <<<Html
                    //     <div class="flex flex-col items-center justify-center">
                    //         <div class="relative w-full !h-16 flex flex-col items-center justify-center py-2">
                    //             <div class="relative w-12 h-12 grow-1 shrink-0 gap-1">
                    //                 $icon
                    //                 <div class="w-full h-full absolute z-10"></div>
                    //             </div>
                    //             <small class="w-full text-center grow-0 shrink-0 h-4 truncate">$iconName</small>
                    //         </div>
                    //     </div>
                    // Html;

                    return view('filament-icon-picker::item', [
                        'icon' => $icon,
                    ])->render();
                });
            })
            ->placeholder(function () {
                return $this->view('filament-icon-picker::placeholder')->render();
            });
    }

    /**
     * @param array|string|Closure|null $sets
     * @return $this
     */
    public function sets(array|Closure|string|null $sets = null): static
    {
        $this->sets = $sets ? (is_string($sets) ? [$sets] : $sets) : null;

        return $this;
    }

    public function getSets(): ?array
    {
        return $this->evaluate($this->sets);
    }

    public function allowedIcons(array|Closure|string $allowedIcons): static
    {
        $this->allowedIcons = $allowedIcons;

        return $this;
    }

    public function getAllowedIcons(): ?array
    {
        return $this->evaluate($this->allowedIcons, [
            'sets' => $this->getSets(),
        ]);
    }

    public function disallowedIcons(array|Closure|string $disallowedIcons): static
    {
        $this->disallowedIcons = $disallowedIcons;

        return $this;
    }

    public function getDisallowedIcons(): ?array
    {
        return $this->evaluate($this->disallowedIcons, [
            'sets' => $this->getSets(),
        ]);
    }

    public function layout(string|Closure $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayout(): string
    {
        return $this->evaluate($this->layout);
    }

    public function itemTemplate(Htmlable|Closure|View $template): static
    {
        $this->itemTemplate = $template;

        return $this;
    }

    public function getItemTemplate(array $options = []): string
    {
        return $this->evaluate($this->itemTemplate, $options);
    }

    public function getSearchResults(string $search): array
    {
        if (!$this->getSearchResultsUsing) {
            return [];
        }

        $results = $this->evaluate($this->getSearchResultsUsing, [
            'query' => $search,
            'search' => $search,
            'searchQuery' => $search,
            'icons' => $this->loadIcons(),
        ]);

        if ($results instanceof Arrayable) {
            $results = $results->toArray();
        }

        return $results;
    }

    public function getOptions(): array
    {
        return $this->evaluate($this->options, [
            'icons' => $this->loadIcons(),
        ]);
    }

    public function relationship(string|Closure|null $name = null, string|Closure|null $titleAttribute = null, ?Closure $modifyQueryUsing = null, bool $ignoreRecord = false): static
    {
        throw new \BadMethodCallException('Method not allowed.');
    }

    // public function options(Arrayable|Closure|array|string|null $options): static
    // {
    //     throw new \BadMethodCallException('Method not allowed.');
    // }

    public function allowHtml(bool|Closure $condition = true): static
    {
        throw new \BadMethodCallException('Method not allowed.');
    }

    public function searchable(bool|array|Closure $condition = true): static
    {
        throw new \BadMethodCallException('Method not allowed.');
    }

    public function getSearchResultsUsing(?Closure $callback): static
    {
        throw new \BadMethodCallException('Method not allowed.');
    }

    public function getOptionLabelFromRecordUsing(?Closure $callback): static
    {
        throw new \BadMethodCallException('Method not allowed.');
    }

    public function createOptionUsing(?Closure $callback): static
    {
        throw new \BadMethodCallException('Method not allowed.');
    }

    public function createOptionAction(?Closure $callback): static
    {
        throw new \BadMethodCallException('Method not allowed.');
    }

    public function createOptionForm(array|Closure|null $schema): static
    {
        throw new \BadMethodCallException('Method not allowed.');
    }

    public function schema(array|Closure $components): static
    {
        throw new \BadMethodCallException('Method not allowed.');
    }

    public function multiple(bool|Closure $condition = true): static
    {
        throw new \BadMethodCallException('Method not allowed.');
    }

    private function loadIcons(): Collection
    {

        $iconsHash = md5(serialize($this->getSets()));
        $key = "icon-picker.fields.{$iconsHash}.{$this->getStatePath()}";

        [$sets, $allowedIcons, $disallowedIcons] = $this->tryCache(
            $key,
            function () {
                $allowedIcons = $this->getAllowedIcons();
                $disallowedIcons = $this->getDisallowedIcons();

                $iconsFactory = App::make(IconFactory::class);
                $allowedSets = $this->getSets();
                $sets = collect($iconsFactory->all());

                if ($allowedSets) {
                    $sets = $sets->filter(fn ($value, $key) => in_array($key, $allowedSets));
                }

                return [$sets, $allowedIcons, $disallowedIcons];
            }
        );

        $iconsKey = "icon-picker.icons.{$iconsHash}.{$this->getStatePath()}";

        $icons = $this->tryCache(
            $iconsKey,
            function () use ($sets, $allowedIcons, $disallowedIcons) {
                $icons = [];

                foreach ($sets as $set) {
                    $prefix = $set['prefix'];
                    foreach ($set['paths'] as $path) {
                        // To include icons from sub-folders, we use File::allFiles instead of File::files
                        // See https://github.com/blade-ui-kit/blade-icons/blob/ce60487deeb7bcbccd5e69188dc91b4c29622aff/src/IconsManifest.php#L40
                        foreach (File::allFiles($path) as $file) {
                            // Simply ignore files that aren't SVGs
                            if ($file->getExtension() !== 'svg') {
                                continue;
                            }

                            $iconName = $this->getIconName($file, parentPath: $path, prefix: $prefix);

                            if ($allowedIcons && !in_array($iconName, $allowedIcons)) {
                                continue;
                            }
                            if ($disallowedIcons && in_array($iconName, $disallowedIcons)) {
                                continue;
                            }

                            // $icons[$iconName] = File::get($file->getRealPath());
                            $icons[] = $iconName;
                        }
                    }
                }


                return $icons;
            }
        );

        return collect($icons);
    }

    /**
     * @see https://github.com/blade-ui-kit/blade-icons and its IconsManifest.php
     * @see https://github.com/blade-ui-kit/blade-icons/blob/ce60487deeb7bcbccd5e69188dc91b4c29622aff/src/IconsManifest.php#L78
     */
    private function getIconName(SplFileInfo $file, string $parentPath, string $prefix): string
    {
        // BladeIcons uses a simple (and view-compliant) naming convention for icon names
        // `xtra-icon` is the `icon.svg` from the `xtra` icon set
        // `xtra-dir.icon` is the `icon.svg` from the `dir/` folder from the `xtra` icon set
        // `xtra-sub.dir.icon` is the `icon.svg` from the `sub/dir/` folder from the `xtra` icon set
        //
        // As such, we:
        // - get the string after the parent directory's path
        // - replace every directory separator by a dot
        // - add the prefix at the beginning, followed by a dash

        $iconName = str($file->getPathname())
            ->after($parentPath . DIRECTORY_SEPARATOR)
            ->replace(DIRECTORY_SEPARATOR, '.')
            ->basename('.svg')
            ->toString();

        return "$prefix-$iconName";
    }
}
