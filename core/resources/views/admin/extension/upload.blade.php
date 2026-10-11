@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4 mb-30">
        {{-- Card 1: ToolsByDcx Social Extension --}}
        <div class="col-xl-6 col-lg-12">
            <div class="card b-radius--10 h-100 border">
                <div class="card-header bg--dark d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar--sm bg--info text-white rounded d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="las la-cookie-bite fs-4"></i>
                        </div>
                        <div>
                            <h5 class="card-title text-white mb-0">@lang('ToolsByDcx Social')</h5>
                            <small class="text-white-50">@lang('Cookie-based Accounts (Netflix, Prime, Canva, etc.)')</small>
                        </div>
                    </div>
                    <span class="badge badge--info px-2 py-1">@lang('v'){{ $socialVersion }}</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.extension.upload.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="extension_type" value="social">

                        <div class="form-group mb-3">
                            <label class="fw-bold">@lang('Social Extension File (.zip)')</label>
                            <div class="custom-file">
                                <input type="file" class="form-control" name="extension_zip" id="socialFile" accept=".zip" required>
                            </div>
                            <small class="text-muted">@lang('Upload zip for cookie-based logins. Filename saved as toolsbydcx-ext.zip')</small>
                        </div>

                        <div class="form-group mb-3">
                            <label class="fw-bold">@lang('Extension Version')</label>
                            <input type="text" class="form-control" name="min_extension_version" value="{{ $socialVersion }}" placeholder="e.g. 2.3.3">
                            <small class="text-muted">@lang('Auto-read from manifest.json inside the zip if left default')</small>
                        </div>

                        <button type="submit" class="btn btn--info w-100 h-45">
                            <i class="las la-cloud-upload-alt me-1"></i> @lang('Upload ToolsByDcx Social')
                        </button>
                    </form>

                    <div class="border-top my-4"></div>

                    <h6 class="mb-3 text-white"><i class="las la-link text--info me-1"></i> @lang('Social Distribution Link')</h6>
                    @if($socialExists)
                        <div class="alert alert-success d-flex flex-column gap-1 mb-3" style="background: rgba(16, 185, 129, 0.12) !important; border: 1px solid rgba(16, 185, 129, 0.35) !important; border-radius: 8px; padding: 12px 16px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-white"><i class="las la-check-circle text-success me-1"></i> @lang('Available for download')</strong>
                                <span class="badge badge--success">v{{ $socialVersion }}</span>
                            </div>
                            <div class="small" style="color: #94a3b8;">@lang('Last updated'): <span class="text-white">{{ $socialLastModified }}</span></div>
                        </div>

                        <div class="form-group mb-2">
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $socialDownloadUrl }}" readonly id="downloadSocialLink">
                                <button class="btn btn--info copy-btn" type="button" data-clipboard-target="#downloadSocialLink">
                                    <i class="las la-copy"></i> @lang('Copy')
                                </button>
                                <a href="{{ $socialDownloadUrl }}" class="btn btn--outline-info" download>
                                    <i class="las la-download"></i>
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <p class="mb-0 small">@lang('No social extension uploaded yet. Upload a .zip file above.')</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 2: ToolsByDcx Flow Extension --}}
        <div class="col-xl-6 col-lg-12">
            <div class="card b-radius--10 h-100 border">
                <div class="card-header bg--dark d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar--sm bg--primary text-white rounded d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="las la-robot fs-4"></i>
                        </div>
                        <div>
                            <h5 class="card-title text-white mb-0">@lang('ToolsByDcx Flow')</h5>
                            <small class="text-white-50">@lang('Google Flow Workspace Automation Extension')</small>
                        </div>
                    </div>
                    <span class="badge badge--primary px-2 py-1">@lang('v'){{ $flowVersion }}</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.extension.upload.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="extension_type" value="flow">

                        <div class="form-group mb-3">
                            <label class="fw-bold">@lang('Flow Extension File (.zip)')</label>
                            <div class="custom-file">
                                <input type="file" class="form-control" name="extension_zip" id="flowFile" accept=".zip" required>
                            </div>
                            <small class="text-muted">@lang('Upload zip for Google Flow automation. Filename saved as toolsbydcx-flow.zip')</small>
                        </div>

                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group mb-3">
                                    <label class="fw-bold">@lang('Extension Version')</label>
                                    <input type="text" class="form-control" name="min_extension_version" value="{{ $flowVersion }}" placeholder="e.g. 1.0.3">
                                    <small class="text-muted">@lang('Auto-read from manifest.json inside the zip')</small>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-3">
                                    <label class="fw-bold">@lang('Force Update')</label>
                                    <div class="form-check form-switch mt-2">
                                        <input type="checkbox" class="form-check-input" name="force_extension_update" value="1" id="forceUpdateSwitch" {{ $forceUpdate ? 'checked' : '' }}>
                                        <label class="form-check-label ms-1" for="forceUpdateSwitch">
                                            <small class="fw-bold">{{ $forceUpdate ? __('STRICT') : __('SOFT') }}</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn--primary w-100 h-45">
                            <i class="las la-cloud-upload-alt me-1"></i> @lang('Upload ToolsByDcx Flow')
                        </button>
                    </form>

                    <div class="border-top my-4"></div>

                    <h6 class="mb-3 text-white"><i class="las la-link text--primary me-1"></i> @lang('Flow Distribution Link')</h6>
                    @if($flowExists)
                        <div class="alert alert-success d-flex flex-column gap-1 mb-3" style="background: rgba(16, 185, 129, 0.12) !important; border: 1px solid rgba(16, 185, 129, 0.35) !important; border-radius: 8px; padding: 12px 16px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-white"><i class="las la-check-circle text-success me-1"></i> @lang('Available for download')</strong>
                                <span class="badge badge--primary">v{{ $flowVersion }}</span>
                            </div>
                            <div class="small" style="color: #94a3b8;">@lang('Last updated'): <span class="text-white">{{ $flowLastModified }}</span></div>
                        </div>

                        <div class="form-group mb-2">
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $flowDownloadUrl }}" readonly id="downloadFlowLink">
                                <button class="btn btn--primary copy-btn" type="button" data-clipboard-target="#downloadFlowLink">
                                    <i class="las la-copy"></i> @lang('Copy')
                                </button>
                                <a href="{{ $flowDownloadUrl }}" class="btn btn--outline-primary" download>
                                    <i class="las la-download"></i>
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <p class="mb-0 small">@lang('No flow extension uploaded yet. Upload a .zip file above.')</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Automatic Version History Table --}}
        <div class="col-12 mt-4">
            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="p-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="las la-history text--primary me-1"></i> @lang('Extension Release History')</h5>
                        <span class="badge badge--primary">{{ count($histories ?? []) }} @lang('Releases')</span>
                    </div>
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Extension')</th>
                                    <th>@lang('Version')</th>
                                    <th>@lang('Filename')</th>
                                    <th>@lang('File Size')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Uploaded Date')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($histories ?? [] as $history)
                                    <tr>
                                        <td>
                                            @if(($history->type ?? 'social') === 'flow' || str_contains($history->filename ?? '', 'flow'))
                                                <span class="badge badge--primary"><i class="las la-robot me-1"></i> ToolsByDcx Flow</span>
                                            @else
                                                <span class="badge badge--info"><i class="las la-cookie-bite me-1"></i> ToolsByDcx Social</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge--dark" style="font-size: 0.95rem; font-weight: 600;">v{{ $history->version }}</span>
                                        </td>
                                        <td><code>{{ $history->filename }}</code></td>
                                        <td>{{ $history->file_size ?: 'N/A' }}</td>
                                        <td>
                                            @if($history->is_current)
                                                <span class="badge badge--success"><i class="las la-check"></i> @lang('Current Active')</span>
                                            @else
                                                <span class="badge badge--secondary">@lang('Archived')</span>
                                            @endif
                                        </td>
                                        <td>{{ showDateTime($history->created_at) }}</td>
                                        <td>
                                            <a href="{{ asset($history->file_path) }}" class="btn btn-sm btn-outline--primary" download>
                                                <i class="las la-download"></i> @lang('Download')
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">
                                            @lang('No version history recorded yet.')
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    (function($){
        "use strict";
        $('.copy-btn').on('click', function () {
            var target = $(this).data('clipboard-target');
            var copyText = $(target)[0];
            if (copyText) {
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(copyText.value).then(function() {
                        notify('success', 'Copied: ' + copyText.value);
                    });
                } else {
                    document.execCommand("copy");
                    notify('success', 'Copied: ' + copyText.value);
                }
            }
        });
    })(jQuery);
</script>
@endpush
