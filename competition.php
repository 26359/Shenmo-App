<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || empty($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competition - Abacus Academy</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }
        button, select { font: inherit; }
        button { cursor: pointer; }
        .dashboard { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-right: 1px solid #e2e8f0;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            z-index: 200;
            transition: transform 0.3s;
        }
        .logo { text-align: center; padding: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px; }
        .logo h1 { color: #ef4444; font-size: 1.3rem; font-weight: 700; }
        .logo p { color: #94a3b8; font-size: 0.8rem; margin-top: 4px; }
        .nav-menu { list-style: none; padding: 0 10px; }
        .nav-item { margin-bottom: 4px; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: #475569;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.92rem;
        }
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, #fee2e2, #fef2f2);
            color: #ef4444;
        }
        .nav-link .icon { font-size: 1.1rem; width: 22px; text-align: center; }
        .main-content { flex: 1; margin-left: 260px; padding: 25px; min-width: 0; }
        .topbar {
            background: #fff;
            padding: 14px 20px;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .topbar h1 { color: #1e293b; font-size: 1.35rem; font-weight: 700; }
        .topbar p { color: #64748b; font-size: 0.88rem; margin-top: 3px; }
        .student-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .screen { display: none; }
        .screen.active { display: block; animation: fadeIn 0.25s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .header-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 24px;
            margin-bottom: 20px;
        }
        .header-card h2 { color: #1e293b; font-size: 1.55rem; font-weight: 700; }
        .header-card > p { color: #64748b; margin-top: 6px; line-height: 1.6; }
        .mode-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; margin: 22px 0; }
        .mode-card {
            text-align: left;
            border: 2px solid #e2e8f0;
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            transition: all 0.2s;
            color: #334155;
        }
        .mode-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(239,68,68,0.12); }
        .mode-card.selected { border-color: #ef4444; background: linear-gradient(135deg, #fef2f2, #fef2f2); box-shadow: 0 6px 18px rgba(239,68,68,0.14); }
        .mode-card strong { display: block; color: #1e293b; font-size: 1.02rem; margin-bottom: 7px; }
        .mode-card span { display: block; color: #64748b; font-size: 0.87rem; line-height: 1.5; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin: 20px 0; }
        .form-group label { display: block; color: #475569; font-size: 0.88rem; font-weight: 600; margin-bottom: 8px; }
        .form-control {
            width: 100%;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
            color: #1e293b;
            background: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus { border-color: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,0.12); }
        .rules-panel {
            background: #f8fafc;
            border-left: 4px solid #ef4444;
            border-radius: 10px;
            padding: 16px 18px;
            color: #475569;
            line-height: 1.65;
            font-size: 0.92rem;
        }
        .rules-panel strong { color: #1e293b; }
        .btn {
            border: none;
            border-radius: 10px;
            padding: 12px 22px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; }
        .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(239,68,68,0.35); }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover:not(:disabled) { background: #cbd5e1; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #f87171); color: #fff; }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #fff; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; box-shadow: none !important; }
        .btn-start { width: 100%; margin-top: 22px; padding: 14px 22px; font-size: 1rem; }
        .loading { opacity: 0.7; pointer-events: none; }
        .competition-header {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 18px 22px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 16px;
            align-items: center;
        }
        .competition-title h2 { color: #1e293b; font-size: 1.25rem; font-weight: 700; }
        .competition-title p { color: #64748b; font-size: 0.86rem; margin-top: 4px; }
        .timer-box, .progress-box {
            min-width: 135px;
            text-align: center;
            padding: 10px 16px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .timer-box.warning { background: #fef3c7; border-color: #fbbf24; color: #92400e; }
        .timer-box.danger { background: #fee2e2; border-color: #ef4444; color: #991b1b; animation: pulseTimer 1s infinite; }
        @keyframes pulseTimer { 50% { box-shadow: 0 0 0 5px rgba(239,68,68,0.12); } }
        .timer-label, .progress-label { display: block; color: #64748b; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
        .timer-value { display: block; color: #1e293b; font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums; margin-top: 2px; }
        .timer-box.warning .timer-value, .timer-box.danger .timer-value { color: inherit; }
        .progress-value { display: block; color: #1e293b; font-size: 1.05rem; font-weight: 700; margin-top: 3px; }
        .competition-layout { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 20px; align-items: start; }
        .question-card, .abacus-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 24px;
        }
        .question-meta { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
        .question-meta strong { color: #ef4444; font-size: 0.92rem; }
        .question-type { color: #64748b; font-size: 0.82rem; }
        .question-text {
            min-height: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: linear-gradient(135deg, #fef2f2, #fef2f2);
            border: 1px solid #fee2e2;
            border-radius: 14px;
            padding: 28px 20px;
            color: #1e293b;
            font-size: clamp(1.35rem, 4vw, 2.35rem);
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 18px;
            overflow-wrap: anywhere;
        }
        .vertical-expression {
            display: inline-flex;
            flex-direction: column;
            align-items: flex-end;
            min-width: 110px;
            font-variant-numeric: tabular-nums;
            line-height: 1.18;
        }
        .vertical-expression .operand-row { white-space: nowrap; }
        .vertical-expression .operator { color: #64748b; margin-right: 4px; }
        .vertical-expression .expression-line {
            width: 100%;
            border-top: 3px solid #1e293b;
            margin: 4px 0 2px;
        }
        .vertical-expression .answer-row { color: #ef4444; }
        .abacus-card.hidden { display: none; }
        .start-overlay {
            position: absolute;
            inset: 0;
            background: rgba(248,250,252,0.96);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            z-index: 5;
        }
        .start-overlay-content { text-align: center; color: #475569; }
        .start-overlay-content strong { display: block; color: #1e293b; font-size: 1.15rem; margin-bottom: 8px; }
        .question-card { position: relative; }
        .abacus-required {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fef3c7;
            color: #92400e;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 14px;
        }
        .answer-display {
            min-height: 62px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            color: #1e293b;
            font-size: 2rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            margin-bottom: 18px;
            padding: 8px 16px;
        }
        .answer-display.filled { border-style: solid; border-color: #ef4444; background: #fef2f2; }
        .answer-display.locked { border-color: #94a3b8; color: #64748b; background: #f1f5f9; }
        .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; max-width: 330px; margin: 0 auto; }
        .keypad button {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 10px;
            padding: 15px 10px;
            color: #1e293b;
            font-size: 1.15rem;
            font-weight: 700;
            box-shadow: 0 2px 0 #e2e8f0;
            transition: all 0.15s;
        }
        .keypad button:hover:not(:disabled) { background: #fef2f2; border-color: #ef4444; transform: translateY(-1px); }
        .keypad button:active:not(:disabled) { transform: translateY(1px); box-shadow: none; }
        .keypad button.utility { color: #92400e; background: #fffbeb; border-color: #fde68a; }
        .question-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
        .question-actions .btn { flex: 1 1 130px; }
        .practice-picker { display: none; margin-top: 16px; }
        .practice-picker.visible { display: block; }
        .practice-picker label { display: block; color: #64748b; font-size: 0.84rem; font-weight: 600; margin-bottom: 7px; }
        .abacus-card { position: sticky; top: 20px; }
        .abacus-card h3 { color: #1e293b; font-size: 1.08rem; font-weight: 700; margin-bottom: 5px; }
        .abacus-card > p { color: #64748b; font-size: 0.84rem; line-height: 1.5; margin-bottom: 14px; }
        .abacus-frame {
            width: 100%;
            min-height: 245px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            display: block;
        }
        .abacus-status { display: flex; align-items: center; gap: 8px; margin-top: 12px; color: #64748b; font-size: 0.84rem; }
        .status-dot { width: 9px; height: 9px; border-radius: 50%; background: #cbd5e1; }
        .status-dot.used { background: #10b981; box-shadow: 0 0 0 4px rgba(16,185,129,0.12); }
        .status-dot.required-missing { background: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,0.1); }
        .results-layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; }
        .results-card {
            background: linear-gradient(135deg, #fef2f2, #fef2f2);
            border: 1px solid #fee2e2;
            border-radius: 18px;
            padding: 28px;
            text-align: center;
        }
        .results-card h2 { color: #1e293b; font-size: 1.4rem; margin-bottom: 20px; }
        .result-score { font-size: 3.5rem; font-weight: 800; color: #ef4444; line-height: 1; font-variant-numeric: tabular-nums; }
        .result-score small { font-size: 1.2rem; color: #64748b; font-weight: 600; }
        .result-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin: 24px 0; }
        .result-detail { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; }
        .result-detail span { display: block; color: #64748b; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; font-weight: 700; }
        .result-detail strong { display: block; color: #1e293b; font-size: 1.15rem; margin-top: 5px; }
        .mistakes-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 24px; }
        .mistakes-card h3 { color: #1e293b; font-size: 1.15rem; margin-bottom: 14px; }
        .mistake-list { display: grid; gap: 10px; max-height: 430px; overflow-y: auto; padding-right: 5px; }
        .mistakes-card.collapsed .mistake-list { display: none; }
        .mistake-item { border: 1px solid #fee2e2; background: #fff7f7; border-radius: 10px; padding: 12px 14px; }
        .mistake-question { color: #1e293b; font-weight: 700; margin-bottom: 5px; }
        .mistake-expression { min-width: 82px; font-size: 1.05rem; vertical-align: middle; }
        .mistake-answer { color: #64748b; font-size: 0.86rem; }
        .mistake-answer strong { color: #16a34a; }
        .empty-state { color: #64748b; text-align: center; padding: 30px 10px; line-height: 1.6; }
        .modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,0.55); z-index: 500; display: none; align-items: center; justify-content: center; padding: 20px; }
        .modal-backdrop.visible { display: flex; }
        .modal { background: #fff; border-radius: 16px; padding: 26px; max-width: 430px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.25); animation: modalIn 0.2s ease; }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }
        .modal h3 { color: #1e293b; font-size: 1.2rem; margin-bottom: 10px; }
        .modal p { color: #64748b; line-height: 1.6; margin-bottom: 20px; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
        .toast {
            position: fixed;
            right: 22px;
            bottom: 22px;
            z-index: 600;
            background: #1e293b;
            color: #fff;
            border-radius: 10px;
            padding: 13px 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            font-size: 0.9rem;
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            transition: all 0.2s;
            max-width: 360px;
        }
        .toast.visible { opacity: 1; transform: translateY(0); }
        .toast.error { background: #b91c1c; }
        @media (max-width: 1050px) {
            .competition-layout { grid-template-columns: 1fr; }
            .abacus-card { position: static; }
            .results-layout { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 14px; }
            .competition-header { grid-template-columns: 1fr; text-align: center; }
            .timer-box, .progress-box { margin: 0 auto; }
            .question-card, .abacus-card, .header-card { padding: 18px; }
        }
        @media (max-width: 480px) {
            .topbar { align-items: flex-start; flex-direction: column; }
            .question-text { min-height: 90px; font-size: 1.25rem; }
            .keypad { gap: 7px; }
            .keypad button { padding: 13px 6px; font-size: 1rem; }
            .result-score { font-size: 2.8rem; }
        }
    </style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <h1>Abacus Academy</h1>
            <p>Learning Portal</p>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="student_dashboard.php" class="nav-link"><span class="icon">📊</span><span>Dashboard</span></a></li>
            <li class="nav-item"><a href="my_learning.php" class="nav-link"><span class="icon">📚</span><span>My Learning</span></a></li>
            <li class="nav-item"><a href="practice.php" class="nav-link"><span class="icon">💪</span><span>Practice</span></a></li>
            <li class="nav-item"><a href="competition.php" class="nav-link active"><span class="icon">🏆</span><span>Competition</span></a></li>
            <li class="nav-item"><a href="leaderboard.php" class="nav-link"><span class="icon">🏅</span><span>Leaderboard</span></a></li>
            <li class="nav-item"><a href="achievements.php" class="nav-link"><span class="icon">⭐</span><span>Achievements</span></a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><span class="icon">👤</span><span>Profile</span></a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link"><span class="icon">🚪</span><span>Logout</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Competition</h1>
                <p>Digital abacus competition with timed papers and instant results</p>
            </div>
            <span class="student-badge">Student session active</span>
        </div>

        <section class="screen active" id="setupScreen">
            <div class="header-card">
                <h2>Choose a competition paper</h2>
                <p>Select a mode, level, and paper. The paper remains hidden until you tap Start, and the timer begins only after Start is pressed.</p>
                <div class="mode-grid">
                    <button type="button" class="mode-card selected" data-mode="official">
                        <strong>Official / Live Competition Mode</strong>
                        <span>Questions unlock one by one in order. The paper auto-submits when time ends.</span>
                    </button>
                    <button type="button" class="mode-card" data-mode="practice">
                        <strong>Practice Mode</strong>
                        <span>Move freely between questions and review answers before submitting.</span>
                    </button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="levelSelect">Level</label>
                        <select id="levelSelect" class="form-control">
                            <option value="1">Level I</option>
                            <option value="2" disabled>Level II (coming soon)</option>
                            <option value="3" disabled>Level III (coming soon)</option>
                            <option value="4" disabled>Level IV (coming soon)</option>
                            <option value="5" disabled>Level V (coming soon)</option>
                            <option value="6" disabled>Level VI (coming soon)</option>
                            <option value="7" disabled>Advanced Level (coming soon)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="paperSelect">Paper</label>
                        <select id="paperSelect" class="form-control">
                            <option value="A">Paper A — 3 one-digit numbers</option>
                            <option value="B">Paper B — 3 one-digit numbers</option>
                            <option value="C">Paper C — 3 one-digit numbers</option>
                            <option value="D">Paper D — 5 one-digit numbers</option>
                        </select>
                    </div>
                </div>
                <div class="rules-panel">
                    <strong>Competition rules:</strong> each correct answer earns 1 point; incorrect, blank, or incomplete answers earn 0 points. Every Level I paper has 3 minutes. Answers can be edited before submission. Only the virtual number pad is accepted; calculators and phones are not allowed. Official mode unlocks questions sequentially; a skipped question makes later answers score 0. Paper D requires the Virtual Abacus in Official mode and provides it in Practice mode. The paper locks at 00:00, and continuing after time ends applies a 20-point penalty.
                </div>
                <button type="button" id="startBtn" class="btn btn-primary btn-start">Start Competition</button>
            </div>
        </section>

        <section class="screen" id="competitionScreen">
            <div class="competition-header">
                <div class="competition-title">
                    <h2 id="competitionTitle">Competition Paper</h2>
                    <p id="competitionSubtitle">Answer in order and submit before time ends</p>
                </div>
                <div class="timer-box" id="timerBox">
                    <span class="timer-label">Remaining time</span>
                    <span class="timer-value" id="timerValue">03:00</span>
                </div>
                <div class="progress-box">
                    <span class="progress-label">Progress</span>
                    <span class="progress-value" id="progressValue">Question 1 of 240</span>
                </div>
            </div>

            <div class="competition-layout">
                <div class="question-card">
                    <div class="start-overlay" id="startOverlay">
                        <div class="start-overlay-content">
                            <strong>Paper is ready</strong>
                            <span>Tap Start to reveal the questions and begin the timer.</span>
                        </div>
                    </div>
                    <div class="question-meta">
                        <strong id="questionCounter">Question 1</strong>
                        <span class="question-type" id="questionType">Mixed calculation</span>
                    </div>
                    <div id="abacusRequiredBadge" class="abacus-required" style="display: none;">Virtual Abacus required for this question</div>
                    <div class="question-text" id="questionText">Loading question...</div>
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
                        <button type="button" class="utility" data-key="backspace">Backspace</button>
                        <button type="button" class="utility" data-key="sign" aria-label="Toggle negative sign">±</button>
                    </div>
                    <div class="practice-picker" id="practicePicker">
                        <label for="questionPicker">Jump to question</label>
                        <select id="questionPicker" class="form-control"></select>
                    </div>
                    <div class="question-actions">
                        <button type="button" id="previousBtn" class="btn btn-secondary">Previous</button>
                        <button type="button" id="nextBtn" class="btn btn-primary">Next</button>
                        <button type="button" id="submitBtn" class="btn btn-danger">Submit</button>
                    </div>
                </div>

                <aside class="abacus-card hidden" id="abacusCard">
                    <h3>Virtual Abacus</h3>
                    <p>Use the in-app abacus for required calculations. The tool records use for the current question.</p>
                    <iframe id="abacusFrame" class="abacus-frame" title="Virtual Abacus" src="virtual_abacus.php?question_id=0"></iframe>
                    <div class="abacus-status">
                        <span class="status-dot" id="abacusStatusDot"></span>
                        <span id="abacusStatusText">No abacus use recorded for this question</span>
                    </div>
                </aside>
            </div>
        </section>

        <section class="screen" id="resultsScreen">
            <div class="results-layout">
                <div class="results-card">
                    <h2>Competition Results</h2>
                    <div class="result-score"><span id="resultScore">0</span><small> / <span id="resultMaxScore">0</span></small></div>
                    <div class="result-details">
                        <div class="result-detail"><span>Accuracy</span><strong id="resultAccuracy">0%</strong></div>
                        <div class="result-detail"><span>Time taken</span><strong id="resultTime">00:00</strong></div>
                        <div class="result-detail"><span>Penalty</span><strong id="resultPenalty">0 points</strong></div>
                        <div class="result-detail"><span>Mode</span><strong id="resultMode">Practice</strong></div>
                    </div>
                    <button type="button" id="reviewMistakesBtn" class="btn btn-primary">Review Mistakes</button>
                </div>
                <div class="mistakes-card">
                    <h3>Mistake Review</h3>
                    <div id="mistakeList" class="mistake-list">
                        <div class="empty-state">Submit a paper to review mistakes.</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<div class="modal-backdrop" id="warningModal">
    <div class="modal">
        <h3 id="warningTitle">Time warning</h3>
        <p id="warningMessage">One minute remains.</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-primary" id="warningContinueBtn">Continue</button>
        </div>
    </div>
</div>
<div class="toast" id="toast"></div>

<script>
(function () {
    const API_URL = 'competition_api.php';
    const WARNING_60 = 60;
    const WARNING_30 = 30;
    const state = {
        mode: 'official',
        level: 1,
        paper: 'A',
        competitionId: null,
        questions: [],
        currentIndex: 0,
        highestUnlocked: 0,
        answers: {},
        abacusUsed: {},
        started: false,
        ended: false,
        submitting: false,
        timeLimit: 300,
        startedAt: 0,
        endsAt: 0,
        timerId: null,
        warning60Shown: false,
        warning30Shown: false,
        lateAttempt: false,
        saveTimer: null,
        savePromise: null,
        result: null
    };

    const $ = function (id) { return document.getElementById(id); };
    const setupScreen = $('setupScreen');
    const competitionScreen = $('competitionScreen');
    const resultsScreen = $('resultsScreen');
    const timerBox = $('timerBox');
    const timerValue = $('timerValue');
    const progressValue = $('progressValue');
    const questionCounter = $('questionCounter');
    const questionType = $('questionType');
    const questionText = $('questionText');
    const answerDisplay = $('answerDisplay');
    const abacusFrame = $('abacusFrame');
    const abacusStatusDot = $('abacusStatusDot');
    const abacusStatusText = $('abacusStatusText');
    const abacusRequiredBadge = $('abacusRequiredBadge');
    const abacusCard = $('abacusCard');
    const startOverlay = $('startOverlay');
    const previousBtn = $('previousBtn');
    const nextBtn = $('nextBtn');
    const submitBtn = $('submitBtn');
    const practicePicker = $('practicePicker');
    const questionPicker = $('questionPicker');
    const warningModal = $('warningModal');
    const toast = $('toast');

    function showScreen(screen) {
        [setupScreen, competitionScreen, resultsScreen].forEach(function (item) {
            item.classList.toggle('active', item === screen);
        });
    }

    function showToast(message, isError) {
        toast.textContent = message;
        toast.classList.toggle('error', Boolean(isError));
        toast.classList.add('visible');
        window.setTimeout(function () { toast.classList.remove('visible'); }, 3500);
    }

    function formatTime(seconds) {
        const safeSeconds = Math.max(0, Math.ceil(seconds));
        const minutes = Math.floor(safeSeconds / 60);
        const remainder = safeSeconds % 60;
        return String(minutes).padStart(2, '0') + ':' + String(remainder).padStart(2, '0');
    }

    function getModeLabel() {
        return state.mode === 'official' ? 'Official / Live Competition Mode' : 'Practice Mode';
    }

    function apiRequest(action, data) {
        const payload = new URLSearchParams(Object.assign({ action: action }, data || {}));
        return fetch(API_URL + '?' + payload.toString(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload.toString()
        }).then(function (response) {
            return response.json().then(function (body) {
                if (!response.ok || body.error) {
                    throw new Error(body.message || body.error || 'Request failed');
                }
                return body;
            });
        });
    }

    function setControlsEnabled(enabled) {
        Array.prototype.forEach.call(document.querySelectorAll('#keypad button'), function (button) {
            button.disabled = !enabled;
        });
        previousBtn.disabled = !enabled || state.currentIndex === 0;
        nextBtn.disabled = !enabled || (state.mode === 'official' && state.currentIndex > state.highestUnlocked);
        submitBtn.disabled = !enabled || state.submitting;
        questionPicker.disabled = !enabled || state.mode === 'official';
    }

    function currentQuestion() {
        return state.questions[state.currentIndex] || null;
    }

    function currentAnswer() {
        const question = currentQuestion();
        return question ? (state.answers[question.id] || '') : '';
    }

    function renderAnswer() {
        const value = currentAnswer();
        answerDisplay.textContent = value === '' ? 'Enter your answer' : value;
        answerDisplay.classList.toggle('filled', value !== '');
        answerDisplay.classList.toggle('locked', state.ended);
    }

    function updateAbacusStatus() {
        const question = currentQuestion();
        if (!question) return;
        const used = Boolean(state.abacusUsed[question.id]);
        const required = Boolean(question.requires_abacus);
        abacusStatusDot.classList.toggle('used', used);
        abacusStatusDot.classList.toggle('required-missing', required && !used);
        if (required && !used && state.mode === 'official') {
            abacusStatusText.textContent = 'Virtual Abacus use required';
        } else if (used) {
            abacusStatusText.textContent = 'Virtual Abacus used for this question';
        } else if (required) {
            abacusStatusText.textContent = 'Virtual Abacus available for this question';
        } else {
            abacusStatusText.textContent = 'Virtual Abacus optional for this question';
        }
        const abacusRequired = required && state.mode === 'official';
        abacusRequiredBadge.style.display = abacusRequired ? 'inline-flex' : 'none';
    }

    function renderQuestion() {
        const question = currentQuestion();
        if (!question) return;

        const blockLabel = question.question_group || (state.paper + '1');
        questionCounter.textContent = 'Question ' + (state.currentIndex + 1) + ' · Block ' + blockLabel;
        progressValue.textContent = 'Question ' + (state.currentIndex + 1) + ' of ' + state.questions.length;
        questionType.textContent = 'Vertical addition and subtraction · ' + (question.operand_count || 3) + ' one-digit numbers';
        questionText.textContent = '';

        const expression = document.createElement('div');
        expression.className = 'vertical-expression';
        const operators = question.operators || [];
        question.operands.forEach(function (operand, index) {
            const row = document.createElement('div');
            row.className = 'operand-row';
            const sign = document.createElement('span');
            sign.className = 'operator';
            sign.textContent = operand < 0 || (index > 0 && operators[index - 1] === '-') ? '−' : '';
            const value = document.createElement('span');
            value.textContent = Math.abs(Number(operand));
            row.appendChild(sign);
            row.appendChild(value);
            expression.appendChild(row);
        });

        const line = document.createElement('div');
        line.className = 'expression-line';
        expression.appendChild(line);

        const answerRow = document.createElement('div');
        answerRow.className = 'answer-row';
        answerRow.textContent = 'ans';
        expression.appendChild(answerRow);
        questionText.appendChild(expression);

        questionPicker.value = String(state.currentIndex);
        renderAnswer();
        updateAbacusStatus();
        abacusCard.classList.toggle('hidden', !question.requires_abacus);
        abacusFrame.src = 'virtual_abacus.php?question_id=' + encodeURIComponent(question.id) + '&t=' + Date.now();
        setControlsEnabled(!state.ended && !state.submitting);
        practicePicker.classList.toggle('visible', state.mode === 'practice');

        if (questionPicker.options.length !== state.questions.length) {
            questionPicker.innerHTML = '';
            state.questions.forEach(function (item, index) {
                const option = document.createElement('option');
                option.value = String(index);
                option.textContent = 'Question ' + (index + 1) + ' · ' + (item.question_group || '');
                questionPicker.appendChild(option);
            });
            questionPicker.value = String(state.currentIndex);
        }
    }

    function resetAbacusForQuestion() {
        try {
            abacusFrame.contentWindow.postMessage({ type: 'resetAbacus' }, window.location.origin);
        } catch (error) {
            abacusFrame.src = abacusFrame.src;
        }
    }

    function goToQuestion(index) {
        if (state.ended || state.submitting || !state.questions[index]) return;
        if (state.mode === 'official' && index > state.highestUnlocked) return;
        state.currentIndex = index;
        resetAbacusForQuestion();
        renderQuestion();
    }

    function queueSave() {
        if (!state.started || state.ended || state.submitting) return;
        window.clearTimeout(state.saveTimer);
        state.saveTimer = window.setTimeout(function () {
            saveCurrentAnswer().catch(function (error) {
                showToast(error.message, true);
            });
        }, 180);
    }

    async function saveCurrentAnswer() {
        if (!state.competitionId || state.submitting) return;
        const question = currentQuestion();
        if (!question) return;
        if (state.savePromise) {
            try { await state.savePromise; } catch (error) {}
        }
        const answer = state.answers[question.id] || '';
        const abacusUsed = state.abacusUsed[question.id] ? 1 : 0;
        state.savePromise = apiRequest('save_answer', {
            competition_id: state.competitionId,
            question_id: question.id,
            answer: answer,
            abacus_used: abacusUsed
        }).then(function () {
            state.savePromise = null;
        }).catch(function (error) {
            state.savePromise = null;
            throw error;
        });
        await state.savePromise;
    }

    async function moveNext() {
        if (state.ended || state.submitting) return;
        await saveCurrentAnswer();
        if (state.mode === 'official' && currentAnswer() === '') {
            showToast('Question skipped. Later answers will score 0.', true);
        }
        if (state.mode === 'official') {
            if (state.currentIndex < state.highestUnlocked) {
                goToQuestion(state.currentIndex + 1);
            } else if (state.currentIndex < state.questions.length - 1) {
                state.highestUnlocked = state.currentIndex + 1;
                goToQuestion(state.currentIndex + 1);
            }
        } else {
            goToQuestion(Math.min(state.questions.length - 1, state.currentIndex + 1));
        }
    }

    function movePrevious() {
        if (state.ended || state.submitting || state.currentIndex === 0) return;
        goToQuestion(state.currentIndex - 1);
    }

    function inputDigit(digit) {
        if (state.ended || state.submitting || !currentQuestion()) return;
        const question = currentQuestion();
        const current = state.answers[question.id] || '';
        if (current === '-' && digit === '0') return;
        state.answers[question.id] = current + digit;
        renderAnswer();
        queueSave();
    }

    function toggleSign() {
        if (state.ended || state.submitting || !currentQuestion()) return;
        const question = currentQuestion();
        const current = state.answers[question.id] || '';
        state.answers[question.id] = current.startsWith('-') ? current.slice(1) : '-' + current;
        renderAnswer();
        queueSave();
    }

    function clearAnswer() {
        if (state.ended || state.submitting || !currentQuestion()) return;
        const question = currentQuestion();
        state.answers[question.id] = '';
        renderAnswer();
        queueSave();
    }

    function backspaceAnswer() {
        if (state.ended || state.submitting || !currentQuestion()) return;
        const question = currentQuestion();
        const value = state.answers[question.id] || '';
        state.answers[question.id] = value === '-' ? '' : value.slice(0, -1);
        renderAnswer();
        queueSave();
    }

    function updateTimer() {
        if (!state.started || state.ended) return;
        const remaining = Math.max(0, Math.ceil((state.endsAt - Date.now()) / 1000));
        timerValue.textContent = formatTime(remaining);
        timerBox.classList.toggle('warning', remaining <= WARNING_60 && remaining > WARNING_30);
        timerBox.classList.toggle('danger', remaining <= WARNING_30);
        if (remaining <= WARNING_60 && !state.warning60Shown) {
            state.warning60Shown = true;
            showWarning('One minute remaining', 'You have 1 minute left. Finish the current question or submit your paper.');
        }
        if (remaining <= WARNING_30 && !state.warning30Shown) {
            state.warning30Shown = true;
            showWarning('Thirty seconds remaining', 'You have 30 seconds left. Check your answers and prepare to submit.');
        }
        if (remaining <= 0) {
            endCompetition(false);
        }
    }

    function showWarning(title, message) {
        $('warningTitle').textContent = title;
        $('warningMessage').textContent = message;
        warningModal.classList.add('visible');
    }

    function hideWarning() {
        warningModal.classList.remove('visible');
    }

    function lockCompetition() {
        state.ended = true;
        window.clearInterval(state.timerId);
        timerValue.textContent = '00:00';
        timerBox.classList.remove('warning');
        timerBox.classList.add('danger');
        answerDisplay.classList.add('locked');
        setControlsEnabled(false);
        Array.prototype.forEach.call(document.querySelectorAll('#keypad button'), function (button) {
            button.disabled = true;
        });
    }

    function markLateAttempt() {
        if (!state.ended || state.lateAttempt || state.submitting) return;
        state.lateAttempt = true;
        showToast('Time has ended. A 20-point penalty will be applied.', true);
        submitCompetition(true);
    }

    async function endCompetition(lateAttempt) {
        if (state.ended || state.submitting) return;
        if (lateAttempt) {
            state.lateAttempt = true;
            lockCompetition();
        } else {
            try {
                await saveCurrentAnswer();
            } catch (error) {
                showToast(error.message, true);
            }
            lockCompetition();
        }
        await submitCompetition(state.lateAttempt, !lateAttempt);
    }

    async function submitCompetition(lateAttempt, autoSubmitted) {
        if (!state.competitionId || state.submitting) return;
        try {
            if (!state.ended) {
                await saveCurrentAnswer();
            }
            state.submitting = true;
            setControlsEnabled(false);
            const elapsed = Math.max(0, Math.floor((Date.now() - state.startedAt) / 1000));
            const result = await apiRequest('submit', {
                competition_id: state.competitionId,
                time_taken: elapsed,
                late_penalty: (lateAttempt || state.lateAttempt) ? 1 : 0,
                auto_submit: autoSubmitted ? 1 : 0
            });
            state.result = result;
            showResults(result);
        } catch (error) {
            showToast(error.message, true);
            state.submitting = false;
            setControlsEnabled(!state.ended);
        }
    }

    function showResults(result) {
        showScreen(resultsScreen);
        $('resultScore').textContent = result.score || 0;
        $('resultMaxScore').textContent = result.max_score || 0;
        $('resultAccuracy').textContent = (result.accuracy || 0) + '%';
        $('resultTime').textContent = formatTime(result.time_taken || 0);
        $('resultPenalty').textContent = (result.penalty || 0) + ' points';
        $('resultMode').textContent = getModeLabel();
        $('mistakeList').innerHTML = '<div class="empty-state">Loading mistake review...</div>';
        apiRequest('get_results', { competition_id: state.competitionId }).then(function (body) {
            renderMistakes(body.wrong_answers || []);
        }).catch(function () {
            $('mistakeList').innerHTML = '<div class="empty-state">Mistake review is unavailable.</div>';
        });
    }

    function createVerticalExpression(question) {
        const expression = document.createElement('div');
        expression.className = 'vertical-expression mistake-expression';
        const operators = question.operators || [];
        question.operands.forEach(function (operand, index) {
            const row = document.createElement('div');
            row.className = 'operand-row';
            const sign = document.createElement('span');
            sign.className = 'operator';
            sign.textContent = operand < 0 || (index > 0 && operators[index - 1] === '-') ? '−' : '';
            const value = document.createElement('span');
            value.textContent = Math.abs(Number(operand));
            row.appendChild(sign);
            row.appendChild(value);
            expression.appendChild(row);
        });
        const line = document.createElement('div');
        line.className = 'expression-line';
        expression.appendChild(line);
        const answerRow = document.createElement('div');
        answerRow.className = 'answer-row';
        answerRow.textContent = 'ans';
        expression.appendChild(answerRow);
        return expression;
    }

    function renderMistakes(mistakes) {
        const list = $('mistakeList');
        if (!mistakes.length) {
            list.innerHTML = '<div class="empty-state">No mistakes. Every scored question was correct.</div>';
            return;
        }
        list.innerHTML = '';
        mistakes.forEach(function (mistake) {
            const item = document.createElement('div');
            item.className = 'mistake-item';
            const question = document.createElement('div');
            question.className = 'mistake-question';
            const questionLabel = document.createElement('div');
            questionLabel.textContent = 'Question ' + mistake.question_number + (mistake.question_group ? ' · Block ' + mistake.question_group : '');
            question.appendChild(questionLabel);
            if (mistake.operands) {
                question.appendChild(createVerticalExpression(mistake));
            } else {
                const fallback = document.createElement('div');
                fallback.textContent = mistake.question_text || '';
                question.appendChild(fallback);
            }
            const answer = document.createElement('div');
            answer.className = 'mistake-answer';
            const answerText = (mistake.answer_text === null || mistake.answer_text === undefined) ? '' : String(mistake.answer_text);
            const studentAnswer = document.createElement('span');
            studentAnswer.textContent = 'Your answer: ' + (answerText === '' ? 'blank' : answerText);
            const correctAnswer = document.createElement('strong');
            correctAnswer.textContent = ' Correct answer: ' + mistake.correct_answer;
            answer.appendChild(studentAnswer);
            answer.appendChild(correctAnswer);
            item.appendChild(question);
            item.appendChild(answer);
            list.appendChild(item);
        });
    }

    async function startCompetition() {
        const startBtn = $('startBtn');
        startBtn.disabled = true;
        startBtn.textContent = 'Starting...';
        try {
            const created = await apiRequest('create', {
                mode: state.mode,
                level: state.level,
                paper: state.paper
            });
            const attempt = await apiRequest('get_attempt', { competition_id: created.competition_id });
            const started = await apiRequest('start', {
                competition_id: created.competition_id
            });
            state.competitionId = created.competition_id;
            state.questions = attempt.questions || [];
            state.abacusUsed = {};
            state.questions.forEach(function (question) {
                if (question.abacus_used) {
                    state.abacusUsed[question.id] = true;
                }
            });
            state.highestUnlocked = 0;
            state.answers = {};
            state.questions.forEach(function (question) {
                if (question.saved_answer !== undefined && question.saved_answer !== null && question.saved_answer !== '') {
                    state.answers[question.id] = String(question.saved_answer);
                }
            });
            state.currentIndex = 0;
            state.started = true;
            state.ended = false;
            state.submitting = false;
            state.lateAttempt = false;
            state.warning60Shown = false;
            state.warning30Shown = false;
            state.timeLimit = started.time_limit_seconds || attempt.competition.time_limit_seconds || 300;
            state.startedAt = Date.now();
            const serverRemaining = (started.end_time_iso || started.end_time) && started.server_time
                ? Math.max(0, (Date.parse(started.end_time_iso || started.end_time) - Date.parse(started.server_time)) / 1000)
                : (attempt.time_remaining_seconds || state.timeLimit);
            state.endsAt = Date.now() + serverRemaining * 1000;
            $('competitionTitle').textContent = 'Level ' + state.level + ' - Paper ' + state.paper;
            const abacusRule = state.paper === 'D' ? (state.mode === 'official' ? 'Virtual Abacus required' : 'Virtual Abacus available') : 'mental arithmetic';
            $('competitionSubtitle').textContent = getModeLabel() + ' - 3 minutes - ' + abacusRule;
            timerValue.textContent = formatTime(state.timeLimit);
            timerBox.classList.remove('warning', 'danger');
            startOverlay.style.display = 'none';
            questionPicker.innerHTML = '';
            showScreen(competitionScreen);
            renderQuestion();
            state.timerId = window.setInterval(updateTimer, 250);
        } catch (error) {
            showToast(error.message, true);
            startBtn.disabled = false;
            startBtn.textContent = 'Start Competition';
        }
    }

    document.querySelectorAll('.mode-card').forEach(function (card) {
        card.addEventListener('click', function () {
            document.querySelectorAll('.mode-card').forEach(function (item) { item.classList.remove('selected'); });
            card.classList.add('selected');
            state.mode = card.dataset.mode;
        });
    });

    $('levelSelect').addEventListener('change', function (event) { state.level = Number(event.target.value); });
    $('paperSelect').addEventListener('change', function (event) { state.paper = event.target.value; });
    $('startBtn').addEventListener('click', startCompetition);
    $('warningContinueBtn').addEventListener('click', hideWarning);
    previousBtn.addEventListener('click', movePrevious);
    nextBtn.addEventListener('click', moveNext);
    submitBtn.addEventListener('click', function () { submitCompetition(false); });
    questionPicker.addEventListener('change', function (event) { goToQuestion(Number(event.target.value)); });

    document.querySelectorAll('#keypad button').forEach(function (button) {
        button.addEventListener('click', function () {
            if (state.ended) {
                markLateAttempt();
                return;
            }
            const key = button.dataset.key;
            if (key === 'clear') clearAnswer();
            else if (key === 'backspace') backspaceAnswer();
            else if (key === 'sign') toggleSign();
            else inputDigit(key);
        });
    });

    $('reviewMistakesBtn').addEventListener('click', function () {
        const card = document.querySelector('.mistakes-card');
        const isCollapsed = card.classList.toggle('collapsed');
        this.textContent = isCollapsed ? 'Show Mistakes' : 'Review Mistakes';
    });

    window.addEventListener('message', function (event) {
        if (event.source !== abacusFrame.contentWindow || !event.data || event.data.type !== 'abacusInteraction') return;
        const question = currentQuestion();
        if (!question) return;
        state.abacusUsed[question.id] = true;
        updateAbacusStatus();
        queueSave();
    });

    window.addEventListener('beforeunload', function () {
        const question = currentQuestion();
        if (state.started && !state.ended && question && currentAnswer() !== '') {
            navigator.sendBeacon(API_URL + '?action=save_answer', new URLSearchParams({
                competition_id: state.competitionId,
                question_id: question.id,
                answer: currentAnswer(),
                abacus_used: state.abacusUsed[question.id] ? 1 : 0
            }));
        }
    });
})();
</script>
</body>
</html>
