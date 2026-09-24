<?php
/**
 * Shenmo Abacus – Question Type Registry
 * =========================================
 * Single source of truth for every level / paper / section.
 * The generator (config/question_generator.php) reads this array.
 * UI selectors in competition.php and abacus_levels.php are also
 * derived from it — levels with no data show "coming soon".
 *
 * Section schema:
 *   id          – unique slug, e.g. "L1-A-1"
 *   level       – int 1–7 (7 = Advanced)
 *   paper       – 'A'|'B'|'C'|'D'
 *   order       – 1-based sort order within the paper
 *   label       – human-readable description shown in UI
 *   kind        – "direct_addsub" | "mixed_addsub" | "multiply" | "divide"
 *   operandCount– how many numbers in each question
 *   digitsMin   – smallest digit count (1 = 1-digit, 2 = 2-digit …)
 *   digitsMax   – largest digit count
 *   operations  – ['+','-'] | ['x'] | ['/']
 *   requiresAbacus – bool
 *   group       – block label used in competition_questions.question_group
 *
 * "direct_addsub" rule (abacus complement-free):
 *   Every operand is 1–9, the running total stays within 0–9 at every
 *   intermediate step, and the total is never negative.
 *
 * "mixed_addsub" rule:
 *   Every operand is within the digit range, operations are + and −,
 *   the running total is never negative, final answer ≥ 0.
 *
 * HOW TO ADD A NEW LEVEL
 * ----------------------
 * 1. Find the disabled stub below (e.g. Level II).
 * 2. Replace the stub entries with real section definitions.
 * 3. Set 'enabled' => true on each section.
 * 4. That's it — no code changes needed anywhere else.
 */

/**
 * Returns the full registry as a flat list of section descriptors.
 * Only sections with 'enabled' => true are used in generation.
 *
 * @return array<int, array<string, mixed>>
 */
