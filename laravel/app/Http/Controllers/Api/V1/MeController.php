<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateMeRequest;
use App\Models\AppUser;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * ログインユーザー情報を取得
     */
    public function show(Request $request)
    {
        return response()->json($this->toArray($request->attributes->get('auth_user')));
    }

    /**
     * ログインユーザー情報を更新
     * 送られなかった項目は既存の値を維持する。
     */
    public function update(UpdateMeRequest $request)
    {
        $user = $request->attributes->get('auth_user');

        $user->update([
            'name' => $request->input('name', $user->name),
            'email' => $request->input('email', $user->email),
            'ja_name' => $request->input('ja_name', $user->ja_name),
            'organization' => $request->has('organization') ? $request->validated('organization') : $user->organization,
        ]);

        return response()->json($this->toArray($user));
    }

    private function toArray(AppUser $user): array
    {
        return [
            'id' => $user->id,
            'cognito_sub' => $user->cognito_sub,
            'name' => $user->name,
            'email' => $user->email,
            'ja_name' => $user->ja_name,
            'organization' => $user->organization,
        ];
    }
}
