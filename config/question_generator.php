<?php
/**
 * Shenmo Abacus – Question Generator & Validator
 * ================================================
 * This file contains:
 *   - generatePaperQuestions()  – main entry point; seedable, multi-section
 *   - generateSectionQuestions() – generates N questions for one section
 *   - generateDirectAddSub()    – enforces the "direct" abacus rule
 *   - generateMixedAddSub()     – general add/sub with non-negative totals
 *   - evaluateAnswer()          – INDEPENDENT evaluator (never the generator's own calc)
 *   - isDirect()                – validates a generated question against the direct rule
 *   - isValidRunningTotal()     – checks no intermediate total goes negative
 *
 * DIRECT rule (abacus complement-free):
 *   Every operand is 1–9 and at every intermediate step the running total
 *   stays within [0, 9]. The final answer is therefore also 0–9 (≥0).
 *
 * MIXED rule:
 *   Every operand is within the digit range. Operations are + and −.
 *   The running total is never negative at any step. Final answer ≥ 0.
 *
 * Both modes:
 *   - Avoid two consecutive identical operands within one question
 *   - Deduplicate across all questions in the same paper/section batch
 *   - Generation is seedable so the same seed reproduces identical output
 */

require_once __DIR__ . '/question_registry.php';

// ──────────────────────────────────────────────────────────────────────────
// Constants (all rules come from the existing competition_api.php constants;
// these are kept in sync — DO NOT hardcode new values here)
// ──────────────────────────────────────────────────────────────────────────
const REGISTRY_TOTAL_QUESTIONS_LEVEL_I = 240;  // mirrors LEVEL_I_TOTAL_QUESTIONS
const REGISTRY_QUESTIONS_PER_BLOCK     = 40;   // mirrors QUESTIONS_PER_BLOCK
const REGISTRY_MAX_REGEN_ATTEMPTS      = 500;  // guard against infinite loops

// ──────────────────────────────────────────────────────────────────────────
// Public API
// ──────────────────────────────────────────────────────────────────────────

/**
 * Generate all questions for one paper, distributed across its sections.
 *
 * @param int    $level       1–7
 * @param string $paper       'A'|'B'|'C'|'D'
 * @param int    $totalQuestions Total questions for this paper (e.g. 240 for Level I)
 * @param int    $seed        Seed for mt_srand; 0 = time-based (non-reproducible)
 *
 * @return array  Each element is a question row ready to insert into competition_questions.
 *                Keys: question_type, operand_count, operands (JSON), operators (JSON),
 *                      correct_answer, question_text, requires_abacus, question_group, display_order
 */
function generatePaperQuestions(int $level, string $paper, int $totalQuestions, int $seed = 0): array
{
    $paper    = strtoupper($paper);
    $sections = getSectionsForPaper($level, $paper);

    if (empty($sections)) {
        return [];
    }

    // Deterministic seed: if none supplied, build from level+paper
    if ($seed === 0) {
        $seed = crc32('shenmo-L' . $level . '-' . $paper);
    }
    mt_srand($seed);

    $sectionCount = count($sections);
    $base         = intdiv($totalQuestions, $sectionCount);
    $remainder    = $totalQuestions % $sectionCount;

    $allQuestions  = [];
    $displayOrder  = 1;
    $seenSignatures = [];   // deduplicate across ALL sections of this paper

    foreach ($sections as $idx => $section) {
        $count = $base + ($idx < $remainder ? 1 : 0);
        $qs    = generateSectionQuestions($section, $count, $seenSignatures);

        foreach ($qs as $q) {
            $allQuestions[] = array_merge($q, ['display_order' => $displayOrder++]);
        }
    }

    return $allQuestions;
}

/**
 * Generate $count questions for a single registry section.
 * Mutates $seenSignatures to propagate deduplication across sections.
 *
 * @param array $section   A registry entry from getQuestionRegistry()
 * @param int   $count     How many questions to generate
 * @param array &$seen     Global seen-set for dedup (passed by reference)
 * @return array
 */
