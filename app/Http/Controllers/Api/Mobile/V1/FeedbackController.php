<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\PublicFeedback;
use App\Models\SatisfactionSurvey;
use App\Services\MobileTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function satisfactionIndex(Request $request, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('viewAny', SatisfactionSurvey::class);
        $paginator = SatisfactionSurvey::query()
            ->with('client')
            ->latest()
            ->cursorPaginate(min($request->integer('per_page', 20), 50));

        return response()->json([
            'data' => $paginator->getCollection()
                ->map(fn (SatisfactionSurvey $survey) => $transformer->satisfactionSurvey($survey)),
            'meta' => ['next_cursor' => $paginator->nextCursor()?->encode()],
        ]);
    }

    public function satisfactionShow(SatisfactionSurvey $survey, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('view', $survey);
        $survey->load('client');

        return response()->json(['data' => $transformer->satisfactionSurvey($survey)]);
    }

    public function publicIndex(Request $request, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('viewAny', PublicFeedback::class);
        $paginator = PublicFeedback::query()
            ->with('users')
            ->latest()
            ->cursorPaginate(min($request->integer('per_page', 20), 50));

        return response()->json([
            'data' => $paginator->getCollection()
                ->map(fn (PublicFeedback $feedback) => $transformer->publicFeedback($feedback)),
            'meta' => ['next_cursor' => $paginator->nextCursor()?->encode()],
        ]);
    }

    public function publicShow(PublicFeedback $feedback, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('view', $feedback);
        $feedback->load('users');

        return response()->json(['data' => $transformer->publicFeedback($feedback)]);
    }
}
