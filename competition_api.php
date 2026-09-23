<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$host = "fdb1028.awardspace.net";
$dbname = "4783798_shenmoapp";
$user = "4783798_shenmoapp";
$pass = "muganwa123";

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new mysqli_sql_exception("Connection failed: " . $conn->connect_error);
    }
} catch (mysqli_sql_exception $e) {
    echo json_encode(['error' => 'Database unavailable', 'message' => $e->getMessage()]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$student_id = $_SESSION['student_id'];

const LEVEL_I_TOTAL_QUESTIONS = 240;
const QUESTIONS_PER_BLOCK = 40;
const LEVEL_I_TIME_LIMIT_SECONDS = 300;
const LATE_PENALTY_POINTS = 20;

if ($action === 'create') {
    handleCreate($conn, $student_id);
} elseif ($action === 'start') {
    handleStart($conn, $student_id);
} elseif ($action === 'save_answer') {
    handleSaveAnswer($conn, $student_id);
} elseif ($action === 'submit') {
    handleSubmit($conn, $student_id);
} elseif ($action === 'end' || $action === 'lock') {
    handleEnd($conn, $student_id);
} elseif ($action === 'get_results') {
    handleGetResults($conn, $student_id);
} elseif ($action === 'get_attempt') {
    handleGetAttempt($conn, $student_id);
} else {
    echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
exit;

function getRandomInRange($min, $max) {
    return mt_rand($min, $max);
}

function getOperandsForType($operandCount, $digitSpec) {
    $operands = [];
    for ($i = 0; $i < $operandCount; $i++) {
        $operands[] = getRandomInRange($digitSpec['min'], $digitSpec['max']);
    }
    return $operands;
}

function generateMixedOps($count) {
    $ops = [];
    for ($i = 0; $i < $count; $i++) {
        $ops[] = (mt_rand(0, 1) === 0) ? '+' : '-';
    }
    return $ops;
}

function calculateResult($operands, $operators) {
    $result = $operands[0];
    for ($i = 0; $i < count($operators); $i++) {
        if ($operators[$i] === '+') {
            $result += $operands[$i + 1];
        } else if ($operators[$i] === '-') {
            $result -= $operands[$i + 1];
        }
    }
    return $result;
}

function generateMultiplication($digitSpec1, $digitSpec2) {
    $a = getRandomInRange($digitSpec1['min'], $digitSpec1['max']);
    $b = getRandomInRange($digitSpec2['min'], $digitSpec2['max']);
    return [
        'operands' => [$a, $b],
        'result' => $a * $b
    ];
}

function generateDivision($digitSpec1, $digitSpec2) {
    $b = getRandomInRange($digitSpec2['min'], $digitSpec2['max']);
    $result = getRandomInRange(2, 20);
    $a = $b * $result;
    return [
        'operands' => [$a, $b],
        'result' => $result
    ];
}

$DIGIT_SPECS = [
    'one_digit_numbers_1_9'       => ['min' => 1,  'max' => 9],
    'one_digit_numbers_0_9'       => ['min' => 0,  'max' => 9],
    'two_digit_numbers'           => ['min' => 10, 'max' => 99],
    'three_digit_numbers'         => ['min' => 100, 'max' => 999],
    'four_digit_numbers'          => ['min' => 1000, 'max' => 9999],
    'five_digit_numbers'          => ['min' => 10000, 'max' => 99999],
    'one_to_two_digit_numbers'    => ['min' => 1,  'max' => 99],
    'one_to_three_digit_numbers'  => ['min' => 1,  'max' => 999],
];

function getLevelPaperDefinitions() {
    global $DIGIT_SPECS;

    return [
        1 => [
            'A' => [
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => 'A1'],
            ],
            'B' => [
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => 'B1'],
            ],
            'C' => [
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => 'C1'],
            ],
            'D' => [
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 1, 'group' => 'D1'],
            ],
        ],
        2 => [
            'A' => [
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => '3_one_digit'],
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => '5_one_digit'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 1, 'group' => '3_two_digit'],
            ],
            'B' => [
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => '3_one_digit'],
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => '5_one_digit'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 1, 'group' => '3_two_digit_a'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 1, 'group' => '3_two_digit_b'],
            ],
            'C' => [
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => '3_one_digit'],
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => '5_one_digit'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 1, 'group' => '3_two_digit_a'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 1, 'group' => '3_two_digit_b'],
            ],
            'D' => [
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 1, 'group' => '3_two_digit_a'],
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 1, 'group' => '5_two_digit'],
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['one_to_two_digit_numbers'], 'requires_abacus' => 1, 'group' => '5_one_to_two'],
            ],
        ],
        3 => [
            'A' => [
                ['type' => 'mixed', 'operands' => 7, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => '7_one_digit'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 0, 'group' => '3_two_digit'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['three_digit_numbers'], 'requires_abacus' => 1, 'group' => '3_three_digit'],
            ],
            'B' => [
                ['type' => 'mixed', 'operands' => 7, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => '7_one_digit'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 0, 'group' => '3_two_digit'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['three_digit_numbers'], 'requires_abacus' => 1, 'group' => '3_three_digit'],
            ],
            'C' => [
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['one_digit_numbers_1_9'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '1d_x_1d'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['two_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '2d_x_1d'],
            ],
            'D' => [
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 0, 'group' => '5_two_digit'],
                ['type' => 'mixed', 'operands' => 7, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 0, 'group' => '7_two_digit'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['two_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '2d_x_1d'],
            ],
        ],
        4 => [
            'A' => [
                ['type' => 'mixed', 'operands' => 10, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => '10_one_digit'],
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 0, 'group' => '5_two_digit'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['three_digit_numbers'], 'requires_abacus' => 0, 'group' => '3_three_digit'],
            ],
            'B' => [
                ['type' => 'mixed', 'operands' => 10, 'digit_spec' => $DIGIT_SPECS['one_digit_numbers_1_9'], 'requires_abacus' => 0, 'group' => '10_one_digit'],
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 0, 'group' => '5_two_digit'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['three_digit_numbers'], 'requires_abacus' => 0, 'group' => '3_three_digit'],
            ],
            'C' => [
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['two_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '2d_x_1d'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['three_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '3d_x_1d'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '4d_x_1d'],
            ],
            'D' => [
                ['type' => 'mixed', 'operands' => 7, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 0, 'group' => '7_two_digit'],
                ['type' => 'mixed', 'operands' => 7, 'digit_spec' => $DIGIT_SPECS['one_to_two_digit_numbers'], 'requires_abacus' => 0, 'group' => '7_one_to_two'],
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['one_to_three_digit_numbers'], 'requires_abacus' => 0, 'group' => '5_one_to_three'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '4d_x_1d'],
            ],
        ],
        5 => [
            'A' => [
                ['type' => 'mixed', 'operands' => 7, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 0, 'group' => '7_two_digit'],
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['three_digit_numbers'], 'requires_abacus' => 0, 'group' => '5_three_digit'],
                ['type' => 'mixed', 'operands' => 2, 'digit_spec' => $DIGIT_SPECS['four_digit_numbers'], 'requires_abacus' => 0, 'group' => '2_four_digit'],
            ],
            'B' => [
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['three_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '3d_x_1d'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '4d_x_1d'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['two_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '2d_x_2d'],
            ],
            'C' => [
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['two_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '2d_div_1d'],
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['three_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '3d_div_1d'],
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '4d_div_1d'],
            ],
            'D' => [
                ['type' => 'mixed', 'operands' => 7, 'digit_spec' => $DIGIT_SPECS['three_digit_numbers'], 'requires_abacus' => 0, 'group' => '7_three_digit'],
                ['type' => 'mixed', 'operands' => 10, 'digit_spec' => $DIGIT_SPECS['one_to_two_digit_numbers'], 'requires_abacus' => 0, 'group' => '10_one_to_two'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['three_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '3d_x_2d'],
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '4d_div_1d'],
            ],
        ],
        6 => [
            'A' => [
                ['type' => 'mixed', 'operands' => 10, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 0, 'group' => '10_two_digit'],
                ['type' => 'mixed', 'operands' => 7, 'digit_spec' => $DIGIT_SPECS['three_digit_numbers'], 'requires_abacus' => 0, 'group' => '7_three_digit'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['four_digit_numbers'], 'requires_abacus' => 0, 'group' => '3_four_digit'],
            ],
            'B' => [
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '4d_x_1d'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['two_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '2d_x_2d'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['three_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '3d_x_2d'],
            ],
            'C' => [
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '4d_div_1d'],
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['five_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '5d_div_1d'],
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['three_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '3d_div_2d'],
            ],
            'D' => [
                ['type' => 'mixed', 'operands' => 15, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 0, 'group' => '15_two_digit'],
                ['type' => 'mixed', 'operands' => 10, 'digit_spec' => $DIGIT_SPECS['three_digit_numbers'], 'requires_abacus' => 0, 'group' => '10_three_digit'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '4d_x_2d'],
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '4d_div_2d'],
            ],
        ],
        7 => [
            'A' => [
                ['type' => 'mixed', 'operands' => 7, 'digit_spec' => $DIGIT_SPECS['three_digit_numbers'], 'requires_abacus' => 0, 'group' => '7_three_digit'],
                ['type' => 'mixed', 'operands' => 5, 'digit_spec' => $DIGIT_SPECS['four_digit_numbers'], 'requires_abacus' => 0, 'group' => '5_four_digit'],
                ['type' => 'mixed', 'operands' => 3, 'digit_spec' => $DIGIT_SPECS['five_digit_numbers'], 'requires_abacus' => 0, 'group' => '3_five_digit'],
            ],
            'B' => [
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['five_digit_numbers'], $DIGIT_SPECS['one_digit_numbers_1_9']], 'requires_abacus' => 1, 'group' => '5d_x_1d'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['two_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '2d_x_2d'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['three_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '3d_x_2d'],
            ],
            'C' => [
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['three_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '3d_div_2d'],
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '4d_div_2d'],
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['five_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '5d_div_2d'],
            ],
            'D' => [
                ['type' => 'mixed', 'operands' => 15, 'digit_spec' => $DIGIT_SPECS['two_digit_numbers'], 'requires_abacus' => 0, 'group' => '15_two_digit'],
                ['type' => 'mixed', 'operands' => 10, 'digit_spec' => $DIGIT_SPECS['three_digit_numbers'], 'requires_abacus' => 0, 'group' => '10_three_digit'],
                ['type' => 'multiplication', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '4d_x_2d'],
                ['type' => 'division', 'operands' => 2, 'digit_specs' => [$DIGIT_SPECS['four_digit_numbers'], $DIGIT_SPECS['two_digit_numbers']], 'requires_abacus' => 1, 'group' => '4d_div_2d'],
            ],
        ],
    ];
}

