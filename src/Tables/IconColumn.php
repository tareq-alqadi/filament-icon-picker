<?php

namespace TareqAlqadi\FilamentIconPicker\Tables;

use Closure;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Concerns\HasColor;
use Filament\Tables\Columns\IconColumn\IconColumnSize;

class IconColumn extends Column
{
    use HasColor;

    protected string $view = 'filament-icon-picker::tables.icon-column';

    protected IconColumnSize | string | Closure | null $size = null;

    public function size(IconColumnSize | string | Closure | null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(mixed $state): IconColumnSize | string | null
    {
        return $this->evaluate($this->size, [
            'state' => $state,
        ]);
    }

}
