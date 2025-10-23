<?php
require_once '../auth.php';
require_admin();

// Sanitize input from GET parameters
$form_action = htmlspecialchars($_GET['form_action'] ?? 'default_handler.php');
$item_id = htmlspecialchars($_GET['id'] ?? '0');
$item_name = htmlspecialchars($_GET['item_name'] ?? 'item');
$hidden_input_name = htmlspecialchars($_GET['input_name'] ?? 'id');
$action_value = htmlspecialchars($_GET['action_value'] ?? 'delete');
$message = htmlspecialchars($_GET['message'] ?? 'Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.');

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    // Prevent direct access if not via AJAX
    die("Acesso direto não permitido.");
}
?>

<div class="confirm-delete-modal">
    <h3>Confirmação de Exclusão</h3>
    <p><?php echo $message; ?></p>

    <form action="<?php echo $form_action; ?>" method="POST" style="margin-top: 20px;">
        <input type="hidden" name="action" value="<?php echo $action_value; ?>">
        <input type="hidden" name="<?php echo $hidden_input_name; ?>" value="<?php echo $item_id; ?>">

        <div class="form-action-buttons-group" style="display: flex; justify-content: center; gap: 15px;">
            <button type="button" class="button-secondary" onclick="closeModal()">Cancelar</button>
            <button type="submit" class="button-delete">Confirmar Exclusão</button>
        </div>
    </form>
</div>

<style>
.button-delete {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}
.button-delete:hover {
    background-color: #c82333;
}
.button-secondary {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}
.button-secondary:hover {
    background-color: #5a6268;
}
</style>