function generateQuestionFromTemplate($template) {
    $type = $template['type'];

    if ($type === 'multiplication' || $type === 'division') {
        $specs = $template['digit_specs'];
        $operandCount = 2;

        if ($type === 'multiplication') {
            $result = generateMultiplication($specs[0], $specs[1]);
        } else {
            $result = generateDivision($specs[0], $specs[1]);
        }

        $operands = $result['operands'];
        $answer = $result['result'];
        $operators = ['×'];

        $questionText = formatQuestionText($operands, $operators);
        $opType = 'multiplication';

    } else {
        $operands = getOperandsForType($template['operands'], $template['digit_spec']);
        $operators = generateMixedOps($template['operands'] - 1);
        $answer = calculateResult($operands, $operators);

        $questionText = formatQuestionText($operands, $operators);
        $opType = 'mixed';
    }

    return [
        'question_type' => $opType,
        'operand_count' => count($operands),
        'operands' => $operands,
        'operators' => $operators,
        'correct_answer' => (string)$answer,
        'question_text' => $questionText,
        'requires_abacus' => $template['requires_abacus'],
        'question_group' => $template['group'],
    ];
}

function formatQuestionText($operands, $operators) {
    $str = (string)$operands[0];
    for ($i = 0; $i < count($operators); $i++) {
        $displayOp = ($operators[$i] === '+') ? '+' : '-';
        $str .= " " . $displayOp . " " . $operands[$i + 1];
    }
    return $str;
}

