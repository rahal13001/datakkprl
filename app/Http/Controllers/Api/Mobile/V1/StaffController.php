<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->can('CreateAssignment') || $request->user()->can('UpdateAssignment'),
            403,
        );

        $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = $request->input('search');

        $users = User::query()
            ->where('status', true)
            ->when($search, fn ($query) => $query->where(function ($nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('jabatan', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'nip', 'jabatan', 'instansi']);

        return response()->json(['data' => $users]);
    }
}
