<?php
$pdo = new PDO("mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4", "u643003184_kios_lumero", "Lawmotion1!@#");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$phone = "086595906906";
$cleanPhone = preg_replace('/[^0-9]/', '', $phone);

// find member
$stmt = $pdo->prepare("SELECT id FROM members WHERE phone LIKE ?");
$stmt->execute(['%' . substr($cleanPhone, -9)]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if ($member) {
    $memberId = $member['id'];
    
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM reward_claims WHERE user_id = ?")->execute([$memberId]);
        $pdo->prepare("DELETE FROM member_point_logs WHERE member_id = ?")->execute([$memberId]);
        $pdo->prepare("DELETE FROM member_activity_logs WHERE member_id = ?")->execute([$memberId]);
        $pdo->prepare("DELETE FROM member_login_otps WHERE phone LIKE ?")->execute(['%' . substr($cleanPhone, -9)]);
        $pdo->prepare("DELETE FROM point_reward_redemptions WHERE member_id = ?")->execute([$memberId]);
        
        // Nullify foreign keys
        try { $pdo->prepare("UPDATE receipt_claims SET claimed_by_member_id = NULL WHERE claimed_by_member_id = ?")->execute([$memberId]); } catch (Exception $e) {}
        try { $pdo->prepare("UPDATE orders SET member_id = NULL WHERE member_id = ?")->execute([$memberId]); } catch (Exception $e) {}
        
        $pdo->prepare("DELETE FROM members WHERE id = ?")->execute([$memberId]);
        
        $pdo->commit();
        echo "Member Teddy Lesmana (ID: $memberId, Phone: $phone) and all associated records have been successfully deleted.\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error deleting: " . $e->getMessage() . "\n";
    }
} else {
    echo "Member with phone $phone not found.\n";
}
