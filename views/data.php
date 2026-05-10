<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0" style="font-weight: 600;"><?= $lang['data_winrate_analysis'] ?></h2>
    </div>

    <div class="data-grid-4 mb-4">
        
        <div class="data-card glass-panel p-4">
            <div class="data-header">
                <i class="fas fa-arrows-alt-v"></i>
                <span class="fw-bold"><?= $lang['direction'] ?></span>
            </div>
            <div id="list-direction" class="data-list-container">
                <div class="loading-spinner small"><?= $lang['loading'] ?></div>
            </div>
        </div>

        <div class="data-card glass-panel p-4">
            <div class="data-header">
                <i class="fas fa-layer-group"></i>
                <span class="fw-bold"><?= $lang['trading_style'] ?></span>
            </div>
            <div id="list-style" class="data-list-container">
                <div class="loading-spinner small"><?= $lang['loading'] ?></div>
            </div>
        </div>

        <div class="data-card glass-panel p-4">
            <div class="data-header">
                <i class="far fa-clock"></i>
                <span class="fw-bold"><?= $lang['entry_tf'] ?></span>
            </div>
            <div id="list-timeframe" class="data-list-container">
                <div class="loading-spinner small"><?= $lang['loading'] ?></div>
            </div>
        </div>

        <div class="data-card glass-panel p-4">
            <div class="data-header">
                <i class="fas fa-crosshairs"></i>
                <span class="fw-bold"><?= $lang['model_execution'] ?></span>
            </div>
            <div id="list-model" class="data-list-container">
                <div class="loading-spinner small"><?= $lang['loading'] ?></div>
            </div>
        </div>

    </div>

    <div class="glass-panel p-4">
        <div class="data-header mb-3">
            <i class="fas fa-coins"></i>
            <span class="fw-bold"><?= $lang['pairs_performance'] ?></span>
        </div>
        <div id="list-pairs" class="pairs-grid-layout">
            <div class="loading-spinner small"><?= $lang['loading'] ?></div>
        </div>
    </div>
</div>