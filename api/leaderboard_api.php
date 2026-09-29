<?php
/**
 * api/leaderboard_api.php
 *
 * Actions:
 *   ?action=snapshot  -> one-shot JSON leaderboard payload
 *   ?action=stream    -> Server-Sent Events stream of leaderboard snapshots
 *
 * Stream integrity rules (never break these):
 *   1. Auth and DB failures are resolved BEFORE the SSE headers are sent, so they
 *      come back as a normal JSON response with a real HTTP status instead of a
 *      bare JSON blob landing in the middle of the event stream.
 *   2. Once streaming has started the only bytes allowed on the wire are
 *      "event:", "data:", "id:" and ":" comment lines. Every failure path inside
 *      the loop goes through sse_error() so the client always gets a
 *      well-formed frame.
 *   3. json_encode() never runs unchecked. Invalid UTF-8, recursion or
 *      INF/NAN would otherwise make it return false, which used to echo an
 *      empty/partial data line that JSON.parse() then rejected.
 *   4. Warnings and notices are routed to the error log, never to the response
 *      body, so a stray notice cannot corrupt a frame.
 */

// Installed before anything else can run: with display_errors on, a single
// notice emitted while the session or the database connection is being set up
// would prepend plain text to the response and break both the SSE headers and
// the first data frame.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file = '', $line = 0) {
    error_log('[leaderboard_api] ' . $message . ' in ' . $file . ':' . $line);
    return true;
});

session_start();

const LEADERBOARD_ROWS = 20;
const SSE_POLL_SECONDS = 5;
const SSE_HEARTBEAT_SECONDS = 20;
const SSE_MAX_SECONDS = 300;

$action = $_GET['action'] ?? $_POST['action'] ?? 'snapshot';

if (!isset($_SESSION['student_id'])) {
    respond_json(['error' => 'Authentication required'], 401);
}

require_once __DIR__ . '/../config/database.php';
$config = require __DIR__ . '/../config/database.php';

try {
    $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['dbname']);
    if ($conn->connect_error) {
        throw new mysqli_sql_exception('Connection failed: ' . $conn->connect_error);
    }
    // No set_charset() here on purpose. Forcing 'utf8mb4' makes mysqli use
    // utf8mb4_general_ci, but these tables were created with the server default
    // (utf8mb4_unicode_ci) and the student_id comparisons across students /
    // xp_points / student_progress then fail with "Illegal mix of collations".
    // leaderboard.php inherits the server default too, so both agree.
} catch (mysqli_sql_exception $e) {
    respond_json(['error' => 'Database unavailable', 'message' => $e->getMessage()], 503);
}

$student_id = $_SESSION['student_id'];

if ($action === 'stream') {
    run_stream($conn, $student_id);
    $conn->close();
    exit;
}

if ($action !== 'snapshot') {
    respond_json(['error' => 'Invalid action'], 400);
}

respond_json(build_leaderboard($conn, $student_id));
$conn->close();
exit;

/**
 * One-shot JSON response. Always emitted before the stream takes over.
 */
function respond_json($payload, $status = 200) {
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo encode_json($payload);
    exit;
}

/**
 * json_encode() that cannot return false and cannot emit partial output.
 */
function encode_json($payload) {
    $flags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;

    $json = json_encode($payload, $flags);
    if ($json === false) {
        $json = json_encode(['error' => 'Payload could not be encoded'], $flags);
    }
    if ($json === false) {
        $json = '{"error":"encoding_failed"}';
    }

    return $json;
}

/**
 * Build the leaderboard payload. Same ranking rules as leaderboard.php
 * (total XP descending) so the stream and the static page never disagree.
 */
