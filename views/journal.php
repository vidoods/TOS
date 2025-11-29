<div class="main-content-wrapper fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="m-0" style="font-weight: 600;">Журнал Сделок</h2>
            <p class="text-muted m-0">Ваша история торговли</p>
        </div>
        
        <div class="page-header-actions d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="show-filters-btn">
                <i class="fas fa-filter me-2"></i> Фильтры
            </button>
            
            <a href="index.php?view=trade_create" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Новая Сделка
            </a>
        </div>
    </div>

    <div id="trades-list-container">
        <div class="loading-spinner">Загрузка сделок...</div>
    </div>
</div>


<div id="filters-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); backdrop-filter: blur(5px);">
    <div class="modal-content glass-panel" style="background: var(--glass-bg); margin: 10vh auto; padding: 30px; border: 1px solid var(--glass-border); width: 90%; max-width: 500px; border-radius: 16px; position: relative; box-shadow: var(--glass-shadow);">
        <span class="modal-close" id="filters-close-btn" style="position: absolute; top: 15px; right: 20px; color: var(--text-secondary); font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h2 style="margin-top: 0; margin-bottom: 25px; text-align: center; color: var(--text-primary);">Фильтрация Журнала</h2>
        
        <form id="filters-form">
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="filter-pair" class="form-label" style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-weight: 500;">Инструмент</label>
                <select class="select-field" id="filter-pair" style="width: 100%; padding: 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); color: var(--text-primary); border-radius: 8px; outline: none;">
                    <option value="">Все инструменты</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="filter-status" class="form-label" style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-weight: 500;">Статус</label>
                <select class="select-field" id="filter-status" style="width: 100%; padding: 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); color: var(--text-primary); border-radius: 8px; outline: none;">
                    <option value="">Все статусы</option>
                    <option value="win">Win (Прибыль)</option>
                    <option value="loss">Loss (Убыток)</option>
                    <option value="breakeven">Breakeven (Безубыток)</option>
                    <option value="partial">Partial (Частичный выход)</option>
                    <option value="pending">Pending (Ожидает)</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="filter-direction" class="form-label" style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-weight: 500;">Направление</label>
                <select class="select-field" id="filter-direction" style="width: 100%; padding: 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); color: var(--text-primary); border-radius: 8px; outline: none;">
                    <option value="">Любое</option>
                    <option value="long">Long</option>
                    <option value="short">Short</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 30px;">
                <label for="filter-month" class="form-label" style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-weight: 500;">Месяц</label>
                 <input type="month" id="filter-month" class="input-field" style="width: 100%; padding: 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); color: var(--text-primary); border-radius: 8px; outline: none;">
            </div>
            
            <div style="display: flex; gap: 15px;">
                <button type="button" class="btn btn-outline-secondary" id="reset-filters-btn" style="flex: 1; border: 1px solid var(--glass-border); background: transparent; color: var(--text-primary); padding: 12px; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">Сбросить</button>
                <button type="submit" class="btn btn-primary" style="flex: 2; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">Применить Фильтры</button>
            </div>
        </form>
    </div>
</div>