function generateSectionQuestions(array $section, int $count, array &$seen): array
{
    $questions = [];
    $attempts  = 0;

    while (count($questions) < $count && $attempts < REGISTRY_MAX_REGEN_ATTEMPTS * $count) {
        $attempts++;

        if ($section['kind'] === 'direct_addsub') {
            $q = generateDirectAddSub($section);
        } elseif ($section['kind'] === 'mixed_addsub') {
            $q = generateMixedAddSub($section);
        } elseif ($section['kind'] === 'multiply') {
            $q = generateMultiply($section);
        } elseif ($section['kind'] === 'divide') {
            $q = generateDivide($section);
        } else {
            continue;
        }

        if ($q === null) {
            continue;
        }

        // Deduplicate: use operands+operators as a signature
        $sig = implode(',', $q['operands']) . '|' . implode(',', $q['operators']);
        if (isset($seen[$sig])) {
            continue;
        }
        $seen[$sig] = true;

        // Independent answer verification
        $verified = evaluateAnswer($q['operands'], $q['operators'], $section['kind']);
        if ($verified === null || $verified !== (int)$q['correct_answer']) {
            // Generator and evaluator disagree — skip this question
            continue;
        }

        $questions[] = [
            'question_type'  => mapKindToType($section['kind']),
            'operand_count'  => count($q['operands']),
            'operands'       => json_encode($q['operands']),
            'operators'      => json_encode($q['operators']),
            'correct_answer' => (string)$q['correct_answer'],
            'question_text'  => formatVerticalText($q['operands'], $q['operators']),
            'requires_abacus'=> $section['requiresAbacus'] ? 1 : 0,
            'question_group' => $section['group'],
        ];
    }

    return $questions;
}

// ──────────────────────────────────────────────────────────────────────────
// Independent evaluator  (never trusts generator's own calculation)
// ──────────────────────────────────────────────────────────────────────────

/**
 * Independently evaluate a question's correct answer.
 * Returns the integer answer, or null if the expression is invalid.
 *
 * @param int[]    $operands
 * @param string[] $operators  '+' | '-' | 'x' | '/'
 * @param string   $kind
 * @return int|null
 */
function evaluateAnswer(array $operands, array $operators, string $kind): ?int
{
    if (empty($operands)) {
        return null;
    }

    if ($kind === 'multiply') {
        if (count($operands) !== 2 || count($operators) !== 1) {
            return null;
        }
        return $operands[0] * $operands[1];
    }

    if ($kind === 'divide') {
        if (count($operands) !== 2 || count($operators) !== 1) {
            return null;
        }
        if ($operands[1] === 0) {
            return null;
        }
        $result = intdiv($operands[0], $operands[1]);
        // Verify exactness (division must be exact)
        if ($result * $operands[1] !== $operands[0]) {
            return null;
        }
        return $result;
    }

    // add/sub
    if (count($operators) !== count($operands) - 1) {
        return null;
    }
    $acc = $operands[0];
    for ($i = 0; $i < count($operators); $i++) {
        $op      = $operators[$i];
        $operand = $operands[$i + 1];
        if ($op === '+') {
            $acc += $operand;
        } elseif ($op === '-') {
            $acc -= $operand;
        } else {
            return null;
        }
    }
    return $acc;
}

// ──────────────────────────────────────────────────────────────────────────
// Direct-rule validator
// ──────────────────────────────────────────────────────────────────────────

/**
 * Returns true iff the question satisfies the "direct" abacus rule:
 *   – Every operand is within [digitsMin_bound, 9]
 *   – Running total after each step stays within [0, 9]
 *
 * @param int[]    $operands
 * @param string[] $operators
 * @return bool
 */
