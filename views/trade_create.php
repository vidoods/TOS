<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0" id="form-page-title" style="font-weight: 600;">Новая Сделка</h2>
    </div>

    <div class="card glass-panel mb-4 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-body p-4" style="padding: 40px;">
            <input type="hidden" id="edit-trade-id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">

            <form id="trade-form">
                <h5 class="text-muted mb-3">Основные Параметры</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="trade-pair" class="form-label fw-bold">Торговая Пара *</label>
                        <select class="form-select" id="trade-pair" name="pair_id" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="trade-account" class="form-label fw-bold">Торговый Счет *</label>
                        <select class="form-select" id="trade-account" name="account_id" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                     <div class="col-md-4">
                        <label for="trade-style" class="form-label fw-bold">Стиль Торговли</label>
                         <select class="form-select" id="trade-style" name="style_id">
                            <option value="">Select</option>
                        </select>
                    </div>
					<div class="col-md-4">
                        <label for="trade-model" class="form-label fw-bold">Модель Входа</label>
                        <select class="form-select" id="trade-model" name="model_id">
                            <option value="">Select</option>
                        </select>
                    </div>
                     <div class="col-md-12">
                        <label for="trade-plan" class="form-label fw-bold">Связать с Планом</label>
                        <select class="form-select" id="trade-plan" name="plan_id">
                            <option value="">--- Без плана ---</option>
                             </select>
                         <div class="form-text">Выберите план, на основе которого была открыта эта сделка.</div>
                    </div>
					<div class="col-md-12 mt-3">
                        <label for="trade-note" class="form-label fw-bold">Привязать Заметку</label>
                        <select class="form-select" id="trade-note" name="note_id">
                            <option value="">--- Без заметки ---</option>
                        </select>
                    </div>
                </div>

                 <h5 class="text-muted mb-3">Вход в Сделку</h5>
                <div class="row g-3 mb-4">
                     <div class="col-md-4">
                        <label for="trade-entry-date" class="form-label fw-bold">Дата и Время Входа *</label>
                        <input type="datetime-local" class="form-control" id="trade-entry-date" name="entry_date" required>
                    </div>
                     <div class="col-md-4">
						<label class="form-label fw-bold d-block">Direction *</label>
						<div class="btn-group w-100" role="group">
							<input type="radio" class="btn-check" name="direction" id="dir-long" value="Long" required checked>
								<label class="btn btn-outline-success d-flex align-items-center justify-content-center gap-2 text-profit" for="dir-long">
								<i class="fas fa-arrow-up"></i> Long
								</label>
        
							<input type="radio" class="btn-check" name="direction" id="dir-short" value="Short">
								<label class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2 text-loss" for="dir-short">
								<i class="fas fa-arrow-down"></i> Short
								</label>
						</div>
					</div>
                    <div class="col-md-4">
                        <label for="trade-entry-tf" class="form-label">Таймфрейм Входа</label>
                         <select class="form-select" id="trade-entry-tf" name="entry_timeframe">
                            <option value="">Select</option>
                            <option value="M5">5 минут</option>
                            <option value="M15">15 минут</option>
                            <option value="H1">1 час</option>
                            <option value="H4">4 часа</option>
                             <option value="1D">1 День</option>
                        </select>
                    </div>
                </div>
                
                 <h5 class="text-muted mb-3">Риск</h5>
                 <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                     <div class="col-md-6">
                         <label for="trade-risk" class="form-label fw-bold">Риск на сделку (%) *</label>
                         <div class="input-group">
                            <input type="number" class="form-control" id="trade-risk" name="risk_percent" step="0.01" value="1.00" required placeholder="Введите % риска">
                         </div>
                    </div>
                 </div>

                  <h5 class="text-muted mb-3">Результаты (Заполнить при закрытии)</h5>
                 <div class="row g-3 mb-4 p-3" style="background-color: #090c14; border-radius: 8px; padding:10px;">
                      <div class="col-md-6">
                        <label for="trade-status" class="form-label fw-bold">Статус Сделки</label>
                        <select class="form-select" id="trade-status" name="status">
                            <option value="pending" selected>🕒 Pending (Открыта)</option>
                            <option value="win">✅ Win (Прибыль)</option>
                            <option value="loss">❌ Loss (Убыток)</option>
                            <option value="breakeven">➖ Breakeven (Безубыток)</option>
                             <option value="partial">🔄 Partial (Частично закрыта)</option>
                            <option value="cancelled">🚫 Cancelled (Отменена)</option>
                        </select>
                    </div>
                     <div class="col-md-6">
                        <label for="trade-exit-date" class="form-label">Дата и Время Выхода</label>
                        <input type="datetime-local" class="form-control" id="trade-exit-date" name="exit_date">
                    </div>
                      <div class="col-md-6">
                        <label for="trade-pnl" class="form-label fw-bold">Прибыль/Убыток (PnL) $</label>
                        <input type="number" class="form-control fw-bold" id="trade-pnl" name="pnl" step="0.01" placeholder="Например: 150.00 или -50.00">
                    </div>
                     <div class="col-md-6">
                        <label for="trade-rr-achieved" class="form-label fw-bold">Достигнутый R:R (Результат)</label>
                        <input type="number" class="form-control fw-bold" id="trade-rr-achieved" name="rr_achieved" step="0.01" placeholder="Авторасчет..." readonly>
                    </div>
                 </div>

                 <h5 class="text-muted mb-3">Анализ и Выводы</h5>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label for="trade-notes" class="form-label">Общие Заметки</label>
                        <textarea class="form-control" id="trade-notes" name="notes" rows="3" placeholder="Любые мысли по ходу сделки..."></textarea>
                    </div>
                     <div class="col-md-6">
                         <label for="trade-conclusions" class="form-label">Выводы (Что сработало/не сработало?)</label>
                        <textarea class="form-control" id="trade-conclusions" name="trade_conclusions" rows="3"></textarea>
                    </div>
                     <div class="col-md-6">
                        <label for="trade-lessons" class="form-label">Ключевые Уроки (Чему я научился?)</label>
                        <textarea class="form-control" id="trade-lessons" name="key_lessons" rows="3" placeholder="Что я сделаю по-другому в следующий раз?"></textarea>
                    </div>
                      <div class="col-md-6">
                        <label for="trade-mistakes" class="form-label">Были ли ошибки?</label>
                         <textarea class="form-control border-danger" id="trade-mistakes" name="mistakes_made" rows="2" placeholder="Например: ранний вход, нарушение правил..."></textarea>
                    </div>
                     <div class="col-md-6">
                        <label for="trade-emotions" class="form-label">Эмоциональное Состояние</label>
                        <input type="text" class="form-control" id="trade-emotions" name="emotional_state" placeholder="Например: уверенность, страх, FOMO, спокойствие...">
                    </div>
                     <div class="col-12">
                         <label for="trade-tags" class="form-label">Теги (через запятую)</label>
                        <input type="text" class="form-control" id="trade-tags" name="tags" placeholder="trend, breakout, news, mistake...">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-muted m-0">Скриншоты Сделки</h5>
                    
                </div>
                <div id="trade-images-container">
                    </div>

                 <div class="mt-4 pt-3 border-top">
				    <button type="button" class="btn btn-secondary" onclick="addTradeImage()">
                        <i class="fas fa-plus me-1"></i> Добавить Изображение
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save me-2"></i> Сохранить Сделку
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>