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
             <div class="card glass-panel border-0 shadow-sm mb-4" style="border-radius: 12px; padding:10px;">
                <div class="card-body p-4">
                    <h5 class="text-muted text-uppercase fw-bold mb-4" style="font-size: 0.9rem; letter-spacing: 1px;">Параметры Сделки</h5>
                    
                    <table class="details-table w-100" style="table-layout: fixed;">
                         <tr>
                            <td style="width: 50%;">
                                <strong class="d-block mb-1">Счет:</strong>
                                <p class="text-muted mb-3" id="trade-account_name">-</p>
                            </td>
                            <td>
                                <strong class="d-block mb-1">План:</strong>
                                <a href="#" id="trade-plan-link" class="text-primary mb-3" style="text-decoration: none;">-</a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="d-block mb-1">Направление:</strong>
                                <span class="badge dir-tag" id="trade-direction">-</span>
                            </td>
                            <td>
                                <strong class="d-block mb-1">Таймфрейм Входа:</strong>
                                <p class="text-muted mb-3" id="trade-entry_timeframe">-</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="d-block mb-1">Дата Входа:</strong>
                                <p class="text-muted mb-3" id="trade-entry_date">-</p>
                            </td>
                            <td>
                                <strong class="d-block mb-1">Дата Выхода:</strong>
                                <p class="text-muted mb-3" id="trade-exit_date">-</p>
                            </td>
                        </tr>
                        <tr>
                             <td>
                                <strong class="d-block mb-1">Стиль:</strong>
                                <p class="text-muted mb-3" id="trade-style_name">-</p>
                            </td>
                            <td>
                                <strong class="d-block mb-1">Модель:</strong>
                                <p class="text-muted mb-3" id="trade-model_name">-</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <strong class="d-block mb-1">Статус:</strong>
                                <span class="badge status-tag" id="trade-status">-</span>
                            </td>
                        </tr>
                    </table>
                    
                    <h5 class="text-muted text-uppercase fw-bold mt-4 mb-4" style="font-size: 0.9rem; letter-spacing: 1px;">Результат и Риск</h5>

                    <table class="details-table w-100" style="table-layout: fixed;">
                         <tr>
                            <td style="width: 50%;">
                                <strong class="d-block mb-1">Риск:</strong>
                                <p class="text-muted mb-3" id="trade-risk_percent">-</p>
                            </td>
                            <td>
                                <strong class="d-block mb-1">RR (Достигнут):</strong>
                                <p class="text-muted mb-3" id="trade-rr_achieved">-</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="d-block mb-1">PnL:</strong>
                                <p class="text-muted mb-3 fw-bold" id="trade-pnl">-</p>
                            </td>
                            <td>
                                 <strong class="d-block mb-1">Длительность:</strong>
                                <p class="text-muted mb-3" id="trade-duration">-</p>
                            </td>
                        </tr>
                    </table>
                </div>
             </div>
             
             <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px; padding:10px;">
                 <div class="card-body p-4">
                     <h5 class="text-muted text-uppercase fw-bold mb-4" style="font-size: 0.9rem; letter-spacing: 1px;">Скриншоты и Анализ</h5>
                     <div id="trade-images-list" class="d-flex flex-wrap justify-content-start gap-3">
                         <div class="empty-state-small">Загрузка изображений...</div>
                     </div>
                 </div>
             </div>
        </div>

        <div class="col-lg-4">
            <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px; padding:10px;">
                <div class="card-body p-4">
                    <h5 class="text-muted text-uppercase fw-bold mb-4" style="font-size: 0.9rem; letter-spacing: 1px;">Анализ и Заключения</h5>

                    <div class="mb-3">
                         <strong class="d-block mb-1" style="color: var(--text-primary);">Заметки:</strong>
                         <p class="text-muted mb-0 small" id="trade-notes">-</p>
                    </div>

                    <div class="mb-3">
                         <strong class="d-block mb-1" style="color: var(--text-primary);">Заключения Сделки:</strong>
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
                     <div class="mb-3">
                         <strong class="d-block mb-1">Эмоции:</strong>
                         <p class="text-muted mb-0 small" id="trade-emotional_state">-</p>
                     </div>
                     
                     <div class="mt-4">
                          <strong class="d-block mb-2" style="color: var(--text-primary);">Теги:</strong>
                          <div id="trade-tags-container">
                              <span class="text-muted" id="trade-tags">Нет</span>
                          </div>
                     </div>

                </div>
             </div>
        </div>
    </div>
</div>
