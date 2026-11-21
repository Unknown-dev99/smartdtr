<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\StaffUser;

abstract class Controller
{
    protected function adminUser(): AdminUser
    {
        /** @var AdminUser $user */
        $user = auth('admin')->user();

        return $user;
    }

    protected function staffUser(): StaffUser
    {
        /** @var StaffUser $user */
        $user = auth('staff')->user();
        abort_unless($user, 403);

        return $user;
    }
}
