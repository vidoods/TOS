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