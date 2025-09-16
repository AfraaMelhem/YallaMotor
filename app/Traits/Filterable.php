<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    public function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if ($this->isTranslatable($key)) {
                $this->applyTranslatableFilter($query, $key, $value);
            } elseif (method_exists($this, $method = 'filterBy' . ucfirst($key))) {
                $this->{$method}($query, $value);
            } elseif ($this->isDateRangeFilter($key)) {
                $this->applyDateRangeFilter($query, $key, $value);
            } elseif (str_contains($key, '.')) {
                $this->applyRelationFilter($query, $key, $value);
            } else {
                $this->applyColumnFilter($query, $key, $value);
            }
        }

        return $query;
    }

    public function applySearch(Builder $query, ?string $search): Builder
    {
        if (!empty($search)) {
            $query->where(function (Builder $q) use ($search) {
                foreach ($this->model->getFillable() as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $search . '%');
                }
            });
        }

        return $query;
    }

    public function applySorting(Builder $query, array $sort): Builder
    {
        foreach ($sort as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }


    protected function applyColumnFilter(Builder $query, string $key, $value): Builder
    {
        if (!$this->isColumnAllowed($key)) {
            return $query;
        }
//        dd($key, $value);

        if (is_array($value)) {
            foreach ($value as $operator => $operatorValue) {
                if (in_array($operator, ['=', '<>', '<', '>', '<=', '>='])) {
                    $query->where($key, $operator, $operatorValue);
                }
            }
        } elseif (is_string($value)) {
            $query->where($key, 'LIKE', "%{$value}%");
        } else {
            $query->where($key, $value);
        }

        return $query;
    }

    protected function applyTranslatableFilter(Builder $query, string $key, $value): Builder
    {
        $locale = app()->getLocale();
//        dd($locale);

        return $query->whereRaw(
            "JSON_UNQUOTE(JSON_EXTRACT(`$key`, '$.\"$locale\"')) LIKE ?",
            ["%{$value}%"]
        );
    }

    protected function isTranslatable(string $key): bool
    {
        return property_exists($this->model, 'translatable') &&
            in_array($key, $this->model->translatable);
    }

    protected function applyRelationFilter(Builder $query, string $key, $value): Builder
    {
        [$relation, $column] = explode('.', $key, 2);

        return $query->whereHas($relation, function (Builder $q) use ($column, $value) {
            if (is_string($value)) {
                $q->where($column, 'LIKE', '%' . $value . '%');
            } else {
                $q->where($column, $value);
            }
        });
    }

    protected function isDateRangeFilter(string $key): bool
    {
        return preg_match('/_(from|to)$/', $key);
    }

    protected function applyDateRangeFilter(Builder $query, string $key, $value): Builder
    {
        $column = preg_replace('/_(from|to)$/', '', $key);
        $operator = str_ends_with($key, '_from') ? '>=' : '<=';

        return $query->where($column, $operator, $value);
    }

    protected function isColumnAllowed(string $key): bool
    {

        return in_array($key, $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable()));
    }
}
