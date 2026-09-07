<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyController extends Controller
{
    public function index(): View
    {
        $surveys = Survey::with(['responses' => function ($q) {
            $q->latest()->take(10);
        }])
        ->orderBy('sort_order')
        ->orderByDesc('id')
        ->get();

        $totalSurveys = $surveys->count();
        $totalResponses = SurveyResponse::count();
        $avgStoreRating = round((float) SurveyResponse::whereNotNull('rating')->avg('rating'), 1);

        return view('admin.surveys.index', compact(
            'surveys',
            'totalSurveys',
            'totalResponses',
            'avgStoreRating'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'question'    => 'required|string|max:500',
            'type'        => 'required|in:rating,single_choice,text',
            'target_page' => 'required|in:all,checkout,order_confirmation,home',
            'options_raw' => 'nullable|string',
            'description' => 'nullable|string|max:1000',
        ]);

        $options = null;
        if ($request->input('type') === 'single_choice' && $request->filled('options_raw')) {
            $options = array_values(array_filter(array_map('trim', explode("\n", (string)$request->input('options_raw')))));
        }

        $survey = Survey::create([
            'title'        => $request->input('title'),
            'question'     => $request->input('question'),
            'description'  => $request->input('description'),
            'type'         => $request->input('type'),
            'options_json' => $options,
            'target_page'  => $request->input('target_page'),
            'is_active'    => true,
            'sort_order'   => Survey::max('sort_order') + 1,
        ]);

        AuditLog::log('survey.create', 'survey', $survey->id, "Created customer poll/survey: {$survey->title}");

        return back()->with('success', "Survey \"{$survey->title}\" created successfully!");
    }

    public function toggle(int $id): RedirectResponse
    {
        $survey = Survey::findOrFail($id);
        $survey->update(['is_active' => !$survey->is_active]);

        $status = $survey->is_active ? 'Activated' : 'Paused';
        AuditLog::log('survey.toggle', 'survey', $survey->id, "{$status} survey: {$survey->title}");

        return back()->with('success', "Survey is now {$status}.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $survey = Survey::findOrFail($id);
        $title = $survey->title;
        $survey->delete();

        AuditLog::log('survey.delete', 'survey', $id, "Deleted survey: {$title}");

        return back()->with('success', "Survey \"{$title}\" and all associated responses deleted.");
    }

    public function deleteResponse(int $id): RedirectResponse
    {
        $response = SurveyResponse::findOrFail($id);
        $surveyId = $response->survey_id;
        $response->delete();

        return back()->with('success', "Survey response deleted.");
    }

    public function exportCsv(int $id): StreamedResponse
    {
        $survey = Survey::with('responses.user')->findOrFail($id);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="survey_' . $survey->id . '_responses_' . date('Y-m-d') . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($survey) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM for Arabic support in Excel

            fputcsv($file, ['ID', 'Date', 'Customer Name', 'Customer Email', 'Rating', 'Selected Option', 'Feedback Text', 'IP Address']);

            foreach ($survey->responses as $r) {
                fputcsv($file, [
                    $r->id,
                    $r->created_at->format('Y-m-d H:i'),
                    $r->customer_name ?? ($r->user?->name ?? 'Guest'),
                    $r->customer_email ?? ($r->user?->email ?? 'N/A'),
                    $r->rating ?? '',
                    $r->selected_option ?? '',
                    $r->response_text ?? '',
                    $r->ip_address ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