function formatVerticalQuestionText($operands, $operators) {
    $lines = [(string) $operands[0]];
    for ($i = 0; $i < count($operators); $i++) {
        $sign = ($operators[$i] === '-') ? '-' : '';
        $lines[] = $sign . abs((int) $operands[$i + 1]);
    }
    $lines[] = '——';
    $lines[] = 'ans';
    return implode("\n", $lines) . "\n";
}

function generateLevelIQuestions($paper) {
    $definitions = getLevelPaperDefinitions();
    $template = $definitions[1][$paper][0];
    $questions = [];
    $operandCount = (int) $template['operands'];

    mt_srand(crc32('shenmo-level-1-paper-' . $paper));

    for ($questionNumber = 1; $questionNumber <= LEVEL_I_TOTAL_QUESTIONS; $questionNumber++) {
        $qData = generateQuestionFromTemplate($template);
        $blockIndex = intdiv($questionNumber - 1, QUESTIONS_PER_BLOCK) + 1;
        $questions[] = [
            'question_type' => 'mixed',
            'operand_count' => $operandCount,
            'operands' => json_encode($qData['operands']),
            'operators' => json_encode($qData['operators']),
            'correct_answer' => $qData['correct_answer'],
            'question_text' => formatVerticalQuestionText($qData['operands'], $qData['operators']),
            'requires_abacus' => $paper === 'D' ? 1 : 0,
            'question_group' => $paper . $blockIndex,
            'display_order' => $questionNumber,
        ];
    }

    return $questions;
}

