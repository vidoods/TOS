<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="m-0" style="font-weight: 600;"><?= $lang['notes_title'] ?></h2>
        </div>
        
        <a href="index.php?view=note_create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> <?= $lang['new_note'] ?>
        </a>
    </div>
	</br>
    <div id="notes-grid-container" class="notes-grid">
        <div class="loading-spinner"><?= $lang['loading_notes'] ?></div>
    </div>
</div>