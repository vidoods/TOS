<div class="fade-in">
    <div class="mb-4">
        <h2 class="fw-bold"><?= $lang['my_profile'] ?></h2>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card glass-panel border-0 text-white">
                <div class="card-body p-5 text-center">
                    
                    <div class="mb-4">
                        <img src="assets/logo.png" class="rounded-circle shadow" 
                             style="width: 150px; height: 80px; object-fit: cover;">
                    </div>

                    <h3 id="profile-page-name" class="fw-bold mb-1"><?= $lang['loading'] ?></h3>
                    <p id="profile-page-email" class="text-muted mb-4">...</p>

                    <hr class="border-secondary opacity-25 my-4">

                    <div class="mb-4 text-start bg-dark p-3 rounded border border-secondary" style="--bs-border-opacity: .2;">
                        <label class="form-label text-muted small fw-bold text-uppercase"><?= $lang['language'] ?></label>
                        <select class="form-select bg-dark text-white border-secondary" id="profile-language-select">
                            <option value="en"><?= $lang['english'] ?></option>
                            <option value="ru"><?= $lang['russian'] ?></option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-center gap-5 mt-4">
                        <div class="text-center">
                            <small class="text-muted text-uppercase" style="font-size: 0.7rem;"><?= $lang['joined'] ?></small>
                            <div id="profile-page-date" class="fw-bold fs-5">-</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>