<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Taking a course quiz: one question at a time with instant feedback, then
 * a score worked out on the server from the answers sent at the end.
 */
class QuizController extends Controller
{
    public function show(Request $request, Quiz $quiz): View
    {
        abort_unless($quiz->isOpenTo($request->user()), 404);

        $quiz->load(['course', 'questions']);

        return view('quizzes.show', [
            'quiz' => $quiz,
            'attempts' => $quiz->attempts()->where('user_id', $request->user()->getKey())->latest('id')->take(5)->get(),
        ]);
    }

    /** Is this answer right? Sent after each question so the page can explain. */
    public function check(Request $request, Quiz $quiz): JsonResponse
    {
        abort_unless($quiz->isOpenTo($request->user()), 404);

        $data = $request->validate([
            'question' => ['required', 'integer'],
            'choice' => ['required', 'integer', 'min:0', 'max:9'],
        ]);

        $question = $quiz->questions()->whereKey($data['question'])->firstOrFail();

        return response()->json([
            'correct' => (int) $data['choice'] === $question->correct_index,
            'answer' => $question->correct_index,
            'explanation' => $question->localized('explanation'),
        ]);
    }

    /** Score the whole run and keep it. */
    public function store(Request $request, Quiz $quiz): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($quiz->isOpenTo($user), 404);

        $request->validate(['answers' => ['required', 'array'], 'answers.*' => ['nullable', 'integer', 'min:0', 'max:9']]);

        $questions = $quiz->questions()->get();
        abort_if($questions->isEmpty(), 422);

        $given = (array) $request->input('answers');
        $answers = [];
        $correct = 0;

        foreach ($questions as $question) {
            $choice = isset($given[$question->id]) ? (int) $given[$question->id] : null;
            $answers[$question->id] = $choice;
            $correct += $choice === $question->correct_index ? 1 : 0;
        }

        $score = (int) round($correct / $questions->count() * 100);

        $attempt = QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->getKey(),
            'answers' => $answers,
            'correct' => $correct,
            'total' => $questions->count(),
            'score' => $score,
            'passed' => $score >= $quiz->pass_percent,
        ]);

        $url = route('quizzes.result', [$quiz, $attempt]);

        if ($request->expectsJson()) {
            return response()->json([
                'score' => $score,
                'correct' => $correct,
                'total' => $questions->count(),
                'passed' => $attempt->passed,
                'url' => $url,
            ]);
        }

        return redirect()->to($url);
    }

    /** Every question with the student's answer and the right one. */
    public function result(Request $request, Quiz $quiz, QuizAttempt $attempt): View
    {
        $user = $request->user();
        abort_unless($attempt->quiz_id === $quiz->id && ($user->isAdmin() || $attempt->user_id === $user->getKey()), 404);

        $quiz->load(['course', 'questions']);

        return view('quizzes.result', compact('quiz', 'attempt'));
    }
}
