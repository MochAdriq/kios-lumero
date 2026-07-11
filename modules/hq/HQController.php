<?php
class HQController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin']);
        $model = new HQModel();
        $branches = $model->allBranchSummary();
        $totals = $model->totalsSummary();
        $weeklyChart = $model->weeklyChartData(7);

        $openBranchCount = 0;
        $attentionBranchCount = 0;
        $criticalBranchCount = 0;
        $totalUsers = 0;
        $healthScoreTotal = 0.0;
        foreach ($branches as &$branch) {
            $isOpen = (string)($branch['store_status'] ?? '') === 'open';
            $users = (int)($branch['user_count'] ?? 0);
            $trx = (int)($branch['today_trx'] ?? 0);
            $revenue = (float)($branch['today_revenue'] ?? 0);
            $profit = (float)($branch['today_profit'] ?? 0);

            $healthScore = 100;
            if (!$isOpen) {
                $healthScore -= 35;
            }
            if ($users <= 0) {
                $healthScore -= 25;
            } elseif ($users === 1) {
                $healthScore -= 5;
            }
            if ($trx <= 0) {
                $healthScore -= 30;
            } elseif ($trx < 5) {
                $healthScore -= 10;
            }
            if ($revenue <= 0) {
                $healthScore -= 10;
            } else {
                $margin = $profit / $revenue;
                if ($margin < 0.10) {
                    $healthScore -= 15;
                } elseif ($margin < 0.20) {
                    $healthScore -= 8;
                }
            }
            $healthScore = max(0, min(100, $healthScore));
            $healthLevel = $healthScore >= 80 ? 'healthy' : ($healthScore >= 60 ? 'warning' : 'critical');
            $healthLabel = $healthScore >= 80 ? 'Sehat' : ($healthScore >= 60 ? 'Waspada' : 'Kritis');

            $branch['health_score'] = $healthScore;
            $branch['health_level'] = $healthLevel;
            $branch['health_label'] = $healthLabel;

            if ($isOpen) {
                $openBranchCount++;
            }
            if ($healthScore < 70) {
                $attentionBranchCount++;
            }
            $totalUsers += max(0, $users);
            $healthScoreTotal += $healthScore;
            if ($healthLevel === 'critical') {
                $criticalBranchCount++;
            }
        }
        unset($branch);

        $totalTrx = (int)($totals['total_trx'] ?? 0);
        $totalRevenue = (float)($totals['total_revenue'] ?? 0);
        $avgTicket = $totalTrx > 0 ? ($totalRevenue / $totalTrx) : 0.0;
        $avgHealthScore = count($branches) > 0 ? ($healthScoreTotal / count($branches)) : 0.0;

        $this->view('hq/index', [
            'pageTitle'  => 'Dashboard Pusat (HQ)',
            'bizDate'    => function_exists('business_date') ? business_date() : today(),
            'branches'   => $branches,
            'totals'     => $totals,
            'weeklyChart'=> $weeklyChart,
            'stats'      => [
                'branch_count' => count($branches),
                'open_branch_count' => $openBranchCount,
                'closed_branch_count' => max(0, count($branches) - $openBranchCount),
                'attention_branch_count' => $attentionBranchCount,
                'critical_branch_count' => $criticalBranchCount,
                'total_users' => $totalUsers,
                'avg_ticket' => $avgTicket,
                'avg_health_score' => $avgHealthScore,
            ],
        ]);
    }

    public function report(): void
    {
        Auth::requireRoles(['super_admin']);
        $model = new HQModel();
        $rawFrom = trim((string)($_GET['from'] ?? ''));
        $rawTo = trim((string)($_GET['to'] ?? ''));
        $from = DateTime::createFromFormat('Y-m-d', $rawFrom) ? $rawFrom : date('Y-m-01');
        $to = DateTime::createFromFormat('Y-m-d', $rawTo) ? $rawTo : today();
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $branchReport = $model->crossBranchReport($from, $to);
        $totals = [
            'revenue' => 0.0,
            'hpp' => 0.0,
            'gross_profit' => 0.0,
            'expense' => 0.0,
            'net_profit' => 0.0,
            'branches' => count($branchReport),
            'days_reported' => 0,
        ];
        foreach ($branchReport as $row) {
            $totals['revenue'] += (float)($row['revenue'] ?? 0);
            $totals['hpp'] += (float)($row['hpp'] ?? 0);
            $totals['gross_profit'] += (float)($row['gross_profit'] ?? 0);
            $totals['expense'] += (float)($row['expense'] ?? 0);
            $totals['net_profit'] += (float)($row['net_profit'] ?? 0);
            $totals['days_reported'] += (int)($row['days_reported'] ?? 0);
        }
        $periodDays = ((new DateTime($from))->diff(new DateTime($to))->days ?? 0) + 1;
        $totals['period_days'] = max(1, $periodDays);
        $totals['avg_daily_revenue'] = $totals['revenue'] / $totals['period_days'];
        $totals['avg_daily_net_profit'] = $totals['net_profit'] / $totals['period_days'];

        $topNet = $branchReport;
        usort($topNet, fn(array $a, array $b): int => ((float)($b['net_profit'] ?? 0)) <=> ((float)($a['net_profit'] ?? 0)));

        $this->view('hq/report', [
            'pageTitle'    => 'Laporan Gabungan Semua Cabang',
            'from'         => $from,
            'to'           => $to,
            'branchReport' => $branchReport,
            'totals'       => $totals,
            'topNet'       => array_slice($topNet, 0, 3),
        ]);
    }
}
