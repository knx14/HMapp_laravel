<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateMeRequest;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * ログインユーザー情報を取得
     * AppUserモデルと同じ構成で返す
     */
    public function show(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        return response()->json([
            'id' => $user->id,
            'cognito_sub' => $user->cognito_sub,
            'name' => $user->name,
            'email' => $user->email,
            'ja_name' => $user->ja_name,
        ]);
    }

    /**
     * ログインユーザー情報を更新
     * AppUserモデルのfillableと同じ構成で更新
     */
    public function update(UpdateMeRequest $request)
    {
        $user = $request->attributes->get('auth_user');

        // AppUserモデルのfillableと同じ構成で更新
        $user->update([
            'name' => $request->input('name', $user->name),
            'email' => $request->input('email', $user->email),
            'ja_name' => $request->input('ja_name', $user->ja_name),
        ]);

        // 更新後のデータを返す
        return response()->json([
            'id' => $user->id,
            'cognito_sub' => $user->cognito_sub,
            'name' => $user->name,
            'email' => $user->email,
            'ja_name' => $user->ja_name,
        ]);
    }
}

