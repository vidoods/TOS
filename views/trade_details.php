<div class="fade-in">
    <input type="hidden" id="current-trade-id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">

    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
        <h1 class="page-title" style="margin: 0; display: flex; align-items: center; gap: 15px;">
            <span id="trade-details-title">Loading...</span>
        </h1>
        
        <div class="trade-actions" style="display: flex; gap: 10px;">
			<a href="index.php?view=journal" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <button class="btn btn-secondary">
                <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> Delete
            </button>
        </div>
    </div>

    <div id="trade-details-container" class="glass-panel" style="padding: 30px; position: relative; min-height: 300px;">
        
        <section style="margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid var(--glass-border);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px;">
                
                <div class="detail-item">
                    <span class="detail-label">Result (PnL)</span>
                    <span id="trade-pnl" class="detail-value">-</span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">R:R</span>
                    <span id="trade-rr_achieved" class="detail-value">-</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span id="trade-status" class="detail-value">-</span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Risk</span>
                    <span id="trade-risk_percent" class="detail-value">-</span>
                </div>

                 <div class="detail-item">
                    <span class="detail-label">Duration</span>
                    <span id="trade-duration" class="detail-value">-</span>
                </div>
            </div>
        </section>

        <section style="margin-bottom: 40px;">
            <h3 class="section-title">Trade parameters</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">
                <div class="detail-item">
                    <span class="detail-label">Pair</span>
                    <span id="trade-pair_symbol" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Direction</span>
                    <span id="trade-direction">-</span> </div>
                <div class="detail-item">
                    <span class="detail-label">Timeframe</span>
                    <span id="trade-entry_timeframe" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Account</span>
                    <span id="trade-account_name" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Trading style</span>
                    <span id="trade-style_name" class="info-badge badge-blue">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Entry model</span>
                    <span id="trade-model_name" class="info-badge badge-blue">-</span>
                </div>
                 <div class="detail-item">
                    <span class="detail-label">Entry</span>
                    <span id="trade-entry_date" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Exit</span>
                    <span id="trade-exit_date" class="info-badge badge-neutral">-</span>
                </div>
                <div class="detail-item">
					<span class="detail-label">Linked Plan</span>
					<a href="#" id="trade-plan-link" class="info-badge badge-neutral">-</a> 
				</div>
            </div>
        </section>

        <section style="margin-bottom: 40px; padding-top: 30px; border-top: 1px solid var(--glass-border);">
            <h3 class="section-title">Mental and Technical analysis</h3>
            
            <div class="analysis-grid">
                <div class="analysis-group">
                    <div class="analysis-label"><i class="fas fa-sticky-note"></i> Notes</div>
                    <div class="analysis-box" id="trade-notes">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label"><i class="fas fa-check-circle"></i> Conclusion</div>
                    <div class="analysis-box" id="trade-trade_conclusions">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label lessons-label"><i class="fas fa-lightbulb"></i> Key lessons</div>
                    <div class="analysis-box" id="trade-key_lessons">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label mistakes-label"><i class="fas fa-exclamation-triangle"></i> Errors</div>
                    <div class="analysis-box mistakes-box" id="trade-mistakes_made">-</div>
                </div>
                <div class="analysis-group">
                    <div class="analysis-label emotions-label"><i class="fas fa-heartbeat"></i> Emotions</div>
                    <div class="analysis-box" id="trade-emotional_state">-</div>
                </div>
                 <div class="analysis-group">
                    <div class="analysis-label"><i class="fas fa-tags"></i> Tags</div>
                    <div id="trade-tags-container" style="padding-top: 5px;">-</div>
                </div>
            </div>
        </section>

        <section>
            <h3 class="section-title">Screenshots</h3>
            <div id="trade-images-list" class="d-flex flex-wrap gap-3">
                <div class="empty-state-small">Loading...</div>
            </div>
        </section>

    </div>
</div>