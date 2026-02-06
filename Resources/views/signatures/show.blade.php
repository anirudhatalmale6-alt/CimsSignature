@extends('smartdash::layouts.default')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Signature Details</h4>
                    <a href="{{ route('signatures.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <!-- Signature Display -->
                    <div class="text-center mb-4 p-4 bg-light rounded">
                        <img src="{{ $signature->signature_data }}" alt="Signature" style="max-width: 100%; max-height: 200px; border: 1px solid #ddd; background: #fff; padding: 10px;">
                    </div>

                    <!-- Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Client Name:</th>
                                    <td><strong>{{ $signature->client_name }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Client Reference:</th>
                                    <td>{{ $signature->client_reference ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>ID Number:</th>
                                    <td>{{ $signature->id_number ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Purpose:</th>
                                    <td>{{ $signature->purpose ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Document Ref:</th>
                                    <td>{{ $signature->document_reference ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Captured At:</th>
                                    <td>{{ $signature->captured_at->format('d M Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Device:</th>
                                    <td>{{ $signature->device_info ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>IP Address:</th>
                                    <td>{{ $signature->ip_address ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Integrity Check -->
                    <div class="mt-3">
                        <h6>Signature Integrity</h6>
                        @if($signature->verifyIntegrity())
                            <span class="badge bg-success"><i class="fa fa-check"></i> Verified - Signature has not been modified</span>
                        @else
                            <span class="badge bg-danger"><i class="fa fa-times"></i> Integrity check failed or no hash available</span>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 pt-3 border-top">
                        <a href="{{ route('signatures.capture') }}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Capture New Signature
                        </a>
                        <button type="button" class="btn btn-info" onclick="copySignatureData()">
                            <i class="fa fa-copy"></i> Copy Base64 Data
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Use This Signature</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">To use this signature in other modules, you can:</p>
                    <ol class="small">
                        <li>Call the API: <code>GET /signatures/{{ $signature->id }}/image</code></li>
                        <li>Or get latest for client: <code>GET /signatures/client/{{ $signature->client_reference ?: 'REFERENCE' }}</code></li>
                    </ol>
                    <hr>
                    <p class="small"><strong>Signature ID:</strong> {{ $signature->id }}</p>
                    @if($signature->client_reference)
                        <p class="small"><strong>Client Ref:</strong> {{ $signature->client_reference }}</p>
                    @endif
                </div>
            </div>

            <!-- Delete Card -->
            <div class="card border-danger mt-3">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Danger Zone</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('signatures.destroy', $signature) }}" method="POST" onsubmit="return confirm('Delete this signature permanently?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fa fa-trash"></i> Delete Signature
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copySignatureData() {
    const data = @json($signature->signature_data);
    navigator.clipboard.writeText(data).then(function() {
        alert('Signature data copied to clipboard!');
    });
}
</script>
@endpush
@endsection
