<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/AnalyzeMealJob.php';

/**
 * Backfill all existing meals with Gemini analysis.
 *
 * Usage:
 *   php Controller/BackfillMealAi.php
 *   php Controller/BackfillMealAi.php --force=1
 *
 * --force=1 will re-analyze even meals already marked analyse_ia=1.
 */
class BackfillMealAi
{
    private $db;
    private $job;

    public function __construct()
    {
        $this->db = config::getConnexion();
        $this->job = new AnalyzeMealJob();
    }

    public function run($force = false)
    {
        $force = (bool)$force;
        $q = $this->db->query('SELECT id_restaurant, meals_json FROM restaurant');
        if (!$q) {
            echo "error: cannot read restaurants\n";
            return;
        }

        $total = 0;
        $processed = 0;
        $skipped = 0;

        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $restaurantId = (int)($row['id_restaurant'] ?? 0);
            $meals = json_decode((string)($row['meals_json'] ?? '[]'), true);
            if ($restaurantId <= 0 || !is_array($meals)) {
                continue;
            }

            foreach ($meals as $meal) {
                if (!is_array($meal)) {
                    continue;
                }
                $mealId = trim((string)($meal['meal_id'] ?? ''));
                if ($mealId === '') {
                    continue;
                }

                $total++;
                $status = (int)($meal['analyse_ia'] ?? 0);
                if (!$force && $status === 1) {
                    $skipped++;
                    continue;
                }

                $this->job->run($restaurantId, $mealId);
                $processed++;
                echo "processed restaurant={$restaurantId} meal={$mealId}\n";
            }
        }

        echo "done total={$total} processed={$processed} skipped={$skipped}\n";
    }
}

function backfill_arg($name, $default = '')
{
    global $argv;
    $prefix = '--' . trim((string)$name) . '=';
    foreach ((array)$argv as $arg) {
        $arg = (string)$arg;
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $default;
}

$force = backfill_arg('force', '0');
$force = in_array(strtolower((string)$force), ['1', 'true', 'yes', 'on'], true);

$runner = new BackfillMealAi();
$runner->run($force);