function generateQuestionsForPaper($level, $paper) {
    if ((int) $level === 1 && in_array(strtoupper((string) $paper), ['A', 'B', 'C', 'D'], true)) {
        return generateLevelIQuestions(strtoupper((string) $paper));
    }

    $definitions = getLevelPaperDefinitions();
    $templates = $definitions[$level][$paper] ?? [];
    $questions = [];
    $totalQuestions = 40;
    $numTemplates = count($templates);

    if ($numTemplates === 0) {
        return [];
    }

    $questionsPerTemplate = intdiv($totalQuestions, $numTemplates);
    $remainder = $totalQuestions % $numTemplates;

    $displayOrder = 1;
    foreach ($templates as $idx => $template) {
        $count = $questionsPerTemplate + ($idx < $remainder ? 1 : 0);
        for ($i = 0; $i < $count; $i++) {
            $qData = generateQuestionFromTemplate($template);
            $questions[] = [
                'question_type' => $qData['question_type'],
                'operand_count' => $qData['operand_count'],
                'operands' => json_encode($qData['operands']),
                'operators' => json_encode($qData['operators']),
                'correct_answer' => $qData['correct_answer'],
                'question_text' => $qData['question_text'],
                'requires_abacus' => $qData['requires_abacus'],
                'question_group' => $qData['question_group'],
                'display_order' => $displayOrder++,
            ];
        }
    }

    shuffle($questions);
    foreach ($questions as $idx => $q) {
        $questions[$idx]['display_order'] = $idx + 1;
    }

    return $questions;
}

function handleCreate($conn, $student_id) {
    $level = (int)($_POST['level'] ?? 1);
    $paper = strtoupper((string) ($_POST['paper'] ?? 'A'));
    $mode = $_POST['mode'] ?? 'practice';

    $validLevels = [1, 2, 3, 4, 5, 6, 7];
    $validPapers = ['A', 'B', 'C', 'D'];
    $validModes = ['official', 'practice'];

    if (!in_array($level, $validLevels)) $level = 1;
    if (!in_array($paper, $validPapers)) $paper = 'A';
    if (!in_array($mode, $validModes)) $mode = 'practice';

    $timeLimit = ($level === 1 && in_array($paper, $validPapers, true)) ? LEVEL_I_TIME_LIMIT_SECONDS : 300;
    $totalQuestions = ($level === 1 && in_array($paper, $validPapers, true)) ? LEVEL_I_TOTAL_QUESTIONS : 40;

    $stmt = $conn->prepare("INSERT INTO competitions (student_id, level_number, paper_name, mode, total_questions, time_limit_seconds) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissii", $student_id, $level, $paper, $mode, $totalQuestions, $timeLimit);
    $stmt->execute();
    $competitionId = $stmt->insert_id;
    $stmt->close();

    if ($competitionId === 0) {
        echo json_encode(['error' => 'Failed to create competition']);
        return;
    }

    $questions = generateQuestionsForPaper($level, $paper);

    foreach ($questions as $q) {
        $stmt = $conn->prepare("INSERT INTO competition_questions (competition_id, question_number, question_type, operand_count, operands, operators, correct_answer, question_text, requires_abacus, question_group, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $qn = $q['display_order'];
        $stmt->bind_param("iisissssisi",
            $competitionId, $qn,
            $q['question_type'],
            $q['operand_count'],
            $q['operands'],
            $q['operators'],
            $q['correct_answer'],
            $q['question_text'],
            $q['requires_abacus'],
            $q['question_group'],
            $q['display_order']
        );
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'competition_id' => $competitionId,
        'total_questions' => count($questions),
        'status' => 'waiting',
    ]);
}

