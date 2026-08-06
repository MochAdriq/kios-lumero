<?php
require_once __DIR__ . '/RaffleModel.php';

class RaffleController extends Controller
{
    private $raffleModel;

    public function __construct()
    {
        $this->raffleModel = new RaffleModel();
        $this->raffleModel->ensureTables();
    }

    public function index()
    {
        Auth::requireLogin();
        Auth::requireRole(['admin', 'owner', 'superadmin']);

        $batches = $this->raffleModel->getBatches();
        
        $this->view('layouts/app', [
            'content' => 'raffle/index',
            'title' => 'Manajemen Undian',
            'batches' => $batches
        ]);
    }

    public function saveBatch()
    {
        Auth::requireLogin();
        Auth::requireRole(['admin', 'owner', 'superadmin']);

        $data = [
            'id' => $_POST['id'] ?? null,
            'name' => trim($_POST['name'] ?? ''),
            'start_date' => trim($_POST['start_date'] ?? ''),
            'end_date' => trim($_POST['end_date'] ?? ''),
            'status' => $_POST['status'] ?? 'draft'
        ];

        if (empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
            flash('error', 'Semua kolom wajib diisi!');
            redirect('/raffle');
        }

        if ($this->raffleModel->saveBatch($data)) {
            flash('success', 'Batch undian berhasil disimpan.');
        } else {
            flash('error', 'Gagal menyimpan batch.');
        }
        redirect('/raffle');
    }

    public function detail(int $id)
    {
        Auth::requireLogin();
        Auth::requireRole(['admin', 'owner', 'superadmin']);

        $batch = $this->raffleModel->getBatchById($id);
        if (!$batch) {
            flash('error', 'Batch tidak ditemukan.');
            redirect('/raffle');
        }

        $prizes = $this->raffleModel->getPrizesByBatch($id);
        $stats = $this->raffleModel->getTicketStatsByBatch($id);

        $this->view('layouts/app', [
            'content' => 'raffle/detail',
            'title' => 'Detail Undian: ' . htmlspecialchars($batch['name']),
            'batch' => $batch,
            'prizes' => $prizes,
            'stats' => $stats
        ]);
    }

    public function savePrize()
    {
        Auth::requireLogin();
        Auth::requireRole(['admin', 'owner', 'superadmin']);

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

        if ($this->raffleModel->savePrize($data)) {
            flash('success', 'Hadiah berhasil disimpan.');
        } else {
            flash('error', 'Gagal menyimpan hadiah.');
        }
        redirect('/raffle/' . $batchId);
    }

    public function deletePrize()
    {
        Auth::requireLogin();
        Auth::requireRole(['admin', 'owner', 'superadmin']);
        
        $id = (int)($_POST['id'] ?? 0);
        $batchId = (int)($_POST['batch_id'] ?? 0);

        if ($this->raffleModel->deletePrize($id)) {
            flash('success', 'Hadiah berhasil dihapus.');
        } else {
            flash('error', 'Gagal menghapus hadiah.');
        }
        redirect('/raffle/' . $batchId);
    }

    public function drawWinner()
    {
        Auth::requireLogin();
        Auth::requireRole(['admin', 'owner', 'superadmin']);

        // Check if user is owner/superadmin for security? Or just let admin do it?
        // Usually, only high level roles should draw. Let's assume Owner/Superadmin.
        $userRole = Auth::user()['role'] ?? 'admin';
        if (!in_array($userRole, ['owner', 'superadmin'])) {
            flash('error', 'Hanya Owner atau Superadmin yang dapat melakukan pengundian.');
            redirect('/raffle/' . (int)($_POST['batch_id'] ?? 0));
        }

        $prizeId = (int)($_POST['prize_id'] ?? 0);
        $batchId = (int)($_POST['batch_id'] ?? 0);

        $result = $this->raffleModel->drawWinner($prizeId, $batchId);

        if ($result['success']) {
            flash('success', $result['message']);
        } else {
            flash('error', $result['message']);
        }
        redirect('/raffle/' . $batchId);
    }
}
