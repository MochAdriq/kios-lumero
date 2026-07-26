<?php
/**
 * RouletteHelper.php
 * Handles weighted random prize selection with dynamic zero-stock override.
 */
class RouletteHelper {

    /**
     * Executes a weighted random roll for a specific event.
     * Ensures zero-stock items cannot be won regardless of initial chance settings.
     *
     * @param PDO $pdo
     * @param string $eventId
     * @return array Selected prize data
     */
    public static function spinWheel(PDO $pdo, string $eventId) {
        // 1. Fetch all active prizes configured for this event
        $stmt = $pdo->prepare("SELECT * FROM event_prizes WHERE event_id = ? AND is_active = 1");
        $stmt->execute([$eventId]);
        $prizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $validPrizes = [];
        $totalWeight = 0;

        // 2. Filter & Override: If stock <= 0, dynamically lock weight to 0
        foreach ($prizes as $prize) {
            $weight = ((int)$prize['stock'] > 0) ? (float)$prize['chance_percentage'] : 0;
            
            if ($weight > 0) {
                $validPrizes[] = [
                    'id'     => $prize['id'],
                    'name'   => $prize['name'],
                    'weight' => $weight,
                    'stock'  => $prize['stock']
                ];
                $totalWeight += $weight;
            }
        }

        // 3. Safety Fallback: If all limited stocks are exhausted, default to unlimited item
        if ($totalWeight <= 0 || empty($validPrizes)) {
            return self::getFallbackPrize($pdo, $eventId);
        }

        // 4. Execute Weighted Random Selection
        $randomRoll = mt_rand(1, (int)($totalWeight * 100)) / 100;
        $currentWeight = 0;

        foreach ($validPrizes as $vp) {
            $currentWeight += $vp['weight'];
            if ($randomRoll <= $currentWeight) {
                return $vp;
            }
        }
        
        // Final fallback to ensure 100% win rate
        return self::getFallbackPrize($pdo, $eventId);
    }

    /**
     * Retrieves the default guaranteed reward (unlimited promo stock).
     */
    private static function getFallbackPrize(PDO $pdo, string $eventId) {
        $stmt = $pdo->prepare("SELECT id, name FROM event_prizes WHERE event_id = ? AND is_default_fallback = 1 LIMIT 1");
        $stmt->execute([$eventId]);
        $fallback = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$fallback) {
            // Ultimate hardcoded fallback if database is misconfigured
            return ['id' => 0, 'name' => 'Voucher Diskon Lumero'];
        }
        return $fallback;
    }
}
?>
