<?php
require_once '../auth.php';
require_once '../db_connect.php';
require_admin();

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $pageTitle = "Adicionar Novo Local";
    require_once '../templates/header.php';
}
?>

<h3>Adicionar Novo Local</h3>
<form action="location_handler.php" method="POST" class="form-admin">
    <input type="hidden" name="action" value="add_location">
    <div class="form-group">
        <label for="name_add_loc">Nome do Local</label>
        <input type="text" id="name_add_loc" name="name" class="form-control" required>
    </div>
    <div class="form-action-buttons-group" style="margin-top: 20px;">
        <button type="button" class="button-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="button-primary"><i class="fa-solid fa-plus"></i> Adicionar Local</button>
    </div>
</form>

<?php
if (!$is_ajax) {
    require_once '../templates/footer.php';
}
?>