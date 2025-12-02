<div class="fade-in">
    <input type="hidden" id="current-trade-id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">

    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
        <h1 class="page-title" style="margin: 0; display: flex; align-items: center; gap: 15px;">
            <span id="trade-details-title">Загрузка...</span>
        </h1>
        
        <div class="trade-actions" style="display: flex; gap: 10px;">
            <button class="btn btn-secondary">
                <i class="fas fa-edit"></i> Редактировать
            </button>
            <button class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> Удалить
            </button>
            <a href="index.php?view=journal" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <div id="trade-details-container" class="glass-panel" style="padding: 30px; position: relative; min-height: 300px;">
        
        <section style="margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid var(--glass-border);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px;">
                
                <div class="detail-item">
                    <span class="detail-label">Результат (PnL)</span>
                    <span id="trade-pnl" class="detail-value">-</span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">R:R</span>
                    <span id="trade-rr_achieved" class="detail-value">-</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Статус</span>
                    <span id="trade-status" class="detail-value">-</span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Риск</span>
                    <span id="trade-risk_percent" class="detail-value">-</span>
                </div>

                 <div class="detail-item">
                    <span class="detail-label">Длительность</span>
                    <span id="trade-duration" class="detail-value">-</span>
                </div>
            </div>
        </section>

        <section style="margin-bottom: 40px;">
            <h3 class="section-title">Параметры Сделки</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">
                <div class="detail-item">
                    <span class="detail-label">Инструмент</span>
                    <span id="trade-pair_symbol" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Направление</span>
                    <span id="trade-direction">-</span> </div>
                <div class="detail-item">
                    <span class="detail-label">Таймфрейм</span>
                    <span id="trade-entry_timeframe" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Счет</span>
                    <span id="trade-account_name" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Стиль</span>
                    <span id="trade-style_name" class="info-badge badge-blue">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Модель</span>
                    <span id="trade-model_name" class="info-badge badge-blue">-</span>
                </div>
                 <div class="detail-item">
                    <span class="detail-label">Вход</span>
                    <span id="trade-entry_date" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Выход</span>
                    <span id="trade-exit_date" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
					<span class="detail-label">Связанный План</span>
					<a href="#" id="trade-plan-link" class="info-badge badge-neutral">-</a> 
				</div>
            </div>
        </section>

        <section style="margin-bottom: 40px; padding-top: 30px; border-top: 1px solid var(--glass-border);">
            <h3 class="section-title">Анализ и Психология</h3>
            
            <div class="analysis-grid">
                <div class="analysis-group">
                    <div class="analysis-label"><i class="fas fa-sticky-note"></i> Общие Заметки</div>
                    <div class="analysis-box" id="trade-notes">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label"><i class="fas fa-check-circle"></i> Выводы</div>
                    <div class="analysis-box" id="trade-trade_conclusions">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label lessons-label"><i class="fas fa-lightbulb"></i> Ключевые Уроки</div>
                    <div class="analysis-box" id="trade-key_lessons">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label mistakes-label"><i class="fas fa-exclamation-triangle"></i> Ошибки</div>
                    <div class="analysis-box mistakes-box" id="trade-mistakes_made">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label emotions-label"><i class="fas fa-heartbeat"></i> Эмоции</div>
                    <div class="analysis-box" id="trade-emotional_state">-</div>
                </div>
                 <div class="analysis-group">
                    <div class="analysis-label"><i class="fas fa-tags"></i> Теги</div>
                    <div id="trade-tags-container" style="padding-top: 5px;">-</div>
                </div>
            </div>
        </section>

        <section>
            <h3 class="section-title">Скриншоты</h3>
            <div id="trade-images-list" class="d-flex flex-wrap gap-3">
                <div class="empty-state-small">Загрузка...</div>
            </div>
        </section>

    </div>
</div>