function isDirect(array $operands, array $operators): bool
{
    // All operands must be 1–9 (direct only operates on single digits)
    foreach ($operands as $v) {
        if ($v < 1 || $v > 9) {
            return false;
        }
    }

    $running = $operands[0];
    if ($running < 0 || $running > 9) {
        return false;
    }

    for ($i = 0; $i < count($operators); $i++) {
        $running = ($operators[$i] === '+')
            ? $running + $operands[$i + 1]
            : $running - $operands[$i + 1];

        if ($running < 0 || $running > 9) {
            return false;
        }
    }
    return true;
}

/**
 * Returns true iff no intermediate running total goes negative.
 *
 * @param int[]    $operands
 * @param string[] $operators
 * @return bool
 */
function isValidRunningTotal(array $operands, array $operators): bool
{
    $running = $operands[0];
    if ($running < 0) {
        return false;
    }
    for ($i = 0; $i < count($operators); $i++) {
        $running = ($operators[$i] === '+')
            ? $running + $operands[$i + 1]
            : $running - $operands[$i + 1];
        if ($running < 0) {
            return false;
        }
    }
    return true;
}

// ──────────────────────────────────────────────────────────────────────────
// Question generators
// ──────────────────────────────────────────────────────────────────────────

/**
 * Generate one direct-add/sub question.
 * Guarantees: all operands 1–9, running total stays 0–9 at every step.
 *
 * @return array|null
 */
function generateDirectAddSub(array $section): ?array
{
    $n = $section['operandCount'];

    for ($try = 0; $try < 200; $try++) {
        $operands  = [];
        $operators = [];
        $running   = mt_rand(1, 9);
        $operands[] = $running;

        $valid = true;
        for ($i = 0; $i < $n - 1; $i++) {
            // Decide direction: can we add without exceeding 9? can we subtract without going below 0?
            $canAdd = ($running < 9);
            $canSub = ($running > 0);

            if (!$canAdd && !$canSub) {
                $valid = false;
                break;
            }

            // Prefer random direction, but fall back if only one option
            if ($canAdd && $canSub) {
                $addOrSub = mt_rand(0, 1) ? '+' : '-';
            } else {
                $addOrSub = $canAdd ? '+' : '-';
            }

            if ($addOrSub === '+') {
                $maxAdd   = min(9 - $running, 9);
                if ($maxAdd < 1) { $valid = false; break; }
                $operand  = mt_rand(1, $maxAdd);
                $running += $operand;
            } else {
                $maxSub  = min($running, 9);
                if ($maxSub < 1) { $valid = false; break; }
                $operand = mt_rand(1, $maxSub);
                $running -= $operand;
            }

            // Avoid consecutive identical operands
            if (!empty($operands) && $operand === end($operands)) {
                $valid = false;
                break;
            }

            $operators[] = $addOrSub;
            $operands[]  = $operand;
        }

        if (!$valid || $running < 0) {
            continue;
        }

        // Final answer must be non-negative (guaranteed by construction but double-check)
        $answer = $running;
        return [
            'operands'      => $operands,
            'operators'     => $operators,
            'correct_answer'=> $answer,
        ];
    }
    return null;
}

/**
 * Generate one mixed add/sub question.
 * Guarantees: operands within digit range, no negative running total.
 *
 * @return array|null
 */
function generateMixedAddSub(array $section): ?array
{
    $n       = $section['operandCount'];
    $dMin    = max(1, (int)pow(10, $section['digitsMin'] - 1));
    $dMax    = (int)(pow(10, $section['digitsMax']) - 1);

    // Clamp for one-digit to 1–9
    if ($section['digitsMin'] === 1 && $section['digitsMax'] === 1) {
        $dMin = 1;
        $dMax = 9;
    }

    for ($try = 0; $try < 200; $try++) {
        $operands  = [];
        $operators = [];
        $running   = mt_rand($dMin, $dMax);
        $operands[] = $running;
        $valid = true;

        for ($i = 0; $i < $n - 1; $i++) {
            $op      = (mt_rand(0, 1) ? '+' : '-');
            $operand = mt_rand($dMin, $dMax);

            // Avoid consecutive identical operands
            if (!empty($operands) && $operand === end($operands)) {
                $valid = false;
                break;
            }

            $next = ($op === '+') ? $running + $operand : $running - $operand;
            if ($next < 0) {
                // Try flipping the operator
                $op   = ($op === '+') ? '-' : '+';
                $next = ($op === '+') ? $running + $operand : $running - $operand;
                if ($next < 0) {
                    // Cannot use this operand without going negative
                    $valid = false;
                    break;
                }
            }

            $running   = $next;
            $operators[] = $op;
            $operands[]  = $operand;
        }

        if (!$valid || $running < 0) {
            continue;
        }

        return [
            'operands'      => $operands,
            'operators'     => $operators,
            'correct_answer'=> $running,
        ];
    }
    return null;
}

