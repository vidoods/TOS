<div class="content-header d-flex justify-content-between align-items-center mb-4">
    <h1 class="m-0" style="font-weight: 600;"><?= $lang['performance_overview'] ?></h1>
    
    <div class="dashboard-actions d-flex gap-2">
        <select id="dashboard-account-select" class="select-field" style="min-width: 150px; cursor: pointer;">
            <option value=""><?= $lang['all_accounts'] ?></option>
        </select>
        
        <select id="dashboard-year-select" class="select-field" style="min-width: 100px; cursor: pointer;">
            <option value=""><?= $lang['all_period'] ?></option>
        </select>

        <select id="dashboard-month-select" class="select-field" style="min-width: 120px; cursor: pointer; display: none;">
            <option value=""><?= $lang['whole_year'] ?></option>
            <option value="01"><?= $lang['january'] ?></option>
            <option value="02"><?= $lang['february'] ?></option>
            <option value="03"><?= $lang['march'] ?></option>
            <option value="04"><?= $lang['april'] ?></option>
            <option value="05"><?= $lang['may'] ?></option>
            <option value="06"><?= $lang['june'] ?></option>
            <option value="07"><?= $lang['july'] ?></option>
            <option value="08"><?= $lang['august'] ?></option>
            <option value="09"><?= $lang['september'] ?></option>
            <option value="10"><?= $lang['october'] ?></option>
            <option value="11"><?= $lang['november'] ?></option>
            <option value="12"><?= $lang['december'] ?></option>
        </select>
    </div>
</div>

<div class="dashboard-grid">
    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-chart-line"></i></div>
        <div class="metric-content">
            <div class="metric-label"><?= $lang['total_trades'] ?></div>
            <div class="metric-value" id="total-trades-value">Loading...</div>
            <div class="metric-subtext" id="total-trades-breakdown"></div>
        </div>
    </div>

    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-trophy"></i></div>
        <div class="metric-content">
            <div class="metric-label">Winrate</div>
            <div class="metric-value" id="winning-ratio-value">Loading...</div>
            <div class="metric-progress-bar">
                <div id="winning-ratio-progress" style="width:0%;"></div>
            </div>
        </div>
    </div>

    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="metric-content">
            <div class="metric-label">Average time in position</div>
            <div class="metric-value" id="avg-time-in-position-value">Loading...</div>
        </div>
    </div>

    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-chart-area"></i></div>
        <div class="metric-content">
            <div class="metric-label">Net profit</div>
            <div class="metric-value" id="net-profit-value">Loading...</div>
            <div class="metric-subtext" id="avg-monthly-profit"></div>
        </div>
    </div>

    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-exchange-alt"></i></div>
        <div class="metric-content">
            <div class="metric-label">Average RR</div>
            <div class="metric-value" id="average-rr-value">Loading...</div>
        </div>
    </div>

    <div class="metric-card glass-panel">
        <div class="metric-icon"><i class="fas fa-arrow-down"></i></div>
        <div class="metric-content">
            <div class="metric-label">Max drawdown</div>
            <div class="metric-value" id="max-drawdown-value">Loading...</div>
        </div>
    </div>
</div>

<div class="charts-area mt-4">
    <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px; padding: 20px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0" style="font-size: 1.2rem; font-weight: 600; color: var(--text-main);">Equity Curve</h3>
        </div>
        
        <div class="chart-wrapper">
            <canvas id="equityChart"></canvas>
        </div>
    </div>
</div>