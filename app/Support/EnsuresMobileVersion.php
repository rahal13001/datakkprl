<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait EnsuresMobileVersion
{
    protected function ensureVersion(Request $request, Model $model): void
    {
        $request->validate(['version' => ['required', 'string']]);
        $current = $model->updated_at?->format('Y-m-d\TH:i:s.uP');

        if (! is_string($current) || ! hash_equals($current, (string) $request->input('version'))) {
            abort(response()->json([
                'message' => 'This record has changed. Reload it before saving.',
                'code' => 'record_changed',
                'request_id' => $request->attributes->get('request_id'),
                'current_version' => $current,
            ], 409));
        }
    }
}