function handleStart($conn, $student_id) {
    $competitionId = (int)($_POST['competition_id'] ?? 0);

    if ($competitionId === 0) {
        echo json_encode(['error' => 'Invalid competition']);
        return;
    }

    $stmt = $conn->prepare("SELECT id, student_id, mode, time_limit_seconds, start_time, end_time, is_submitted FROM competitions WHERE id = ?");
    $stmt->bind_param("i", $competitionId);
    $stmt->execute();
    $attempt = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$attempt) {
        echo json_encode(['error' => 'Attempt not found']);
        return;
    }

    if ($attempt['student_id'] !== $student_id) {
        echo json_encode(['error' => 'Attempt belongs to another student']);
        return;
    }

    if ((int) $attempt['is_submitted']) {
        echo json_encode(['error' => 'Competition already submitted']);
        return;
    }

    if (!$attempt['start_time']) {
        $endTime = date('Y-m-d H:i:s', time() + (int) $attempt['time_limit_seconds']);
        $updateStmt = $conn->prepare("UPDATE competitions SET start_time = NOW(), end_time = ? WHERE id = ?");
        $updateStmt->bind_param("si", $endTime, $competitionId);
        $updateStmt->execute();
        $updateStmt->close();
        $attempt['start_time'] = date('Y-m-d H:i:s');
        $attempt['end_time'] = $endTime;
    }

    echo json_encode([
        'success' => true,
        'competition_id' => $competitionId,
        'status' => 'running',
        'start_time' => $attempt['start_time'],
        'end_time' => $attempt['end_time'],
        'end_time_iso' => $attempt['end_time'] ? date(DATE_ATOM, strtotime($attempt['end_time'])) : null,
        'time_limit_seconds' => (int) $attempt['time_limit_seconds'],
        'server_time' => date(DATE_ATOM),
    ]);
}

function handleSaveAnswer($conn, $student_id) {
    $competitionId = (int)($_POST['competition_id'] ?? 0);
    $questionId = (int)($_POST['question_id'] ?? 0);
    $answer = trim($_POST['answer'] ?? '');
    $abacusUsed = isset($_POST['abacus_used']) ? (int)$_POST['abacus_used'] : 0;

    if ($competitionId === 0 || $questionId === 0) {
        echo json_encode(['error' => 'Invalid parameters']);
        return;
    }

    $checkStmt = $conn->prepare("SELECT q.correct_answer, q.requires_abacus, c.mode, c.is_submitted, c.start_time, c.end_time FROM competition_questions q JOIN competitions c ON c.id = q.competition_id WHERE q.id = ? AND q.competition_id = ? AND c.student_id = ?");
    $checkStmt->bind_param("iis", $questionId, $competitionId, $student_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $checkStmt->close();

    if (!$row) {
        echo json_encode(['error' => 'Question not found']);
        return;
    }

    if ((int) $row['is_submitted'] || !$row['start_time'] || ($row['end_time'] && strtotime($row['end_time']) <= time())) {
        echo json_encode(['error' => 'Competition is locked']);
        return;
    }

    if ($answer !== '' && !preg_match('/^-?\d+$/', $answer)) {
        echo json_encode(['error' => 'Only integer numeric answers are allowed']);
        return;
    }

    $correctAnswer = $row['correct_answer'];
    $requiresAbacus = (int)$row['requires_abacus'];
    $isCorrect = 0;
    if ($answer !== '' && (int) $answer === (int) $correctAnswer) {
        $isCorrect = 1;
    }

    $pointsEarned = $isCorrect;
    if ($row['mode'] === 'official' && $requiresAbacus && !$abacusUsed) {
        $isCorrect = 0;
        $pointsEarned = 0;
    }

    $stmt = $conn->prepare("INSERT INTO competition_answers (competition_id, question_id, student_id, answer_text, is_correct, points_earned, abacus_used) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE answer_text = VALUES(answer_text), is_correct = VALUES(is_correct), points_earned = VALUES(points_earned), abacus_used = VALUES(abacus_used), answered_at = NOW()");
    $stmt->bind_param("iissiii", $competitionId, $questionId, $student_id, $answer, $isCorrect, $pointsEarned, $abacusUsed);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'is_correct' => (bool)$isCorrect,
        'points_earned' => $pointsEarned,
    ]);
}

