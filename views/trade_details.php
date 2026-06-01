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
            
            <!-- НОВАЯ КНОПКА ГЕНЕРАЦИИ ТОКЕНА И ШАРИНГА -->
            <button id="btn-share-trade" class="btn btn-primary">
                <i class="fas fa-share-alt"></i> <?= $lang['share'] ?? 'Поделиться' ?>
            </button>
            
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

<div id="share-trade-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.85); z-index: 1050; justify-content: center; align-items: center; backdrop-filter: blur(8px);">
    <div class="glass-panel fade-in" style="width: 90%; max-width: 420px; padding: 30px; position: relative;">
        <button id="close-share-modal" style="position: absolute; top: 15px; right: 15px; background: none; border: none; color: var(--text-muted); font-size: 24px; cursor: pointer; transition: 0.2s;">
            &times;
        </button>
        
        <h4 style="margin-top: 0; text-align: center; margin-bottom: 5px;">
            <i class="fas fa-share-alt" style="color: var(--accent-blue); margin-right: 8px;"></i> Поделиться сделкой
        </h4>
        <p class="text-muted text-center" style="font-size: 14px; margin-bottom: 25px;">Публичная ссылка на вашу статистику готова</p>
        
        <div style="text-align: center; margin: 20px 0; min-height: 160px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img id="share-generated-card" src="" alt="Trade Card" style="width: 100%; max-width: 350px; border-radius: 8px; display: none; box-shadow: 0 4px 15px rgba(0,0,0,0.5); border: 1px solid var(--glass-border);">
            <div id="share-loading" class="text-muted">
                <i class="fas fa-circle-notch fa-spin fa-2x mb-2" style="color: var(--accent-blue);"></i><br>
                <span>Генерация ссылки...</span>
            </div>
        </div>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="detail-label" style="font-size: 12px; margin-bottom: 5px; display: block;">Публичная ссылка:</label>
            <div style="display: flex; gap: 8px;">
                <input type="text" id="share-link-input" class="form-control" readonly style="flex: 1; background: rgba(0,0,0,0.2); color: white; border: 1px solid var(--glass-border); font-size: 13px;">
                <button id="copy-share-link" class="btn btn-primary" title="Скопировать">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 10px;">
            <a id="download-card-btn" href="#" download="TradeOS_Card.png" class="btn btn-outline w-100" style="display: flex; justify-content: center; align-items: center; gap: 10px;">
                <i class="fas fa-image"></i> Скачать карточку (PNG)
            </a>
        </div>
    </div>
</div>