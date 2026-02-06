@extends('smartdash::layouts.default')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-center">
                    <h4 class="card-title mb-0">Quick Signature Capture</h4>
                </div>
                <div class="card-body">
                    <div id="statusMessage" class="alert d-none"></div>

                    <form id="quickForm">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="client_name" name="client_name" required autofocus>
                            </div>
                            <div class="col-md-6">
                                <label for="purpose" class="form-label">Purpose</label>
                                <input type="text" class="form-control form-control-lg" id="purpose" name="purpose" placeholder="Optional">
                            </div>
                        </div>

                        <!-- Signature Area -->
                        <div class="text-center my-4">
                            <div id="signatureBox" style="border: 3px dashed #007bff; min-height: 180px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                                <div id="signaturePlaceholder">
                                    <i class="fa fa-pen fa-4x text-primary mb-3"></i>
                                    <h5 class="text-muted">Click button below to capture signature</h5>
                                </div>
                                <img id="signatureImage" src="" alt="Signature" style="max-width: 90%; max-height: 160px; display: none;">
                            </div>
                            <input type="hidden" id="signature_data" name="signature_data">
                        </div>

                        <!-- Canvas Fallback -->
                        <div class="mb-4">
                            <p class="text-center text-muted">Sign with mouse/touch:</p>
                            <canvas id="signatureCanvas" width="600" height="150" style="border: 2px solid #007bff; background: #fff; cursor: crosshair; display: block; margin: 0 auto; border-radius: 5px;"></canvas>
                        </div>

                        <!-- Buttons -->
                        <div class="text-center">
                            <button type="button" id="btnCapture" class="btn btn-primary btn-lg mx-2">
                                <i class="fa fa-tablet"></i> Capture from Wacom
                            </button>
                            <button type="button" id="btnUseCanvas" class="btn btn-info btn-lg mx-2">
                                <i class="fa fa-check"></i> Use Canvas Signature
                            </button>
                            <button type="button" id="btnClear" class="btn btn-warning btn-lg mx-2">
                                <i class="fa fa-eraser"></i> Clear
                            </button>
                            <button type="submit" id="btnSave" class="btn btn-success btn-lg mx-2" disabled>
                                <i class="fa fa-save"></i> Save
                            </button>
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
    const signatureImage = document.getElementById('signatureImage');
    const signaturePlaceholder = document.getElementById('signaturePlaceholder');
    const signatureData = document.getElementById('signature_data');
    const btnSave = document.getElementById('btnSave');
    const statusMessage = document.getElementById('statusMessage');

    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;

    // Canvas setup
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        if (e.touches) {
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

    canvas.addEventListener('mousedown', (e) => { isDrawing = true; const p = getPos(e); lastX = p.x; lastY = p.y; });
    canvas.addEventListener('mousemove', (e) => {
        if (!isDrawing) return;
        const p = getPos(e);
        ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(p.x, p.y); ctx.stroke();
        lastX = p.x; lastY = p.y;
    });
    canvas.addEventListener('mouseup', () => isDrawing = false);
    canvas.addEventListener('mouseout', () => isDrawing = false);
    canvas.addEventListener('touchstart', (e) => { e.preventDefault(); isDrawing = true; const p = getPos(e); lastX = p.x; lastY = p.y; });
    canvas.addEventListener('touchmove', (e) => {
        e.preventDefault();
        if (!isDrawing) return;
        const p = getPos(e);
        ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(p.x, p.y); ctx.stroke();
        lastX = p.x; lastY = p.y;
    });
    canvas.addEventListener('touchend', () => isDrawing = false);

    function showMessage(msg, type) {
        statusMessage.className = 'alert alert-' + type;
        statusMessage.textContent = msg;
        statusMessage.classList.remove('d-none');
    }

    function displaySignature(dataUrl) {
        signatureData.value = dataUrl;
        signatureImage.src = dataUrl;
        signatureImage.style.display = 'block';
        signaturePlaceholder.style.display = 'none';
        btnSave.disabled = false;
    }

    document.getElementById('btnUseCanvas').addEventListener('click', function() {
        displaySignature(canvas.toDataURL('image/png'));
        showMessage('Canvas signature ready. Enter client name and click Save.', 'info');
    });

    document.getElementById('btnClear').addEventListener('click', function() {
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        signatureData.value = '';
        signatureImage.style.display = 'none';
        signaturePlaceholder.style.display = 'block';
        btnSave.disabled = true;
    });

    document.getElementById('btnCapture').addEventListener('click', function() {
        showMessage('Attempting Wacom capture... If nothing happens, SigCaptX may not be running. Use canvas instead.', 'warning');
        // SigCaptX capture would go here if available
    });

    document.getElementById('quickForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!signatureData.value) { showMessage('Please capture a signature first', 'warning'); return; }
        if (!document.getElementById('client_name').value) { showMessage('Please enter client name', 'warning'); return; }

        fetch('{{ route("signatures.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                client_name: document.getElementById('client_name').value,
                purpose: document.getElementById('purpose').value,
                signature_data: signatureData.value
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showMessage('Signature saved!', 'success');
                setTimeout(() => window.location.href = '{{ route("signatures.index") }}', 1000);
            } else {
                showMessage('Error: ' + (data.message || 'Unknown'), 'danger');
            }
        })
        .catch(err => showMessage('Error: ' + err.message, 'danger'));
    });
});
</script>
@endpush
@endsection
