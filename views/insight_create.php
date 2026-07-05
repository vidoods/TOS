<?php
$insight_id = $_GET['id'] ?? null;
$is_edit = !empty($insight_id);
$page_title = $is_edit ? $lang['edit_insight'] : $lang['add_new_insight'];
?>

<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0 fw-bold"><?= $page_title ?></h2>
        <div class="d-flex gap-2">
            <?php if($is_edit): ?>
            <button type="button" class="btn btn-danger" onclick="deleteInsight(<?= $insight_id ?>)">
                <i class="fas fa-trash"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            <input type="hidden" id="edit-insight-id" value="<?= htmlspecialchars($insight_id ?? '') ?>">

            <form id="insight-form" onsubmit="saveInsight(event)">
                <div class="mb-4">
                    <label class="form-lanel">Select Asset</label>
                        <select class="input-field" name="asset_id" required>
                            <option value="">-- Select Asset --</option>
                            <?php
                                // Здесь вы должны получить список пар пользователя из БД
                                // Пример (упрощенно):
                                $stmt = $pdo->prepare("SELECT id, symbol FROM user_pairs WHERE user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='{$row['id']}'>{$row['symbol']}</option>";
                                }
                            ?>
                        </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label"><?= $lang['content'] ?></label>
                    <div id="insight-editor-container" style="height: 400px; background: rgba(0,0,0,0.2); border-radius: 0 0 8px 8px;"></div>
                    <input type="hidden" name="content" id="insight-content-hidden">
                </div>

                <div class="d-flex gap-2 pt-3 border-top border-secondary">
                    <button type="button" class="btn btn-outline" onclick="window.history.back()"><?= $lang['cancel'] ?></button>
                    <button type="submit" class="btn btn-primary px-4"><?= $lang['save'] ?></button>
                </div>
            </form>
        </div>
    </div>
</div>