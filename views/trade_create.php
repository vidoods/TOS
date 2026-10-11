<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0" id="form-page-title" style="font-weight: 600;"><?= $lang['new_trade'] ?></h2>
        <a href="index.php?view=journal" class="btn btn-outline-secondary btn-sm"><?= $lang['cancel'] ?></a>
    </div>

    <div class="card glass-panel mb-4 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-body p-4" style="padding: 40px;">
            <input type="hidden" id="edit-trade-id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">

            <form id="trade-form">
                <h5 class="text-muted mb-3"><?= $lang['main_parameters'] ?></h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="trade-pair" class="form-label fw-bold"><?= $lang['pair_star'] ?></label>
                        <select class="form-select" id="trade-pair" name="pair_id" required>
                            <option value=""><?= $lang['select'] ?></option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="trade-account" class="form-label fw-bold"><?= $lang['account_star'] ?></label>
                        <select class="form-select" id="trade-account" name="account_id" required>
                            <option value=""><?= $lang['select'] ?></option>
                        </select>
                    </div>
                     <div class="col-md-4">
                        <label for="trade-style" class="form-label fw-bold"><?= $lang['trading_style'] ?></label>
                         <select class="form-select" id="trade-style" name="style_id">
                            <option value=""><?= $lang['select'] ?></option>
                        </select>
                    </div>
					<div class="col-md-4">
                        <label for="trade-model" class="form-label fw-bold"><?= $lang['entry_model'] ?></label>
                        <select class="form-select" id="trade-model" name="model_id">
                            <option value=""><?= $lang['select'] ?></option>
                        </select>
                    </div>
                     <div class="col-md-12">
                        <label for="trade-plan" class="form-label fw-bold"><?= $lang['link_to_plan'] ?></label>
                        <select class="form-select" id="trade-plan" name="plan_id">
                            <option value=""><?= $lang['no_plan'] ?></option>
                             </select>
                         <div class="form-text"><?= $lang['select_plan_desc'] ?></div>
                    </div>
					<div class="col-md-12 mt-3">
                        <label for="trade-note" class="form-label fw-bold"><?= $lang['link_to_note'] ?></label>
                        <select class="form-select" id="trade-note" name="note_id">
                            <option value=""><?= $lang['no_note'] ?></option>
                        </select>
                    </div>
                </div>

                 <h5 class="text-muted mb-3"><?= $lang['trade_entry'] ?></h5>
                <div class="row g-3 mb-4">
                     <div class="col-md-4">
                        <label for="trade-entry-date" class="form-label fw-bold"><?= $lang['trade_entry_date_star'] ?></label>
                        <input type="datetime-local" class="form-control" id="trade-entry-date" name="entry_date" required>
                    </div>
                     <div class="col-md-4">
						<label class="form-label fw-bold d-block"><?= $lang['direction_star'] ?></label>
						<div class="btn-group w-100" role="group">
							<input type="radio" class="btn-check" name="direction" id="dir-long" value="Long" required checked>
								<label class="btn btn-outline-success d-flex align-items-center justify-content-center gap-2 text-profit" for="dir-long">
								<i class="fas fa-arrow-up"></i> <?= $lang['long'] ?>
								</label>
        
							<input type="radio" class="btn-check" name="direction" id="dir-short" value="Short">
								<label class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2 text-loss" for="dir-short">
								<i class="fas fa-arrow-down"></i> <?= $lang['short'] ?>
								</label>
						</div>
					</div>
                    <div class="col-md-4">
                        <label for="trade-entry-tf" class="form-label"><?= $lang['entry_timeframe'] ?></label>
                         <select class="form-select" id="trade-entry-tf" name="entry_timeframe">
                            <option value=""><?= $lang['select'] ?></option>
                            <option value="M5">M5</option>
                            <option value="M15">M15</option>
                            <option value="H1">H1</option>
                            <option value="H4">H4</option>
                             <option value="1D">1D</option>
                        </select>
                    </div>
                </div>
                
                 <h5 class="text-muted mb-3"><?= $lang['risk'] ?></h5>
                 <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                     <div class="col-md-6">
                         <label for="trade-risk" class="form-label fw-bold"><?= $lang['risk_percent_star'] ?></label>
                         <div class="input-group">
                            <input type="number" class="form-control" id="trade-risk" name="risk_percent" step="0.01" value="1.00" required placeholder="<?= $lang['enter_risk_percent'] ?>">
                         </div>
                    </div>
                 </div>

                  <h5 class="text-muted mb-3"><?= $lang['result_final'] ?></h5>
                 <div class="row g-3 mb-4 p-3" style="background-color: #090c14; border-radius: 8px; padding:10px;">
                      <div class="col-md-6">
                        <label for="trade-status" class="form-label fw-bold"><?= $lang['status'] ?></label>
                        <select class="form-select" id="trade-status" name="status">
                            <option value="pending" selected>🕒 <?= $lang['pending'] ?></option>
                            <option value="win">✅ <?= $lang['win'] ?></option>
                            <option value="loss">❌ <?= $lang['loss'] ?></option>
                            <option value="breakeven">➖ <?= $lang['breakeven'] ?></option>
                             <option value="partial">🔄 <?= $lang['partial'] ?></option>
                            <option value="cancelled">🚫 <?= $lang['cancelled'] ?></option>
                        </select>
                    </div>
                     <div class="col-md-6">
                        <label for="trade-exit-date" class="form-label"><?= $lang['exit_date_time'] ?></label>
                        <input type="datetime-local" class="form-control" id="trade-exit-date" name="exit_date">
                    </div>
                      <div class="col-md-6">
                        <label for="trade-pnl" class="form-label fw-bold"><?= $lang['pnl_dollar'] ?></label>
                        <input type="number" class="form-control fw-bold" id="trade-pnl" name="pnl" step="0.01" placeholder="<?= $lang['pnl_placeholder'] ?>">
                    </div>
                     <div class="col-md-6">
                        <label for="trade-rr-achieved" class="form-label fw-bold"><?= $lang['rr_result'] ?></label>
                        <input type="number" class="form-control fw-bold" id="trade-rr-achieved" name="rr_achieved" step="0.01" placeholder="<?= $lang['calculated_automatically'] ?>" readonly>
                    </div>
                 </div>

                 <h5 class="text-muted mb-3"><?= $lang['analysis_and_conclusion'] ?></h5>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label for="trade-notes" class="form-label"><?= $lang['notes'] ?></label>
                        <textarea class="form-control" id="trade-notes" name="notes" rows="3" placeholder="<?= $lang['notes_placeholder'] ?>"></textarea>
                    </div>
                     <div class="col-md-6">
                         <label for="trade-conclusions" class="form-label"><?= $lang['conclusion'] ?></label>
                        <textarea class="form-control" id="trade-conclusions" name="trade_conclusions" rows="3" placeholder="<?= $lang['conclusion_placeholder'] ?>"></textarea>
                    </div>
                     <div class="col-md-6">
                        <label for="trade-lessons" class="form-label"><?= $lang['key_lessons'] ?></label>
                        <textarea class="form-control" id="trade-lessons" name="key_lessons" rows="3" placeholder="<?= $lang['lessons_placeholder'] ?>"></textarea>
                    </div>
                      <div class="col-md-6">
                        <label for="trade-mistakes" class="form-label"><?= $lang['errors_q'] ?></label>
                         <textarea class="form-control border-danger" id="trade-mistakes" name="mistakes_made" rows="2" placeholder="<?= $lang['errors_placeholder'] ?>"></textarea>
                    </div>
                     <div class="col-md-6">
                        <label for="trade-emotions" class="form-label"><?= $lang['emotional_condition'] ?></label>
                        <input type="text" class="form-control" id="trade-emotions" name="emotional_state" placeholder="<?= $lang['emotions_placeholder'] ?>">
                    </div>
                     <div class="col-12">
                         <label for="trade-tags" class="form-label"><?= $lang['tags'] ?></label>
                        <input type="text" class="form-control" id="trade-tags" name="tags" placeholder="<?= $lang['tags_placeholder'] ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-muted m-0"><?= $lang['trade_screenshots'] ?></h5>
                    
                </div>
                <div id="trade-images-container">
                    </div>

                 <div class="mt-4 pt-3 border-top">
				    <button type="button" class="btn btn-secondary" onclick="addTradeImage()">
                        <i class="fas fa-plus me-1"></i> <?= $lang['add_image'] ?>
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save me-2"></i> <?= $lang['save_trade'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>