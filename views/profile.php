<?php

// Подключаем файл с функциями перевода
require_once __DIR__ . '/api/handlers/lang.php';

// Функция для получения перевода из базы данных
function get_translation($key) {
    global $user_id;
    return get_translation($key, $user_id);
}
?>
<div class="fade-in">
    <div class="mb-4">
        <h2 class="fw-bold"><?php echo get_translation('my_profile'); ?></h2>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card glass-panel border-0 text-white">
                <div class="card-body p-5 text-center">
                    
                    <div class="mb-4">
                        <img src="assets/logo.png" class="rounded-circle shadow" 
                             style="width: 150px; height: 80px;">
                    </div>

                    <h3 id="profile-page-name" class="fw-bold mb-1"><?php echo get_translation('name'); ?></h3>
                    <p id="profile-page-email" class="text-muted mb-4"><?php echo get_translation('email'); ?></p>

                    <hr class="border-secondary opacity-25 my-4">

                    <div class="d-flex justify-content-center gap-5">
                        <div class="text-center">
                            <small class="text-muted text-uppercase" style="font-size: 0.7rem;"><?php echo get_translation('joined'); ?></small>
                            <div id="profile-page-date" class="fw-bold fs-5">-</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>