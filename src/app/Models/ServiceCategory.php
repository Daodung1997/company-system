<?php

namespace App\Models;

use App\Constants\Master\Models\ServiceCategory\ServiceCategoryLevelConst;
use App\Constants\Master\Models\ServiceCategory\ServiceCategoryStatusConst;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ServiceCategory Model (2-level hierarchy: main + sub)
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string|null $icon_code
 * @property string $status
 * @property int $sort_order
 * @property int|null $parent_id
 * @property int $level
 */
class ServiceCategory extends BaseMasterModel
{
    use HasFactory;
    use SoftDeletes;

    public const TABLE_NAME = 'm_service_categories';

    const PREFIX_CODE = 'CAT';

    const MAX_LENGTH_CODE = 10;

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon_code',
        'status',
        'sort_order',
        'parent_id',
        'level',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'parent_id' => 'integer',
        'level' => 'integer',
    ];

    // ── Relationships ──

    public function icon(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'icon_code', 'code');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()
            ->where('status', ServiceCategoryStatusConst::ACTIVE);
    }

    // ── Scopes ──

    public function scopeMain($query)
    {
        return $query->where('level', ServiceCategoryLevelConst::MAIN);
    }

    public function scopeSub($query)
    {
        return $query->where('level', ServiceCategoryLevelConst::SUB);
    }
}
