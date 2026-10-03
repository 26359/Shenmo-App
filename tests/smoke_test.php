<?php
require 'config/question_registry.php';
require 'config/question_generator.php';

$today = date('Y-m-d');

// Test Level I all 4 papers
foreach (['A','B','C','D'] as $paper) {
    $seed = crc32('practice-L1-' . $paper . '-' . $today);
    $qs   = generatePaperQuestions(1, $paper, 240, $seed);
    $count = count($qs);

    if (!empty($qs)) {
        $firstOperands  = json_decode($qs[0]['operands'], true);
        $firstOperators = json_decode($qs[0]['operators'], true);
        $storedAnswer   = (int)$qs[0]['correct_answer'];

        // Independent verification
        $acc = $firstOperands[0];
        foreach ($firstOperators as $i => $op) {
            $acc = ($op === '+') ? $acc + $firstOperands[$i+1] : $acc - $firstOperands[$i+1];
        }
        $ok = ($acc === $storedAnswer) ? 'PASS' : 'FAIL(evaluator mismatch)';
        echo "Paper $paper: $count questions, Q1 answer=$storedAnswer verified=$acc [$ok]\n";
    } else {
        echo "Paper $paper: 0 questions FAIL\n";
    }
}

echo "\n";

// Check section distribution for Paper D (should have 3 sections: D1, D2, D3)
$seed = crc32('practice-L1-D-' . $today);
$dqs  = generatePaperQuestions(1, 'D', 240, $seed);
$groups = [];
foreach ($dqs as $q) {
    $g = $q['question_group'];
    $groups[$g] = ($groups[$g] ?? 0) + 1;
}
ksort($groups);
echo "Paper D section distribution:\n";
foreach ($groups as $g => $c) {
    echo "  $g: $c questions\n";
}

echo "\n";

// Verify direct rule on Paper A
$seed = crc32('practice-L1-A-' . $today);
$aqs  = generatePaperQuestions(1, 'A', 240, $seed);
$directFail = 0;
foreach ($aqs as $q) {
    $ops  = json_decode($q['operators'], true);
    $opds = json_decode($q['operands'],  true);
    if (!isDirect($opds, $ops)) $directFail++;
}
echo "Paper A direct-rule violations: $directFail (should be 0)\n";

// Check no negative running totals for Paper D
$negFail = 0;
foreach ($dqs as $q) {
    $ops  = json_decode($q['operators'], true);
    $opds = json_decode($q['operands'],  true);
    if (!isValidRunningTotal($opds, $ops)) $negFail++;
}
echo "Paper D negative-running-total violations: $negFail (should be 0)\n";

echo "\nAll tests done.\n";
