<?php
class RecipeController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        $m = new RecipeModel();
        
        $this->view('recipes/index', [
            'recipes'    => $m->listRecipes(),
            'subRecipes' => $m->listSubRecipes(),
            'units'      => (new InventoryModel())->units(),
            'pageTitle'  => 'Pengaturan Resep'
        ]);
    }


    public function showByVariant($variantId): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        
        try {
            $recipeId = (new RecipeModel())->findOrCreateByVariant((int)$variantId);
            $this->redirect('/recipes/' . $recipeId);
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('/products');
        }
    }

    public function show($recipeId): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        $m = new RecipeModel();
        $recipe = $m->getRecipe((int)$recipeId);
        
        if (!$recipe) {
            $_SESSION['flash_error'] = 'Resep tidak ditemukan.';
            $this->redirect('/recipes');
            return;
        }

        $recipeOutletId = (int)($recipe['outlet_id'] ?? 1);
        $invModel = new InventoryModel();
        $this->view('recipes/show', [
            'recipe'       => $recipe,
            'rawMaterials' => $invModel->list('', 0, $recipeOutletId),
            'subRecipes'   => $m->listSubRecipes($recipeOutletId),
            'units'        => $invModel->units(),
            'pageTitle'    => 'Detail Resep / HPP'
        ]);
    }


    public function recalculate($recipeId): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();
        
        $hpp = (new RecipeModel())->recalculate((int)$recipeId, Auth::id());
        Audit::log('recalculate_hpp', 'recipes', (int)$recipeId);
        
        $_SESSION['flash_success'] = 'HPP berhasil dihitung ulang (Rp ' . number_format($hpp, 0, ',', '.') . ').';
        $this->redirect('/recipes/' . $recipeId);
    }

    public function recalculateAll(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();
        
        $m = new RecipeModel();
        $count = $m->recalculateAll(Auth::id() ?: 1);
        Audit::log('recalculate_all_hpp', 'recipes', 0, null, ['count' => $count]);
        
        $_SESSION['flash_success'] = "Berhasil mensinkronkan ulang HPP untuk {$count} resep.";
        $this->redirect('/recipes');
    }

    public function addItem($recipeId): void
    {
        Auth::requireRoles(['super_admin', 'administrator']); verify_csrf();
        $itemType = $_POST['item_type'] ?? 'raw_material';
        $itemId = (int)($_POST['item_id'] ?? 0);
        $qty = (float)($_POST['qty'] ?? 0);
        $unitId = (int)($_POST['unit_id'] ?? 0);
        
        if ($itemId <= 0 || $qty <= 0 || $unitId <= 0) {
            $_SESSION['flash_error'] = 'Data bahan tidak valid.';
        } else {
            try {
                (new RecipeModel())->addItem((int)$recipeId, $itemType, $itemId, $qty, $unitId);
                $_SESSION['flash_success'] = 'Bahan berhasil ditambahkan ke resep.';
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        $this->redirect('/recipes/' . $recipeId);
    }

    public function removeItem($itemId): void
    {
        Auth::requireRoles(['super_admin', 'administrator']); verify_csrf();
        $recipeId = (int)($_POST['recipe_id'] ?? 0);
        try {
            (new RecipeModel())->removeItem((int)$itemId, Auth::id());
            $_SESSION['flash_success'] = 'Bahan berhasil dihapus dari resep.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        $this->redirect('/recipes/' . $recipeId);
    }

}
