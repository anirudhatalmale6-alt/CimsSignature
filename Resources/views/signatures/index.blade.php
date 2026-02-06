@extends('smartdash::layouts.default')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Saved Signatures</h4>
                    <a href="{{ route('signatures.capture') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Capture New Signature
                    </a>
                </div>
                <div class="card-body">
                    <!-- Search -->
                    <form method="GET" action="{{ route('signatures.index') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search by name, reference, or ID..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fa fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Client Name</th>
                                    <th>Reference</th>
                                    <th>ID Number</th>
                                    <th>Signature</th>
                                    <th>Purpose</th>
                                    <th>Captured</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($signatures as $sig)
                                    <tr>
                                        <td><strong>{{ $sig->client_name }}</strong></td>
                                        <td>{{ $sig->client_reference ?: '-' }}</td>
                                        <td>{{ $sig->id_number ?: '-' }}</td>
                                        <td>
                                            <img src="{{ $sig->signature_data }}" alt="Signature" style="max-width: 150px; max-height: 50px; border: 1px solid #ddd;">
                                        </td>
                                        <td>{{ Str::limit($sig->purpose, 30) ?: '-' }}</td>
                                        <td>{{ $sig->captured_at->format('d M Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('signatures.show', $sig) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="{{ $sig->id }}" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <p class="text-muted mb-0">No signatures captured yet.</p>
                                            <a href="{{ route('signatures.capture') }}" class="btn btn-primary mt-2">Capture First Signature</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $signatures->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.btn-delete').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (!confirm('Are you sure you want to delete this signature?')) return;

        const id = this.dataset.id;
        fetch('/signatures/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting signature');
            }
        });
    });
});
</script>
@endpush
@endsection
