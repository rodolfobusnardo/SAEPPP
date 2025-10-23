<?php
require_once '../auth.php';
require_super_admin();

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$roles = [
    'common' => 'Comum',
    'admin' => 'Admin',
    'admin-aprovador' => 'Admin Aprovador',
    'superAdmin' => 'SuperAdmin'
];

if (!$is_ajax) {
    $pageTitle = "Adicionar Novo Usuário";
    require_once '../templates/header.php';
}
?>

<h3>Registrar Novo Usuário</h3>
<form action="user_management_handler.php" method="POST" class="form-admin">
    <input type="hidden" name="action" value="register_user">

    <div class="form-group" style="margin-bottom: 15px;">
        <label for="username_reg">Usuário:</label>
        <input type="text" id="username_reg" name="username" class="form-control" required>
    </div>
    <div class="form-group" style="margin-bottom: 15px;">
        <label for="full_name_reg">Nome Completo:</label>
        <input type="text" id="full_name_reg" name="full_name" class="form-control" maxlength="255">
    </div>
    <div class="form-group" style="margin-bottom: 15px;">
        <label for="role_reg">Função:</label>
        <select id="role_reg" name="role" class="form-control" required>
            <?php foreach($roles as $role_val => $role_name): ?>
                <option value="<?php echo htmlspecialchars($role_val); ?>"><?php echo htmlspecialchars($role_name); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-action-buttons-group" style="margin-top: 20px;">
        <button type="button" class="button-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="button-primary"><i class="fa-solid fa-plus"></i> Registrar</button>
    </div>
</form>

<?php
if (!$is_ajax) {
    require_once '../templates/footer.php';
}
?>