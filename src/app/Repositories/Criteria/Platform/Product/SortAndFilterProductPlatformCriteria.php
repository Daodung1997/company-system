<?php

namespace App\Repositories\Criteria\Platform\Product;

use App\Constants\Commons\CommonImageTypeConst;
use App\Constants\Master\Models\Image\ImageColumn;
use App\Constants\Master\Models\Product\ProductColumn;
use App\Constants\Master\Models\Product\ProductImage\ProductImageColumn;
use App\Constants\Master\Models\Product\ProductImage\ProductImageRelation;
use App\Constants\Master\Models\Product\ProductRelation;
use App\Constants\Master\Models\Tag\TagColumn;
use App\Models\BaseMasterModel;
use App\Models\Product;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;

class SortAndFilterProductPlatformCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository): BaseMasterModel|Builder|null
    {
        $select = ['*'];
        $relationship = [
            ProductRelation::CATEGORY,
            ProductRelation::TAGS,
            ProductRelation::VARIANTS,
            ProductRelation::PRIMARY_IMAGE => function ($q) {
                $q->where(ProductImageColumn::IMAGE_TYPE, CommonImageTypeConst::PRIMARY)
                    ->with([
                        ProductImageRelation::IMAGE => function ($q) {
                            $q->select([
                                ImageColumn::CODE,
                                ImageColumn::PATH_IMAGE_RESIZE,
                                ImageColumn::PATH_IMAGE_ORIGINAL,
                                ImageColumn::DISK,
                            ]);
                        },
                    ]);
            },
        ];

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function sort($builder): BaseMasterModel|Builder|null
    {
        return $this->sortByConditions($builder, $this->sorts, [
            ProductColumn::CODE => Product::TABLE_NAME.'.'.ProductColumn::CODE,
            ProductColumn::NAME_VN => Product::TABLE_NAME.'.'.ProductColumn::NAME_VN,
            ProductColumn::NAME_EN => Product::TABLE_NAME.'.'.ProductColumn::NAME_EN,
            ProductColumn::NAME_JP => Product::TABLE_NAME.'.'.ProductColumn::NAME_JP,
            ProductColumn::SALE_PRICE => Product::TABLE_NAME.'.'.ProductColumn::SALE_PRICE,
            ProductColumn::CATEGORY_CODE => Product::TABLE_NAME.'.'.ProductColumn::CATEGORY_CODE,
            ProductColumn::IS_CUSTOMIZABLE => Product::TABLE_NAME.'.'.ProductColumn::IS_CUSTOMIZABLE,
            ProductColumn::IS_BEST_SELLER => Product::TABLE_NAME.'.'.ProductColumn::IS_BEST_SELLER,
            ProductColumn::STATUS => Product::TABLE_NAME.'.'.ProductColumn::STATUS,
        ]);
    }

    public function filter($builder): BaseMasterModel|Builder|null
    {
        if (! empty($this->filters[ProductColumn::SALE_PRICE_RANGE])) {
            $builder = $this->filterByPriceRange($builder, $this->filters[ProductColumn::SALE_PRICE_RANGE]);
            unset($this->filters[ProductColumn::SALE_PRICE_RANGE]);
        }

        $tagCodes = $this->filters[ProductRelation::TAGS] ?? null;
        if ($tagCodes) {
            unset($this->filters[ProductRelation::TAGS]);
        }

        $builder = $this->filterByConditions($builder, $this->filters, [
            ProductColumn::CODE => Product::TABLE_NAME.'.'.ProductColumn::CODE,
            ProductColumn::CATEGORY_CODE => Product::TABLE_NAME.'.'.ProductColumn::CATEGORY_CODE,
            ProductColumn::IS_CUSTOMIZABLE => Product::TABLE_NAME.'.'.ProductColumn::IS_CUSTOMIZABLE,
            ProductColumn::IS_BEST_SELLER => Product::TABLE_NAME.'.'.ProductColumn::IS_BEST_SELLER,
            ProductColumn::STATUS => Product::TABLE_NAME.'.'.ProductColumn::STATUS,
        ]);

        if ($tagCodes) {
            $tagCodesArr = explode(',', $tagCodes);
            $builder = $builder->whereHas(ProductRelation::TAGS, function ($q) use ($tagCodesArr) {
                $q->whereIn(TagColumn::CODE, $tagCodesArr);
            });
        }

        return $builder;
    }

    private function filterByPriceRange($builder, $priceRange): Builder
    {
        $parts = explode('-', $priceRange);

        $min = $parts[0] ?? null;
        $max = $parts[1] ?? null;

        if ($min && ! $max) {
            $builder = $builder->where(Product::TABLE_NAME.'.'.ProductColumn::SALE_PRICE, '>=', $min);
        } elseif (! $min && $max) {
            $builder = $builder->where(Product::TABLE_NAME.'.'.ProductColumn::SALE_PRICE, '<=', $max);
        } elseif ($min && $max) {
            $builder = $builder->whereBetween(
                Product::TABLE_NAME.'.'.ProductColumn::SALE_PRICE,
                [(int) $min, (int) $max]
            );
        }

        return $builder;
    }

    public function search($builder): BaseMasterModel|Builder|null
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            ProductColumn::CODE => Product::TABLE_NAME.'.'.ProductColumn::CODE,
            ProductColumn::NAME_VN => Product::TABLE_NAME.'.'.ProductColumn::NAME_VN,
            ProductColumn::NAME_EN => Product::TABLE_NAME.'.'.ProductColumn::NAME_EN,
            ProductColumn::NAME_JP => Product::TABLE_NAME.'.'.ProductColumn::NAME_JP,
            ProductColumn::CATEGORY_CODE => Product::TABLE_NAME.'.'.ProductColumn::CATEGORY_CODE,
        ]);
    }
}
