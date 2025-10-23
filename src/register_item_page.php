<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth.php';
require_once 'db_connect.php';
require_login('index.php?error=pleaselogin');

$pageTitle = "Cadastrar Novo Item Encontrado";

// Busca de categorias e locais (lógica original mantida)
$categories = [];
$sql_cats = "SELECT id, name FROM categories ORDER BY name ASC";
if ($conn) {
    $result_cats = $conn->query($sql_cats);
    if ($result_cats) {
        while ($row = $result_cats->fetch_assoc()) {
            $categories[] = $row;
        }
    }
}
$locations = [];
$sql_locs = "SELECT id, name FROM locations ORDER BY name ASC";
if ($conn) {
    $result_locs = $conn->query($sql_locs);
    if ($result_locs) {
        while ($row = $result_locs->fetch_assoc()) {
            $locations[] = $row;
        }
    }
}

require_once 'templates/header.php';
?>

<style>
    #drop-area {
        border: 2px dashed #dee2e6;
        border-radius: .375rem;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.3s, background-color 0.3s;
    }
    #drop-area.drag-over {
        border-color: #0d6efd;
        background-color: #e7f1ff;
    }
    #preview-container {
        margin-top: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .preview-item {
        position: relative;
        width: 150px;
        height: 150px;
    }
    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: .375rem;
        border: 1px solid #dee2e6;
    }
    .remove-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(0,0,0,0.6);
        color: white;
        border: none;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        cursor: pointer;
        font-weight: bold;
        line-height: 25px;
        text-align: center;
    }
</style>

<div>
    <header class="admin-header mb-4">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </header>

    <?php
    if (isset($_SESSION['success_message'])) {
        echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('" . addslashes($_SESSION['success_message']) . "', 'success'); });</script>";
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('" . addslashes($_SESSION['error_message']) . "', 'error'); });</script>";
        unset($_SESSION['error_message']);
    }
    ?>

    <form id="item-form" action="add_item_handler.php" method="POST" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Nome do item:</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="found_date" class="form-label">Data do achado:</label>
                <input type="date" id="found_date" name="found_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-6">
                <label for="category_id" class="form-label">Categoria:</label>
                <select id="category_id" name="category_id" required style="width: 100%;"></select>
            </div>
            <div class="col-md-6">
                <label for="location_id" class="form-label">Local onde foi encontrado:</label>
                <select id="location_id" name="location_id" required style="width: 100%;"></select>
            </div>
            <div class="col-12">
                <label for="description" class="form-label">Descrição (opcional):</label>
                <textarea id="description" name="description" rows="4" class="form-control"></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Fotos do Item (máximo 2):</label>
                <input type="file" id="file-input" accept="image/*" multiple class="d-none">
                <input type="file" name="item_image_1" id="item_image_1" class="d-none">
                <input type="file" name="item_image_2" id="item_image_2" class="d-none">
                <div id="drop-area">
                    <p>Arraste e solte as imagens aqui, ou clique para selecionar.</p>
                    <small class="text-muted">Formatos aceitos: JPG, PNG, GIF, WebP. Tamanho máximo: 5MB.</small>
                </div>
                <div id="preview-container"></div>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" name="action" value="register" class="btn btn-primary">Cadastrar Item</button>
                <button type="submit" name="action" value="register_and_print" class="btn btn-secondary">Cadastrar e Imprimir</button>
            </div>
        </div>
    </form>
</div>

<?php
require_once 'templates/footer.php';
?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Lógica do Select2 para Categoria e Local (adaptada para preencher com PHP)
$(document).ready(function() {
    $('#category_id').select2({
        placeholder: "Pesquise ou selecione uma categoria",
        allowClear: true,
        data: [
            { id: '', text: '' }, // Adiciona a opção vazia para o placeholder
            <?php foreach ($categories as $category): ?>
            { id: '<?php echo $category['id']; ?>', text: '<?php echo addslashes(htmlspecialchars($category['name'])); ?>' },
            <?php endforeach; ?>
        ]
    });

    $('#location_id').select2({
        placeholder: "Pesquise ou selecione um local",
        allowClear: true,
        data: [
            { id: '', text: '' }, // Adiciona a opção vazia para o placeholder
            <?php foreach ($locations as $location): ?>
            { id: '<?php echo $location['id']; ?>', text: '<?php echo addslashes(htmlspecialchars($location['name'])); ?>' },
            <?php endforeach; ?>
        ]
    });
});
</script>

<script>
// --- NOVA LÓGICA JAVASCRIPT PARA UPLOAD DRAG-AND-DROP ---
document.addEventListener('DOMContentLoaded', () => {
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('file-input');
    const previewContainer = document.getElementById('preview-container');
    const itemForm = document.getElementById('item-form');
    
    // Armazena os arquivos selecionados (objetos File)
    let selectedFiles = [];
    const MAX_FILES = 2;

    // Abrir o seletor de arquivos ao clicar na área de drop
    dropArea.addEventListener('click', () => fileInput.click());

    // Adicionar classe visual ao arrastar arquivos sobre a área
    dropArea.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropArea.classList.add('drag-over');
    });

    // Remover classe visual ao sair da área
    dropArea.addEventListener('dragleave', () => {
        dropArea.classList.remove('drag-over');
    });

    // Lidar com os arquivos soltos na área
    dropArea.addEventListener('drop', (event) => {
        event.preventDefault();
        dropArea.classList.remove('drag-over');
        const files = event.dataTransfer.files;
        handleFiles(files);
    });

    // Lidar com os arquivos selecionados pelo seletor de arquivos
    fileInput.addEventListener('change', () => {
        handleFiles(fileInput.files);
        // Limpa o valor para permitir selecionar o mesmo arquivo novamente
        fileInput.value = '';
    });

    // Função central para processar os arquivos selecionados/soltos
    function handleFiles(files) {
        for (const file of files) {
            if (selectedFiles.length < MAX_FILES && file.type.startsWith('image/')) {
                selectedFiles.push(file);
            }
        }
        updatePreview();
    }

    // Função para atualizar a pré-visualização das imagens
    function updatePreview() {
        previewContainer.innerHTML = ''; // Limpa a pré-visualização
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                
                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '&times;';
                removeBtn.type = 'button'; // Para não submeter o formulário
                removeBtn.onclick = () => {
                    selectedFiles.splice(index, 1); // Remove o arquivo do array
                    updatePreview(); // Atualiza a UI
                };

                previewItem.appendChild(img);
                previewItem.appendChild(removeBtn);
                previewContainer.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        });
    }
    
    // --- PASSO CRUCIAL: Preparar os arquivos para o envio do formulário ---
    itemForm.addEventListener('submit', (event) => {
        // Pega os inputs de arquivo escondidos que serão enviados para o PHP
        const imageInput1 = document.getElementById('item_image_1');
        const imageInput2 = document.getElementById('item_image_2');
        
        // Cria um objeto DataTransfer para manipular a lista de arquivos dos inputs
        const dataTransfer1 = new DataTransfer();
        const dataTransfer2 = new DataTransfer();

        // Adiciona os arquivos selecionados aos objetos DataTransfer
        if (selectedFiles.length > 0) {
            dataTransfer1.items.add(selectedFiles[0]);
        }
        if (selectedFiles.length > 1) {
            dataTransfer2.items.add(selectedFiles[1]);
        }
        
        // Atribui os arquivos aos inputs escondidos
        imageInput1.files = dataTransfer1.files;
        imageInput2.files = dataTransfer2.files;
    });
});
</script>