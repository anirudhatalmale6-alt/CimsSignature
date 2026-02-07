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
                                        <span id="deviceStatus" class="badge bg-secondary">Checking connection...</span>
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
                                        <div class="btn-group mb-3">
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

                                        <div class="text-muted small">
                                            <p class="mb-1"><strong>Requirements:</strong> WacomSTUSigCaptX service must be running on your computer.</p>
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

<!-- Wacom STU SigCaptX SDK Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/q.js/1.5.1/q.min.js"></script>
<script>
// Wacom STU SigCaptX WebSocket Communication
// This connects to the local wgssSTU service on port 9000

var WacomSTU = {
    ws: null,
    messageId: 0,
    pendingCalls: {},
    connected: false,
    port: 9000,

    connect: function() {
        var self = this;
        return new Promise(function(resolve, reject) {
            try {
                // Try secure WebSocket first
                self.ws = new WebSocket('wss://localhost:' + self.port);

                self.ws.onopen = function() {
                    self.connected = true;
                    console.log('Connected to WacomSTU SigCaptX service');
                    resolve(true);
                };

                self.ws.onerror = function(error) {
                    console.log('WSS failed, trying WS...');
                    // Try non-secure if secure fails
                    self.ws = new WebSocket('ws://localhost:' + self.port);

                    self.ws.onopen = function() {
                        self.connected = true;
                        console.log('Connected to WacomSTU SigCaptX service (non-secure)');
                        resolve(true);
                    };

                    self.ws.onerror = function() {
                        self.connected = false;
                        reject(new Error('Could not connect to SigCaptX service on port ' + self.port));
                    };

                    self.ws.onmessage = function(event) {
                        self.handleMessage(event.data);
                    };
                };

                self.ws.onmessage = function(event) {
                    self.handleMessage(event.data);
                };

                self.ws.onclose = function() {
                    self.connected = false;
                    console.log('Disconnected from SigCaptX service');
                };

            } catch (e) {
                reject(e);
            }
        });
    },

    handleMessage: function(data) {
        try {
            var response = JSON.parse(data);
            if (response.id && this.pendingCalls[response.id]) {
                this.pendingCalls[response.id](response);
                delete this.pendingCalls[response.id];
            }
        } catch (e) {
            console.error('Error parsing response:', e);
        }
    },

    call: function(method, params) {
        var self = this;
        return new Promise(function(resolve, reject) {
            if (!self.connected || !self.ws) {
                reject(new Error('Not connected'));
                return;
            }

            var id = ++self.messageId;
            var message = {
                jsonrpc: '2.0',
                id: id,
                method: method,
                params: params || {}
            };

            self.pendingCalls[id] = function(response) {
                if (response.error) {
                    reject(response.error);
                } else {
                    resolve(response.result);
                }
            };

            self.ws.send(JSON.stringify(message));

            // Timeout after 30 seconds
            setTimeout(function() {
                if (self.pendingCalls[id]) {
                    delete self.pendingCalls[id];
                    reject(new Error('Request timeout'));
                }
            }, 30000);
        });
    },

    getDevices: function() {
        return this.call('QueryDevices');
    },

    captureSignature: function(options) {
        return this.call('Capture', options || {});
    }
};
</script>

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
    const deviceStatus = document.getElementById('deviceStatus');

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

    // Try to connect to Wacom SigCaptX service
    WacomSTU.connect()
        .then(function() {
            updateDeviceStatus('SigCaptX Connected', 'success');
            return WacomSTU.getDevices();
        })
        .then(function(devices) {
            if (devices && devices.length > 0) {
                updateDeviceStatus('STU Device Ready', 'success');
            } else {
                updateDeviceStatus('No STU device found', 'warning');
            }
        })
        .catch(function(error) {
            console.error('Connection error:', error);
            updateDeviceStatus('SigCaptX not running', 'danger');
            showMessage('Could not connect to Wacom SigCaptX service. Make sure the WacomSTUSigCaptX service is running. You can use the canvas fallback below.', 'warning');
        });

    // Capture signature
    btnCapture.addEventListener('click', function() {
        if (!WacomSTU.connected) {
            showMessage('SigCaptX service not connected. Please use the canvas fallback below.', 'warning');
            return;
        }

        showMessage('Please sign on the Wacom device...', 'info');
        btnCapture.disabled = true;

        WacomSTU.captureSignature({
            who: document.getElementById('client_name').value || 'Client',
            why: document.getElementById('purpose').value || 'Signature'
        })
        .then(function(result) {
            btnCapture.disabled = false;

            if (result && result.image) {
                // result.image should be base64 PNG
                var dataUrl = 'data:image/png;base64,' + result.image;
                displaySignature(dataUrl);
                showMessage('Signature captured successfully!', 'success');
            } else if (result && result.cancelled) {
                showMessage('Signature capture cancelled.', 'warning');
            } else {
                showMessage('No signature data received.', 'warning');
            }
        })
        .catch(function(error) {
            btnCapture.disabled = false;
            console.error('Capture error:', error);
            showMessage('Error capturing signature: ' + (error.message || error), 'danger');
        });
    });

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
