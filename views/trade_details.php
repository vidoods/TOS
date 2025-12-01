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
                                <strong>Счет:</strong>
                                <span class="info-badge badge-neutral" id="trade-account_name">-</span>
                            </td>
                            <td>
                                <strong>План:</strong>
								<p>
                                <a href="#" id="trade-plan-link" class="info-badge badge-neutral" style="text-decoration: none; color: var(--accent-blue); border-color: rgba(41, 151, 255, 0.3);">-</a>
								</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Направление:</strong>
                                <span class="info-badge badge-neutral" id="trade-direction">-</span>
                            </td>
                            <td>
                                <strong>Таймфрейм Входа:</strong>
                                <span class="info-badge badge-neutral" id="trade-entry_timeframe">-</span>
                            </td>
                        </tr>
                        <tr>
                             <td>
                                <strong>Стиль:</strong>
                                <span class="info-badge badge-blue" id="trade-style_name">-</span>
                            </td>
                            <td>
                                <strong>Модель:</strong>
                                <span class="info-badge badge-blue" id="trade-model_name">-</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Дата Входа:</strong>
                                <span class="info-badge badge-neutral" id="trade-entry_date">-</span>
                            </td>
                            <td>
                                <strong>Дата Выхода:</strong>
                                <span class="info-badge badge-neutral" id="trade-exit_date">-</span>
                            </td>
                        </tr>
                         <tr>
                            <td colspan="2">
                                <strong>Статус:</strong>
                                <span class="badge status-tag" id="trade-status">-</span>
                            </td>
                        </tr>
                    </table>
                    
                    <hr class="my-4" style="opacity: 0.1;">

                    <h5 class="text-muted text-uppercase fw-bold mb-4" style="font-size: 0.9rem; letter-spacing: 1px;">Результат и Риск</h5>

                    <table class="details-table w-100" style="table-layout: fixed;">
                         <tr>
                            <td style="width: 50%;">
                                <strong>Риск:</strong>
                                <span class="info-badge badge-neutral" id="trade-risk_percent">-</span>
                            </td>
                            <td>
                                <strong>RR (Достигнут):</strong>
                                <span class="info-badge badge-neutral" id="trade-rr_achieved">-</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>PnL:</strong>
                                <span class="info-badge badge-neutral" id="trade-pnl">-</span>
                            </td>
                            <td>
                                 <strong>Длительность:</strong>
                                <span class="info-badge badge-neutral" id="trade-duration">-</span>
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
             <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px; position: sticky; top: 20px; padding:10px;">
                <div class="card-body p-4">
                     <h5 class="text-muted text-uppercase fw-bold mb-4" style="font-size: 0.9rem; letter-spacing: 1px;">
                        <i class="fas fa-brain me-2"></i>Анализ и Психология
                     </h5>
                     
                     <div class="analysis-group">
                         <div class="analysis-label">
                             <i class="fas fa-sticky-note"></i> Общие Заметки
                         </div>
                         <div class="analysis-box" id="trade-notes">-</div>
                     </div>

                     <hr class="my-4" style="opacity: 0.1;">

                     <div class="analysis-group">
                         <div class="analysis-label">
                             <i class="fas fa-check-circle"></i> Выводы
                         </div>
                         <div class="analysis-box" id="trade-trade_conclusions">-</div>
                     </div>

                     <div class="analysis-group">
                         <div class="analysis-label lessons-label">
                             <i class="fas fa-lightbulb"></i> Ключевые Уроки
                         </div>
                         <div class="analysis-box" id="trade-key_lessons">-</div>
                     </div>

                     <div class="analysis-group">
                         <div class="analysis-label emotions-label">
                             <i class="fas fa-exclamation-triangle"></i> Ошибки
                         </div>
                         <div class="analysis-box" id="trade-mistakes_made">-</div>
                     </div>
                     
                     <div class="analysis-group">
                         <div class="analysis-label emotions-label">
                             <i class="fas fa-heartbeat"></i> Эмоции
                         </div>
                         <div class="analysis-box" id="trade-emotional_state">-</div>
                     </div>
                     
                     <div class="mt-4">
                          <div class="analysis-label">
                              <i class="fas fa-tags"></i> Теги
                          </div>
                          <div id="trade-tags-container">
                              <span class="text-muted ps-2" id="trade-tags">Нет тегов</span>
                          </div>
                     </div>

                </div>
             </div>
        </div>
    </div>
</div>
