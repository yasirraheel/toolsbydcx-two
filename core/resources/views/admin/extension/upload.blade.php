@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-none-30">
        <div class="col-xl-12 col-md-12 mb-30">
            <div class="card b-radius--10 ">
                <div class="card-body">
                    <h5 class="card-title mb-4">@lang('Upload Extension (.zip)')</h5>

                    <form action="{{ route('admin.extension.upload.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Extension File (.zip)')</label>
                                    <div class="custom-file">
                                        <input type="file" class="form-control" name="extension_zip" id="customFile" accept=".zip">
                                    </div>
                                    <small class="text-muted">@lang('Version is automatically read from manifest.json inside the zip')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Minimum Required Version')</label>
                                    <input type="text" class="form-control" name="min_extension_version" value="{{ $minVersion }}" placeholder="e.g. 1.0.0" required>
                                    <small class="text-muted">@lang('Auto-updated from manifest version if left default')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Strict Force Update Mode')</label>
                                    <div class="form-check form-switch mt-2">
                                        <input type="checkbox" class="form-check-input" name="force_extension_update" value="1" id="forceUpdateSwitch" {{ $forceUpdate ? 'checked' : '' }}>
                                        <label class="form-check-label ms-2" for="forceUpdateSwitch">
                                            <strong>{{ $forceUpdate ? __('STRICT FORCE UPDATE') : __('SOFT REMINDER (6-Hour Snooze Allowed)') }}</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">@lang('Strict Mode blocks usage until updated.')</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn--primary w-100 h-45">@lang('Save & Upload Extension')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card b-radius--10 mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">@lang('Distribution Link')</h5>
                    
                    @if($extensionExists)
                        <div class="alert alert-success d-flex flex-column gap-2" style="background: rgba(16, 185, 129, 0.12) !important; border: 1px solid rgba(16, 185, 129, 0.35) !important; color: #cbd5e1 !important; border-radius: 8px; padding: 18px 20px;">
                            <h5 class="alert-heading text-white mb-1"><i class="las la-check-circle text-success me-1"></i> @lang('Extension is currently available for download!')</h5>
                            <div style="font-size: 13.5px; color: #94a3b8;">@lang('Current Active Version'): <strong class="text-white">v{{ $minVersion }}</strong> — @lang('Last updated'): <strong class="text-white">{{ $lastModified }}</strong></div>
                            <div class="border-top border-secondary border-opacity-25 my-1"></div>
                            <p class="mb-0 small" style="color: #cbd5e1;">@lang('Share the link below with your users. Clicking it will automatically download the extension.')</p>
                        </div>

                        <div class="form-group">
                            <label>@lang('Direct Download Link')</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $downloadUrl }}" readonly id="downloadLink">
                                <button class="btn btn--primary copy-btn" type="button" data-clipboard-target="#downloadLink"><i class="las la-copy"></i> @lang('Copy')</button>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <h4 class="alert-heading">@lang('No extension uploaded yet.')</h4>
                            <p>@lang('Please upload a .zip file above to generate the distribution link.')</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Automatic Version History Table --}}
            <div class="card b-radius--10 mt-4">
                <div class="card-body p-0">
                    <div class="p-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="las la-history text-primary me-1"></i> @lang('Version History')</h5>
                        <span class="badge badge--primary">{{ count($histories ?? []) }} @lang('Releases')</span>
                    </div>
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
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
                                            @if($extensionExists)
                                                <div class="py-3">
                                                    <span class="badge badge--success">v{{ $minVersion }}</span> — <code>toolsbydcx-flow-v{{ $minVersion }}.zip</code> (@lang('Current distribution'))
                                                </div>
                                            @else
                                                @lang('No version history recorded yet.')
                                            @endif
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
            var copyText = document.getElementById("downloadLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            notify('success', 'Copied: ' + copyText.value);
        });
    })(jQuery);
</script>
@endpush
