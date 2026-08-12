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
            // Tabel mungkin sudah ada atau ada constraint issue — lanjutkan saja
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
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name' => trim($_POST['name'] ?? ''),
            'start_date' => trim($_POST['start_date'] ?? ''),
            'end_date' => trim($_POST['end_date'] ?? ''),
            'status' => in_array($_POST['status'] ?? '', ['draft','active','completed']) ? $_POST['status'] : 'draft'
        ];

        if (empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
            flash('error', 'Semua kolom wajib diisi!');
            $this->redirect('/raffle');
            return;
        }

        $ok = $this->raffleModel->saveBatch($data);
        flash($ok ? 'success' : 'error', $ok ? 'Batch undian berhasil disimpan.' : 'Gagal menyimpan batch.');
        $this->redirect('/raffle');
    }

    public function detail(int $id)
    {
        Auth::requireRoles(['super_admin', 'administrator']);

        $batch = $this->raffleModel->getBatchById($id);
        if (!$batch) {
            flash('error', 'Batch tidak ditemukan.');
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
            'id' => $_POST['id'] ?? null,
            'batch_id' => $batchId,
            'name' => trim($_POST['name'] ?? '')
        ];

        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['image']['tmp_name'];
            $name = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $dir = __DIR__ . '/../../public/assets/images/event-prizes/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $newFileName = 'raffle_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                if (move_uploaded_file($tmp, $dir . $newFileName)) {
                    $data['image_url'] = 'images/event-prizes/' . $newFileName;
                }
            }
        }

        $ok = $this->raffleModel->savePrize($data);
        flash($ok ? 'success' : 'error', $ok ? 'Hadiah berhasil disimpan.' : 'Gagal menyimpan hadiah.');
        $this->redirect('/raffle/' . $batchId);
    }

    public function deletePrize()
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        
        $id = (int)($_POST['id'] ?? 0);
        $batchId = (int)($_POST['batch_id'] ?? 0);

        $ok = $this->raffleModel->deletePrize($id);
        flash($ok ? 'success' : 'error', $ok ? 'Hadiah berhasil dihapus.' : 'Gagal menghapus hadiah.');
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

        flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/raffle/' . $batchId);
    }
}
