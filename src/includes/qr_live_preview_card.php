<?php
$isDynamic = !empty($isDynamicQr) || (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === 'dynamic_qrcode.php') || (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === 'dynamic_qrcode.php');
?>
<div class="card card-outline card-primary sticky-top qr-preview-card" style="top: 75px; z-index: 10;">
    <div class="card-header py-2">
        <h3 class="card-title text-sm font-weight-bold"><i class="fas fa-eye mr-2"></i>Live QR Preview</h3>
        <div class="card-tools">
            <span class="badge badge-success" id="qr-preview-badge"><i class="fas fa-check-circle mr-1"></i> Interactive</span>
        </div>
    </div>
    <div class="card-body text-center p-3">
        <div class="qr-preview-container d-flex justify-content-center align-items-center mb-3" style="min-height: 250px; background: repeating-conic-gradient(#f1f3f5 0% 25%, #e9ecef 0% 50%) 50% / 20px 20px; border-radius: 8px; padding: 16px; border: 1px dashed #ced4da;">
            <canvas id="qr-preview-canvas" width="220" height="220" style="max-width: 100%; height: auto; box-shadow: 0 4px 14px rgba(0,0,0,0.12); border-radius: 6px; background: #ffffff;"></canvas>
        </div>

        <?php if ($isDynamic): ?>
        <div class="alert alert-warning py-2 px-3 text-left small mb-3 border-0" style="font-size: 11.5px; border-left: 4px solid #ffc107 !important; background-color: rgba(255, 193, 7, 0.15);">
            <i class="fas fa-exclamation-triangle mr-1 text-warning"></i>
            <strong>Disclaimer:</strong> This preview may differ from the final QR code (the final code encodes an auto-generated slug for the integrated shortener).
        </div>
        <?php endif; ?>

        <div class="preview-details text-left text-muted small border-top pt-2">
            <div class="d-flex justify-content-between mb-1">
                <span><i class="fas fa-expand mr-1"></i> Target Resolution:</span>
                <strong id="preview-size-label" class="text-dark">300 x 300 px</strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span><i class="fas fa-shield-alt mr-1"></i> Precision / ECC:</span>
                <strong id="preview-ecc-label" class="text-dark">Medium (15%)</strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span><i class="fas fa-palette mr-1"></i> Colors:</span>
                <span>
                    <span id="preview-fg-swatch" style="display:inline-block; width:12px; height:12px; background:#000000; border-radius:2px; vertical-align:middle; border:1px solid #ccc;"></span>
                    <span id="preview-fg-hex" class="text-dark">#000000</span> /
                    <span id="preview-bg-swatch" style="display:inline-block; width:12px; height:12px; background:#ffffff; border-radius:2px; vertical-align:middle; border:1px solid #ccc;"></span>
                    <span id="preview-bg-hex" class="text-dark">#FFFFFF</span>
                </span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span><i class="fas fa-icons mr-1"></i> Center Emblem:</span>
                <strong id="preview-logo-label" class="text-dark">None</strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span><i class="fas fa-shapes mr-1"></i> Block Mode:</span>
                <strong id="preview-block-label" class="text-dark">Margin</strong>
            </div>
            <div class="text-truncate mb-3 p-2 bg-light rounded" style="font-family: monospace; font-size: 11px;">
                <i class="fas fa-qrcode mr-1 text-primary"></i> <span id="preview-data-label">https://example.com</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary btn-block" id="btn-download-preview">
                <i class="fas fa-download mr-1"></i> Download Preview Image
            </button>
        </div>
    </div>
</div>
