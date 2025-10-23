<?php
require_once 'auth.php';
require_once 'db_connect.php';

require_login();

$page_title = "Gerenciar Termos";

// Apenas busca dados que são estáticos e necessários para os filtros no carregamento da página.
$companies = [];
$sql_companies = "SELECT id, name FROM companies WHERE status = 'active' ORDER BY name ASC";
$result_companies = $conn->query($sql_companies);
if ($result_companies) {
    while ($row = $result_companies->fetch_assoc()) {
        $companies[] = $row;
    }
}

require_once 'templates/header.php';
?>

<style>
    /* Apenas estilos específicos da página que não estão no style.css principal */
    .admin-container h3 {
        color: #007bff;
        margin-top: 20px;
        margin-bottom: 20px;
    }
    .filter-buttons {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    /* Ajustes para responsividade dos filtros */
    @media (max-width: 991.98px) {
        .form-filters .filter-group {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-buttons {
            flex-direction: column;
            width: 100%;
            gap: 5px;
        }
        .filter-buttons > .btn {
            width: 100%;
        }
        .pagination {
            justify-content: center;
        }
    }
    
    /* Correção de Alinhamento e Altura para Botões de Filtro */
    .filter-buttons > .btn {
        height: 38px; /* Altura fixa, igual aos inputs */
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding-top: 0;
        padding-bottom: 0;
        gap: 8px; /* Adiciona um espaço entre o ícone e o texto */
    }
</style>

<header class="admin-header">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>
</header>

<h3>Termos de Devolução</h3>
<form method="GET" class="form-filters" id="devolution-filters-form">
    <div class="filter-group">
        <div>
            <label for="filter_owner_name" class="form-label">Nome do Proprietário</label>
            <input type="text" id="filter_owner_name" name="filter_owner_name" class="form-control" placeholder="Buscar por nome...">
        </div>
        <div>
            <label for="filter_item_name_dev" class="form-label">Nome do Item</label>
            <input type="text" id="filter_item_name_dev" name="filter_item_name_dev" class="form-control" placeholder="Buscar por item...">
        </div>
        <div class="filter-buttons">
            <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Filtrar</button>
            <button type="button" id="clear-devolution-filters" class="btn btn-outline-secondary"><i class="fas fa-broom"></i> Limpar</button>
        </div>
    </div>
</form>
<div id="devolution-terms-content" class="mb-4"></div>
<div id="devolution-pagination" class="pagination"></div>

<hr style="margin: 40px 0;">

<h3>Termos de Doação</h3>
<form method="GET" class="form-filters" id="donation-filters-form">
    <div class="filter-group">
        <div>
            <label for="filter_status" class="form-label">Status do Termo</label>
            <select id="filter_status" name="filter_status" class="form-select">
                <option value="">Todos os Status</option>
                <option value="Em aprovação">Em aprovação</option>
                <option value="Aprovado">Aprovado</option>
                <option value="Negado">Negado</option>
                <option value="Doado">Doado</option>
            </select>
        </div>
        <div>
            <label for="filter_company" class="form-label">Instituição</label>
            <select id="filter_company" name="filter_company" class="form-select">
                <option value="">Todas as Instituições</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?php echo $company['id']; ?>">
                        <?php echo htmlspecialchars($company['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_start_date" class="form-label">Data Inicial</label>
            <input type="date" id="filter_start_date" name="filter_start_date" class="form-control">
        </div>
        <div>
            <label for="filter_end_date" class="form-label">Data Final</label>
            <input type="date" id="filter_end_date" name="filter_end_date" class="form-control">
        </div>
         <div class="filter-buttons">
            <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Filtrar</button>
            <button type="button" id="clear-donation-filters" class="btn btn-outline-secondary"><i class="fas fa-broom"></i> Limpar</button>
        </div>
    </div>
</form>
<div id="donation-terms-content" class="mb-4"></div>
<div id="donation-pagination" class="pagination"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Seletores de Elementos ---
    const devolutionForm = document.getElementById('devolution-filters-form');
    const donationForm = document.getElementById('donation-filters-form');
    const devolutionContent = document.getElementById('devolution-terms-content');
    const donationContent = document.getElementById('donation-terms-content');
    const devolutionPagination = document.getElementById('devolution-pagination');
    const donationPagination = document.getElementById('donation-pagination');

    // --- Funções Auxiliares ---
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[match]));
    }

    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // --- Funções de Renderização ---

    // Tabela de Devolução (Desktop)
    function renderDevolutionTable(terms) {
        const table = document.createElement('table');
        table.className = 'admin-table table table-bordered table-hover d-none d-lg-table';
        table.innerHTML = `
            <thead class="table-dark">
                <tr><th>ID</th><th>Data</th><th>Item</th><th>Proprietário</th><th>Usuário</th><th>Ações</th></tr>
            </thead>
            <tbody></tbody>`;
        const tbody = table.querySelector('tbody');
        terms.forEach(term => {
            const tr = document.createElement('tr');
            tr.style.cursor = 'pointer';
            tr.onclick = () => window.location.href = `manage_devolutions.php?view_id=${term.id}`;
            const userName = term.returned_by || term.returned_by_username || 'N/A';
            tr.innerHTML = `
                <td>${escapeHTML(term.id)}</td>
                <td>${escapeHTML(term.devolution_timestamp_formatted)}</td>
                <td>${escapeHTML(term.item_name || 'N/A')}</td>
                <td>${escapeHTML(term.owner_name || 'N/A')}</td>
                <td title="${escapeHTML(userName)}">${escapeHTML(userName)}</td>
                <td><a href="manage_devolutions.php?view_id=${term.id}" class="action-icon"><i class="fas fa-file-lines"></i></a></td>`;
            tbody.appendChild(tr);
        });
        return table;
    }

    // Cards de Devolução (Mobile)
    function renderDevolutionCards(terms) {
        const cardContainer = document.createElement('div');
        cardContainer.className = 'row d-lg-none';
        terms.forEach(term => {
            const col = document.createElement('div');
            col.className = 'col-md-6 mb-4';
            const card = document.createElement('div');
            card.className = 'card h-100';
            card.style.cursor = 'pointer';
            card.onclick = () => window.location.href = `manage_devolutions.php?view_id=${term.id}`;
            const userName = term.returned_by || term.returned_by_username || 'N/A';
            card.innerHTML = `
                <div class="card-header d-flex justify-content-between">
                    <strong>Termo #${escapeHTML(term.id)}</strong>
                    <a href="manage_devolutions.php?view_id=${term.id}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> Ver</a>
                </div>
                <div class="card-body">
                    <p class="card-text"><strong>Item:</strong> ${escapeHTML(term.item_name || 'N/A')}</p>
                    <p class="card-text"><strong>Proprietário:</strong> ${escapeHTML(term.owner_name || 'N/A')}</p>
                    <p class="card-text"><strong>Data:</strong> ${escapeHTML(term.devolution_timestamp_formatted)}</p>
                    <p class="card-text"><strong>Usuário:</strong> ${escapeHTML(userName)}</p>
                </div>`;
            col.appendChild(card);
            cardContainer.appendChild(col);
        });
        return cardContainer;
    }

    // Tabela de Doação (Desktop) - CORRIGIDA
    function renderDonationTable(terms) {
        const table = document.createElement('table');
        table.className = 'admin-table table table-bordered table-hover d-none d-lg-table';
        table.innerHTML = `
            <thead class="table-dark">
                <tr><th>ID</th><th>Data</th><th>Instituição</th><th>Status</th><th>Usuário</th><th>Ações</th></tr>
            </thead>
            <tbody></tbody>`;
        const tbody = table.querySelector('tbody');
        terms.forEach(term => {
            const tr = document.createElement('tr');
            tr.style.cursor = 'pointer';
            tr.onclick = () => window.location.href = `view_donation_term_page.php?term_id=${term.term_id}`;
            const userName = term.registered_by || term.registered_by_username || 'N/A';
            tr.innerHTML = `
                <td>${escapeHTML(term.term_id)}</td>
                <td>${escapeHTML(term.created_at_formatted)}</td>
                <td>${escapeHTML(term.company_name || 'N/A')}</td>
                <td><span class="badge bg-${term.status_slug}">${escapeHTML(term.status)}</span></td>
                <td title="${escapeHTML(userName)}">${escapeHTML(userName)}</td>
                <td><a href="view_donation_term_page.php?term_id=${term.term_id}" class="action-icon"><i class="fas fa-file-lines"></i></a></td>`;
            tbody.appendChild(tr);
        });
        return table;
    }

    // Cards de Doação (Mobile) - CORRIGIDO
    function renderDonationCards(terms) {
        const cardContainer = document.createElement('div');
        cardContainer.className = 'row d-lg-none';
        terms.forEach(term => {
            const col = document.createElement('div');
            col.className = 'col-md-6 mb-4';
            const card = document.createElement('div');
            card.className = 'card h-100';
            card.style.cursor = 'pointer';
            card.onclick = () => window.location.href = `view_donation_term_page.php?term_id=${term.term_id}`;
            const userName = term.registered_by || term.registered_by_username || 'N/A';
            card.innerHTML = `
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Termo #${escapeHTML(term.term_id)}</strong>
                    <span class="badge bg-${term.status_slug}">${escapeHTML(term.status)}</span>
                </div>
                <div class="card-body">
                    <p class="card-text"><strong>Instituição:</strong> ${escapeHTML(term.company_name || 'N/A')}</p>
                    <p class="card-text"><strong>Data:</strong> ${escapeHTML(term.created_at_formatted)}</p>
                    <p class="card-text"><strong>Usuário:</strong> ${escapeHTML(userName)}</p>
                </div>
                <div class="card-footer">
                    <a href="view_donation_term_page.php?term_id=${term.term_id}" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-eye"></i> Ver Detalhes</a>
                </div>`;
            col.appendChild(card);
            cardContainer.appendChild(col);
        });
        return cardContainer;
    }

    function renderPagination(element, data, type) {
        element.innerHTML = '';
        const { currentPage, totalPages } = data;
        if (totalPages <= 1) return;

        let pages = [];
        const range = 2;
        for (let i = Math.max(2, currentPage - range); i <= Math.min(totalPages - 1, currentPage + range); i++) {
            pages.push(i);
        }
        if (currentPage - range > 2) pages.unshift('...');
        if (currentPage + range < totalPages - 1) pages.push('...');
        pages.unshift(1);
        pages.push(totalPages);
        pages = [...new Set(pages)];

        if (currentPage > 1) {
            element.innerHTML += `<a href="#" data-page="1" data-type="${type}" class="pagination-link">Primeira</a>`;
        }

        pages.forEach(p => {
            if (p === '...') {
                element.innerHTML += `<span class="pagination-dots">...</span>`;
            } else {
                element.innerHTML += `<a href="#" data-page="${p}" data-type="${type}" class="pagination-link ${p === currentPage ? 'current-page' : ''}">${p}</a>`;
            }
        });

        if (currentPage < totalPages) {
            element.innerHTML += `<a href="#" data-page="${totalPages}" data-type="${type}" class="pagination-link">Última</a>`;
        }
    }

    // --- Lógica Principal ---
    function fetchTerms(params = new URLSearchParams(window.location.search)) {
        devolutionContent.innerHTML = '<p class="alert alert-info">Carregando...</p>';
        donationContent.innerHTML = '<p class="alert alert-info">Carregando...</p>';

        history.pushState(null, '', `?${params.toString()}`);

        fetch(`get_terms_handler.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                // Render Devolution
                devolutionContent.innerHTML = '';
                if (data.devolution.terms.length > 0) {
                    devolutionContent.appendChild(renderDevolutionTable(data.devolution.terms));
                    devolutionContent.appendChild(renderDevolutionCards(data.devolution.terms));
                } else {
                    devolutionContent.innerHTML = '<p class="alert alert-warning">Nenhum termo de devolução encontrado.</p>';
                }
                renderPagination(devolutionPagination, data.devolution.pagination, 'dev');

                // Render Donation
                donationContent.innerHTML = '';
                if (data.donation.terms.length > 0) {
                    donationContent.appendChild(renderDonationTable(data.donation.terms));
                    donationContent.appendChild(renderDonationCards(data.donation.terms));
                } else {
                    donationContent.innerHTML = '<p class="alert alert-warning">Nenhum termo de doação encontrado.</p>';
                }
                renderPagination(donationPagination, data.donation.pagination, 'don');
            })
            .catch(error => {
                console.error("Erro ao buscar termos:", error);
                devolutionContent.innerHTML = '<p class="alert alert-danger">Erro ao carregar dados.</p>';
                donationContent.innerHTML = '<p class="alert alert-danger">Erro ao carregar dados.</p>';
            });
    }

    // --- Event Listeners ---
    const debouncedFetch = debounce(() => {
        const params = new URLSearchParams(new FormData(devolutionForm));
        new FormData(donationForm).forEach((val, key) => params.append(key, val));
        fetchTerms(params);
    }, 350);

    devolutionForm.addEventListener('submit', e => { e.preventDefault(); debouncedFetch(); });
    donationForm.addEventListener('submit', e => { e.preventDefault(); debouncedFetch(); });

    document.getElementById('clear-devolution-filters').addEventListener('click', () => {
        devolutionForm.reset();
        debouncedFetch();
    });

    document.getElementById('clear-donation-filters').addEventListener('click', () => {
        donationForm.reset();
        debouncedFetch();
    });

    document.querySelectorAll('input[type="text"], input[type="date"], select').forEach(el => {
        el.addEventListener('change', debouncedFetch);
    });

    document.addEventListener('click', e => {
        const target = e.target.closest('.pagination-link');
        if (target && !target.classList.contains('current-page')) {
            e.preventDefault();
            const page = target.dataset.page;
            const type = target.dataset.type;
            const params = new URLSearchParams(window.location.search);
            params.set(`page_${type}`, page);
            fetchTerms(params);
        }
    });

    // --- Carga Inicial ---
    fetchTerms();
});
</script>

<?php
require_once 'templates/footer.php';
?>