<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final class UserController extends Controller
{
    /**
     * ユーザー一覧を返す（論理削除済みを含む）。
     */
    public function index(): JsonResponse
    {
        $users = User::withTrashed()
            ->with(['company' => fn ($query) => $query->withTrashed()->select('id', 'name')])
            ->get()
            ->map(fn (User $user): array => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'is_admin'   => $user->is_admin,
                'company'    => $user->company
                    ? ['id' => $user->company->id, 'name' => $user->company->name]
                    : null,
                'deleted_at' => $user->deleted_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $users]);
    }

    /**
     * ユーザーを新規作成する。
     */
    public function store(StoreUserRequest $request, AuditLogger $audit): JsonResponse
    {
        $user = User::create([
            'company_id' => $request->validated('company_id'),
            'name'       => $request->validated('name'),
            'email'      => $request->validated('email'),
            'password'   => Hash::make((string) $request->validated('password')),
            'is_admin'   => (bool) $request->validated('is_admin', false),
        ]);

        $audit->log(
            adminId:    $request->user()->id,
            action:     'create_user',
            targetType: 'User',
            targetId:   $user->id,
        );

        return response()->json(['id' => $user->id], 201);
    }

    /**
     * ユーザーを論理削除する。
     */
    public function destroy(Request $request, int $id, AuditLogger $audit): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        $audit->log(
            adminId:    $request->user()->id,
            action:     'delete_user',
            targetType: 'User',
            targetId:   $user->id,
        );

        return response()->json(null, 204);
    }
}
