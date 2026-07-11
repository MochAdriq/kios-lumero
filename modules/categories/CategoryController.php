<?php
class CategoryController extends Controller
{
 public function index(): void { Auth::requireRoles(['super_admin','administrator']); $m=new CategoryModel(); $this->view('categories/index',['pageTitle'=>'Kategori & Variant','productCategories'=>$m->productCategories(),'rawCategories'=>$m->rawCategories(),'variants'=>$m->variants()]); }
 public function storeProductCategory(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); (new CategoryModel())->storeProductCategory($_POST); $_SESSION['flash_success']='Kategori produk berhasil disimpan.'; $this->redirect('/categories'); }
 public function updateProductCategory(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); (new CategoryModel())->updateProductCategory($_POST); $_SESSION['flash_success']='Kategori produk berhasil diperbarui.'; $this->redirect('/categories'); }
 public function deleteProductCategory(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); try { (new CategoryModel())->deleteProductCategory((int)($_POST['id']??0)); $_SESSION['flash_success']='Kategori produk berhasil dihapus.'; } catch(Throwable $e) { $_SESSION['flash_error']=$e->getMessage(); } $this->redirect('/categories'); }
 public function storeRawCategory(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); (new CategoryModel())->storeRawCategory($_POST); $_SESSION['flash_success']='Kategori bahan berhasil disimpan.'; $this->redirect('/categories'); }
 public function updateRawCategory(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); (new CategoryModel())->updateRawCategory($_POST); $_SESSION['flash_success']='Kategori bahan berhasil diperbarui.'; $this->redirect('/categories'); }
 public function deleteRawCategory(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); try { (new CategoryModel())->deleteRawCategory((int)($_POST['id']??0)); $_SESSION['flash_success']='Kategori bahan berhasil dihapus.'; } catch(Throwable $e) { $_SESSION['flash_error']=$e->getMessage(); } $this->redirect('/categories'); }
 public function recipesIndex(): void { Auth::requireRoles(['super_admin','administrator']); $this->view('categories/recipes',['pageTitle'=>'Resep / BOM / HPP','items'=>(new CategoryModel())->recipeVariants()]); }
}
