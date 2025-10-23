<?php
// A sessão agora é iniciada pelo 'auth.php' (incluído no header), então a linha session_start() foi removida daqui.

require_once 'auth.php';
require_once 'db_connect.php';

require_login();

$pageTitle = "Itens Encontrados";

// Busca categorias para os filtros
$filter_categories = [];
$sql_filter_cats = "SELECT id, name FROM categories ORDER BY name ASC";
$result_filter_cats = $conn->query($sql_filter_cats);
if ($result_filter_cats && $result_filter_cats->num_rows > 0) {
    while ($row_fc = $result_filter_cats->fetch_assoc()) {
        $filter_categories[] = $row_fc;
    }
}

// Busca locais para os filtros
$filter_locations = [];
$sql_filter_locs = "SELECT id, name FROM locations ORDER BY name ASC";
$result_filter_locs = $conn->query($sql_filter_locs);
if ($result_filter_locs && $result_filter_locs->num_rows > 0) {
    while ($row_fl = $result_filter_locs->fetch_assoc()) {
        $filter_locations[] = $row_fl;
    }
}

// Inclui o manipulador de itens para a carga inicial da página
require_once 'get_items_handler.php';

$current_user_is_admin = is_admin();

require_once 'templates/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlalHpgO7oR/7X7k9+4o0p4l+7g4+1z6l5r5j5O6p6u+2r5z4b5+9z5o3l5p5u5w5t5v5u5w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<div>
    <header class="admin-header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </header>

    <?php
    if (isset($_GET['message_type']) && isset($_GET['message'])) {
        $message_type = htmlspecialchars($_GET['message_type']);
        $message = htmlspecialchars($_GET['message']);
        $alert_class = $message_type === 'success' ? 'alert-success' : 'alert-danger';
        echo "<div class='alert {$alert_class}'>{$message}</div>";
    }
    ?>

    <div class="form-filters">
        <form id="filterForm" method="GET" action="">
            <div class="filter-group">
                <div>
                    <label for="filter_item_name">Nome do Item (contém):</label>
                    <input type="text" id="filter_item_name" name="filter_item_name" class="form-control" value="<?php echo htmlspecialchars($_GET['filter_item_name'] ?? ''); ?>" placeholder="Digite parte do nome...">
                </div>
                <div>
                    <label for="filter_category_id">Categoria:</label>
                    <select id="filter_category_id" name="filter_category_id" class="form-select">
                        <option value="">Todas as Categorias</option>
                        <?php foreach ($filter_categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo (isset($_GET['filter_category_id']) && $_GET['filter_category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_status">Status do Item:</label>
                    <select id="filter_status" name="filter_status" class="form-select">
                        <option value="" <?php echo (!isset($_GET['filter_status']) || $_GET['filter_status'] == '') ? 'selected' : ''; ?>>Todos os Status</option>
                        <option value="Pendente" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
                        <option value="Devolvido" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Devolvido') ? 'selected' : ''; ?>>Devolvido</option>
                        <option value="Doado" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Doado') ? 'selected' : ''; ?>>Doado</option>
                        <option value="Descartado" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Descartado') ? 'selected' : ''; ?>>Descartado</option>
                        <option value="Em Aprovação" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Em Aprovação') ? 'selected' : ''; ?>>Em Aprovação</option>
                        <option value="Aprovado" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                    </select>
                </div>
                <div>
                    <label for="filter_barcode">Código de Barras:</label>
                    <input type="text" id="filter_barcode" name="filter_barcode" class="form-control" value="<?php echo htmlspecialchars($_GET['filter_barcode'] ?? ''); ?>" placeholder="Digite o código de barras...">
                </div>

                <div>
                    <label for="filter_location_id">Local:</label>
                    <select id="filter_location_id" name="filter_location_id" class="form-select">
                        <option value="">Todos os Locais</option>
                        <?php foreach ($filter_locations as $location): ?>
                            <option value="<?php echo htmlspecialchars($location['id']); ?>" <?php echo (isset($_GET['filter_location_id']) && $_GET['filter_location_id'] == $location['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_days_waiting">Tempo Aguardando:</label>
                    <select id="filter_days_waiting" name="filter_days_waiting" class="form-select">
                        <option value="">Qualquer</option>
                        <option value="0-7" <?php echo (isset($_GET['filter_days_waiting']) && $_GET['filter_days_waiting'] == '0-7') ? 'selected' : ''; ?>>0-7 dias</option>
                        <option value="8-30" <?php echo (isset($_GET['filter_days_waiting']) && $_GET['filter_days_waiting'] == '8-30') ? 'selected' : ''; ?>>8-30 dias</option>
                        <option value="31-9999" <?php echo (isset($_GET['filter_days_waiting']) && $_GET['filter_days_waiting'] == '31-9999') ? 'selected' : ''; ?>>31+ dias</option>
                    </select>
                </div>
                <div>
                    <label for="filter_found_date_start">Achado de (data):</label>
                    <input type="date" id="filter_found_date_start" name="filter_found_date_start" class="form-control" value="<?php echo htmlspecialchars($_GET['filter_found_date_start'] ?? ''); ?>">
                </div>
                <div>
                    <label for="filter_found_date_end">Até (data):</label>
                    <input type="date" id="filter_found_date_end" name="filter_found_date_end" class="form-control" value="<?php echo htmlspecialchars($_GET['filter_found_date_end'] ?? ''); ?>">
                </div>
            </div>

            <div class="filter-buttons">
                <button type="submit" class="button-filter"><i class="fas fa-check"></i> Aplicar</button>
                <a href="home.php" class="button-filter-clear" style="text-decoration: none;"><i class="fas fa-broom"></i> Limpar</a>
            </div>
        </form>
    </div>
    <hr>
    
    <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-lg-center gap-3 mb-3">
        <?php if ($current_user_is_admin): ?>
        <div class="btn-toolbar w-100 d-grid d-lg-flex flex-wrap justify-content-center justify-content-lg-start gap-2" role="toolbar">
            <div class="btn-group" role="group">
                <?php
                $filter_keys = ['filter_item_name', 'filter_barcode', 'filter_category_id', 'filter_status', 'filter_location_id', 'filter_days_waiting', 'filter_found_date_start', 'filter_found_date_end'];
                $is_filter_active = false;
                foreach ($filter_keys as $key) {
                    if (!empty($_GET[$key])) {
                        $is_filter_active = true;
                        break;
                    }
                }
                $tooltip_attr = '';
                $checkbox_attrs = '';
                if (!$is_filter_active) {
                    $tooltip_attr = 'data-bs-toggle="tooltip" data-bs-title="Para fazer uso desta funcionalidade, primeiro aplique algum filtro.<br>(Por exemplo: selecione e aplique o filtro \'Pendente\')."';
                    $checkbox_attrs = 'disabled';
                }
                ?>
                <span class="input-group-text d-flex justify-content-center w-100" <?php echo $tooltip_attr; ?>>
                    <input class="form-check-input mt-0" type="checkbox" id="selectFilteredCheckbox" <?php echo $checkbox_attrs; ?>>
                    <label class="form-check-label ms-2" for="selectFilteredCheckbox">Selecionar Filtrados</label>
                </span>
            </div>
            <div class="btn-group d-flex" role="group">
                <button id="devolverButton" class="btn btn-secondary flex-fill" disabled>Devolver</button>
                <button id="doarButton" class="btn btn-secondary flex-fill" disabled>Doar</button>
                <button id="descartarButton" class="btn btn-secondary flex-fill" disabled>Descartar</button>
            </div>
            <div class="btn-group d-grid" role="group">
                <button id="imprimirCodBarrasButton" class="btn btn-info" disabled>Imprimir Cód. Barras</button>
            </div>
        </div>
        <?php endif; ?>
        <div id="pagination-top" class="pagination justify-content-center mt-3 mt-lg-0"></div>
    </div>

    <div id="itemListContainer"></div>

    <div id="table-footer" class="table-footer-container" style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
        <div id="item-count-container" class="item-count-info"></div>
        <div id="pagination-bottom" class="pagination"></div>
    </div>
</div>

<div id="itemDetailModal" class="modal" style="display: none;"></div>
<div id="global-tooltip" style="display: none;"></div>

<script>
// Passa as variáveis do PHP para o JS para a carga inicial
const initial_php_items = <?php echo json_encode($items); ?>;
const initialTotalItems = <?php echo (int)($total_items ?? 0); ?>;
const initialTotalPages = <?php echo (int)($total_pages ?? 1); ?>;
const initialCurrentPage = <?php echo (int)($current_page ?? 1); ?>;
const current_user_is_admin = <?php echo json_encode($current_user_is_admin); ?>;
</script>

<?php require_once 'templates/footer.php'; ?>