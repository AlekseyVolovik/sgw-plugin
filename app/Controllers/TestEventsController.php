<?php declare(strict_types=1);

namespace SGWPlugin\Controllers;

use SGWClient;
use SGWPlugin\Classes\Fields;

class TestEventsController
{
    private SGWClient $sgw;
    private ?int $projectId;
    private ?string $sport;

    private string $from;
    private string $to;
    private ?string $status;
    private ?string $period;
    private ?int $competitionId;

    public function __construct(array $params)
    {
        $this->sgw = SGWClient::getInstance();
        $this->projectId = Fields::get_general_project_id();
        $this->sport = Fields::get_general_sport();

        // ѕо умолчанию Ч текуща€ дата
        $today = date('Y-m-d');

        $this->from = isset($_GET['from']) && $_GET['from'] !== '' ? (string)$_GET['from'] : $today;
        $this->to   = isset($_GET['to'])   && $_GET['to']   !== '' ? (string)$_GET['to']   : $this->from;

        $this->status = isset($_GET['status']) ? trim((string)$_GET['status']) : null;   // live
        $this->period = isset($_GET['period']) ? trim((string)$_GET['period']) : null;   // today|upcoming|finished

        $cid = $_GET['competitionId'] ?? null;
        $this->competitionId = ($cid !== null && $cid !== '') ? (int)$cid : null;
    }

    private function canView(): bool
    {
        return function_exists('current_user_can') && current_user_can('manage_options');
    }

    private function fetch(): array
    {
        if (!$this->projectId || !$this->sport) {
            return ['success' => false, 'data' => [], 'error' => 'ProjectId or sport not configured'];
        }

        $params = [];
        // ≈сли передан period/status Ч используем их; иначе берЄм диапазон дат
        if (!empty($this->period)) {
            $params['period'] = $this->period; // today|upcoming|finished
        } elseif (!empty($this->status)) {
            $params['status'] = $this->status; // live
        } else {
            $params['fromDate'] = $this->from;
            $params['toDate']   = $this->to;
        }

        if ($this->competitionId) {
            $params['competitionId'] = $this->competitionId;
        }

        return $this->sgw->api->matchcentre->getMatchCentreEvents(
            $this->projectId,
            $this->sport,
            $params
        );
    }

    private function e($s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function render(): string
    {
        if (!$this->canView()) {
            status_header(403);
            return '<div style="padding:16px">Forbidden</div>';
        }

        // JSON режим: /football/test?json=1
        if (!empty($_GET['json'])) {
            $resp = $this->fetch();
            header('Content-Type: application/json; charset=utf-8');
            return json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $resp = $this->fetch();

        ob_start(); ?>
        <div style="font:14px/1.5 system-ui,Segoe UI,Arial,sans-serif; padding:16px; max-width:1200px;">
            <h2 style="margin:0 0 12px;">Test: getMatchCentreEvents (RAW)</h2>

            <form method="get" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
                <label>From: <input type="date" name="from" value="<?= $this->e($this->from) ?>"></label>
                <label>To: <input type="date" name="to" value="<?= $this->e($this->to) ?>"></label>
                <label>Status:
                    <select name="status">
                        <option value="">Ч</option>
                        <option value="live" <?= $this->status==='live'?'selected':''; ?>>live</option>
                    </select>
                </label>
                <label>Period:
                    <select name="period">
                        <option value="">Ч</option>
                        <option value="today"    <?= $this->period==='today'?'selected':''; ?>>today</option>
                        <option value="upcoming" <?= $this->period==='upcoming'?'selected':''; ?>>upcoming</option>
                        <option value="finished" <?= $this->period==='finished'?'selected':''; ?>>finished</option>
                    </select>
                </label>
                <label>Competition ID:
                    <input type="number" name="competitionId" value="<?= $this->competitionId ? (int)$this->competitionId : '' ?>">
                </label>
                <button type="submit">Apply</button>
                <a href="?from=<?= $this->e($this->from) ?>&to=<?= $this->e($this->to) ?>&json=1" style="margin-left:auto;">View JSON</a>
            </form>

            <details open>
                <summary><strong>Raw response</strong></summary>
                <pre style="white-space:pre-wrap;background:#111;color:#ddd;padding:12px;border-radius:8px;overflow:auto;max-height:70vh;">
<?= $this->e(print_r($resp, true)) ?>
                </pre>
            </details>

            <?php
            if (!empty($resp['success']) && !empty($resp['data']['data'])):
                $events = $resp['data']['data'];
            ?>
                <h3 style="margin-top:20px;">Events list (<?= count($events) ?>)</h3>
                <ol>
                <?php foreach ($events as $idx => $e): ?>
                    <li style="margin-bottom:10px;">
                        <pre style="white-space:pre-wrap;background:#111;color:#ddd;padding:12px;border-radius:8px;overflow:auto;max-height:50vh;">
<?= $this->e(print_r($e, true)) ?>
                        </pre>
                    </li>
                <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <div style="padding:12px;background:#fee;border:1px solid #fbb;margin-top:12px;">
                    Empty or error.
                </div>
            <?php endif; ?>
        </div>
        <?php
        return (string)ob_get_clean();
    }
}
