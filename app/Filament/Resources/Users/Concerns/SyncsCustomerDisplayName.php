<?php

namespace App\Filament\Resources\Users\Concerns;

use Illuminate\Support\Str;

trait SyncsCustomerDisplayName
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareCustomerData(array $data): array
    {
        $data['name'] = filled($data['company_name'] ?? null)
            ? $data['company_name']
            : (filled($data['pic_name'] ?? null)
                ? $data['pic_name']
                : Str::before($data['email'], '@'));

        return $data;
    }
}
