<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Requests\UpdateOrganizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    /**
     * 所属が未入力のユーザーに入力を求める画面。
     */
    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->hasOrganization()) {
            return redirect(AuthenticatedSessionController::homeUrl($user));
        }

        return view('organization.edit', ['user' => $user]);
    }

    /**
     * 所属入力画面と設定画面の両方から使う。
     */
    public function update(UpdateOrganizationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['organization' => $request->validated('organization')]);

        if ($request->validated('return_to') === 'profile') {
            return redirect()->route('profile.edit')->with('status', '所属を変更しました。');
        }

        return redirect(AuthenticatedSessionController::homeUrl($user));
    }
}
