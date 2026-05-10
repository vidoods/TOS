<div class="fade-in">
    <div class="system-header mb-4">
        <h1><?= $lang['trading_strategy'] ?></h1>
    </div>

    <div class="d-flex justify-content-end mb-4">
        <a href="index.php?view=strategy_create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> <?= $lang['add_module'] ?>
        </a>
    </div>

    <div id="strategy-grid" class="strategy-grid">
        <div class="loading-spinner"><?= $lang['loading_system'] ?></div>
    </div>
</div>