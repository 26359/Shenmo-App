<?php
/**
 * practice.php – Student Practice Landing Page
 * ─────────────────────────────────────────────
 * After login this is the first page a student sees.
 * Rules followed: same as Competition mode
 *   • 300-second (5-minute) timer per paper
 *   • 1 pt correct, 0 pt incorrect / blank
 *   • No hints, no feedback during the session
 *   • Answer entry via numeric keypad only
 *   • Abacus required for Paper D questions
 *   • Skipping a question in "Strict" sub-mode zeros later answers
 *   • 20-point penalty if answers are submitted after time ends
 * Unlike competition, Practice mode lets the student:
 *   • Choose any enabled level / paper
 *   • Move freely between questions (no sequential lock)
 *   • Review results immediately
 */
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || empty($_SESSION['student_id'])) {
    header("Location: login.php"); exit;
}

require_once __DIR__ . '/config/question_registry.php';
require_once __DIR__ . '/config/question_generator.php';

$student_name = htmlspecialchars($_SESSION['full_name'] ?? 'Student');
$levelMeta    = getLevelMeta();

// ── Inline PHP: build registry JSON for the JS ─────────────────────────────
$registryJs = [];
foreach ($levelMeta as $lvl => $lm) {
    $papersJs = [];
    foreach ($lm['papers'] as $p => $pm) {
        $sections = getSectionsForPaper($lvl, $p);
        $sectionLabels = array_map(fn($s) => $s['label'], $sections);
        $papersJs[$p] = [
            'label'     => $pm['label'],
            'enabled'   => $pm['enabled'],
            'sections'  => $sectionLabels,
        ];
    }
    $registryJs[$lvl] = [
        'label'   => $lm['label'],
        'enabled' => $lm['enabled'],
        'papers'  => $papersJs,
    ];
}
$registryJson = json_encode($registryJs, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practice – Abacus Academy</title>
    <style>
        /* ── Base ─────────────────────────────────────────────────────── */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
               background: #f8fafc; color: #1e293b; min-height: 100vh; }
        button, select { font: inherit; cursor: pointer; }

        /* ── Layout ───────────────────────────────────────────────────── */
        .dashboard { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px; background: linear-gradient(180deg,#fff 0%,#f8fafc 100%);
            border-right: 1px solid #e2e8f0; padding: 20px 0; position: fixed;
            height: 100vh; overflow-y: auto; box-shadow: 2px 0 10px rgba(0,0,0,.05);
            z-index: 200; transition: transform .3s;
        }
        .logo { text-align: center; padding: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px; }
        .logo h1 { color: #ef4444; font-size: 1.3rem; font-weight: 700; }
        .logo p  { color: #94a3b8; font-size: .8rem; margin-top: 4px; }
        .nav-menu { list-style: none; padding: 0 10px; }
        .nav-item { margin-bottom: 4px; }
        .nav-link {
            display: flex; align-items: center; gap: 12px; padding: 11px 14px;
            color: #475569; text-decoration: none; border-radius: 12px;
            transition: all .2s; font-weight: 500; font-size: .92rem;
        }
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg,#fee2e2,#fef2f2); color: #ef4444;
        }
        .nav-link .icon { font-size: 1.1rem; width: 22px; text-align: center; }
        .main-content { flex: 1; margin-left: 260px; padding: 25px; min-width: 0; }

        /* ── Topbar ───────────────────────────────────────────────────── */
        .topbar {
            background: #fff; padding: 14px 20px; border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 22px;
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
        }
        .topbar h1 { color: #1e293b; font-size: 1.3rem; font-weight: 700; }
        .topbar p  { color: #64748b; font-size: .88rem; margin-top: 3px; }
        .student-badge {
            background: linear-gradient(135deg,#ef4444,#dc2626); color: #fff;
            padding: 6px 14px; border-radius: 20px; font-size: .85rem; font-weight: 600;
            white-space: nowrap;
        }

        /* ── Screens ──────────────────────────────────────────────────── */
        .screen { display: none; }
        .screen.active { display: block; animation: fadeIn .25s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

        /* ── Setup Screen ─────────────────────────────────────────────── */
        .setup-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05); padding: 28px; margin-bottom: 20px;
        }
        .setup-card h2 { color: #1e293b; font-size: 1.5rem; font-weight: 700; margin-bottom: 8px; }
        .setup-card > p { color: #64748b; line-height: 1.6; margin-bottom: 22px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 16px; margin: 20px 0; }
        .form-group label { display: block; color: #475569; font-size: .88rem; font-weight: 600; margin-bottom: 8px; }
        .form-control {
            width: 100%; border: 2px solid #e2e8f0; border-radius: 10px;
            padding: 12px 14px; color: #1e293b; background: #fff; outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-control:focus { border-color: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,.12); }
        .form-control:disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }
        .rules-panel {
            background: #f8fafc; border-left: 4px solid #ef4444; border-radius: 10px;
            padding: 16px 18px; color: #475569; line-height: 1.65; font-size: .92rem;
            margin: 20px 0;
        }
        .rules-panel strong { color: #1e293b; }
        .sections-preview {
            background: #fef2f2; border: 1px solid #fee2e2; border-radius: 10px;
            padding: 14px 18px; margin: 14px 0; font-size: .88rem; color: #475569;
        }
        .sections-preview strong { color: #1e293b; display: block; margin-bottom: 8px; }
        .sections-preview ol { padding-left: 18px; }
        .sections-preview li { margin-bottom: 4px; }

        /* ── Buttons ─────────────────────────────────────────────────── */
        .btn {
            border: none; border-radius: 10px; padding: 12px 22px; font-weight: 600;
            transition: all .2s; display: inline-flex; align-items: center;
            justify-content: center; gap: 8px;
        }
        .btn-primary { background: linear-gradient(135deg,#ef4444,#dc2626); color: #fff; }
        .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(239,68,68,.35); }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover:not(:disabled) { background: #cbd5e1; }
        .btn-danger { background: linear-gradient(135deg,#ef4444,#f87171); color: #fff; }
        .btn:disabled { opacity: .5; cursor: not-allowed; transform: none !important; box-shadow: none !important; }
        .btn-start { width: 100%; margin-top: 22px; padding: 14px 22px; font-size: 1rem; }

        /* ── Practice screen header ───────────────────────────────────── */
        .prac-header {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05); padding: 18px 22px;
            margin-bottom: 20px;
            display: grid; grid-template-columns: 1fr auto auto; gap: 16px; align-items: center;
        }
        .prac-title h2 { color: #1e293b; font-size: 1.2rem; font-weight: 700; }
        .prac-title p  { color: #64748b; font-size: .86rem; margin-top: 4px; }
        .timer-box, .progress-box {
            min-width: 130px; text-align: center; padding: 10px 16px;
            border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0;
        }
        .timer-box.warning { background: #fef3c7; border-color: #fbbf24; }
        .timer-box.danger  { background: #fee2e2; border-color: #ef4444; animation: pulseT 1s infinite; }
        @keyframes pulseT { 50% { box-shadow: 0 0 0 5px rgba(239,68,68,.12); } }
        .timer-label, .progress-label { display: block; color: #64748b; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; }
        .timer-value { display: block; color: #1e293b; font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums; margin-top: 2px; }
        .timer-box.warning .timer-value, .timer-box.danger .timer-value { color: inherit; }
        .progress-value { display: block; color: #1e293b; font-size: 1.05rem; font-weight: 700; margin-top: 3px; }

        /* ── Q layout ─────────────────────────────────────────────────── */
        .prac-layout { display: grid; grid-template-columns: minmax(0,1fr) 360px; gap: 20px; align-items: start; }
        .question-card, .abacus-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05); padding: 24px;
        }
        .question-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .question-meta strong { color: #ef4444; font-size: .92rem; }
        .question-type-label { color: #64748b; font-size: .82rem; }
        .question-text {
            min-height: 110px; display: flex; align-items: center; justify-content: center;
            text-align: center;
            background: linear-gradient(135deg,#fef2f2,#fef2f2);
            border: 1px solid #fee2e2; border-radius: 14px; padding: 28px 20px;
            color: #1e293b; font-size: clamp(1.35rem,4vw,2.35rem); font-weight: 700;
            letter-spacing: .02em; margin-bottom: 18px; overflow-wrap: anywhere;
        }
        .vertical-expression { display: inline-flex; flex-direction: column; align-items: flex-end; min-width: 110px; font-variant-numeric: tabular-nums; line-height: 1.18; }
        .vertical-expression .op-row { white-space: nowrap; }
        .vertical-expression .op-sign { color: #64748b; margin-right: 4px; }
        .vertical-expression .expr-line { width: 100%; border-top: 3px solid #1e293b; margin: 4px 0 2px; }
        .vertical-expression .ans-row { color: #ef4444; }
        .abacus-req {
            display: inline-flex; align-items: center; gap: 6px;
            background: #fef3c7; color: #92400e; border-radius: 20px;
            padding: 6px 12px; font-size: .8rem; font-weight: 700; margin-bottom: 14px;
        }
        .answer-display {
            min-height: 62px; display: flex; align-items: center; justify-content: center;
            border: 2px dashed #cbd5e1; border-radius: 12px; background: #f8fafc;
            color: #1e293b; font-size: 2rem; font-weight: 700;
            font-variant-numeric: tabular-nums; margin-bottom: 18px; padding: 8px 16px;
        }
        .answer-display.filled { border-style: solid; border-color: #ef4444; background: #fef2f2; }
        .answer-display.locked { border-color: #94a3b8; color: #64748b; background: #f1f5f9; }
        .keypad { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; max-width: 330px; margin: 0 auto; }
        .keypad button {
            border: 1px solid #e2e8f0; background: #fff; border-radius: 10px;
            padding: 15px 10px; color: #1e293b; font-size: 1.15rem; font-weight: 700;
            box-shadow: 0 2px 0 #e2e8f0; transition: all .15s;
        }
        .keypad button:hover:not(:disabled) { background: #fef2f2; border-color: #ef4444; transform: translateY(-1px); }
        .keypad button:active:not(:disabled) { transform: translateY(1px); box-shadow: none; }
        .keypad button.utility { color: #92400e; background: #fffbeb; border-color: #fde68a; }
        .q-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
        .q-actions .btn { flex: 1 1 130px; }
        .jump-picker { margin-top: 16px; }
        .jump-picker label { display: block; color: #64748b; font-size: .84rem; font-weight: 600; margin-bottom: 7px; }
        .abacus-card.hidden { display: none; }
        .abacus-card { position: sticky; top: 20px; }
        .abacus-card h3 { color: #1e293b; font-size: 1.08rem; font-weight: 700; margin-bottom: 5px; }
        .abacus-card > p { color: #64748b; font-size: .84rem; line-height: 1.5; margin-bottom: 14px; }
        .abacus-frame { width: 100%; min-height: 245px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; display: block; }

        /* ── Section badge ────────────────────────────────────────────── */
        .section-badge {
            display: inline-block; background: #fef3c7; color: #92400e;
            border-radius: 8px; padding: 4px 10px; font-size: .78rem; font-weight: 700;
            margin-bottom: 14px;
        }

        /* ── Results ─────────────────────────────────────────────────── */
        .results-layout { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 20px; }
        .results-card {
            background: linear-gradient(135deg,#fef2f2,#fef2f2); border: 1px solid #fee2e2;
            border-radius: 18px; padding: 28px; text-align: center;
        }
        .results-card h2 { color: #1e293b; font-size: 1.4rem; margin-bottom: 20px; }
        .result-score { font-size: 3.5rem; font-weight: 800; color: #ef4444; line-height: 1; font-variant-numeric: tabular-nums; }
        .result-score small { font-size: 1.2rem; color: #64748b; font-weight: 600; }
        .result-details { display: grid; grid-template-columns: repeat(auto-fit,minmax(120px,1fr)); gap: 12px; margin: 24px 0; }
        .result-detail { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; }
        .result-detail span { display: block; color: #64748b; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
        .result-detail strong { display: block; color: #1e293b; font-size: 1.15rem; margin-top: 5px; }
        .mistakes-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 24px; }
        .mistakes-card h3 { color: #1e293b; font-size: 1.15rem; margin-bottom: 14px; }
        .mistake-list { display: grid; gap: 10px; max-height: 430px; overflow-y: auto; padding-right: 5px; }
        .mistake-item { border: 1px solid #fee2e2; background: #fff7f7; border-radius: 10px; padding: 12px 14px; }
        .mistake-q { color: #1e293b; font-weight: 700; margin-bottom: 5px; }
        .mistake-ans { color: #64748b; font-size: .86rem; }
        .mistake-ans strong { color: #16a34a; }
        .empty-state { color: #64748b; text-align: center; padding: 30px 10px; line-height: 1.6; }

        /* ── Toast ─────────────────────────────────────────────────────── */
        .toast {
            position: fixed; right: 22px; bottom: 22px; z-index: 600;
            background: #1e293b; color: #fff; border-radius: 10px;
            padding: 13px 18px; box-shadow: 0 8px 24px rgba(0,0,0,.2);
            font-size: .9rem; opacity: 0; transform: translateY(10px);
            pointer-events: none; transition: all .2s; max-width: 360px;
        }
        .toast.visible { opacity: 1; transform: translateY(0); }
        .toast.error { background: #b91c1c; }

        /* ── Modal ─────────────────────────────────────────────────────── */
        .modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 500; display: none; align-items: center; justify-content: center; padding: 20px; }
        .modal-backdrop.visible { display: flex; }
        .modal { background: #fff; border-radius: 16px; padding: 26px; max-width: 430px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.25); animation: mIn .2s ease; }
        @keyframes mIn { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: scale(1); } }
        .modal h3 { color: #1e293b; font-size: 1.2rem; margin-bottom: 10px; }
        .modal p  { color: #64748b; line-height: 1.6; margin-bottom: 20px; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; }

        /* ── Responsive ────────────────────────────────────────────────── */
        @media(max-width:1050px) { .prac-layout { grid-template-columns: 1fr; } .abacus-card { position: static; } .results-layout { grid-template-columns: 1fr; } }
        @media(max-width:768px)  { .sidebar { transform: translateX(-100%); } .sidebar.open { transform: translateX(0); } .main-content { margin-left: 0; padding: 14px; } .prac-header { grid-template-columns: 1fr; text-align: center; } }
        @media(max-width:480px)  { .question-text { min-height: 90px; font-size: 1.25rem; } .keypad { gap: 7px; } .keypad button { padding: 13px 6px; font-size: 1rem; } }
    </style>
</head>
<body>
<div class="dashboard">
    <!-- ── Sidebar ─────────────────────────────────────────────────────── -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <h1>🎓 Abacus Academy</h1>
            <p>Learning Portal</p>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="practice.php"        class="nav-link active"><span class="icon">💪</span><span>Practice</span></a></li>
            <li class="nav-item"><a href="competition.php"     class="nav-link"><span class="icon">🏆</span><span>Competition</span></a></li>
            <li class="nav-item"><a href="student_dashboard.php" class="nav-link"><span class="icon">📊</span><span>Dashboard</span></a></li>
            <li class="nav-item"><a href="leaderboard.php"     class="nav-link"><span class="icon">🏅</span><span>Leaderboard</span></a></li>
            <li class="nav-item"><a href="achievements.php"    class="nav-link"><span class="icon">⭐</span><span>Achievements</span></a></li>
            <li class="nav-item"><a href="profile.php"         class="nav-link"><span class="icon">👤</span><span>Profile</span></a></li>
            <li class="nav-item"><a href="logout.php"          class="nav-link"><span class="icon">🚪</span><span>Logout</span></a></li>
        </ul>
    </aside>

    <!-- ── Main ───────────────────────────────────────────────────────── -->
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Practice</h1>
                <p>Pick a level and paper — competition rules apply, no hints</p>
            </div>
            <span class="student-badge"><?= $student_name ?></span>
        </div>

        <!-- ═══════════════════════════ SETUP SCREEN ═════════════════════ -->
        <section class="screen active" id="setupScreen">
            <div class="setup-card">
                <h2>Choose a practice paper</h2>
                <p>Competition rules are enforced: 5-minute timer, 1 point per correct answer, no hints.
                   You can move freely between questions. Review mistakes when done.</p>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="levelSelect">Level</label>
                        <select id="levelSelect" class="form-control">
                            <?php foreach ($levelMeta as $lvl => $lm): ?>
                            <option value="<?= $lvl ?>" <?= !$lm['enabled'] ? 'disabled' : '' ?>>
                                <?= $lm['label'] ?><?= !$lm['enabled'] ? ' (coming soon)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="paperSelect">Paper</label>
                        <select id="paperSelect" class="form-control">
                            <!-- filled by JS when level changes -->
                        </select>
                    </div>
                </div>

                <div id="sectionsPreview" class="sections-preview" style="display:none">
                    <strong>📋 Sections in this paper:</strong>
                    <ol id="sectionsList"></ol>
                </div>

                <div class="rules-panel">
                    <strong>Practice rules (same as Competition):</strong>
                    Each correct answer earns 1 point; incorrect or blank answers earn 0.
                    Timer: <strong>5 minutes</strong> per paper. Answering after time ends applies a
                    <strong>20-point penalty</strong>. Paper D requires the Virtual Abacus.
                    No calculators or phones allowed. Results and mistake review appear immediately.
                </div>

                <button type="button" id="startBtn" class="btn btn-primary btn-start">
                    ▶ Start Practice
                </button>
            </div>
        </section>

        <!-- ═══════════════════════════ PRACTICE SCREEN ══════════════════ -->
        <section class="screen" id="practiceScreen">
            <div class="prac-header">
                <div class="prac-title">
                    <h2 id="pracTitle">Practice Paper</h2>
                    <p id="pracSubtitle">Answer freely • Submit before time ends</p>
                </div>
                <div class="timer-box" id="timerBox">
                    <span class="timer-label">Remaining</span>
                    <span class="timer-value" id="timerValue">05:00</span>
                </div>
                <div class="progress-box">
                    <span class="progress-label">Progress</span>
                    <span class="progress-value" id="progressValue">1 / 0</span>
                </div>
            </div>

            <div class="prac-layout">
                <div class="question-card">
                    <div class="question-meta">
                        <strong id="qCounter">Question 1</strong>
                        <span class="question-type-label" id="qTypeLabel"></span>
                    </div>
                    <div id="abacusReqBadge" class="abacus-req" style="display:none">🧮 Virtual Abacus required</div>
                    <div id="sectionBadge" class="section-badge" style="display:none"></div>
                    <div class="question-text" id="questionText">Loading…</div>
                    <div class="answer-display" id="answerDisplay">Enter your answer</div>
                    <div class="keypad" id="keypad">
                        <button type="button" data-key="1">1</button>
                        <button type="button" data-key="2">2</button>
                        <button type="button" data-key="3">3</button>
                        <button type="button" data-key="4">4</button>
                        <button type="button" data-key="5">5</button>
                        <button type="button" data-key="6">6</button>
                        <button type="button" data-key="7">7</button>
                        <button type="button" data-key="8">8</button>
                        <button type="button" data-key="9">9</button>
                        <button type="button" class="utility" data-key="clear">Clear</button>
                        <button type="button" data-key="0">0</button>
                        <button type="button" class="utility" data-key="back">⌫</button>
                        <button type="button" class="utility" data-key="sign" style="grid-column:span 3;">± Toggle sign</button>
                    </div>
                    <div class="jump-picker">
                        <label for="qPicker">Jump to question</label>
                        <select id="qPicker" class="form-control"></select>
                    </div>
                    <div class="q-actions">
                        <button type="button" id="prevBtn" class="btn btn-secondary">← Previous</button>
                        <button type="button" id="nextBtn" class="btn btn-primary">Next →</button>
                        <button type="button" id="submitBtn" class="btn btn-danger">Submit Paper</button>
                    </div>
                </div>

                <aside class="abacus-card hidden" id="abacusCard">
                    <h3>🧮 Virtual Abacus</h3>
                    <p>Use the abacus for Paper D questions. Abacus use is recorded.</p>
                    <iframe id="abacusFrame" class="abacus-frame" title="Virtual Abacus" src="virtual_abacus.php?question_id=0"></iframe>
                </aside>
            </div>
        </section>

        <!-- ═══════════════════════════ RESULTS SCREEN ═══════════════════ -->
        <section class="screen" id="resultsScreen">
            <div class="results-layout">
                <div class="results-card">
                    <h2>🎉 Practice Results</h2>
                    <div class="result-score"><span id="rScore">0</span><small> / <span id="rMax">0</span></small></div>
                    <div class="result-details">
                        <div class="result-detail"><span>Accuracy</span><strong id="rAccuracy">0%</strong></div>
                        <div class="result-detail"><span>Time taken</span><strong id="rTime">00:00</strong></div>
                        <div class="result-detail"><span>Penalty</span><strong id="rPenalty">0 pts</strong></div>
                        <div class="result-detail"><span>Correct</span><strong id="rCorrect">0</strong></div>
                    </div>
                    <button type="button" id="practiceAgainBtn" class="btn btn-primary" style="width:100%;margin-top:10px">
                        🔄 Practice Again
                    </button>
                    <a href="competition.php" class="btn btn-secondary" style="width:100%;margin-top:10px;text-decoration:none;display:flex;align-items:center;justify-content:center;">
                        🏆 Go to Competition
                    </a>
                </div>
                <div class="mistakes-card">
                    <h3>Mistake Review</h3>
                    <div id="mistakeList" class="mistake-list">
                        <div class="empty-state">Complete a paper to see your mistakes.</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- ── Warning Modal ──────────────────────────────────────────────────── -->
<div class="modal-backdrop" id="warningModal">
    <div class="modal">
        <h3 id="warnTitle">⚠️ Time Warning</h3>
        <p id="warnMsg">One minute remaining.</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-primary" id="warnOkBtn">Continue</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function () {
    'use strict';

    /* ── Registry (built from PHP) ─────────────────────────────────── */
    const REGISTRY = <?= $registryJson ?>;

    /* ── Constants (mirror competition_api.php) ────────────────────── */
    const TIME_LIMIT_SECONDS = 300;   // 5 minutes — same as competition
    const LATE_PENALTY       = 20;    // same as LATE_PENALTY_POINTS
    const WARN_60            = 60;
    const WARN_30            = 30;

    /* ── DOM refs ──────────────────────────────────────────────────── */
    const $ = id => document.getElementById(id);
    const setupScreen   = $('setupScreen');
    const practiceScreen= $('practiceScreen');
    const resultsScreen = $('resultsScreen');
    const levelSelect   = $('levelSelect');
    const paperSelect   = $('paperSelect');
    const startBtn      = $('startBtn');
    const timerBox      = $('timerBox');
    const timerValue    = $('timerValue');
    const progressValue = $('progressValue');
    const qCounter      = $('qCounter');
    const qTypeLabel    = $('qTypeLabel');
    const questionText  = $('questionText');
    const answerDisplay = $('answerDisplay');
    const abacusReqBadge= $('abacusReqBadge');
    const sectionBadge  = $('sectionBadge');
    const abacusCard    = $('abacusCard');
    const abacusFrame   = $('abacusFrame');
    const qPicker       = $('qPicker');
    const prevBtn       = $('prevBtn');
    const nextBtn       = $('nextBtn');
    const submitBtn     = $('submitBtn');
    const warningModal  = $('warningModal');
    const toast         = $('toast');

    /* ── State ─────────────────────────────────────────────────────── */
    const state = {
        level: 1, paper: 'A',
        questions: [],      // generated locally
        currentIdx: 0,
        answers: {},        // questionIdx -> string
        abacusUsed: {},     // questionIdx -> bool
        started: false,
        ended: false,
        submitting: false,
        lateAttempt: false,
        startedAt: 0,
        endsAt: 0,
        timerId: null,
        warn60: false, warn30: false,
    };

    /* ── Utility ────────────────────────────────────────────────────── */
    function showScreen(s) {
        [setupScreen, practiceScreen, resultsScreen].forEach(x => x.classList.toggle('active', x === s));
    }
    function showToast(msg, isErr) {
        toast.textContent = msg;
        toast.classList.toggle('error', Boolean(isErr));
        toast.classList.add('visible');
        setTimeout(() => toast.classList.remove('visible'), 3500);
    }
    function fmtTime(sec) {
        const s = Math.max(0, Math.ceil(sec));
        return String(Math.floor(s/60)).padStart(2,'0') + ':' + String(s%60).padStart(2,'0');
    }

    /* ── Level / Paper selector ─────────────────────────────────────── */
    function rebuildPaperSelect() {
        const lvl = parseInt(levelSelect.value, 10);
        const meta = REGISTRY[lvl];
        paperSelect.innerHTML = '';
        if (!meta) return;
        ['A','B','C','D'].forEach(p => {
            const pm = meta.papers[p];
            const opt = document.createElement('option');
            opt.value = p;
            opt.textContent = 'Paper ' + p + (pm.enabled ? '' : ' (coming soon)');
            opt.disabled = !pm.enabled;
            paperSelect.appendChild(opt);
        });
        // Select first enabled paper
        const first = paperSelect.querySelector('option:not([disabled])');
        if (first) first.selected = true;
        updateSectionsPreview();
    }

    function updateSectionsPreview() {
        const lvl   = parseInt(levelSelect.value, 10);
        const paper = paperSelect.value;
        const meta  = REGISTRY[lvl]?.papers?.[paper];
        const preview= $('sectionsPreview');
        const list   = $('sectionsList');
        if (!meta || !meta.enabled || !meta.sections.length) { preview.style.display='none'; return; }
        list.innerHTML = meta.sections.map(s => '<li>' + s + '</li>').join('');
        preview.style.display = 'block';
    }

    levelSelect.addEventListener('change', () => { rebuildPaperSelect(); });
    paperSelect.addEventListener('change', updateSectionsPreview);
    rebuildPaperSelect(); // initialise

    /* ── Question Generator (client-side, mirrors PHP logic) ─────────
     * We call the server-side generator via practice_api.php so the same
     * PHP rules (direct-rule, independent evaluator, dedup) are used.
     * ─────────────────────────────────────────────────────────────── */
    async function loadQuestions(level, paper) {
        const resp = await fetch('practice_api.php?action=generate&level=' + level + '&paper=' + paper);
        if (!resp.ok) throw new Error('Failed to load questions');
        const body = await resp.json();
        if (body.error) throw new Error(body.error);
        return body.questions;
    }

    /* ── Start ──────────────────────────────────────────────────────── */
    startBtn.addEventListener('click', async () => {
        const lvl   = parseInt(levelSelect.value, 10);
        const paper = paperSelect.value;
        if (!REGISTRY[lvl]?.papers?.[paper]?.enabled) { showToast('That paper is coming soon.', true); return; }

        startBtn.disabled = true;
        startBtn.textContent = 'Generating questions…';

        try {
            const questions = await loadQuestions(lvl, paper);
            if (!questions || !questions.length) throw new Error('No questions returned');

            state.level     = lvl;
            state.paper     = paper;
            state.questions = questions;
            state.answers   = {};
            state.abacusUsed= {};
            state.currentIdx= 0;
            state.started   = true;
            state.ended     = false;
            state.submitting= false;
            state.lateAttempt = false;
            state.warn60    = false;
            state.warn30    = false;
            state.startedAt = Date.now();
            state.endsAt    = Date.now() + TIME_LIMIT_SECONDS * 1000;

            buildPicker();
            $('pracTitle').textContent = 'Level ' + lvl + ' — Paper ' + paper;
            $('pracSubtitle').textContent = 'Practice Mode · 5 minutes · Move freely between questions';
            timerValue.textContent = fmtTime(TIME_LIMIT_SECONDS);
            timerBox.classList.remove('warning','danger');

            showScreen(practiceScreen);
            renderQuestion();
            state.timerId = setInterval(tickTimer, 250);
        } catch (err) {
            showToast(err.message, true);
        } finally {
            startBtn.disabled = false;
            startBtn.textContent = '▶ Start Practice';
        }
    });

    /* ── Timer ──────────────────────────────────────────────────────── */
    function tickTimer() {
        if (!state.started || state.ended) return;
        const rem = Math.max(0, (state.endsAt - Date.now()) / 1000);
        timerValue.textContent = fmtTime(rem);
        timerBox.classList.toggle('warning', rem <= WARN_60 && rem > WARN_30);
        timerBox.classList.toggle('danger',  rem <= WARN_30);

        if (rem <= WARN_60 && !state.warn60) {
            state.warn60 = true;
            showWarning('⏰ One minute remaining', 'You have 1 minute left. Finish your current question or submit.');
        }
        if (rem <= WARN_30 && !state.warn30) {
            state.warn30 = true;
            showWarning('🚨 30 seconds remaining', 'Hurry! 30 seconds left. Check answers and submit.');
        }
        if (rem <= 0) autoSubmit();
    }

    function showWarning(title, msg) {
        $('warnTitle').textContent = title;
        $('warnMsg').textContent = msg;
        warningModal.classList.add('visible');
    }
    $('warnOkBtn').addEventListener('click', () => warningModal.classList.remove('visible'));

    /* ── Render question ────────────────────────────────────────────── */
    function renderQuestion() {
        const q   = state.questions[state.currentIdx];
        if (!q) return;

        qCounter.textContent     = 'Question ' + (state.currentIdx + 1);
        progressValue.textContent= (state.currentIdx + 1) + ' / ' + state.questions.length;
        qTypeLabel.textContent   = q.question_group || '';
        qPicker.value            = String(state.currentIdx);

        // Section badge
        if (q.question_group) {
            sectionBadge.textContent = 'Section ' + q.question_group;
            sectionBadge.style.display = 'inline-block';
        } else { sectionBadge.style.display = 'none'; }

        // Abacus badge
        abacusReqBadge.style.display = q.requires_abacus ? 'inline-flex' : 'none';
        abacusCard.classList.toggle('hidden', !q.requires_abacus);
        if (q.requires_abacus) {
            abacusFrame.src = 'virtual_abacus.php?question_id=' + encodeURIComponent(state.currentIdx) + '&t=' + Date.now();
        }

        // Render expression
        questionText.textContent = '';
        const expr = buildVerticalExpr(q.operands, q.operators);
        questionText.appendChild(expr);

        renderAnswer();
        setControlsEnabled(!state.ended && !state.submitting);

        prevBtn.disabled = state.currentIdx === 0 || state.ended;
        nextBtn.disabled = state.currentIdx === state.questions.length - 1 || state.ended;
    }

    function buildVerticalExpr(operands, operators) {
        const wrap = document.createElement('div');
        wrap.className = 'vertical-expression';
        operands.forEach((operand, i) => {
            const row  = document.createElement('div');
            row.className = 'op-row';
            const sign = document.createElement('span');
            sign.className = 'op-sign';
            if (i > 0) {
                const op = operators[i - 1];
                sign.textContent = op === '-' ? '−' : op === 'x' ? '×' : op === '/' ? '÷' : '';
            }
            const val = document.createElement('span');
            val.textContent = Math.abs(Number(operand));
            row.appendChild(sign); row.appendChild(val);
            wrap.appendChild(row);
        });
        const line = document.createElement('div'); line.className = 'expr-line'; wrap.appendChild(line);
        const ans  = document.createElement('div'); ans.className = 'ans-row'; ans.textContent = 'ans'; wrap.appendChild(ans);
        return wrap;
    }

    function renderAnswer() {
        const val = state.answers[state.currentIdx] ?? '';
        answerDisplay.textContent = val === '' ? 'Enter your answer' : val;
        answerDisplay.classList.toggle('filled', val !== '');
        answerDisplay.classList.toggle('locked', state.ended);
    }

    function buildPicker() {
        qPicker.innerHTML = '';
        state.questions.forEach((q, i) => {
            const opt = document.createElement('option');
            opt.value = String(i);
            opt.textContent = 'Q' + (i+1) + (q.question_group ? ' [' + q.question_group + ']' : '');
            qPicker.appendChild(opt);
        });
    }

    /* ── Controls ───────────────────────────────────────────────────── */
    function setControlsEnabled(on) {
        document.querySelectorAll('#keypad button').forEach(b => b.disabled = !on);
        qPicker.disabled  = !on;
        submitBtn.disabled = !on || state.submitting;
    }

    prevBtn.addEventListener('click', () => {
        if (state.currentIdx > 0) { state.currentIdx--; renderQuestion(); }
    });
    nextBtn.addEventListener('click', () => {
        if (state.currentIdx < state.questions.length - 1) { state.currentIdx++; renderQuestion(); }
    });
    qPicker.addEventListener('change', e => {
        state.currentIdx = parseInt(e.target.value, 10);
        renderQuestion();
    });

    document.querySelectorAll('#keypad button').forEach(btn => {
        btn.addEventListener('click', () => {
            if (state.ended) { markLate(); return; }
            const key = btn.dataset.key;
            const cur = state.answers[state.currentIdx] ?? '';
            if      (key === 'clear') state.answers[state.currentIdx] = '';
            else if (key === 'back')  state.answers[state.currentIdx] = cur.length > 1 ? cur.slice(0,-1) : (cur === '-' ? '' : cur.slice(0,-1));
            else if (key === 'sign')  state.answers[state.currentIdx] = cur.startsWith('-') ? cur.slice(1) : (cur === '' ? '' : '-' + cur);
            else {
                if (cur === '-' && key === '0') return; // no "-0"
                state.answers[state.currentIdx] = cur + key;
            }
            renderAnswer();
        });
    });

    /* ── Submit ─────────────────────────────────────────────────────── */
    submitBtn.addEventListener('click', () => doSubmit(false));

    function autoSubmit() {
        if (!state.ended && !state.submitting) {
            state.ended = true;
            clearInterval(state.timerId);
            timerValue.textContent = '00:00';
            timerBox.classList.add('danger');
            setControlsEnabled(false);
            answerDisplay.classList.add('locked');
            doSubmit(false, true);
        }
    }

    function markLate() {
        if (!state.lateAttempt && !state.submitting) {
            state.lateAttempt = true;
            showToast('Time has ended. A 20-point penalty will be applied.', true);
            doSubmit(true);
        }
    }

    async function doSubmit(lateAttempt, autoSub) {
        if (state.submitting) return;
        state.submitting = true;
        if (!state.ended) {
            state.ended = true;
            clearInterval(state.timerId);
            timerValue.textContent = '00:00';
            setControlsEnabled(false);
        }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Calculating…';

        const timeTaken = Math.max(0, Math.floor((Date.now() - state.startedAt) / 1000));
        const penalty   = (lateAttempt || state.lateAttempt) ? LATE_PENALTY : 0;

        // Score locally (we have correct_answer in each question from the API)
        let correct = 0;
        const mistakes = [];
        state.questions.forEach((q, i) => {
            const ans = (state.answers[i] ?? '').trim();
            const ok  = ans !== '' && parseInt(ans, 10) === parseInt(q.correct_answer, 10);
            if (ok) correct++;
            else mistakes.push({ idx: i, q, ans });
        });

        const maxScore   = state.questions.length;
        const finalScore = Math.max(0, correct - penalty);
        const accuracy   = maxScore > 0 ? Math.round((correct / maxScore) * 100) : 0;

        // Show results
        $('rScore').textContent    = finalScore;
        $('rMax').textContent      = maxScore;
        $('rAccuracy').textContent = accuracy + '%';
        $('rTime').textContent     = fmtTime(timeTaken);
        $('rPenalty').textContent  = penalty + ' pts';
        $('rCorrect').textContent  = correct;

        const list = $('mistakeList');
        if (!mistakes.length) {
            list.innerHTML = '<div class="empty-state">🎉 Perfect score! No mistakes.</div>';
        } else {
            list.innerHTML = '';
            mistakes.forEach(m => {
                const item = document.createElement('div');
                item.className = 'mistake-item';
                const qLabel = document.createElement('div');
                qLabel.className = 'mistake-q';
                qLabel.textContent = 'Q' + (m.idx+1) + (m.q.question_group ? ' [' + m.q.question_group + ']' : '');
                item.appendChild(qLabel);
                // expression
                const expr = document.createElement('div');
                expr.style.cssText = 'font-size:.9rem;margin:4px 0';
                expr.textContent = m.q.question_text?.replace(/\n/g,' ').trim() || '';
                item.appendChild(expr);
                const ansDiv = document.createElement('div');
                ansDiv.className = 'mistake-ans';
                ansDiv.innerHTML = 'Your answer: <span>' + (m.ans === '' ? 'blank' : m.ans) + '</span>  '
                    + 'Correct: <strong>' + m.q.correct_answer + '</strong>';
                item.appendChild(ansDiv);
                list.appendChild(item);
            });
        }

        showScreen(resultsScreen);
        state.submitting = false;
    }

    /* ── Practice Again ─────────────────────────────────────────────── */
    $('practiceAgainBtn').addEventListener('click', () => {
        clearInterval(state.timerId);
        state.started = false; state.ended = false;
        submitBtn.textContent = 'Submit Paper';
        showScreen(setupScreen);
        rebuildPaperSelect();
    });

    /* ── Abacus postMessage ─────────────────────────────────────────── */
    window.addEventListener('message', e => {
        if (e.source !== abacusFrame.contentWindow || !e.data || e.data.type !== 'abacusInteraction') return;
        state.abacusUsed[state.currentIdx] = true;
    });

})();
</script>
</body>
</html>
