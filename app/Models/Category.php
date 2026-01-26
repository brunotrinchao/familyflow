<?php

namespace App\Models;

use App\Enums\CategoryColorPaletteEnum;
use App\Enums\CategoryIconEnum;
use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Traits\HasFamily;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
    use HasFactory, SoftDeletes, HasFamily;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'icon',
        'type',
        'color',
        'family_id',
    ];

    protected function casts(): array
    {
        return [
            'icon'  => CategoryIconEnum::class,
            'type'  => CategoryTypeEnum::class,
            'color' => CategoryColorPaletteEnum::class
        ];
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