/**
 * Generate one multiplication question.
 * operands[0] is the larger number, operands[1] is the smaller multiplier.
 *
 * @return array|null
 */
function generateMultiply(array $section): ?array
{
    $dMin1 = (int)pow(10, $section['digitsMin'] - 1);
    $dMax1 = (int)(pow(10, $section['digitsMax']) - 1);
    // Multiplier is always 1-digit (1–9) for Level I registry entries
    $dMin2 = 1;
    $dMax2 = 9;

    $a      = mt_rand(max(1, $dMin1), $dMax1);
    $b      = mt_rand($dMin2, $dMax2);
    $answer = $a * $b;

    return [
        'operands'      => [$a, $b],
        'operators'     => ['x'],
        'correct_answer'=> $answer,
    ];
}

/**
 * Generate one exact-division question.
 * Ensures dividend / divisor is an exact integer.
 *
 * @return array|null
 */
function generateDivide(array $section): ?array
{
    for ($try = 0; $try < 200; $try++) {
        $dMin2  = 2;  // divisor always 2–9
        $dMax2  = 9;
        $b      = mt_rand($dMin2, $dMax2);
        $result = mt_rand(2, 20);
        $a      = $b * $result;

        $dMin1  = (int)pow(10, $section['digitsMin'] - 1);
        $dMax1  = (int)(pow(10, $section['digitsMax']) - 1);

        if ($a < $dMin1 || $a > $dMax1) {
            continue;
        }

        return [
            'operands'      => [$a, $b],
            'operators'     => ['/'],
            'correct_answer'=> $result,
        ];
    }
    return null;
}

// ──────────────────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────────────────

/**
 * Maps a registry 'kind' to the question_type ENUM used in the DB.
 */
function mapKindToType(string $kind): string
{
    return match ($kind) {
        'direct_addsub' => 'mixed',
        'mixed_addsub'  => 'mixed',
        'multiply'      => 'multiplication',
        'divide'        => 'division',
        default         => 'mixed',
    };
}

/**
 * Formats a vertical question text (stored in question_text column).
 */
function formatVerticalText(array $operands, array $operators): string
{
    $lines = [(string)$operands[0]];
    for ($i = 0; $i < count($operators); $i++) {
        $op = $operators[$i];
        if ($op === 'x') {
            $lines[] = '× ' . $operands[$i + 1];
        } elseif ($op === '/') {
            $lines[] = '÷ ' . $operands[$i + 1];
        } elseif ($op === '-') {
            $lines[] = '− ' . abs((int)$operands[$i + 1]);
        } else {
            $lines[] = '+ ' . $operands[$i + 1];
        }
    }
    $lines[] = '——';
    $lines[] = 'ans';
    return implode("\n", $lines) . "\n";
}

/**
 * Returns a formatted inline expression string (e.g. "3 + 5 − 2").
 */
function formatInlineText(array $operands, array $operators): string
{
    $str = (string)$operands[0];
    for ($i = 0; $i < count($operators); $i++) {
        $op      = $operators[$i];
        $display = match ($op) {
            '+' => '+', '-' => '−', 'x' => '×', '/' => '÷', default => $op
        };
        $str .= ' ' . $display . ' ' . $operands[$i + 1];
    }
    return $str;
}

