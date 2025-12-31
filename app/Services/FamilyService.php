<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Family;
use App\Models\User;
use Carbon\Carbon;

class FamilyService
{
    public function create(array $data): ?Family
    {

        $data['trial_ends_at'] = Carbon::now()->addDays(7);
        return Family::create($data);

    }
}
