<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $admin = $this->adminUser();

        return view('admin.profile', compact('admin'));
    }
}
