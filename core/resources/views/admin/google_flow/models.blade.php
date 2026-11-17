@extends('admin.layouts.app')

@section('panel')
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3"><i class="las la-plus-circle text--primary"></i> Add Flow Guard model</h5>
                    <p class="text-muted small">These names are used only by the separate Flow Guard extension. Enter the native Google Flow model name exactly as shown in Flow.</p>
                    <form method="POST" action="{{ route('admin.google-flow.models.store') }}">
                        @csrf
                        <label class="form-label">Model name</label>
                        <input type="text" name="name" class="form-control" maxlength="191" placeholder="e.g. Omni 1.2 Flash" required>
                        <button class="btn btn--primary w-100 mt-3" type="submit"><i class="las la-save"></i> Add model</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table--light style--two table align-middle mb-0">
                            <thead>
                                <tr><th>Model</th><th>Status</th><th>Update</th><th>Delete</th></tr>
                            </thead>
                            <tbody>
                                @forelse($models as $model)
                                    <tr>
                                        <td class="fw-bold">{{ $model->name }}</td>
                                        <td>
                                            @if($model->enabled)
                                                <span class="badge badge--success">Enabled</span>
                                            @else
                                                <span class="badge badge--danger">Disabled</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.google-flow.models.update', $model->id) }}" class="d-flex flex-wrap gap-2 align-items-center">
                                                @csrf
                                                <input type="text" name="name" class="form-control form-control-sm" maxlength="191" value="{{ $model->name }}" required style="min-width:210px">
                                                <label class="form-check mb-0"><input type="checkbox" class="form-check-input" name="enabled" value="1" @checked($model->enabled)> <span class="form-check-label">Enabled</span></label>
                                                <button type="submit" class="btn btn--primary btn-sm"><i class="las la-save"></i></button>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.google-flow.models.delete', $model->id) }}" onsubmit="return confirm('Delete this Flow Guard model?')">
                                                @csrf
                                                <button type="submit" class="btn btn-outline--danger btn-sm"><i class="las la-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="100%" class="text-center text-muted py-4">No Flow Guard models configured.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($models->hasPages())
                    <div class="card-footer py-4">{{ paginateLinks($models) }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.google-flow.index') }}" class="btn btn-outline--dark"><i class="las la-arrow-left"></i> Accounts</a>
@endpush
