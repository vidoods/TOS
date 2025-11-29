<div class="fade-in">
    <input type="hidden" id="current-trade-id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="m-0 fw-bold" id="trade-details-title" style="color: var(--text-primary);">Загрузка...</h2>
             </div>
        <div class="trade-actions d-flex gap-2">
             <a href="index.php?view=journal" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Назад в Журнал
            </a>
            <button class="btn btn-secondary">
                <i class="fas fa-edit me-2"></i> Редактировать
            </button>
            <button class="btn btn-danger">
                <i class="fas fa-trash-alt me-2"></i> Удалить
            </button>
        </div>
    </div>

    <div id="trade-details-container" class="row g-4" style="transition: opacity 0.3s ease;">
        
        <div class="col-lg-8">
             <div class="card glass-panel border-0 shadow-sm mb-4" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <h5 class="text-muted text-uppercase fw-bold mb-4" style="font-size: 0.9rem; letter-spacing: 1px;">Параметры Сделки</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                             <table class="table table-borderless details-table mb-0">
                                <tr>
                                    <td class="text-muted py-2 ps-0">Пара:</td>
                                    <td class="fw-bold py-2 pe-0" id="trade-pair_symbol">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2 ps-0">Направление:</td>
                                    <td class="fw-bold py-2 pe-0" id="trade-direction">-</td>
                                </tr>
                                 <tr>
                                    <td class="text-muted py-2 ps-0">Статус:</td>
                                    <td class="fw-bold py-2 pe-0" id="trade-status">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                             <table class="table table-borderless details-table mb-0">
                                <tr>
                                    <td class="text-muted py-2 ps-0">Счет:</td>
                                    <td class="py-2 pe-0" id="trade-account_name">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2 ps-0">Стиль:</td>
                                    <td class="py-2 pe-0" id="trade-style_name">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr class="my-4" style="opacity: 0.1;">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Дата Входа:</small>
                            <span class="fw-500" id="trade-entry_date">-</span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Дата Выхода:</small>
                            <span class="fw-500" id="trade-exit_date">-</span>
                        </div>
                     </div>
                </div>
            </div>

             <div class="card glass-panel border-0 shadow-sm mb-4" style="border-radius: 12px; background: rgba(var(--accent-blue-rgb), 0.05);">
                <div class="card-body p-4">
                     <h5 class="text-muted text-uppercase fw-bold mb-4" style="font-size: 0.9rem; letter-spacing: 1px;">Результаты и Риск</h5>
                     <div class="row g-4 text-center">
                          <div class="col-md-3 col-6">
                             <small class="text-muted d-block mb-2 text-uppercase fw-bold" style="font-size: 0.75rem;">Риск</small>
                             <span class="fs-4 fw-bold" id="trade-risk_percent">-</span> <span class="text-muted">%</span>
                         </div>
                         <div class="col-md-3 col-6">
                             <small class="text-muted d-block mb-2 text-uppercase fw-bold" style="font-size: 0.75rem;">R:R Итог</small>
                             <span class="fs-4 fw-bold" id="trade-rr_achieved">-</span> <span class="text-muted">R</span>
                         </div>
                          <div class="col-md-3 col-6">
                             <small class="text-muted d-block mb-2 text-uppercase fw-bold" style="font-size: 0.75rem;">PnL ($)</small>
                             <span class="fs-4 fw-bold" id="trade-pnl">-</span>
                         </div>
                         </div>
                </div>
            </div>
            
            <h5 class="mb-3 fw-bold" style="color: var(--text-primary);">Скриншоты Сделки</h5>
            <div id="trade-images-list" class="d-flex flex-wrap gap-3">
                <div class="loading-spinner">Загрузка изображений...</div>
            </div>

        </div>

        <div class="col-lg-4">
             <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px; position: sticky; top: 20px;">
                <div class="card-body p-4">
                     <h5 class="text-muted text-uppercase fw-bold mb-4" style="font-size: 0.9rem; letter-spacing: 1px;">Анализ и Заметки</h5>
                     
                     <div class="mb-4">
                         <h6 class="fw-bold mb-2" style="color: var(--text-primary);">Причина Входа</h6>
                         <p class="text-muted mb-0" id="trade-reason_for_entry" style="white-space: pre-line;">-</p>
                     </div>
                     
                     <div class="mb-4">
                         <h6 class="fw-bold mb-2" style="color: var(--text-primary);">Общие Заметки</h6>
                         <p class="text-muted mb-0" id="trade-notes" style="white-space: pre-line;">-</p>
                     </div>

                     <hr class="my-4" style="opacity: 0.1;">

                     <div class="mb-3">
                         <strong class="d-block mb-1" style="color: var(--text-primary);">Выводы:</strong>
                         <p class="text-muted mb-0 small" id="trade-trade_conclusions">-</p>
                     </div>
                      <div class="mb-3">
                         <strong class="d-block mb-1" style="color: var(--text-primary);">Ключевые Уроки:</strong>
                         <p class="text-muted mb-0 small" id="trade-key_lessons">-</p>
                     </div>
                      <div class="mb-3">
                         <strong class="d-block mb-1 text-danger">Ошибки:</strong>
                         <p class="text-muted mb-0 small" id="trade-mistakes_made">-</p>
                     </div>
                     
                     <div class="mt-4">
                          <strong class="d-block mb-2" style="color: var(--text-primary);">Теги:</strong>
                          <div id="trade-tags-container">
                              <span class="text-muted" id="trade-tags">-</span>
                          </div>
                     </div>

                </div>
             </div>
        </div>
    </div>
</div>

<style>
    .details-table td {
        vertical-align: middle;
    }
    #trade-images-list .timeframe-card {
        width: calc(50% - 10px); /* Два изображения в ряд */
        margin-bottom: 0;
    }
    #trade-images-list img {
        height: 200px;
        object-fit: cover;
        width: 100%;
        border-radius: 8px;
        border: 1px solid var(--glass-border);
    }
    @media (max-width: 768px) {
         #trade-images-list .timeframe-card {
            width: 100%;
        }
    }
</style>