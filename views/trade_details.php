<div class="fade-in">
    <input type="hidden" id="current-trade-id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">

    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
        <h1 class="page-title" style="margin: 0; display: flex; align-items: center; gap: 15px;">
            <span id="trade-details-title"><span class="skeleton" style="height: 38px; width: 180px; display: inline-block;"></span></span>
        </h1>
        
        <div class="trade-actions" style="display: flex; gap: 10px;">
			<a href="index.php?view=journal" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> <?= $lang['back'] ?>
            </a>
            <button class="btn btn-secondary">
                <i class="fas fa-edit"></i> <?= $lang['edit'] ?>
            </button>
            <button class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> <?= $lang['delete'] ?>
            </button>
        </div>
    </div>

    <div id="trade-details-container" class="glass-panel" style="padding: 30px; position: relative; min-height: 300px;">
        
        <section style="margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid var(--glass-border);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px;">
                
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['result_pnl'] ?></span>
                    <span id="trade-pnl" class="detail-value">-</span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['rr'] ?></span>
                    <span id="trade-rr_achieved" class="detail-value">-</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label"><?= $lang['status'] ?></span>
                    <span id="trade-status" class="detail-value">-</span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['risk'] ?></span>
                    <span id="trade-risk_percent" class="detail-value">-</span>
                </div>

                 <div class="detail-item">
                    <span class="detail-label"><?= $lang['duration'] ?></span>
                    <span id="trade-duration" class="detail-value">-</span>
                </div>
            </div>
        </section>

        <section style="margin-bottom: 40px;">
            <h3 class="section-title"><?= $lang['trade_parameters'] ?></h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['pair'] ?></span>
                    <span id="trade-pair_symbol" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['direction'] ?></span>
                    <span id="trade-direction">-</span> </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['timeframe'] ?></span>
                    <span id="trade-entry_timeframe" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['account'] ?></span>
                    <span id="trade-account_name" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['trading_style'] ?></span>
                    <span id="trade-style_name" class="info-badge badge-blue">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['entry_model'] ?></span>
                    <span id="trade-model_name" class="info-badge badge-blue">-</span>
                </div>
                 <div class="detail-item">
                    <span class="detail-label"><?= $lang['entry'] ?></span>
                    <span id="trade-entry_date" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['exit'] ?></span>
                    <span id="trade-exit_date" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
					<span class="detail-label"><?= $lang['linked_plan'] ?></span>
					<a href="#" id="trade-plan-link" class="info-badge badge-neutral">-</a> 
				</div>
            </div>
        </section>

        <section style="margin-bottom: 40px; padding-top: 30px; border-top: 1px solid var(--glass-border);">
            <h3 class="section-title"><?= $lang['mental_tech_analysis'] ?></h3>
            
            <div class="analysis-grid">
                <div class="analysis-group">
                    <div class="analysis-label"><i class="fas fa-sticky-note"></i> <?= $lang['notes'] ?></div>
                    <div class="analysis-box" id="trade-notes">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label"><i class="fas fa-check-circle"></i> <?= $lang['conclusion_short'] ?></div>
                    <div class="analysis-box" id="trade-trade_conclusions">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label lessons-label"><i class="fas fa-lightbulb"></i> <?= $lang['key_lessons_short'] ?></div>
                    <div class="analysis-box" id="trade-key_lessons">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label mistakes-label"><i class="fas fa-exclamation-triangle"></i> <?= $lang['errors_short'] ?></div>
                    <div class="analysis-box mistakes-box" id="trade-mistakes_made">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label emotions-label"><i class="fas fa-heartbeat"></i> <?= $lang['emotions_short'] ?></div>
                    <div class="analysis-box" id="trade-emotional_state">-</div>
                </div>
                 <div class="analysis-group">
                    <div class="analysis-label"><i class="fas fa-tags"></i> <?= $lang['tags'] ?></div>
                    <div id="trade-tags-container" style="padding-top: 5px;">-</div>
                </div>
            </div>
        </section>

        <section>
            <h3 class="section-title"><?= $lang['screenshots'] ?></h3>
            <div id="trade-images-list" class="d-flex flex-wrap gap-3">
                <div class="empty-state-small"><?= $lang['loading'] ?></div>
            </div>
        </section>

    </div>
</div>