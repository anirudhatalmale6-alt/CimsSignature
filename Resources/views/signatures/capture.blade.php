@extends('smartdash::layouts.default')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Capture Signature</h4>
                    <a href="{{ route('signatures.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <!-- Status Messages -->
                    <div id="statusMessage" class="alert d-none"></div>

                    <!-- Client Details Form -->
                    <form id="signatureForm">
                        @csrf
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="client_name" name="client_name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="client_reference" class="form-label">Client Reference</label>
                                    <input type="text" class="form-control" id="client_reference" name="client_reference" placeholder="e.g., CLT001">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="id_number" class="form-label">ID Number</label>
                                    <input type="text" class="form-control" id="id_number" name="id_number">
                                </div>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="purpose" class="form-label">Purpose / Document</label>
                                    <input type="text" class="form-control" id="purpose" name="purpose" placeholder="e.g., SARS Representative Appointment">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="document_reference" class="form-label">Document Reference</label>
                                    <input type="text" class="form-control" id="document_reference" name="document_reference">
                                </div>
                            </div>
                        </div>

                        <!-- Signature Capture Area -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h5 class="mb-0">Signature</h5>
                                    </div>
                                    <div class="card-body text-center">
                                        <!-- Signature Display Box -->
                                        <div id="signatureBox" style="border: 2px dashed #ccc; min-height: 150px; background: #fff; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                                            <div id="signaturePlaceholder">
                                                <i class="fa fa-pen fa-3x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Click "Capture Signature" to sign on Wacom device</p>
                                            </div>
                                            <img id="signatureImage" src="" alt="Signature" style="max-width: 100%; max-height: 140px; display: none;">
                                        </div>

                                        <!-- Hidden field to store signature data -->
                                        <input type="hidden" id="signature_data" name="signature_data">

                                        <!-- Buttons -->
                                        <div class="btn-group">
                                            <button type="button" id="btnCapture" class="btn btn-primary btn-lg">
                                                <i class="fa fa-pen"></i> Capture Signature
                                            </button>
                                            <button type="button" id="btnClear" class="btn btn-warning btn-lg" disabled>
                                                <i class="fa fa-eraser"></i> Clear
                                            </button>
                                            <button type="submit" id="btnSave" class="btn btn-success btn-lg" disabled>
                                                <i class="fa fa-save"></i> Save Signature
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Connection Status -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div id="connectionStatus" class="text-muted small">
                                    <i class="fa fa-circle text-secondary"></i> SigCaptX Status: Not Connected
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Fallback Canvas for testing without Wacom -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Fallback: Mouse/Touch Signature (for testing)</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Use this if SigCaptX is not available or for testing:</p>
                    <canvas id="fallbackCanvas" width="500" height="150" style="border: 1px solid #000; background: #fff; cursor: crosshair;"></canvas>
                    <div class="mt-2">
                        <button type="button" id="btnUseFallback" class="btn btn-info btn-sm">
                            <i class="fa fa-check"></i> Use This Signature
                        </button>
                        <button type="button" id="btnClearFallback" class="btn btn-secondary btn-sm">
                            <i class="fa fa-eraser"></i> Clear Canvas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const btnCapture = document.getElementById('btnCapture');
    const btnClear = document.getElementById('btnClear');
    const btnSave = document.getElementById('btnSave');
    const signatureImage = document.getElementById('signatureImage');
    const signaturePlaceholder = document.getElementById('signaturePlaceholder');
    const signatureData = document.getElementById('signature_data');
    const signatureForm = document.getElementById('signatureForm');
    const statusMessage = document.getElementById('statusMessage');
    const connectionStatus = document.getElementById('connectionStatus');

    // Fallback canvas
    const canvas = document.getElementById('fallbackCanvas');
    const ctx = canvas.getContext('2d');
    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;

    // SigCaptX variables
    let sigCapt = null;
    let sigCtl = null;
    let dynCapt = null;

    // Initialize SigCaptX
    function initSigCaptX() {
        if (typeof wgssSigCaptX === 'undefined') {
            updateConnectionStatus('SigCaptX library not loaded', 'warning');
            return;
        }

        wgssSigCaptX.init().then(function() {
            updateConnectionStatus('Connecting to SigCaptX...', 'info');
            return wgssSigCaptX.checkSigCaptXIsRunning();
        }).then(function(isRunning) {
            if (isRunning) {
                updateConnectionStatus('SigCaptX Connected', 'success');
                return wgssSigCaptX.getSigCapt();
            } else {
                throw new Error('SigCaptX service not running');
            }
        }).then(function(sc) {
            sigCapt = sc;
        }).catch(function(error) {
            updateConnectionStatus('SigCaptX not available: ' + error.message, 'danger');
            console.error('SigCaptX error:', error);
        });
    }

    // Update connection status display
    function updateConnectionStatus(message, type) {
        const colors = {
            'success': 'text-success',
            'danger': 'text-danger',
            'warning': 'text-warning',
            'info': 'text-info'
        };
        connectionStatus.innerHTML = '<i class="fa fa-circle ' + (colors[type] || 'text-secondary') + '"></i> SigCaptX Status: ' + message;
    }

    // Show status message
    function showMessage(message, type) {
        statusMessage.className = 'alert alert-' + type;
        statusMessage.textContent = message;
        statusMessage.classList.remove('d-none');
        setTimeout(function() {
            statusMessage.classList.add('d-none');
        }, 5000);
    }

    // Capture signature using SigCaptX
    btnCapture.addEventListener('click', function() {
        if (sigCapt) {
            captureWithSigCaptX();
        } else {
            showMessage('SigCaptX not available. Please use the fallback canvas below or ensure SigCaptX is running.', 'warning');
        }
    });

    function captureWithSigCaptX() {
        sigCapt.getDynCapt().then(function(dc) {
            dynCapt = dc;
            return dynCapt.setLicence("<!-- Your license here if required -->");
        }).then(function() {
            return dynCapt.capture(sigCapt, "Please sign below");
        }).then(function(sigObj) {
            if (sigObj && !sigObj.isCaptured) {
                showMessage('Signature capture cancelled', 'warning');
                return;
            }
            return sigObj.renderBitmap("image/png", 300, 100, 0.5, 0x00000000, 0xFFFFFFFF, 10, 10);
        }).then(function(imageData) {
            if (imageData) {
                displaySignature('data:image/png;base64,' + imageData);
            }
        }).catch(function(error) {
            showMessage('Capture error: ' + error.message, 'danger');
            console.error('Capture error:', error);
        });
    }

    // Display captured signature
    function displaySignature(dataUrl) {
        signatureData.value = dataUrl;
        signatureImage.src = dataUrl;
        signatureImage.style.display = 'block';
        signaturePlaceholder.style.display = 'none';
        btnClear.disabled = false;
        btnSave.disabled = false;
    }

    // Clear signature
    btnClear.addEventListener('click', function() {
        signatureData.value = '';
        signatureImage.src = '';
        signatureImage.style.display = 'none';
        signaturePlaceholder.style.display = 'block';
        btnClear.disabled = true;
        btnSave.disabled = true;
    });

    // Save signature
    signatureForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!signatureData.value) {
            showMessage('Please capture a signature first', 'warning');
            return;
        }

        const formData = {
            client_name: document.getElementById('client_name').value,
            client_reference: document.getElementById('client_reference').value,
            id_number: document.getElementById('id_number').value,
            purpose: document.getElementById('purpose').value,
            document_reference: document.getElementById('document_reference').value,
            signature_data: signatureData.value,
            _token: '{{ csrf_token() }}'
        };

        fetch('{{ route("signatures.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Signature saved successfully!', 'success');
                setTimeout(function() {
                    window.location.href = '{{ route("signatures.index") }}';
                }, 1500);
            } else {
                showMessage('Error saving signature: ' + (data.message || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            showMessage('Error saving signature: ' + error.message, 'danger');
        });
    });

    // ========== Fallback Canvas Drawing ==========
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        if (e.touches) {
            return {
                x: e.touches[0].clientX - rect.left,
                y: e.touches[0].clientY - rect.top
            };
        }
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }

    canvas.addEventListener('mousedown', function(e) {
        isDrawing = true;
        const pos = getPos(e);
        lastX = pos.x;
        lastY = pos.y;
    });

    canvas.addEventListener('mousemove', function(e) {
        if (!isDrawing) return;
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        lastX = pos.x;
        lastY = pos.y;
    });

    canvas.addEventListener('mouseup', () => isDrawing = false);
    canvas.addEventListener('mouseout', () => isDrawing = false);

    // Touch support
    canvas.addEventListener('touchstart', function(e) {
        e.preventDefault();
        isDrawing = true;
        const pos = getPos(e);
        lastX = pos.x;
        lastY = pos.y;
    });

    canvas.addEventListener('touchmove', function(e) {
        e.preventDefault();
        if (!isDrawing) return;
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        lastX = pos.x;
        lastY = pos.y;
    });

    canvas.addEventListener('touchend', () => isDrawing = false);

    document.getElementById('btnClearFallback').addEventListener('click', function() {
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    });

    document.getElementById('btnUseFallback').addEventListener('click', function() {
        const dataUrl = canvas.toDataURL('image/png');
        displaySignature(dataUrl);
        showMessage('Canvas signature loaded. Fill in details and click Save.', 'info');
    });

    // Try to initialize SigCaptX
    initSigCaptX();
});
</script>
@endpush
@endsection
