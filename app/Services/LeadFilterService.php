<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class LeadFilterService
{
    /**
     * Apply dynamic field filters to the Lead query.
     */
    public function applyDynamicFilters(Builder $query, array $dynamicFilters, $filterableFields)
    {
        foreach ($filterableFields as $field) {
            $filterValue = $dynamicFilters[$field->slug] ?? null;

            if (is_array($filterValue)) {
                $filterValue = array_values(array_filter($filterValue, fn($value) => $value !== null && $value !== ''));
                if (empty($filterValue)) {
                    continue;
                }
            } elseif ($filterValue === null || trim((string) $filterValue) === '') {
                continue;
            }

            $jsonPath = '$."' . $field->slug . '"';

            if ($field->type === 'checkbox') {
                $query->where(function ($fieldQuery) use ($field, $filterValue) {
                    foreach ((array) $filterValue as $checkboxValue) {
                        $fieldQuery->orWhereJsonContains('data->' . $field->slug, $checkboxValue);
                    }
                });
                continue;
            }

            if (in_array($field->type, ['text', 'textarea', 'email', 'phone'], true)) {
                $query->whereRaw(
                    'LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, ?))) LIKE ?',
                    [$jsonPath, '%' . Str::lower(trim((string) $filterValue)) . '%']
                );
                continue;
            }

            $query->whereRaw(
                'JSON_UNQUOTE(JSON_EXTRACT(data, ?)) = ?',
                [$jsonPath, (string) $filterValue]
            );
        }

        return $query;
    }
}
