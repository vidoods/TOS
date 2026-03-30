<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0" id="form-page-title" style="font-weight: 600;">New trade</h2>
        <a href="index.php?view=journal" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>

    <div class="card glass-panel mb-4 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-body p-4" style="padding: 40px;">
            <input type="hidden" id="edit-trade-id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">

            <form id="trade-form">
                <h5 class="text-muted mb-3">Main parameters</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="trade-pair" class="form-label fw-bold">Pair *</label>
                        <select class="form-select" id="trade-pair" name="pair_id" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="trade-account" class="form-label fw-bold">Account *</label>
                        <select class="form-select" id="trade-account" name="account_id" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                     <div class="col-md-4">
                        <label for="trade-style" class="form-label fw-bold">Trading style</label>
                         <select class="form-select" id="trade-style" name="style_id">
                            <option value="">Select</option>
                        </select>
                    </div>
					<div class="col-md-4">
                        <label for="trade-model" class="form-label fw-bold">Entry model</label>
                        <select class="form-select" id="trade-model" name="model_id">
                            <option value="">Select</option>
                        </select>
                    </div>
                     <div class="col-md-12">
                        <label for="trade-plan" class="form-label fw-bold">Link to Plan</label>
                        <select class="form-select" id="trade-plan" name="plan_id">
                            <option value="">--- No Plan ---</option>
                             </select>
                         <div class="form-text">Select the Plan you want to link to this Trade.</div>
                    </div>
					<div class="col-md-12 mt-3">
                        <label for="trade-note" class="form-label fw-bold">Link to Note</label>
                        <select class="form-select" id="trade-note" name="note_id">
                            <option value="">--- No Note ---</option>
                        </select>
                    </div>
                </div>

                 <h5 class="text-muted mb-3">Trade Entry</h5>
                <div class="row g-3 mb-4">
                     <div class="col-md-4">
                        <label for="trade-entry-date" class="form-label fw-bold">Trade entry Date Time *</label>
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
                        <label for="trade-entry-tf" class="form-label">Entry timeframe</label>
                         <select class="form-select" id="trade-entry-tf" name="entry_timeframe">
                            <option value="">Select</option>
                            <option value="M5">M5</option>
                            <option value="M15">M15</option>
                            <option value="H1">H1</option>
                            <option value="H4">H4</option>
                             <option value="1D">1D</option>
                        </select>
                    </div>
                </div>
                
                 <h5 class="text-muted mb-3">Risk</h5>
                 <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                     <div class="col-md-6">
                         <label for="trade-risk" class="form-label fw-bold">Risk on trade (%) *</label>
                         <div class="input-group">
                            <input type="number" class="form-control" id="trade-risk" name="risk_percent" step="0.01" value="1.00" required placeholder="Enter risk %">
                         </div>
                    </div>
                 </div>

                  <h5 class="text-muted mb-3">Result (final on completion)</h5>
                 <div class="row g-3 mb-4 p-3" style="background-color: #090c14; border-radius: 8px; padding:10px;">
                      <div class="col-md-6">
                        <label for="trade-status" class="form-label fw-bold">Status</label>
                        <select class="form-select" id="trade-status" name="status">
                            <option value="pending" selected>🕒 Pending</option>
                            <option value="win">✅ Win</option>
                            <option value="loss">❌ Loss</option>
                            <option value="breakeven">➖ Breakeven</option>
                             <option value="partial">🔄 Partial</option>
                            <option value="cancelled">🚫 Cancelled</option>
                        </select>
                    </div>
                     <div class="col-md-6">
                        <label for="trade-exit-date" class="form-label">Exit Date Time</label>
                        <input type="datetime-local" class="form-control" id="trade-exit-date" name="exit_date">
                    </div>
                      <div class="col-md-6">
                        <label for="trade-pnl" class="form-label fw-bold">PnL $</label>
                        <input type="number" class="form-control fw-bold" id="trade-pnl" name="pnl" step="0.01" placeholder="Example: 150.00 or -50.00">
                    </div>
                     <div class="col-md-6">
                        <label for="trade-rr-achieved" class="form-label fw-bold">R:R (Result)</label>
                        <input type="number" class="form-control fw-bold" id="trade-rr-achieved" name="rr_achieved" step="0.01" placeholder="Calculated automatically" readonly>
                    </div>
                 </div>

                 <h5 class="text-muted mb-3">Analysis and conclusion</h5>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label for="trade-notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="trade-notes" name="notes" rows="3" placeholder="Any notes on duration of the Trade..."></textarea>
                    </div>
                     <div class="col-md-6">
                         <label for="trade-conclusions" class="form-label">Conclusion (What works / Not works?)</label>
                        <textarea class="form-control" id="trade-conclusions" name="trade_conclusions" rows="3" placeholder="Example: Price made correction to POI and confirm it on lower TF as per my TS"></textarea>
                    </div>
                     <div class="col-md-6">
                        <label for="trade-lessons" class="form-label">Key lessons (What I learned?)</label>
                        <textarea class="form-control" id="trade-lessons" name="key_lessons" rows="3" placeholder="What can I do different next time?"></textarea>
                    </div>
                      <div class="col-md-6">
                        <label for="trade-mistakes" class="form-label">Errors?</label>
                         <textarea class="form-control border-danger" id="trade-mistakes" name="mistakes_made" rows="2" placeholder="Example: early entry, rule breach, not follow strategy..."></textarea>
                    </div>
                     <div class="col-md-6">
                        <label for="trade-emotions" class="form-label">Emotional condition</label>
                        <input type="text" class="form-control" id="trade-emotions" name="emotional_state" placeholder="Example: confidence, fear, FOMO, calm...">
                    </div>
                     <div class="col-12">
                         <label for="trade-tags" class="form-label">Tags</label>
                        <input type="text" class="form-control" id="trade-tags" name="tags" placeholder="trend, breakout, news, mistake...">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-muted m-0">Trade screenshots</h5>
                    
                </div>
                <div id="trade-images-container">
                    </div>

                 <div class="mt-4 pt-3 border-top">
				    <button type="button" class="btn btn-secondary" onclick="addTradeImage()">
                        <i class="fas fa-plus me-1"></i> Add Image
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save me-2"></i> Save trade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>