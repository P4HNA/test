<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** The teacher's quiz builder: a quiz per course, with multiple-choice questions. */
class QuizController extends Controller
{
    public const MAX_QUESTIONS = 40;

    public function index(): View
    {
        $quizzes = Quiz::query()
            ->with('course')
            ->withCount(['questions', 'attempts'])
            ->withAvg('attempts', 'score')
            ->orderBy('sort_order')->orderByDesc('id')
            ->get();

        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function create(): View
    {
        return view('admin.quizzes.form', [
            'quiz' => new Quiz(['is_published' => true, 'pass_percent' => 70]),
            'questions' => [],
            'courses' => Course::query()->orderBy('sort_order')->get(['id', 'title_en']),
        ]);
    }

    public function edit(Quiz $quiz): View
    {
        return view('admin.quizzes.form', [
            'quiz' => $quiz,
            'questions' => $quiz->questions()->get()->map(fn ($q) => [
                'prompt_en' => $q->prompt_en,
                'prompt_km' => (string) $q->prompt_km,
                'options' => array_map(fn ($o) => ['en' => (string) ($o['en'] ?? ''), 'km' => (string) ($o['km'] ?? '')], (array) $q->options),
                'correct' => $q->correct_index,
                'explanation_en' => (string) $q->explanation_en,
                'explanation_km' => (string) $q->explanation_km,
            ])->all(),
            'courses' => Course::query()->orderBy('sort_order')->get(['id', 'title_en']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $quiz = new Quiz;
        $this->save($request, $quiz);

        Log::info('Admin made a quiz', ['admin_id' => $request->user()->getKey(), 'quiz_id' => $quiz->id]);

        return redirect()->route('admin.quizzes')->with('status', "\"{$quiz->title_en}\" is ready with {$quiz->questions()->count()} questions.");
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->save($request, $quiz);

        return redirect()->route('admin.quizzes')->with('status', "\"{$quiz->title_en}\" is saved.");
    }

    public function toggle(Quiz $quiz): RedirectResponse
    {
        $quiz->forceFill(['is_published' => ! $quiz->is_published])->save();

        return back()->with('status', $quiz->is_published ? "\"{$quiz->title_en}\" is visible to students." : "\"{$quiz->title_en}\" is hidden from students.");
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $title = $quiz->title_en;
        $this->forgetPicture($quiz->cover_url);
        $quiz->delete();

        return redirect()->route('admin.quizzes')->with('status', "\"{$title}\" and its results are deleted.");
    }

    private function save(Request $request, Quiz $quiz): void
    {
        $data = $request->validate([
            'course_id' => ['required', Rule::exists('courses', 'id')],
            'title_en' => ['required', 'string', 'max:160'],
            'title_km' => ['nullable', 'string', 'max:160'],
            'topic' => ['nullable', 'string', 'max:160'],
            'pass_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'cover' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'questions' => ['required', 'array', 'min:1', 'max:'.self::MAX_QUESTIONS],
            'questions.*.prompt_en' => ['required', 'string', 'max:500'],
            'questions.*.prompt_km' => ['nullable', 'string', 'max:500'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.options.*.en' => ['nullable', 'string', 'max:300'],
            'questions.*.options.*.km' => ['nullable', 'string', 'max:300'],
            'questions.*.correct' => ['required', 'integer', 'min:0', 'max:5'],
            'questions.*.explanation_en' => ['nullable', 'string', 'max:1000'],
            'questions.*.explanation_km' => ['nullable', 'string', 'max:1000'],
        ], [
            'questions.required' => 'Add at least one question.',
            'questions.*.prompt_en.required' => 'Every question needs its English wording.',
            'questions.*.correct.required' => 'Mark the right answer for every question.',
        ], ['course_id' => 'course', 'title_en' => 'English title', 'pass_percent' => 'pass mark']);

        // Drop blank answers; the right answer must still be one that is left.
        $questions = [];
        foreach (array_values($data['questions']) as $n => $q) {
            $options = [];
            $correct = null;
            foreach (array_values($q['options']) as $i => $o) {
                if (blank($o['en'] ?? null) && blank($o['km'] ?? null)) {
                    continue;
                }
                if ((int) $q['correct'] === $i) {
                    $correct = count($options);
                }
                $options[] = ['en' => trim((string) ($o['en'] ?? '')), 'km' => trim((string) ($o['km'] ?? ''))];
            }
            if (count($options) < 2 || $correct === null) {
                throw ValidationException::withMessages(["questions.$n.options" => 'Question '.($n + 1).' needs at least two answers, and the one marked right must be filled in.']);
            }
            $questions[] = [
                'prompt_en' => trim($q['prompt_en']),
                'prompt_km' => filled($q['prompt_km'] ?? null) ? trim($q['prompt_km']) : null,
                'options' => $options,
                'correct_index' => $correct,
                'explanation_en' => filled($q['explanation_en'] ?? null) ? trim($q['explanation_en']) : null,
                'explanation_km' => filled($q['explanation_km'] ?? null) ? trim($q['explanation_km']) : null,
                'sort_order' => $n,
            ];
        }

        DB::transaction(function () use ($request, $quiz, $data, $questions) {
            $quiz->fill([
                'course_id' => (int) $data['course_id'],
                'title_en' => trim($data['title_en']),
                'title_km' => filled($data['title_km'] ?? null) ? trim($data['title_km']) : null,
                'topic' => filled($data['topic'] ?? null) ? trim($data['topic']) : null,
                'pass_percent' => (int) $data['pass_percent'],
                'is_published' => $request->boolean('is_published'),
            ]);

            if ($file = $request->file('cover')) {
                $this->forgetPicture($quiz->cover_url);
                $quiz->cover_url = '/storage/'.$file->store('quiz-covers', 'public');
            }

            $quiz->save();
            // Questions are replaced as a set; past attempts keep their own score.
            $quiz->questions()->delete();
            $quiz->questions()->createMany($questions);
        });
    }

    private function forgetPicture(?string $url): void
    {
        if ($url && str_starts_with($url, '/storage/quiz-covers/')) {
            Storage::disk('public')->delete(Str::after($url, '/storage/'));
        }
    }
}
