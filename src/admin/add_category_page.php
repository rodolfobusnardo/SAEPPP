<?php
require_once '../auth.php';
require_once '../db_connect.php';
require_admin();

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $pageTitle = "Adicionar Nova Categoria";
    require_once '../templates/header.php';
}
?>

<h3>Adicionar Nova Categoria</h3>
<form action="category_handler.php" method="POST" class="form-admin form-add-category-inline">
    <input type="hidden" name="action" value="add_category">
    <div>
        <label for="name_add">Nome da Categoria</label>
        <input type="text" id="name_add" name="name" required>
    </div>
    <div>
        <label for="code_add">Código</label>
        <input type="text" id="code_add" name="code" required maxlength="10" pattern="[A-Za-z0-9_]+" title="Use letras, números ou underscore." placeholder="Ex: ROP, ELE">
    </div>
    <div class="form-button-group">
        <button type="submit" class="button-primary"><i class="fa-solid fa-plus"></i> Adicionar Categoria</button>
    </div>
</form>

<?php
if (!$is_ajax) {
    require_once '../templates/footer.php';
}
?>