function handleSubmit($conn, $student_id) {
    $competitionId = (int)($_POST['competition_id'] ?? 0);
    $latePenaltyRequested = isset($_POST['late_penalty']) && in_array((string) $_POST['late_penalty'], ['1', 'true'], true);
    $autoSubmitted = isset($_POST['auto_submit']) && in_array((string) $_POST['auto_submit'], ['1', 'true'], true);

    if ($competitionId === 0) {
        echo json_encode(['error' => 'Invalid competition']);
        return;
    }

    $competitionStmt = $conn->prepare("SELECT id, student_id, mode, total_questions, time_limit_seconds, start_time, end_time, is_submitted FROM competitions WHERE id = ?");
    $competitionStmt->bind_param("i", $competitionId);
    $competitionStmt->execute();
    $competition = $competitionStmt->get_result()->fetch_assoc();
    $competitionStmt->close();

    if (!$competition) {
        echo json_encode(['error' => 'Attempt not found']);
        return;
    }

    if ($competition['student_id'] !== $student_id) {
        echo json_encode(['error' => 'Attempt belongs to another student']);
        return;
    }

    if ((int) $competition['is_submitted']) {
        $existingResult = $conn->prepare("SELECT id, final_score, max_score, accuracy_percent, time_taken_seconds, penalty_applied FROM competition_results WHERE competition_id = ? ORDER BY id DESC LIMIT 1");
        $existingResult->bind_param("i", $competitionId);
        $existingResult->execute();
        $resultRow = $existingResult->get_result()->fetch_assoc();
        $existingResult->close();

        if ($resultRow) {
            echo json_encode([
                'success' => true,
                'competition_id' => $competitionId,
                'score' => (int) $resultRow['final_score'],
                'max_score' => (int) $resultRow['max_score'],
                'accuracy' => (float) $resultRow['accuracy_percent'],
                'penalty' => (int) $resultRow['penalty_applied'],
                'time_taken' => (int) $resultRow['time_taken_seconds'],
                'already_submitted' => true,
            ]);
        } else {
            echo json_encode(['error' => 'Competition already submitted']);
        }
        return;
    }

    if (!$competition['start_time']) {
        echo json_encode(['error' => 'Competition has not started']);
        return;
    }

    $now = time();
    $startTime = strtotime($competition['start_time']);
    $endTime = $competition['end_time'] ? strtotime($competition['end_time']) : $startTime + (int) $competition['time_limit_seconds'];
    $timeTaken = max(0, $now - $startTime);
    $latePenalty = ($latePenaltyRequested || ($now > $endTime && !$autoSubmitted)) ? LATE_PENALTY_POINTS : 0;


    $questionsStmt = $conn->prepare("SELECT id, question_number, correct_answer, requires_abacus FROM competition_questions WHERE competition_id = ? ORDER BY display_order, question_number");
    $questionsStmt->bind_param("i", $competitionId);
    $questionsStmt->execute();
    $questionsResult = $questionsStmt->get_result();
    $questions = [];
    while ($question = $questionsResult->fetch_assoc()) {
        $questions[] = $question;
    }
    $questionsStmt->close();

    $answersStmt = $conn->prepare("SELECT question_id, answer_text, is_correct, points_earned, abacus_used FROM competition_answers WHERE competition_id = ?");
    $answersStmt->bind_param("i", $competitionId);
    $answersStmt->execute();
    $answersResult = $answersStmt->get_result();
    $answersByQuestion = [];
    while ($answer = $answersResult->fetch_assoc()) {
        $answersByQuestion[(int) $answer['question_id']] = $answer;
    }
    $answersStmt->close();

    $effectiveScore = 0;
    $correctCount = 0;
    $answeredCount = 0;
    $skipStarted = false;
    $updates = [];
    $inserts = [];

    foreach ($questions as $question) {
        $questionId = (int) $question['id'];
        $saved = $answersByQuestion[$questionId] ?? null;
        $answerText = $saved ? trim((string) $saved['answer_text']) : '';
        $hasAnswer = $answerText !== '';
        if ($hasAnswer) {
            $answeredCount++;
        }

        if ($competition['mode'] === 'official' && !$hasAnswer) {
            $skipStarted = true;
        }

        $rawCorrect = $hasAnswer && preg_match('/^-?\d+$/', $answerText) && (int) $answerText === (int) $question['correct_answer'];
        $points = $rawCorrect ? 1 : 0;

        if ($competition['mode'] === 'official' && $skipStarted) {
            $points = 0;
        }

        if ($competition['mode'] === 'official' && (int) $question['requires_abacus'] && $rawCorrect && (!$saved || !(int) $saved['abacus_used'])) {
            $points = 0;
        }

        if ($points) {
            $effectiveScore++;
            $correctCount++;
        }

        if ($saved) {
            $updates[] = [$questionId, $points];
        } else {
            $inserts[] = [$questionId, ''];
        }
    }

    $maxScore = count($questions);
    $accuracy = $maxScore > 0 ? round(($correctCount / $maxScore) * 100, 2) : 0;
    $finalScore = $effectiveScore - $latePenalty;
    $submittedAt = date('Y-m-d H:i:s', $now);

    $conn->begin_transaction();

    try {
        $updateAnswerStmt = $conn->prepare("UPDATE competition_answers SET points_earned = ?, is_correct = ? WHERE competition_id = ? AND question_id = ?");
        foreach ($updates as $update) {
            $isCorrect = $update[1] ? 1 : 0;
            $updateAnswerStmt->bind_param("iiii", $update[1], $isCorrect, $competitionId, $update[0]);
            $updateAnswerStmt->execute();
        }
        $updateAnswerStmt->close();

        $insertAnswerStmt = $conn->prepare("INSERT INTO competition_answers (competition_id, question_id, student_id, answer_text, is_correct, points_earned) VALUES (?, ?, ?, ?, 0, 0)");
        foreach ($inserts as $insert) {
            $insertAnswerStmt->bind_param("iiss", $competitionId, $insert[0], $student_id, $insert[1]);
            $insertAnswerStmt->execute();
        }
        $insertAnswerStmt->close();

        $resultStmt = $conn->prepare("INSERT INTO competition_results (competition_id, student_id, final_score, max_score, accuracy_percent, time_taken_seconds, penalty_applied, result_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $resultDetails = json_encode([
            'mode' => $competition['mode'],
            'official_sequence_applied' => $competition['mode'] === 'official',
            'late_penalty_applied' => $latePenalty > 0,
        ]);
        $resultStmt->bind_param("isiidiis", $competitionId, $student_id, $finalScore, $maxScore, $accuracy, $timeTaken, $latePenalty, $resultDetails);
        $resultStmt->execute();
        $resultId = $conn->insert_id;
        $resultStmt->close();

        $updateCompetitionStmt = $conn->prepare("UPDATE competitions SET end_time = ?, final_score = ?, max_score = ?, accuracy_percent = ?, is_submitted = 1 WHERE id = ?");
        $updateCompetitionStmt->bind_param("siidi", $submittedAt, $finalScore, $maxScore, $accuracy, $competitionId);
        $updateCompetitionStmt->execute();
        $updateCompetitionStmt->close();

        $xpStmt = $conn->prepare("INSERT INTO xp_points (student_id, xp_amount, xp_source, source_id, description) VALUES (?, ?, 'exam_pass', ?, ?)");
        $xpAmount = max(0, $finalScore);
        $xpStmt->bind_param("siis", $student_id, $xpAmount, $resultId, "Competition score: $finalScore/$maxScore");
        $xpStmt->execute();
        $xpStmt->close();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'competition_id' => $competitionId,
            'score' => $finalScore,
            'max_score' => $maxScore,
            'accuracy' => $accuracy,
            'penalty' => $latePenalty,
            'answered' => $answeredCount,
            'correct' => $correctCount,
            'time_taken' => $timeTaken,
            'result_id' => $resultId,
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Submission failed: ' . $e->getMessage()]);
    }
}

