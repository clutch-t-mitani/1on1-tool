<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    /**
     * ログイン処理。
     *
     * @throws ValidationException
     */
    public function store(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['メールアドレスまたはパスワードが正しくありません。'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => [
                'id'         => Auth::user()->id,
                'name'       => Auth::user()->name,
                'email'      => Auth::user()->email,
                'is_admin'   => Auth::user()->is_admin,
                'company_id' => Auth::user()->company_id,
            ],
        ]);
    }

    /**
     * ログアウト処理。
     */
    public function destroy(Request $request): Response
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