function build_leaderboard($conn, $student_id) {
    // students.student_id is utf8mb4_unicode_ci while xp_points,
    // student_progress and student_achievements are utf8mb4_general_ci, so an
    // unadorned join dies with "Illegal mix of collations". The explicit
    // COLLATE resolves it and leaves the indexes on the general_ci side usable.
    $stmt = $conn->prepare("
        SELECT
            s.student_id,
            s.full_name,
            COUNT(DISTINCT sa.id) AS total_achievements,
            COALESCE(SUM(xp.xp_amount), 0) AS total_xp,
            COUNT(DISTINCT CASE WHEN sp.is_completed = 1 THEN sp.course_id END) AS completed_levels
        FROM students s
        LEFT JOIN student_achievements sa ON s.student_id COLLATE utf8mb4_general_ci = sa.student_id
        LEFT JOIN xp_points xp ON s.student_id COLLATE utf8mb4_general_ci = xp.student_id
        LEFT JOIN student_progress sp ON s.student_id COLLATE utf8mb4_general_ci = sp.student_id
        GROUP BY s.student_id, s.full_name
        ORDER BY total_xp DESC
        LIMIT " . (int) LEADERBOARD_ROWS
    );

    if ($stmt === false) {
        throw new RuntimeException('Leaderboard query could not be prepared');
    }
    if (!$stmt->execute()) {
        throw new RuntimeException('Leaderboard query failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result === false) {
        throw new RuntimeException('Leaderboard result unavailable: ' . $stmt->error);
    }

    $entries = [];
    $viewer_rank = null;
    $rank = 1;

    while ($row = $result->fetch_assoc()) {
        if ((string) $row['student_id'] === (string) $student_id) {
            $viewer_rank = $rank;
        }
        $entries[] = [
            'rank' => $rank,
            'student_id' => (string) $row['student_id'],
            'full_name' => (string) $row['full_name'],
            'total_xp' => (int) $row['total_xp'],
            'total_achievements' => (int) $row['total_achievements'],
            'completed_levels' => (int) $row['completed_levels'],
        ];
        $rank++;
    }

    $stmt->close();

    return [
        'success' => true,
        'generated_at' => gmdate('c'),
        'viewer_id' => (string) $student_id,
        'viewer_rank' => $viewer_rank,
        'entries' => $entries,
    ];
}

function run_stream($conn, $student_id) {
    sse_begin();

    $started_at = time();
    $last_sent_at = 0;
    $last_signature = null;
    $stream_ended = false;

    register_shutdown_function(function () use (&$stream_ended) {
        if ($stream_ended) {
            return;
        }
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            sse_event('stream_error', ['error' => 'Stream terminated unexpectedly', 'code' => $error['type']]);
        }
    });

    while (true) {
        if (connection_aborted() === 1) {
            break;
        }

        $now = time();
        if (($now - $started_at) >= SSE_MAX_SECONDS) {
            sse_comment('stream window elapsed');
            $stream_ended = true;
            break;
        }

        try {
            $payload = build_leaderboard($conn, $student_id);
            $signature = md5((string) json_encode($payload['entries']));

            if ($signature !== $last_signature) {
                sse_event('leaderboard', $payload);
                $last_signature = $signature;
                $last_sent_at = $now;
            } elseif (($now - $last_sent_at) >= SSE_HEARTBEAT_SECONDS) {
                sse_comment('keepalive');
                $last_sent_at = $now;
            }
        } catch (Throwable $e) {
            sse_event('stream_error', ['error' => $e->getMessage()]);
            $stream_ended = true;
            break;
        }

        sleep(SSE_POLL_SECONDS);
    }

    sse_comment('bye');
}

/**
 * Switch the connection into SSE mode. Diagnostics were already redirected at
 * the top of this file, so from here on only frames reach the client.
 */
function sse_begin() {
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    ob_implicit_flush(true);

    @set_time_limit(0);
    ignore_user_abort(false);

    // The session lock would block every other request from the same student.
    if (function_exists('session_write_close')) {
        session_write_close();
    }

    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    sse_comment('stream open');
}

function sse_event($event, $payload) {
    $body = encode_json($payload);
    $lines = [];

    foreach (explode("\n", $body) as $line) {
        $lines[] = 'data: ' . $line;
    }

    sse_write('event: ' . $event . "\n" . implode("\n", $lines) . "\n\n");
}

function sse_comment($text) {
    sse_write(': ' . str_replace(["\r", "\n"], ' ', $text) . "\n\n");
}

function sse_write($chunk) {
    echo $chunk;
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}