function handleEnd($conn, $student_id) {
    $competitionId = (int)($_POST['competition_id'] ?? 0);

    if ($competitionId === 0) {
        echo json_encode(['error' => 'Invalid competition']);
        return;
    }

    $stmt = $conn->prepare("SELECT id, student_id, start_time, end_time, is_submitted FROM competitions WHERE id = ?");
    $stmt->bind_param("i", $competitionId);
    $stmt->execute();
    $attempt = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$attempt) {
        echo json_encode(['error' => 'Attempt not found']);
        return;
    }

    if ($attempt['student_id'] !== $student_id) {
        echo json_encode(['error' => 'Attempt belongs to another student']);
        return;
    }

    if ((int) $attempt['is_submitted']) {
        echo json_encode(['success' => true, 'status' => 'completed', 'competition_id' => $competitionId]);
        return;
    }

    if (!$attempt['start_time']) {
        echo json_encode(['error' => 'Competition has not started']);
        return;
    }

    $updateStmt = $conn->prepare("UPDATE competitions SET end_time = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $competitionId);
    $updateStmt->execute();
    $updateStmt->close();

    echo json_encode([
        'success' => true,
        'status' => 'ended',
        'competition_id' => $competitionId,
        'end_time' => date('Y-m-d H:i:s'),
        'server_time' => date(DATE_ATOM),
    ]);
}

