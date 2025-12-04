<div class="content-header d-flex justify-content-between align-items-center mb-4">
    <h1 class="m-0" style="font-weight: 600;">Обзор эффективности</h1>
    
    <div class="dashboard-actions d-flex gap-2">
        <select id="dashboard-account-select" class="select-field" style="min-width: 150px; cursor: pointer;">
            <option value="">Все счета</option>
        </select>
        
        <select id="dashboard-year-select" class="select-field" style="min-width: 100px; cursor: pointer;">
            <option value="">Весь период</option>
        </select>

        <select id="dashboard-month-select" class="select-field" style="min-width: 120px; cursor: pointer; display: none;">
            <option value="">Весь год</option>
            <option value="01">Январь</option>
            <option value="02">Февраль</option>
            <option value="03">Март</option>
            <option value="04">Апрель</option>
            <option value="05">Май</option>
            <option value="06">Июнь</option>
            <option value="07">Июль</option>
            <option value="08">Август</option>
            <option value="09">Сентябрь</option>
            <option value="10">Октябрь</option>
            <option value="11">Ноябрь</option>
            <option value="12">Декабрь</option>
        </select>
    </div>
</div>

<div class="dashboard-grid">
    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-chart-line"></i></div>
        <div class="metric-content">
            <div class="metric-label">Всего сделок</div>
            <div class="metric-value" id="total-trades-value">Загрузка...</div>
            <div class="metric-subtext" id="total-trades-breakdown"></div>
        </div>
    </div>

    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-trophy"></i></div>
        <div class="metric-content">
            <div class="metric-label">Процент выигрыша</div>
            <div class="metric-value" id="winning-ratio-value">Загрузка...</div>
            <div class="metric-progress-bar">
                <div id="winning-ratio-progress" style="width:0%;"></div>
            </div>
        </div>
    </div>

    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="metric-content">
            <div class="metric-label">Среднее время в позиции</div>
            <div class="metric-value" id="avg-time-in-position-value">Загрузка...</div>
        </div>
    </div>

    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-chart-area"></i></div>
        <div class="metric-content">
            <div class="metric-label">Чистая прибыль / Среднемес.</div>
            <div class="metric-value" id="net-profit-value">Загрузка...</div>
            <div class="metric-subtext" id="avg-monthly-profit"></div>
        </div>
    </div>

    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-exchange-alt"></i></div>
        <div class="metric-content">
            <div class="metric-label">Средний RR</div>
            <div class="metric-value" id="average-rr-value">Загрузка...</div>
        </div>
    </div>

    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-arrow-down"></i></div>
        <div class="metric-content">
            <div class="metric-label">Макс. просадка</div>
            <div class="metric-value" id="max-drawdown-value">Загрузка...</div>
        </div>
    </div>
</div>

<div class="charts-area mt-4">
    <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px; padding: 20px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0" style="font-size: 1.2rem; font-weight: 600; color: var(--text-main);">Equity Curve (Кривая Капитала)</h3>
        </div>
        
        <div class="chart-wrapper">
            <canvas id="equityChart"></canvas>
        </div>
    </div>
</div>