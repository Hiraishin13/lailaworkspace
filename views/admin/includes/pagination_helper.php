<?php
/**
 * Helper de pagination pour les pages admin
 */

function renderPagination($page, $total_pages, $params = []) {
    if ($total_pages <= 1) return '';
    
    $query_string = http_build_query($params);
    $query_string = $query_string ? '&' . $query_string : '';
    
    $start_page = max(1, $page - 2);
    $end_page = min($total_pages, $page + 2);
    
    ob_start();
    ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted">
            Page <?= $page ?> sur <?= $total_pages ?>
        </div>
        <nav aria-label="Pagination">
            <ul class="pagination pagination-sm mb-0">
                <!-- Précédent -->
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $query_string ?>">
                            <i class="bi bi-chevron-left"></i> Précédent
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">
                            <i class="bi bi-chevron-left"></i> Précédent
                        </span>
                    </li>
                <?php endif; ?>
                
                <!-- Première page -->
                <?php if ($start_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?= $query_string ?>">1</a>
                    </li>
                    <?php if ($start_page > 2): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Pages centrales -->
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $query_string ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <!-- Dernière page -->
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $total_pages ?><?= $query_string ?>">
                            <?= $total_pages ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Suivant -->
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $query_string ?>">
                            Suivant <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">
                            Suivant <i class="bi bi-chevron-right"></i>
                        </span>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php
    return ob_get_clean();
}

function renderPerPageSelector($per_page, $options = [10, 20, 50, 100]) {
    ob_start();
    ?>
    <div class="col-md-2">
        <label class="form-label">Par page</label>
        <select class="form-select" name="per_page">
            <?php foreach ($options as $option): ?>
                <option value="<?= $option ?>" <?= $per_page === $option ? 'selected' : '' ?>><?= $option ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
    return ob_get_clean();
}

function getPaginationParams($default_per_page = 20) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : $default_per_page;
    $per_page = in_array($per_page, [10, 20, 50, 100]) ? $per_page : $default_per_page;
    $offset = ($page - 1) * $per_page;
    
    return [
        'page' => $page,
        'per_page' => $per_page,
        'offset' => $offset
    ];
}
?> 