<?php
require_once '../auth.php';
require_once '../db_connect.php';
require_admin();

$location_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$location = null;

if ($location_id) {
    $stmt = $conn->prepare("SELECT id, name FROM locations WHERE id = ?");
    $stmt->bind_param("i", $location_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $location = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$location) {
    die('<p class="alert alert-danger">Local não encontrado ou ID inválido.</p>');
}

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $pageTitle = "Editar Local";
    require_once '../templates/header.php';
}
?>

<h3>Editar Local</h3>
<form action="location_handler.php" method="POST" class="form-admin">
    <input type="hidden" name="action" value="edit_location">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($location['id']); ?>">

    <div class="form-group">
        <label for="name_edit_loc">Nome do Local:</label>
        <input type="text" id="name_edit_loc" name="name" class="form-control" value="<?php echo htmlspecialchars($location['name']); ?>" required>
    </div>

    <div class="form-action-buttons-group" style="margin-top: 20px;">
        <button type="button" class="button-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="button-primary">Salvar Alterações</button>
    </div>
</form>

<?php
if (!$is_ajax) {
    require_once '../templates/footer.php';
}
?>