<?php
require_once '../auth.php';
require_once '../db_connect.php';
require_admin();

$category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$category = null;

if ($category_id) {
    $stmt = $conn->prepare("SELECT id, name, code FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $category = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$category) {
    die('<p class="alert alert-danger">Categoria não encontrada ou ID inválido.</p>');
}

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $pageTitle = "Editar Categoria";
    require_once '../templates/header.php';
}
?>

<h3>Editar Categoria</h3>
<form action="category_handler.php" method="POST" class="form-admin">
    <input type="hidden" name="action" value="edit_category">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($category['id']); ?>">

    <div class="form-group">
        <label for="name_edit">Nome da Categoria:</label>
        <input type="text" id="name_edit" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
    </div>
    <div class="form-group">
        <label for="code_edit">Código (ex: ROP, ELE, max 10 chars):</label>
        <input type="text" id="code_edit" name="code" value="<?php echo htmlspecialchars($category['code']); ?>" required maxlength="10" pattern="[A-Za-z0-9_]+" title="Use letras, números ou underscore.">
    </div>

    <div class="form-action-buttons-group">
        <button type="button" class="button-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="button-primary">Salvar Alterações</button>
    </div>
</form>

<?php
if (!$is_ajax) {
    require_once '../templates/footer.php';
}
?>