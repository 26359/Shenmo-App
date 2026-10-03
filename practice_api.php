<?php
/**
 * practice_api.php – Practice Question Generator API
 * ────────────────────────────────────────────────────
 * Endpoint: GET practice_api.php?action=generate&level=1&paper=A
 *
 * Uses the same generator as competition_api.php (config/question_generator.php)
 * and enforces the same rules:
 *   • Direct rule for direct_addsub sections
 *   • Non-negative running total for all add/sub sections
 *   • Independent answer evaluator (never trusts generator's own calc)
 *   • Seedable, deduplicated questions
 *
 * Returns JSON: { questions: [ { question_group, operands, operators,
 *                                correct_answer, question_text, requires_abacus,
 *                                operand_count, question_type } ] }
 *
 * NOTE: correct_answer IS returned here (unlike competition) because
 * practice mode scores locally in the browser. This is intentional —
 * practice is not a ranked competition attempt.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['error' => 'Authentication required']); exit;
}

require_once __DIR__ . '/config/question_registry.php';
require_once __DIR__ . '/config/question_generator.php';

$action = $_GET['action'] ?? '';

if ($action !== 'generate') {
    echo json_encode(['error' => 'Invalid action']); exit;
}

$level = max(1, min(7, (int)($_GET['level'] ?? 1)));
$paper = strtoupper(trim($_GET['paper'] ?? 'A'));

if (!in_array($paper, ['A','B','C','D'], true)) {
    echo json_encode(['error' => 'Invalid paper']); exit;
}

// Check the paper is enabled in the registry
$sections = getSectionsForPaper($level, $paper);
if (empty($sections)) {
    echo json_encode(['error' => 'This paper is not yet available', 'questions' => []]); exit;
}

// Total questions: Level I = 240 (same as competition); other levels = 40
$totalQuestions = ($level === 1) ? 240 : 40;

// Seedable: use a consistent seed so the same level/paper always produces
// reproducible questions (useful for auditing and testing).
// The seed changes daily so students see fresh content each day.
$dailySeed = crc32('practice-L' . $level . '-' . $paper . '-' . date('Y-m-d'));

$rawQuestions = generatePaperQuestions($level, $paper, $totalQuestions, $dailySeed);

if (empty($rawQuestions)) {
    echo json_encode(['error' => 'Question generation failed. Please try again.', 'questions' => []]); exit;
}

// Decode JSON operands/operators back to arrays for JS consumption
$questions = [];
foreach ($rawQuestions as $q) {
    $questions[] = [
        'question_group'  => $q['question_group'],
        'operands'        => json_decode($q['operands'], true),
        'operators'       => json_decode($q['operators'], true),
        'correct_answer'  => $q['correct_answer'],
        'question_text'   => $q['question_text'],
        'requires_abacus' => (bool)$q['requires_abacus'],
        'operand_count'   => (int)$q['operand_count'],
        'question_type'   => $q['question_type'],
        'display_order'   => $q['display_order'],
    ];
}

echo json_encode([
    'success'   => true,
    'level'     => $level,
    'paper'     => $paper,
    'total'     => count($questions),
    'questions' => $questions,
]);
