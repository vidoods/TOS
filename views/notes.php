<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="m-0" style="font-weight: 600;">Notes</h2>
            <p class="text-muted m-0">NOTES DATABASE</p>
        </div>
        
        <a href="index.php?view=note_create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> New Note
        </a>
    </div>

    <div class="d-flex gap-2 mb-4">
        <button class="btn btn-sm btn-secondary active">All</button>
        <button class="btn btn-sm btn-outline-secondary">List</button>
    </div>

    <div id="notes-grid-container" class="notes-grid">
        <div class="loading-spinner">Загрузка заметок...</div>
    </div>
</div>