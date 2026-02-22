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
                                        <h5 class="mb-0">Sign Below</h5>
                                    </div>
                                    <div class="card-body text-center">
                                        <!-- Signature Canvas -->
                                        <div style="position: relative; display: inline-block;">
                                            <canvas id="signatureCanvas" width="600" height="200" style="border: 2px solid #333; background: #fff; cursor: crosshair; touch-action: none;"></canvas>
                                            <div id="signaturePlaceholder" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none; text-align: center;">
                                                <i class="fa fa-pen fa-2x text-muted mb-2" style="display:block;"></i>
                                                <span class="text-muted">Sign here using your Wacom pen or mouse</span>
                                            </div>
                                        </div>

                                        <!-- Hidden field to store signature data -->
                                        <input type="hidden" id="signature_data" name="signature_data">

                                        <!-- Buttons -->
                                        <div class="mt-3 mb-3">
                                            <button type="button" id="btnClear" class="btn btn-warning btn-lg">
                                                <i class="fa fa-eraser"></i> Clear
                                            </button>
                                            <button type="submit" id="btnSave" class="btn btn-success btn-lg" disabled>
                                                <i class="fa fa-save"></i> Save Signature
                                            </button>
                                        </div>

                                        <div class="text-muted small">
                                            <p class="mb-0">Use your Wacom pen directly on this canvas to sign, or use your mouse. The Wacom pen works as a pointer device.</p>
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
    const canvas = document.getElementById('signatureCanvas');
    const ctx = canvas.getContext('2d');
    const signatureData = document.getElementById('signature_data');
    const signatureForm = document.getElementById('signatureForm');
    const statusMessage = document.getElementById('statusMessage');
    const placeholder = document.getElementById('signaturePlaceholder');
    const btnClear = document.getElementById('btnClear');
    const btnSave = document.getElementById('btnSave');

    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;
    let hasSignature = false;

    // Setup canvas
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    function showMessage(message, type) {
        statusMessage.className = 'alert alert-' + type;
        statusMessage.textContent = message;
        statusMessage.classList.remove('d-none');
        if (type === 'success') {
            setTimeout(function() { statusMessage.classList.add('d-none'); }, 5000);
        }
    }

    function getPos(e) {
        var rect = canvas.getBoundingClientRect();
        var scaleX = canvas.width / rect.width;
        var scaleY = canvas.height / rect.height;

        if (e.touches && e.touches.length > 0) {
            return {
                x: (e.touches[0].clientX - rect.left) * scaleX,
                y: (e.touches[0].clientY - rect.top) * scaleY
            };
        }
        return {
            x: (e.clientX - rect.left) * scaleX,
            y: (e.clientY - rect.top) * scaleY
        };
    }

    function startDraw(e) {
        e.preventDefault();
        isDrawing = true;
        var pos = getPos(e);
        lastX = pos.x;
        lastY = pos.y;

        if (!hasSignature) {
            hasSignature = true;
            placeholder.style.display = 'none';
            btnSave.disabled = false;
        }
    }

    function draw(e) {
        e.preventDefault();
        if (!isDrawing) return;
        var pos = getPos(e);

        // Apply pressure if available (Wacom pen provides pressure data via PointerEvent)
        if (e.pressure && e.pressure > 0) {
            ctx.lineWidth = Math.max(1, e.pressure * 4);
        }

        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        lastX = pos.x;
        lastY = pos.y;
    }

    function stopDraw(e) {
        if (e) e.preventDefault();
        isDrawing = false;
    }

    // Pointer events (best for Wacom pen - supports pressure)
    canvas.addEventListener('pointerdown', startDraw);
    canvas.addEventListener('pointermove', draw);
    canvas.addEventListener('pointerup', stopDraw);
    canvas.addEventListener('pointerout', stopDraw);

    // Prevent touch scrolling on canvas
    canvas.addEventListener('touchstart', function(e) { e.preventDefault(); }, { passive: false });
    canvas.addEventListener('touchmove', function(e) { e.preventDefault(); }, { passive: false });

    // Clear button
    btnClear.addEventListener('click', function() {
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        hasSignature = false;
        placeholder.style.display = 'block';
        btnSave.disabled = true;
        signatureData.value = '';
    });

    // Save signature
    signatureForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!hasSignature) {
            showMessage('Please sign in the box above first.', 'warning');
            return;
        }

        if (!document.getElementById('client_name').value) {
            showMessage('Please enter client name.', 'warning');
            return;
        }

        // Get signature as PNG data URL
        signatureData.value = canvas.toDataURL('image/png');

        var formData = {
            client_name: document.getElementById('client_name').value,
            client_reference: document.getElementById('client_reference').value,
            id_number: document.getElementById('id_number').value,
            purpose: document.getElementById('purpose').value,
            document_reference: document.getElementById('document_reference').value,
            signature_data: signatureData.value,
            _token: '{{ csrf_token() }}'
        };

        btnSave.disabled = true;
        btnSave.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';

        fetch('{{ route("signatures.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(formData)
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showMessage('Signature saved successfully!', 'success');
                setTimeout(function() {
                    window.location.href = '{{ route("signatures.index") }}';
                }, 1500);
            } else {
                showMessage('Error saving signature: ' + (data.message || 'Unknown error'), 'danger');
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fa fa-save"></i> Save Signature';
            }
        })
        .catch(function(error) {
            showMessage('Error saving signature: ' + error.message, 'danger');
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fa fa-save"></i> Save Signature';
        });
    });
});
</script>
@endpush
@endsection
