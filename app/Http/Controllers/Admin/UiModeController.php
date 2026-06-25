<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UiModeController extends Controller
{
    public function update(Request $request): Response
    {
        $data = $request->validate([
            'ui_mode' => ['required', Rule::in(User::UI_MODES)],
        ]);

        $user = $request->user();
        $user->ui_mode = $data['ui_mode'];
        $user->save();

        return response()->noContent();
    }
}
