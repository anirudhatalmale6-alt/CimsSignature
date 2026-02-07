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
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Signature</h5>
                                        <span id="deviceStatus" class="badge bg-secondary">No device connected</span>
                                    </div>
                                    <div class="card-body text-center">
                                        <!-- Signature Display Box -->
                                        <div id="signatureBox" style="border: 2px dashed #ccc; min-height: 150px; background: #fff; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                                            <div id="signaturePlaceholder">
                                                <i class="fa fa-pen fa-3x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Click "Connect Wacom" then "Capture" to sign</p>
                                            </div>
                                            <img id="signatureImage" src="" alt="Signature" style="max-width: 100%; max-height: 140px; display: none;">
                                        </div>

                                        <!-- Hidden field to store signature data -->
                                        <input type="hidden" id="signature_data" name="signature_data">

                                        <!-- Wacom STU Canvas (hidden, used for capture) -->
                                        <canvas id="stuCanvas" style="display: none;"></canvas>

                                        <!-- Buttons -->
                                        <div class="btn-group mb-3">
                                            <button type="button" id="btnConnect" class="btn btn-info btn-lg">
                                                <i class="fa fa-usb"></i> Connect Wacom
                                            </button>
                                            <button type="button" id="btnCapture" class="btn btn-primary btn-lg" disabled>
                                                <i class="fa fa-pen"></i> Capture Signature
                                            </button>
                                            <button type="button" id="btnClear" class="btn btn-warning btn-lg" disabled>
                                                <i class="fa fa-eraser"></i> Clear
                                            </button>
                                            <button type="submit" id="btnSave" class="btn btn-success btn-lg" disabled>
                                                <i class="fa fa-save"></i> Save Signature
                                            </button>
                                        </div>

                                        <div class="text-muted small">
                                            <p class="mb-1"><strong>Note:</strong> Use Chrome, Edge, or Opera browser for Wacom support.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fallback Canvas -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Alternative: Draw with Mouse/Touch</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="fallbackCanvas" width="500" height="150" style="border: 1px solid #000; background: #fff; cursor: crosshair;"></canvas>
                                        <div class="mt-2">
                                            <button type="button" id="btnUseFallback" class="btn btn-info btn-sm">
                                                <i class="fa fa-check"></i> Use This Signature
                                            </button>
                                            <button type="button" id="btnClearFallback" class="btn btn-secondary btn-sm">
                                                <i class="fa fa-eraser"></i> Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const btnConnect = document.getElementById('btnConnect');
    const btnCapture = document.getElementById('btnCapture');
    const btnClear = document.getElementById('btnClear');
    const btnSave = document.getElementById('btnSave');
    const signatureImage = document.getElementById('signatureImage');
    const signaturePlaceholder = document.getElementById('signaturePlaceholder');
    const signatureData = document.getElementById('signature_data');
    const signatureForm = document.getElementById('signatureForm');
    const statusMessage = document.getElementById('statusMessage');
    const deviceStatus = document.getElementById('deviceStatus');
    const stuCanvas = document.getElementById('stuCanvas');
    const stuCtx = stuCanvas.getContext('2d');

    // Wacom STU device
    let stuDevice = null;
    let isCapturing = false;
    let penData = [];

    // STU-430 dimensions
    const STU_WIDTH = 320;
    const STU_HEIGHT = 200;

    // Check WebHID support
    if (!navigator.hid) {
        showMessage('WebHID not supported. Please use Chrome, Edge, or Opera browser.', 'warning');
        btnConnect.disabled = true;
    }

    // Show status message
    function showMessage(message, type) {
        statusMessage.className = 'alert alert-' + type;
        statusMessage.textContent = message;
        statusMessage.classList.remove('d-none');
        if (type === 'success') {
            setTimeout(function() {
                statusMessage.classList.add('d-none');
            }, 5000);
        }
    }

    // Update device status badge
    function updateDeviceStatus(status, type) {
        deviceStatus.textContent = status;
        deviceStatus.className = 'badge bg-' + type;
    }

    // Connect to Wacom STU device
    btnConnect.addEventListener('click', async function() {
        try {
            showMessage('Requesting device access...', 'info');

            // Request HID device - Wacom STU vendor ID is 0x056A
            const devices = await navigator.hid.requestDevice({
                filters: [
                    { vendorId: 0x056A }, // Wacom
                ]
            });

            if (devices.length === 0) {
                showMessage('No device selected', 'warning');
                return;
            }

            stuDevice = devices[0];

            if (!stuDevice.opened) {
                await stuDevice.open();
            }

            updateDeviceStatus('Connected: ' + stuDevice.productName, 'success');
            showMessage('Wacom device connected! Click "Capture Signature" to begin.', 'success');
            btnCapture.disabled = false;

            // Set up canvas
            stuCanvas.width = STU_WIDTH;
            stuCanvas.height = STU_HEIGHT;
            stuCtx.fillStyle = '#fff';
            stuCtx.fillRect(0, 0, STU_WIDTH, STU_HEIGHT);
            stuCtx.strokeStyle = '#000';
            stuCtx.lineWidth = 2;
            stuCtx.lineCap = 'round';
            stuCtx.lineJoin = 'round';

            // Handle input reports from device
            stuDevice.addEventListener('inputreport', handleInputReport);

        } catch (error) {
            console.error('Connection error:', error);
            showMessage('Failed to connect: ' + error.message, 'danger');
        }
    });

    // Handle pen input from STU device
    function handleInputReport(event) {
        if (!isCapturing) return;

        const data = new Uint8Array(event.data.buffer);

        // Parse pen data - format depends on STU model
        // STU-430 typically sends: reportId, x_low, x_high, y_low, y_high, pressure_low, pressure_high, buttons
        if (data.length >= 7) {
            const x = data[1] | (data[2] << 8);
            const y = data[3] | (data[4] << 8);
            const pressure = data[5] | (data[6] << 8);
            const buttons = data.length > 7 ? data[7] : 0;

            // Check if pen is touching (pressure > 0 or button pressed)
            const penDown = pressure > 10 || (buttons & 0x01);

            if (penDown) {
                // Scale coordinates to canvas
                const scaledX = (x / 4096) * STU_WIDTH;
                const scaledY = (y / 4096) * STU_HEIGHT;

                if (penData.length > 0) {
                    const lastPoint = penData[penData.length - 1];
                    stuCtx.beginPath();
                    stuCtx.moveTo(lastPoint.x, lastPoint.y);
                    stuCtx.lineTo(scaledX, scaledY);
                    stuCtx.stroke();
                }

                penData.push({ x: scaledX, y: scaledY, pressure: pressure });
            }
        }
    }

    // Start signature capture
    btnCapture.addEventListener('click', function() {
        if (!stuDevice) {
            showMessage('Please connect device first', 'warning');
            return;
        }

        // Clear canvas
        stuCtx.fillStyle = '#fff';
        stuCtx.fillRect(0, 0, STU_WIDTH, STU_HEIGHT);
        penData = [];
        isCapturing = true;

        btnCapture.innerHTML = '<i class="fa fa-stop"></i> Stop Capture';
        btnCapture.classList.remove('btn-primary');
        btnCapture.classList.add('btn-danger');

        showMessage('Signing on Wacom device... Press button again when done.', 'info');

        // Toggle capture mode
        btnCapture.onclick = function() {
            stopCapture();
        };
    });

    // Stop capture and display signature
    function stopCapture() {
        isCapturing = false;

        btnCapture.innerHTML = '<i class="fa fa-pen"></i> Capture Signature';
        btnCapture.classList.remove('btn-danger');
        btnCapture.classList.add('btn-primary');
        btnCapture.onclick = function() {
            // Clear and restart
            stuCtx.fillStyle = '#fff';
            stuCtx.fillRect(0, 0, STU_WIDTH, STU_HEIGHT);
            penData = [];
            isCapturing = true;
            btnCapture.innerHTML = '<i class="fa fa-stop"></i> Stop Capture';
            btnCapture.classList.remove('btn-primary');
            btnCapture.classList.add('btn-danger');
            showMessage('Signing on Wacom device... Press button again when done.', 'info');
            btnCapture.onclick = function() { stopCapture(); };
        };

        if (penData.length > 5) {
            // Get signature image from canvas
            const dataUrl = stuCanvas.toDataURL('image/png');
            displaySignature(dataUrl);
            showMessage('Signature captured! Fill in details and click Save.', 'success');
        } else {
            showMessage('No signature detected. Please try again.', 'warning');
        }
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
        penData = [];
        if (stuCtx) {
            stuCtx.fillStyle = '#fff';
            stuCtx.fillRect(0, 0, STU_WIDTH, STU_HEIGHT);
        }
    });

    // Save signature
    signatureForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!signatureData.value) {
            showMessage('Please capture a signature first', 'warning');
            return;
        }

        if (!document.getElementById('client_name').value) {
            showMessage('Please enter client name', 'warning');
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
    const canvas = document.getElementById('fallbackCanvas');
    const ctx = canvas.getContext('2d');
    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;

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
        showMessage('Signature loaded. Fill in details and click Save.', 'info');
    });
});
</script>
@endpush
@endsection