function getQuestionRegistry(): array
{
    return [

        // ============================================================
        // LEVEL I  — fully enabled
        // ============================================================

        // ── Paper A ─────────────────────────────────────────────────
        [
            'id'             => 'L1-A-1',
            'level'          => 1,
            'paper'          => 'A',
            'order'          => 1,
            'label'          => 'Direct Addition & Subtraction – 3 one-digit numbers',
            'kind'           => 'direct_addsub',
            'operandCount'   => 3,
            'digitsMin'      => 1,
            'digitsMax'      => 1,
            'operations'     => ['+', '-'],
            'requiresAbacus' => false,
            'group'          => 'A1',
            'enabled'        => true,
        ],

        // ── Paper B ─────────────────────────────────────────────────
        [
            'id'             => 'L1-B-1',
            'level'          => 1,
            'paper'          => 'B',
            'order'          => 1,
            'label'          => 'Direct Addition & Subtraction – 3 one-digit numbers',
            'kind'           => 'direct_addsub',
            'operandCount'   => 3,
            'digitsMin'      => 1,
            'digitsMax'      => 1,
            'operations'     => ['+', '-'],
            'requiresAbacus' => false,
            'group'          => 'B1',
            'enabled'        => true,
        ],
        [
            'id'             => 'L1-B-2',
            'level'          => 1,
            'paper'          => 'B',
            'order'          => 2,
            'label'          => 'Addition & Subtraction – 3 one-digit numbers',
            'kind'           => 'mixed_addsub',
            'operandCount'   => 3,
            'digitsMin'      => 1,
            'digitsMax'      => 1,
            'operations'     => ['+', '-'],
            'requiresAbacus' => false,
            'group'          => 'B2',
            'enabled'        => true,
        ],

        // ── Paper C ─────────────────────────────────────────────────
        [
            'id'             => 'L1-C-1',
            'level'          => 1,
            'paper'          => 'C',
            'order'          => 1,
            'label'          => 'Direct Addition & Subtraction – 3 one-digit numbers',
            'kind'           => 'direct_addsub',
            'operandCount'   => 3,
            'digitsMin'      => 1,
            'digitsMax'      => 1,
            'operations'     => ['+', '-'],
            'requiresAbacus' => false,
            'group'          => 'C1',
            'enabled'        => true,
        ],
        [
            'id'             => 'L1-C-2',
            'level'          => 1,
            'paper'          => 'C',
            'order'          => 2,
            'label'          => 'Addition & Subtraction – 3 one-digit numbers',
            'kind'           => 'mixed_addsub',
            'operandCount'   => 3,
            'digitsMin'      => 1,
            'digitsMax'      => 1,
            'operations'     => ['+', '-'],
            'requiresAbacus' => false,
            'group'          => 'C2',
            'enabled'        => true,
        ],
        [
            'id'             => 'L1-C-3',
            'level'          => 1,
            'paper'          => 'C',
            'order'          => 3,
            'label'          => 'Addition & Subtraction – 5 one-digit numbers',
            'kind'           => 'mixed_addsub',
            'operandCount'   => 5,
            'digitsMin'      => 1,
            'digitsMax'      => 1,
            'operations'     => ['+', '-'],
            'requiresAbacus' => false,
            'group'          => 'C3',
            'enabled'        => true,
        ],

        // ── Paper D ─────────────────────────────────────────────────
        [
            'id'             => 'L1-D-1',
            'level'          => 1,
            'paper'          => 'D',
            'order'          => 1,
            'label'          => 'Addition & Subtraction – 5 one-digit numbers',
            'kind'           => 'mixed_addsub',
            'operandCount'   => 5,
            'digitsMin'      => 1,
            'digitsMax'      => 1,
            'operations'     => ['+', '-'],
            'requiresAbacus' => true,
            'group'          => 'D1',
            'enabled'        => true,
        ],
        [
            'id'             => 'L1-D-2',
            'level'          => 1,
            'paper'          => 'D',
            'order'          => 2,
            'label'          => 'Addition & Subtraction – 7 one-digit numbers',
            'kind'           => 'mixed_addsub',
            'operandCount'   => 7,
            'digitsMin'      => 1,
            'digitsMax'      => 1,
            'operations'     => ['+', '-'],
            'requiresAbacus' => true,
            'group'          => 'D2',
            'enabled'        => true,
        ],
        [
            'id'             => 'L1-D-3',
            'level'          => 1,
            'paper'          => 'D',
            'order'          => 3,
            'label'          => 'Addition & Subtraction – 10 one-digit numbers',
            'kind'           => 'mixed_addsub',
            'operandCount'   => 10,
            'digitsMin'      => 1,
            'digitsMax'      => 1,
            'operations'     => ['+', '-'],
            'requiresAbacus' => true,
            'group'          => 'D3',
            'enabled'        => true,
        ],

        // ============================================================
        // LEVEL II  — DISABLED STUBS (add real definitions when ready)
        // To enable: replace stub entries with real data, set enabled=>true
        // ============================================================

        // Level II Paper A stubs
        [
            'id' => 'L2-A-1', 'level' => 2, 'paper' => 'A', 'order' => 1,
            'label' => 'STUB – 3 one-digit numbers', 'kind' => 'mixed_addsub',
            'operandCount' => 3, 'digitsMin' => 1, 'digitsMax' => 1,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L2A1', 'enabled' => false,
        ],
        [
            'id' => 'L2-A-2', 'level' => 2, 'paper' => 'A', 'order' => 2,
            'label' => 'STUB – 5 one-digit numbers', 'kind' => 'mixed_addsub',
            'operandCount' => 5, 'digitsMin' => 1, 'digitsMax' => 1,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L2A2', 'enabled' => false,
        ],
        [
            'id' => 'L2-A-3', 'level' => 2, 'paper' => 'A', 'order' => 3,
            'label' => 'STUB – 3 two-digit numbers', 'kind' => 'mixed_addsub',
            'operandCount' => 3, 'digitsMin' => 2, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => true,
            'group' => 'L2A3', 'enabled' => false,
        ],

        // Level II Paper B stubs
        [
            'id' => 'L2-B-1', 'level' => 2, 'paper' => 'B', 'order' => 1,
            'label' => 'STUB – 3 one-digit numbers', 'kind' => 'mixed_addsub',
            'operandCount' => 3, 'digitsMin' => 1, 'digitsMax' => 1,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L2B1', 'enabled' => false,
        ],
        [
            'id' => 'L2-B-2', 'level' => 2, 'paper' => 'B', 'order' => 2,
            'label' => 'STUB – 5 one-digit numbers', 'kind' => 'mixed_addsub',
            'operandCount' => 5, 'digitsMin' => 1, 'digitsMax' => 1,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L2B2', 'enabled' => false,
        ],
        [
            'id' => 'L2-B-3', 'level' => 2, 'paper' => 'B', 'order' => 3,
            'label' => 'STUB – 3 two-digit numbers (a)', 'kind' => 'mixed_addsub',
            'operandCount' => 3, 'digitsMin' => 2, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => true,
            'group' => 'L2B3', 'enabled' => false,
        ],
        [
            'id' => 'L2-B-4', 'level' => 2, 'paper' => 'B', 'order' => 4,
            'label' => 'STUB – 3 two-digit numbers (b)', 'kind' => 'mixed_addsub',
            'operandCount' => 3, 'digitsMin' => 2, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => true,
            'group' => 'L2B4', 'enabled' => false,
        ],

        // Level II Paper C stub  ⚠️ OPEN QUESTION — column split ambiguous in PDF
        // Resolve from PDF before enabling.
        [
            'id' => 'L2-C-AMBIGUOUS', 'level' => 2, 'paper' => 'C', 'order' => 1,
            'label' => 'STUB – Level II Paper C (ambiguous — resolve from PDF)',
            'kind' => 'mixed_addsub',
            'operandCount' => 3, 'digitsMin' => 1, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L2C1', 'enabled' => false,
        ],

        // Level II Paper D stubs
        [
            'id' => 'L2-D-1', 'level' => 2, 'paper' => 'D', 'order' => 1,
            'label' => 'STUB – 3 two-digit numbers', 'kind' => 'mixed_addsub',
            'operandCount' => 3, 'digitsMin' => 2, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => true,
            'group' => 'L2D1', 'enabled' => false,
        ],
        [
            'id' => 'L2-D-2', 'level' => 2, 'paper' => 'D', 'order' => 2,
            'label' => 'STUB – 5 two-digit numbers', 'kind' => 'mixed_addsub',
            'operandCount' => 5, 'digitsMin' => 2, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => true,
            'group' => 'L2D2', 'enabled' => false,
        ],
        [
            'id' => 'L2-D-3', 'level' => 2, 'paper' => 'D', 'order' => 3,
            'label' => 'STUB – 5 one-to-two-digit numbers', 'kind' => 'mixed_addsub',
            'operandCount' => 5, 'digitsMin' => 1, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => true,
            'group' => 'L2D3', 'enabled' => false,
        ],

        // ============================================================
        // LEVELS III–VI and Advanced — disabled stubs
        // Insert real definitions one level at a time.
        // ============================================================

        // Level III stubs
        [
            'id' => 'L3-A-STUB', 'level' => 3, 'paper' => 'A', 'order' => 1,
            'label' => 'STUB – Level III Paper A', 'kind' => 'mixed_addsub',
            'operandCount' => 7, 'digitsMin' => 1, 'digitsMax' => 1,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L3A1', 'enabled' => false,
        ],
        [
            'id' => 'L3-B-STUB', 'level' => 3, 'paper' => 'B', 'order' => 1,
            'label' => 'STUB – Level III Paper B', 'kind' => 'mixed_addsub',
            'operandCount' => 7, 'digitsMin' => 1, 'digitsMax' => 1,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L3B1', 'enabled' => false,
        ],
        [
            'id' => 'L3-C-STUB', 'level' => 3, 'paper' => 'C', 'order' => 1,
            'label' => 'STUB – Level III Paper C (1-digit × 1-digit)', 'kind' => 'multiply',
            'operandCount' => 2, 'digitsMin' => 1, 'digitsMax' => 1,
            'operations' => ['x'], 'requiresAbacus' => true,
            'group' => 'L3C1', 'enabled' => false,
        ],
        [
            'id' => 'L3-D-STUB', 'level' => 3, 'paper' => 'D', 'order' => 1,
            'label' => 'STUB – Level III Paper D', 'kind' => 'mixed_addsub',
            'operandCount' => 5, 'digitsMin' => 2, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L3D1', 'enabled' => false,
        ],

        // Level IV stubs
        [
            'id' => 'L4-A-STUB', 'level' => 4, 'paper' => 'A', 'order' => 1,
            'label' => 'STUB – Level IV Paper A', 'kind' => 'mixed_addsub',
            'operandCount' => 10, 'digitsMin' => 1, 'digitsMax' => 1,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L4A1', 'enabled' => false,
        ],
        [
            'id' => 'L4-B-STUB', 'level' => 4, 'paper' => 'B', 'order' => 1,
            'label' => 'STUB – Level IV Paper B', 'kind' => 'mixed_addsub',
            'operandCount' => 10, 'digitsMin' => 1, 'digitsMax' => 1,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L4B1', 'enabled' => false,
        ],
        [
            'id' => 'L4-C-STUB', 'level' => 4, 'paper' => 'C', 'order' => 1,
            'label' => 'STUB – Level IV Paper C (2-digit × 1-digit)', 'kind' => 'multiply',
            'operandCount' => 2, 'digitsMin' => 1, 'digitsMax' => 2,
            'operations' => ['x'], 'requiresAbacus' => true,
            'group' => 'L4C1', 'enabled' => false,
        ],
        [
            'id' => 'L4-D-STUB', 'level' => 4, 'paper' => 'D', 'order' => 1,
            'label' => 'STUB – Level IV Paper D', 'kind' => 'mixed_addsub',
            'operandCount' => 7, 'digitsMin' => 2, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L4D1', 'enabled' => false,
        ],

        // Level V stubs
        [
            'id' => 'L5-A-STUB', 'level' => 5, 'paper' => 'A', 'order' => 1,
            'label' => 'STUB – Level V Paper A', 'kind' => 'mixed_addsub',
            'operandCount' => 7, 'digitsMin' => 2, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L5A1', 'enabled' => false,
        ],
        [
            'id' => 'L5-B-STUB', 'level' => 5, 'paper' => 'B', 'order' => 1,
            'label' => 'STUB – Level V Paper B (3-digit × 1-digit)', 'kind' => 'multiply',
            'operandCount' => 2, 'digitsMin' => 1, 'digitsMax' => 3,
            'operations' => ['x'], 'requiresAbacus' => true,
            'group' => 'L5B1', 'enabled' => false,
        ],
        [
            'id' => 'L5-C-STUB', 'level' => 5, 'paper' => 'C', 'order' => 1,
            'label' => 'STUB – Level V Paper C (2-digit ÷ 1-digit)', 'kind' => 'divide',
            'operandCount' => 2, 'digitsMin' => 1, 'digitsMax' => 2,
            'operations' => ['/'], 'requiresAbacus' => true,
            'group' => 'L5C1', 'enabled' => false,
        ],
        [
            'id' => 'L5-D-STUB', 'level' => 5, 'paper' => 'D', 'order' => 1,
            'label' => 'STUB – Level V Paper D', 'kind' => 'mixed_addsub',
            'operandCount' => 7, 'digitsMin' => 3, 'digitsMax' => 3,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L5D1', 'enabled' => false,
        ],

        // Level VI stubs
        [
            'id' => 'L6-A-STUB', 'level' => 6, 'paper' => 'A', 'order' => 1,
            'label' => 'STUB – Level VI Paper A', 'kind' => 'mixed_addsub',
            'operandCount' => 10, 'digitsMin' => 2, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L6A1', 'enabled' => false,
        ],
        [
            'id' => 'L6-B-STUB', 'level' => 6, 'paper' => 'B', 'order' => 1,
            'label' => 'STUB – Level VI Paper B (4-digit × 1-digit)', 'kind' => 'multiply',
            'operandCount' => 2, 'digitsMin' => 1, 'digitsMax' => 4,
            'operations' => ['x'], 'requiresAbacus' => true,
            'group' => 'L6B1', 'enabled' => false,
        ],
        [
            'id' => 'L6-C-STUB', 'level' => 6, 'paper' => 'C', 'order' => 1,
            'label' => 'STUB – Level VI Paper C (4-digit ÷ 1-digit)', 'kind' => 'divide',
            'operandCount' => 2, 'digitsMin' => 1, 'digitsMax' => 4,
            'operations' => ['/'], 'requiresAbacus' => true,
            'group' => 'L6C1', 'enabled' => false,
        ],
        [
            'id' => 'L6-D-STUB', 'level' => 6, 'paper' => 'D', 'order' => 1,
            'label' => 'STUB – Level VI Paper D', 'kind' => 'mixed_addsub',
            'operandCount' => 15, 'digitsMin' => 2, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'L6D1', 'enabled' => false,
        ],

        // Advanced Level stubs
        [
            'id' => 'LA-A-STUB', 'level' => 7, 'paper' => 'A', 'order' => 1,
            'label' => 'STUB – Advanced Paper A', 'kind' => 'mixed_addsub',
            'operandCount' => 7, 'digitsMin' => 3, 'digitsMax' => 3,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'LAA1', 'enabled' => false,
        ],
        [
            'id' => 'LA-B-STUB', 'level' => 7, 'paper' => 'B', 'order' => 1,
            'label' => 'STUB – Advanced Paper B (5-digit × 1-digit)', 'kind' => 'multiply',
            'operandCount' => 2, 'digitsMin' => 1, 'digitsMax' => 5,
            'operations' => ['x'], 'requiresAbacus' => true,
            'group' => 'LAB1', 'enabled' => false,
        ],
        [
            'id' => 'LA-C-STUB', 'level' => 7, 'paper' => 'C', 'order' => 1,
            'label' => 'STUB – Advanced Paper C (3-digit ÷ 2-digit)', 'kind' => 'divide',
            'operandCount' => 2, 'digitsMin' => 2, 'digitsMax' => 3,
            'operations' => ['/'], 'requiresAbacus' => true,
            'group' => 'LAC1', 'enabled' => false,
        ],
        [
            'id' => 'LA-D-STUB', 'level' => 7, 'paper' => 'D', 'order' => 1,
            'label' => 'STUB – Advanced Paper D', 'kind' => 'mixed_addsub',
            'operandCount' => 15, 'digitsMin' => 2, 'digitsMax' => 2,
            'operations' => ['+', '-'], 'requiresAbacus' => false,
            'group' => 'LAD1', 'enabled' => false,
        ],
    ];
}

