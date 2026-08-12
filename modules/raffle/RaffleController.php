<?php
require_once __DIR__ . '/RaffleModel.php';

class RaffleController extends Controller
{
    private RaffleModel $raffleModel;

    public function __construct()
    {
        $this->raffleModel = new RaffleModel();
        try {
            $this->raffleModel->ensureTables();
        } catch (Throwable $e) {
            error_log('[RaffleController] ensureTables warning: ' . $e->getMessage());
        }
    }

    public function index()
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        $batches = $this->raffleModel->getBatches();
        $this->view('raffle/index', [
            'pageTitle' => 'Manajemen Undian',
            'batches'   => $batches,
        ]);
    }

    public function saveBatch()
    {
        Auth::requireRoles(['super_admin', 'administrator']);

        $data = [
            'id'         => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name'       => trim($_POST['name']       ?? ''),
            'start_date' => trim($_POST['start_date'] ?? ''),
            'end_date'   => trim($_POST['end_date']   ?? ''),
            'status'     => in_array($_POST['status'] ?? '', ['draft','active','completed'])
                                ? $_POST['status'] : 'draft',
        ];

        if (empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
            $_SESSION['flash_error'] = 'Semua kolom wajib diisi!';
            $this->redirect('/raffle');
            return;
        }

        $ok = $this->raffleModel->saveBatch($data);
        if ($ok) {
            $_SESSION['flash_success'] = 'Batch undian berhasil disimpan.';
        } else {
            $_SESSION['flash_error'] = 'Gagal menyimpan batch.';
        }
        $this->redirect('/raffle');
    }

    public function detail(int $id)
    {
        Auth::requireRoles(['super_admin', 'administrator']);

        $batch = $this->raffleModel->getBatchById($id);
        if (!$batch) {
            $_SESSION['flash_error'] = 'Batch tidak ditemukan.';
            $this->redirect('/raffle');
            return;
        }

        $prizes  = $this->raffleModel->getPrizesByBatch($id);
        $stats   = $this->raffleModel->getTicketStatsByBatch($id);
        $tickets = $this->raffleModel->getTicketsByBatch($id);

        $this->view('raffle/detail', [
            'pageTitle' => 'Detail Undian: ' . htmlspecialchars($batch['name']),
            'batch'     => $batch,
            'prizes'    => $prizes,
            'stats'     => $stats,
            'tickets'   => $tickets,
        ]);
    }

    public function savePrize()
    {
        Auth::requireRoles(['super_admin', 'administrator']);

        $batchId = (int)($_POST['batch_id'] ?? 0);
        $data = [
            'id'       => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'batch_id' => $batchId,
            'name'     => trim($_POST['name'] ?? ''),
        ];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['image']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $dir = __DIR__ . '/../../public/assets/images/event-prizes/';

                // Pastikan direktori ada
                if (!is_dir($dir)) {
                    $mkResult = mkdir($dir, 0755, true);
                    error_log('[Raffle] mkdir result: ' . ($mkResult ? 'OK' : 'FAIL') . ' | dir: ' . $dir);
                }

                if (is_dir($dir)) {
                    $newFileName = 'raffle_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $targetPath  = $dir . $newFileName;
                    if (move_uploaded_file($tmp, $targetPath)) {
                        $data['image_url'] = 'images/event-prizes/' . $newFileName;
                        error_log('[Raffle] upload OK: ' . $targetPath);
                    } else {
                        error_log('[Raffle] move_uploaded_file FAIL | tmp=' . $tmp . ' | target=' . $targetPath);
                    }
                } else {
                    error_log('[Raffle] dir tidak ada dan tidak bisa dibuat: ' . $dir);
                }
            } else {
                error_log('[Raffle] ekstensi tidak didukung: ' . $ext);
            }
        } elseif (isset($_FILES['image'])) {
            error_log('[Raffle] upload error code: ' . $_FILES['image']['error']);
        }


        $ok = $this->raffleModel->savePrize($data);
        if ($ok) {
            $_SESSION['flash_success'] = 'Hadiah berhasil disimpan.';
        } else {
            $_SESSION['flash_error'] = 'Gagal menyimpan hadiah.';
        }
        $this->redirect('/raffle/' . $batchId);
    }

    public function deletePrize()
    {
        Auth::requireRoles(['super_admin', 'administrator']);

        $id      = (int)($_POST['id']       ?? 0);
        $batchId = (int)($_POST['batch_id'] ?? 0);

        $ok = $this->raffleModel->deletePrize($id);
        if ($ok) {
            $_SESSION['flash_success'] = 'Hadiah berhasil dihapus.';
        } else {
            $_SESSION['flash_error'] = 'Gagal menghapus hadiah.';
        }
        $this->redirect('/raffle/' . $batchId);
    }

    public function drawWinner()
    {
        Auth::requireRoles(['super_admin']);

        $prizeId = (int)($_POST['prize_id'] ?? 0);
        $batchId = (int)($_POST['batch_id'] ?? 0);
        $isAjax  = !empty($_POST['_ajax']);

        $result = $this->raffleModel->drawWinner($prizeId, $batchId);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            exit;
        }

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        $this->redirect('/raffle/' . $batchId);
    }
}
