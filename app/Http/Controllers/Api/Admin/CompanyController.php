<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

final class CompanyController extends Controller
{
    /**
     * 会社一覧を返す（論理削除済みを含む）。
     */
    public function index(): JsonResponse
    {
        $companies = Company::withTrashed()
            ->get()
            ->map(fn (Company $company): array => [
                'id'         => $company->id,
                'name'       => $company->name,
                'deleted_at' => $company->deleted_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $companies]);
    }

    /**
     * 会社を新規作成する。
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = Company::create([
            'name' => $request->validated('name'),
        ]);

        return response()->json(['id' => $company->id], 201);
    }
}
