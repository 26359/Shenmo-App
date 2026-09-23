<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Abacus - Competition Tool</title>
    <style>
        .abacus-container {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            max-width: 500px;
            margin: 0 auto;
        }
        .abacus-title {
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
        }
        #abacusCanvas {
            background: #f1f5f9;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            display: block;
            margin: 0 auto;
        }
        .abacus-controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }
        .abacus-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .abacus-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239,68,68, 0.4);
        }
        .abacus-btn.reset {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
        }
        .abacus-display {
            text-align: center;
            margin-top: 10px;
            font-family: monospace;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            min-height: 28px;
        }
        .abacus-required-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="abacus-container">
        <div class="abacus-title">
            Virtual Abacus <span class="abacus-required-badge">Required for Level II+</span>
        </div>
        <canvas id="abacusCanvas" width="460" height="200"></canvas>
        <div class="abacus-display" id="abacusValue">0</div>
        <div class="abacus-controls">
            <button class="abacus-btn reset" onclick="resetAbacus()">Reset</button>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('abacusCanvas');
        const ctx = canvas.getContext('2d');
        const display = document.getElementById('abacusValue');

        const COLS = 9;
        const ROWS_UPPER = 1;
        const ROWS_LOWER = 4;

        const COL_WIDTH = 48;
        const ROW_HEIGHT = 22;
        const BEAD_RADIUS = 8;

        const FRAME_TOP = 20;
        const FRAME_BOTTOM = 160;
        const FRAME_LEFT = 20;
        const FRAME_RIGHT = 440;

        const beadsUpper = Array(COLS).fill().map(() => [
            { value: 5, active: false }
        ]);
        const beadsLower = Array(COLS).fill().map(() =>
            Array(ROWS_LOWER).fill().map((_, i) => ({ value: 1, active: false }))
        );

        let lastInteractionTime = 0;

        function drawAbacus() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            ctx.fillStyle = '#dc2626';
            ctx.fillRect(FRAME_LEFT, FRAME_TOP, FRAME_RIGHT - FRAME_LEFT, FRAME_BOTTOM - FRAME_TOP);
            ctx.strokeStyle = '#991b1b';
            ctx.lineWidth = 2;
            ctx.strokeRect(FRAME_LEFT, FRAME_TOP, FRAME_RIGHT - FRAME_LEFT, FRAME_BOTTOM - FRAME_TOP);

            ctx.strokeStyle = '#1e293b';
            ctx.lineWidth = 1;
            for (let i = 0; i <= COLS; i++) {
                const x = FRAME_LEFT + i * COL_WIDTH;
                ctx.beginPath();
                ctx.moveTo(x, FRAME_TOP);
                ctx.lineTo(x, FRAME_BOTTOM);
                ctx.stroke();
            }

            ctx.beginPath();
            ctx.moveTo(FRAME_LEFT, FRAME_TOP + ROW_HEIGHT, FRAME_RIGHT, FRAME_TOP + ROW_HEIGHT);
            ctx.moveTo(FRAME_LEFT, FRAME_TOP + 2 * ROW_HEIGHT, FRAME_RIGHT, FRAME_TOP + 2 * ROW_HEIGHT);
            ctx.moveTo(FRAME_LEFT, FRAME_TOP + 3 * ROW_HEIGHT, FRAME_RIGHT, FRAME_TOP + 3 * ROW_HEIGHT);
            ctx.stroke();

            ctx.fillStyle = '#fbbf24';
            for (let col = 0; col < COLS; col++) {
                const x = FRAME_LEFT + col * COL_WIDTH + COL_WIDTH / 2;
                const yUpper = FRAME_TOP + ROW_HEIGHT / 2;
                const bead = beadsUpper[col][0];
                ctx.beginPath();
                if (bead.active) {
                    ctx.arc(x, yUpper - BEAD_RADIUS - 2, BEAD_RADIUS, 0, Math.PI * 2);
                } else {
                    ctx.arc(x, yUpper + BEAD_RADIUS + 2, BEAD_RADIUS, 0, Math.PI * 2);
                }
                ctx.fill();
                ctx.strokeStyle = '#f59e0b';
                ctx.stroke();
            }

            ctx.fillStyle = '#ef4444';
            for (let col = 0; col < COLS; col++) {
                const x = FRAME_LEFT + col * COL_WIDTH + COL_WIDTH / 2;
                for (let row = 0; row < ROWS_LOWER; row++) {
                    const y = FRAME_TOP + 2 * ROW_HEIGHT + row * ROW_HEIGHT + ROW_HEIGHT / 2;
                    const bead = beadsLower[col][row];
                    ctx.beginPath();
                    if (bead.active) {
                        ctx.arc(x, y + BEAD_RADIUS + 2, BEAD_RADIUS, 0, Math.PI * 2);
                    } else {
                        ctx.arc(x, y - BEAD_RADIUS - 2, BEAD_RADIUS, 0, Math.PI * 2);
                    }
                    ctx.fill();
                    ctx.strokeStyle = '#dc2626';
                    ctx.stroke();
                }
            }

            ctx.fillStyle = '#94a3b8';
            ctx.font = '12px monospace';
            ctx.textAlign = 'center';
            const placeValues = ['', '', '', '', '', '', '', '', ''];
            for (let col = 0; col < COLS; col++) {
                const x = FRAME_LEFT + col * COL_WIDTH + COL_WIDTH / 2;
                const placeValue = Math.pow(10, COLS - 1 - col);
                if (placeValue >= 1) {
                    ctx.fillText(placeValue, x, FRAME_TOP - 5);
                }
            }

            updateDisplay();
        }

        function calculateValue() {
            let value = 0;
            for (let col = 0; col < COLS; col++) {
                const placeValue = Math.pow(10, COLS - 1 - col);
                if (beadsUpper[col][0].active) value += placeValue * 5;
                for (let row = 0; row < ROWS_LOWER; row++) {
                    if (beadsLower[col][row].active) value += placeValue * 1;
                }
            }
            return value;
        }

        function updateDisplay() {
            display.textContent = formatNumber(calculateValue());
        }

        function formatNumber(num) {
            return num.toLocaleString('en-US');
        }

        function resetAbacus() {
            for (let col = 0; col < COLS; col++) {
                beadsUpper[col][0].active = false;
                for (let row = 0; row < ROWS_LOWER; row++) {
                    beadsLower[col][row].active = false;
                }
            }
            drawAbacus();
            lastInteractionTime = Date.now();
            window.parent.postMessage({ type: 'abacusInteraction', active: true }, '*');
        }

        function handleCanvasClick(e) {
            lastInteractionTime = Date.now();
            window.parent.postMessage({ type: 'abacusInteraction', active: true }, '*');

            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const col = Math.floor((x - FRAME_LEFT) / COL_WIDTH);
            if (col < 0 || col >= COLS) return;

            const upperRowY = FRAME_TOP + ROW_HEIGHT / 2;
            const distToUpper = Math.abs(y - upperRowY);

            if (distToUpper < ROW_HEIGHT) {
                beadsUpper[col][0].active = !beadsUpper[col][0].active;
                drawAbacus();
                return;
            }

            const lowerRowsStart = FRAME_TOP + 2 * ROW_HEIGHT + BEAD_RADIUS;
            for (let row = 0; row < ROWS_LOWER; row++) {
                const beadY = lowerRowsStart + row * ROW_HEIGHT;
                if (Math.abs(y - beadY) < BEAD_RADIUS + 5) {
                    beadsLower[col][row].active = !beadsLower[col][row].active;
                    drawAbacus();
                    return;
                }
            }
        }

        canvas.addEventListener('click', handleCanvasClick);

        drawAbacus();

        window.abacus = {
            getValue: calculateValue,
            reset: resetAbacus,
            setValue: function(target) {
                let remaining = target;
                for (let col = 0; col < COLS; col++) {
                    const placeValue = Math.pow(10, COLS - 1 - col);
                    const upperCount = Math.floor(remaining / (placeValue * 5));
                    if (upperCount >= 1) {
                        beadsUpper[col][0].active = true;
                        remaining -= placeValue * 5;
                    }
                    const lowerCount = Math.floor(remaining / placeValue);
                    for (let row = 0; row < ROWS_LOWER && row < lowerCount; row++) {
                        beadsLower[col][row].active = true;
                    }
                    remaining -= lowerCount * placeValue;
                }
                drawAbacus();
                lastInteractionTime = Date.now();
                window.parent.postMessage({ type: 'abacusInteraction', active: true }, '*');
            },
            hasBeenUsed: function() {
                return lastInteractionTime > 0;
            }
        };
    </script>
</body>
</html>