/**
 * Returns only enabled sections for a given level and paper, sorted by order.
 *
 * @param int    $level 1–7
 * @param string $paper 'A'|'B'|'C'|'D'
 * @return array<int, array<string, mixed>>
 */
function getSectionsForPaper(int $level, string $paper): array
{
    $paper = strtoupper($paper);
    $all   = getQuestionRegistry();
    $out   = [];
    foreach ($all as $section) {
        if ($section['level'] === $level && $section['paper'] === $paper && $section['enabled']) {
            $out[] = $section;
        }
    }
    usort($out, fn($a, $b) => $a['order'] <=> $b['order']);
    return $out;
}

/**
 * Returns meta for every level: which papers have any enabled sections.
 * Used to build the level/paper selector in the UI.
 *
 * @return array<int, array{level: int, label: string, papers: array<string, array{label: string, sectionCount: int, enabled: bool}>}>
 */
function getLevelMeta(): array
{
    $levelNames = [
        1 => 'Level I',
        2 => 'Level II',
        3 => 'Level III',
        4 => 'Level IV',
        5 => 'Level V',
        6 => 'Level VI',
        7 => 'Advanced',
    ];

    // Build a map: level -> paper -> [sections]
    $map = [];
    foreach (getQuestionRegistry() as $s) {
        $map[$s['level']][$s['paper']][] = $s;
    }

    $result = [];
    foreach ($levelNames as $lvl => $name) {
        $papers = [];
        foreach (['A', 'B', 'C', 'D'] as $p) {
            $sections     = $map[$lvl][$p] ?? [];
            $enabledCount = count(array_filter($sections, fn($s) => $s['enabled']));
            $papers[$p]   = [
                'label'        => 'Paper ' . $p,
                'sectionCount' => $enabledCount,
                'enabled'      => $enabledCount > 0,
            ];
        }
        $levelEnabled  = count(array_filter($papers, fn($p) => $p['enabled'])) > 0;
        $result[$lvl]  = [
            'level'   => $lvl,
            'label'   => $name,
            'enabled' => $levelEnabled,
            'papers'  => $papers,
        ];
    }
    return $result;
}

