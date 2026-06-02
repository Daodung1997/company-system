<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait SortFilterSearchCriteria
{
    public function sortByConditions($query, array $sortData, array $sortConditions)
    {
        $table = $query->getModel()->getTable();
        foreach ($sortData as $key => $value) {
            if (! empty($sortConditions[$key]) && ! empty($value)) {
                if (is_array($sortConditions[$key])) {
                    foreach ($sortConditions[$key] as $field) {
                        $query = $query->orderBy($field, $value);
                    }
                } else {
                    $query = $query->orderBy($sortConditions[$key], $value);
                }

                $this->currentSort = true;
            }
        }

        if (! $this->currentSort) {
            return $query->orderBy($table.'.id', 'desc');
        }

        return $query;
    }

    public function filterByConditions($query, array $filterData, array $filterFields)
    {
        foreach ($filterData as $key => $value) {
            if (! empty($filterFields[$key]) && (! empty($value) || is_numeric($value))) {
                $query = $query->whereIn($filterFields[$key], explode(',', $value));
            }
        }

        return $query;
    }

    public function searchByConditions($query, array $searchData, array $searchFields)
    {
        $query = $query->where(function ($query) use ($searchData, $searchFields) {
            $values = [];
            foreach ($searchData as $key => $value) {
                if (empty($searchFields[$key]) || empty($value)) {
                    continue;
                }
                $values = explode(' ', $value);
                foreach ($values as $index => $text) {
                    if (Str::contains($text, '　')) {
                        $texts = explode('　', $text);
                        $values = array_merge($values, $texts);
                        unset($values[$index]);
                    }
                }
                break;
            }

            foreach ($values as $value) {
                $query->where(function ($query) use ($value, $searchData, $searchFields) {
                    $first = true;
                    foreach (array_keys($searchData) as $key) {
                        if (empty($searchFields[$key]) || empty($value)) {
                            continue;
                        }

                        if ($first) {
                            $query = $query->where(function ($query) use ($searchFields, $key, $value) {
                                if (is_array($searchFields[$key])) {
                                    foreach ($searchFields[$key] as $field) {
                                        $query->orWhere($field, 'LIKE', '%'.$this->clean($value).'%');
                                    }

                                    return;
                                }
                                $query->where($searchFields[$key], 'LIKE', '%'.$this->clean($value).'%');
                            });
                            $first = false;
                        } else {
                            $query = $query->orWhere(function ($query) use ($searchFields, $key, $value) {
                                if (is_array($searchFields[$key])) {
                                    foreach ($searchFields[$key] as $field) {
                                        $query->orWhere($field, 'LIKE', '%'.$this->clean($value).'%');
                                    }

                                    return;
                                }
                                $query->where($searchFields[$key], 'LIKE', '%'.$this->clean($value).'%');
                            });
                        }
                    }
                });
            }

            return $query;
        });

        return $query;
    }

    public function clean($string)
    {
        $string = preg_quote($string);

        return str_replace(['_', '%'], ['\_', '\%'], $string);
    }
}
