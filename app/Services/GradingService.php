<?php

namespace App\Services;

use App\Models\Quiz;

class GradingService
{
    public function grade(Quiz $quiz, array $answers): array
    {
        $quiz->loadMissing('questions');
        $totalPoints = 0;
        $earnedPoints = 0;
        $breakdown = [];

        foreach ($quiz->questions as $q) {
            $points = (float) ($q->points ?? 1);
            $totalPoints += $points;

            $userAnswer = $answers[$q->id] ?? $answers[(string) $q->id] ?? null;
            $rawCorrect = $q->correct_answer;

            $correct = match ($q->type) {
                'mcq', 'multiple_choice', 'true_false' => $this->matchesChoiceAnswer($userAnswer, $rawCorrect),
                'fill_blank' => $this->matchesFillBlankAnswer($userAnswer, $rawCorrect),
                'essay' => null, // teacher grades manually
                default => false,
            };

            if ($correct === true) {
                $earnedPoints += $points;
            }

            $breakdown[$q->id] = [
                'correct' => $correct,
                'points'  => $points,
            ];
        }

        $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0.0;
        $passed = $score >= (float) ($quiz->passing_score ?? 50);

        return [$score, $passed, $breakdown];
    }

    protected function normalizeScalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return '';
        }
        if (is_scalar($value)) {
            return mb_strtolower(trim((string) $value), 'UTF-8');
        }
        return '';
    }

    protected function extractAnswerStrings(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            $flattened = [];
            foreach ($value as $item) {
                if (is_array($item)) {
                    foreach ($item as $sub) {
                        $norm = $this->normalizeScalar($sub);
                        if ($norm !== '') {
                            $flattened[] = $norm;
                        }
                    }
                } else {
                    $norm = $this->normalizeScalar($item);
                    if ($norm !== '') {
                        $flattened[] = $norm;
                    }
                }
            }
            return array_values(array_unique($flattened));
        }

        $norm = $this->normalizeScalar($value);
        return $norm !== '' ? [$norm] : [];
    }

    protected function matchesChoiceAnswer(mixed $userAnswer, mixed $correctAnswer): bool
    {
        $userList = $this->extractAnswerStrings($userAnswer);
        $correctList = $this->extractAnswerStrings($correctAnswer);

        if (empty($userList) || empty($correctList)) {
            return false;
        }

        // Single-answer comparison (most common: ['Option A'] vs 'Option A', or ['true'] vs 'true')
        if (count($correctList) === 1 && count($userList) === 1) {
            return $userList[0] === $correctList[0];
        }

        // Multi-answer comparison (order-independent set equality)
        sort($userList);
        sort($correctList);
        return $userList === $correctList;
    }

    protected function matchesFillBlankAnswer(mixed $userAnswer, mixed $correctAnswer): bool
    {
        $userStr = is_array($userAnswer)
            ? ($this->extractAnswerStrings($userAnswer)[0] ?? '')
            : $this->normalizeScalar($userAnswer);

        if ($userStr === '') {
            return false;
        }

        $acceptedAnswers = $this->extractAnswerStrings($correctAnswer);
        if (empty($acceptedAnswers)) {
            return false;
        }

        return in_array($userStr, $acceptedAnswers, true);
    }
}
