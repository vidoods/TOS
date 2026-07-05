<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="m-0" style="font-weight: 600;"><?= $lang['notes_title'] ?></h2>
        </div>
        
        <a href="index.php?view=note_create" id="add-note-btn" class="btn btn-primary">
    <i class="fas fa-plus me-2"></i> 
    <span class="btn-text"><?= $lang['new_note'] ?></span>
</a>    
    </div>

    <!-- Навигация по вкладкам -->
    <div class="account-tabs-nav">
        <button class="tab-btn active" data-tab="notes"><?= $lang['notes'] ?></button>
        <button class="tab-btn" data-tab="assets-insights"><?= $lang['assets_insights'] ?></button>
    </div>

    <!-- Контейнеры для вкладок -->
    <div id="tab-notes" class="tab-content active">
        <!-- Содержимое вкладки Notes -->
        <div id="notes-grid-container" class="notes-grid">
            <div class="row w-100 m-0">
            <div class="col-md-4 mb-4">
                <div class="skeleton-card">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="skeleton" style="height: 20px; width: 45%;"></div>
                        <div class="skeleton" style="height: 20px; width: 15%;"></div>
                    </div>
                    <div class="skeleton" style="height: 15px; width: 80%; margin-bottom: 10px;"></div>
                    <div class="skeleton" style="height: 15px; width: 60%;"></div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="skeleton-card">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="skeleton" style="height: 20px; width: 45%;"></div>
                        <div class="skeleton" style="height: 20px; width: 15%;"></div>
                    </div>
                    <div class="skeleton" style="height: 15px; width: 80%; margin-bottom: 10px;"></div>
                    <div class="skeleton" style="height: 15px; width: 60%;"></div>
                </div>
            </div>
                <div class="col-md-4 mb-4">
                    <div class="skeleton-card">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="skeleton" style="height: 20px; width: 45%;"></div>
                            <div class="skeleton" style="height: 20px; width: 15%;"></div>
        </div>
                        <div class="skeleton" style="height: 15px; width: 80%; margin-bottom: 10px;"></div>
                        <div class="skeleton" style="height: 15px; width: 60%;"></div>
    </div>
</div>
            </div>
        </div>
    </div>

    <div id="tab-assets-insights" class="tab-content">
        <!-- Содержимое вкладки Assets Insights -->
        <div id="assets-insights-grid-container" class="notes-grid">
            <div class="row w-100 m-0">
                <!-- Здесь будут отображаться наблюдения активов -->
            </div>
        </div>
    </div>
</div>