function handleGetResults($conn, $student_id) {
    $competitionId = (int)($_GET['competition_id'] ?? 0);

    if ($competitionId === 0) {
        echo json_encode(['error' => 'Invalid competition']);
        return;
    }

    $compStmt = $conn->prepare("SELECT c.*, cr.final_score, cr.max_score, cr.accuracy_percent, cr.time_taken_seconds, cr.penalty_applied FROM competitions c JOIN competition_results cr ON c.id = cr.competition_id WHERE c.id = ? AND c.student_id = ?");
    $compStmt->bind_param("is", $competitionId, $student_id);
    $compStmt->execute();
    $compRow = $compStmt->get_result()->fetch_assoc();
    $compStmt->close();

    if (!$compRow) {
        echo json_encode(['error' => 'Results not found']);
        return;
    }

    $questions = $conn->query("SELECT cq.id, cq.question_number, cq.operands, cq.operators, cq.question_text, cq.question_group, cq.requires_abacus, ca.answer_text, ca.is_correct, ca.abacus_used FROM competition_questions cq LEFT JOIN competition_answers ca ON cq.id = ca.question_id AND ca.competition_id = $competitionId WHERE cq.competition_id = $competitionId ORDER BY cq.display_order");

    $questionList = [];
    $wrongAnswers = [];
    while ($q = $questions->fetch_assoc()) {
        $q['operands'] = json_decode($q['operands'] ?? '[]', true);
        $q['operators'] = json_decode($q['operators'] ?? '[]', true);
        $questionList[] = $q;
        if (!$q['is_correct']) {
            $wrongAnswers[] = $q;
        }
    }

    echo json_encode([
        'success' => true,
        'results' => $compRow,
        'questions' => $questionList,
        'wrong_answers' => $wrongAnswers
    ]);
}

function handleGetAttempt($conn, $student_id) {
    $competitionId = (int)($_GET['competition_id'] ?? 0);

    if ($competitionId === 0) {
        echo json_encode(['error' => 'Invalid competition']);
        return;
    }

    $compRow = $conn->prepare("SELECT id, level_number, paper_name, mode, total_questions, time_limit_seconds, start_time, end_time, is_submitted, final_score, max_score, accuracy_percent FROM competitions WHERE id = ? AND student_id = ?");
    $compRow->bind_param("is", $competitionId, $student_id);
    $compRow->execute();
    $competition = $compRow->get_result()->fetch_assoc();
    $compRow->close();

    if (!$competition) {
        echo json_encode(['error' => 'Attempt not found']);
        return;
    }

    $now = time();
    if ((int) $competition['is_submitted']) {
        $status = 'completed';
        $timeRemaining = 0;
    } elseif (!$competition['start_time']) {
        $status = 'waiting';
        $timeRemaining = (int) $competition['time_limit_seconds'];
    } else {
        $endTime = $competition['end_time'] ? strtotime($competition['end_time']) : $now + (int) $competition['time_limit_seconds'];
        $status = $endTime <= $now ? 'ended' : 'running';
        $timeRemaining = max(0, $endTime - $now);
    }

    $questionsStmt = $conn->prepare("SELECT id, question_number, operands, operators, question_text, requires_abacus, question_group, display_order FROM competition_questions WHERE competition_id = ? ORDER BY display_order, question_number");
    $questionsStmt->bind_param("i", $competitionId);
    $questionsStmt->execute();
    $questionsResult = $questionsStmt->get_result();
    $questionList = [];
    while ($question = $questionsResult->fetch_assoc()) {
        $question['operands'] = json_decode($question['operands'] ?? '[]', true);
        $question['operators'] = json_decode($question['operators'] ?? '[]', true);
        $question['requires_abacus'] = (bool) $question['requires_abacus'];
        $questionList[] = $question;
    }
    $questionsStmt->close();

    $answersStmt = $conn->prepare("SELECT question_id, answer_text, is_correct, abacus_used FROM competition_answers WHERE competition_id = ?");
    $answersStmt->bind_param("i", $competitionId);
    $answersStmt->execute();
    $answersResult = $answersStmt->get_result();
    $answersByQuestion = [];
    while ($answer = $answersResult->fetch_assoc()) {
        $answersByQuestion[(int) $answer['question_id']] = $answer;
    }
    $answersStmt->close();

    foreach ($questionList as &$question) {
        $answerRow = $answersByQuestion[(int) $question['id']] ?? null;
        $question['saved_answer'] = $answerRow ? (string) $answerRow['answer_text'] : '';
        $question['is_correct'] = $answerRow ? (bool) $answerRow['is_correct'] : false;
        $question['abacus_used'] = $answerRow ? (bool) $answerRow['abacus_used'] : false;
    }
    unset($question);

    echo json_encode([
        'success' => true,
        'competition' => $competition,
        'status' => $status,
        'time_remaining_seconds' => $timeRemaining,
        'server_time' => date(DATE_ATOM),
        'questions' => $questionList,
    ]);
}
