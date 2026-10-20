@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-none-30">
        {{-- Card 1: ToolsByDcx Social --}}
        <div class="col-xl-6 col-lg-6 mb-30">
            <div class="card b-radius--10">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">
                            <i class="las la-cookie-bite text--primary me-1"></i> @lang('ToolsByDcx Social')
                        </h5>
                        <span class="badge badge--primary">v{{ $socialVersion }}</span>
                    </div>

                    <form action="{{ route('admin.extension.upload.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="extension_type" value="social">

                        <div class="form-group">
                            <label>@lang('Social Extension File (.zip)')</label>
                            <div class="custom-file">
                                <input type="file" class="form-control" name="extension_zip" accept=".zip" required>
                            </div>
                            <small class="text-muted">@lang('Tools access extension (Netflix, Prime, Canva). Filename: toolsbydcx-ext.zip')</small>
                        </div>

                        <div class="form-group">
                            <label>@lang('Extension Version')</label>
                            <input type="text" class="form-control" name="min_extension_version" value="{{ $socialVersion }}" placeholder="e.g. 2.3.3" required>
                            <small class="text-muted">@lang('Auto-updated from manifest.json inside zip if left unchanged')</small>
                        </div>

                        <button type="submit" class="btn btn--primary w-100 h-45">
                            <i class="las la-cloud-upload-alt me-1"></i> @lang('Upload ToolsByDcx Social')
                        </button>
                    </form>

                    <div class="border-top my-4"></div>

                    <h6 class="card-title mb-3" style="font-size: 1rem;">
                        <i class="las la-link text--primary me-1"></i> @lang('Social Distribution Link')
                    </h6>

                    @if($socialExists)
                        <div class="form-group mb-0">
                            <label class="d-flex justify-content-between">
                                <span>@lang('Direct Download Link')</span>
                                <small class="text-muted">@lang('Updated'): {{ $socialLastModified }}</small>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $socialDownloadUrl }}" readonly id="downloadSocialLink">
                                <button class="btn btn--primary copy-btn" type="button" data-clipboard-target="#downloadSocialLink">
                                    <i class="las la-copy"></i> @lang('Copy')
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <p class="mb-0 small">@lang('No social extension uploaded yet.')</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 2: ToolsByDcx Flow --}}
        <div class="col-xl-6 col-lg-6 mb-30">
            <div class="card b-radius--10">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">
                            <i class="las la-robot text--primary me-1"></i> @lang('ToolsByDcx Flow')
                        </h5>
                        <span class="badge badge--primary">v{{ $flowVersion }}</span>
                    </div>

                    <form action="{{ route('admin.extension.upload.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="extension_type" value="flow">

                        <div class="form-group">
                            <label>@lang('Flow Extension File (.zip)')</label>
                            <div class="custom-file">
                                <input type="file" class="form-control" name="extension_zip" accept=".zip" required>
                            </div>
                            <small class="text-muted">@lang('Google Flow workspace automation. Filename: toolsbydcx-flow.zip')</small>
                        </div>

                        <div class="row">
                            <div class="col-sm-7">
                                <div class="form-group">
                                    <label>@lang('Extension Version')</label>
                                    <input type="text" class="form-control" name="min_extension_version" value="{{ $flowVersion }}" placeholder="e.g. 1.0.3" required>
                                    <small class="text-muted">@lang('Auto-updated from manifest.json')</small>
                                </div>
                            </div>
                            <div class="col-sm-5">
                                <div class="form-group">
                                    <label>@lang('Force Update')</label>
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

                    <h6 class="card-title mb-3" style="font-size: 1rem;">
                        <i class="las la-link text--primary me-1"></i> @lang('Flow Distribution Link')
                    </h6>

                    @if($flowExists)
                        <div class="form-group mb-0">
                            <label class="d-flex justify-content-between">
                                <span>@lang('Direct Download Link')</span>
                                <small class="text-muted">@lang('Updated'): {{ $flowLastModified }}</small>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $flowDownloadUrl }}" readonly id="downloadFlowLink">
                                <button class="btn btn--primary copy-btn" type="button" data-clipboard-target="#downloadFlowLink">
                                    <i class="las la-copy"></i> @lang('Copy')
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <p class="mb-0 small">@lang('No flow extension uploaded yet.')</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 3: Kiwi Browser --}}
        <div class="col-xl-6 col-lg-6 mb-30">
            <div class="card b-radius--10">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">
                            <i class="las la-mobile-alt text--primary me-1"></i> @lang('Kiwi Browser')
                        </h5>
                        <span class="badge badge--primary">v{{ $kiwiVersion }}</span>
                    </div>

                    <form id="kiwiUploadForm" action="{{ route('admin.extension.upload.chunk') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="extension_type" value="kiwi">
                        <div class="form-group">
                            <label>@lang('Kiwi Browser APK/XAPK')</label>
                            <input id="kiwiFile" type="file" class="form-control" name="extension_zip" accept=".apk,.xapk" required>
                            <small class="text-muted">@lang('Large files upload in small chunks so APKs around 250 MB do not exceed server request limits.')</small>
                        </div>
                        <div class="form-group">
                            <label>@lang('Browser Version')</label>
                            <input id="kiwiVersion" type="text" class="form-control" name="min_extension_version" value="{{ $kiwiHistory ? $kiwiHistory->version : '' }}" placeholder="e.g. 124.0.6367.179" required>
                        </div>
                        <div id="kiwiUploadProgress" class="progress mb-3" style="height: 8px; display: none;">
                            <div id="kiwiUploadBar" class="progress-bar bg--primary" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div id="kiwiUploadStatus" class="small text-muted mb-3" role="status"></div>
                        <button id="kiwiUploadButton" type="submit" class="btn btn--primary w-100 h-45">
                            <i class="las la-cloud-upload-alt me-1"></i> @lang('Upload Kiwi Browser')
                        </button>
                    </form>

                    <div class="border-top my-4"></div>
                    <h6 class="card-title mb-3" style="font-size: 1rem;">
                        <i class="las la-link text--primary me-1"></i> @lang('Kiwi Distribution Link')
                    </h6>
                    @if($kiwiExists)
                        <div class="form-group mb-0">
                            <label class="d-flex justify-content-between">
                                <span>@lang('Direct Download Link')</span>
                                <small class="text-muted">@lang('Updated'): {{ $kiwiLastModified }}</small>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $kiwiDownloadUrl }}" readonly id="downloadKiwiLink">
                                <button class="btn btn--primary copy-btn" type="button" data-clipboard-target="#downloadKiwiLink">
                                    <i class="las la-copy"></i> @lang('Copy')
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <p class="mb-0 small">@lang('No Kiwi Browser APK uploaded yet.')</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Version History Table --}}
        <div class="col-xl-12 col-md-12 mb-30">
            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="p-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="las la-history text--primary me-1"></i> @lang('Version History')</h5>
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
                                            @if(($history->type ?? 'social') === 'kiwi' || str_contains($history->filename ?? '', 'kiwi'))
                                                <span class="badge badge--success"><i class="las la-mobile-alt me-1"></i> Kiwi Browser</span>
                                            @elseif(($history->type ?? 'social') === 'flow' || str_contains($history->filename ?? '', 'flow'))
                                                <span class="badge badge--primary"><i class="las la-robot me-1"></i> ToolsByDcx Flow</span>
                                            @else
                                                <span class="badge badge--dark"><i class="las la-cookie-bite me-1"></i> ToolsByDcx Social</span>
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

    // Kiwi APKs can be about 250 MB. Upload 1 MB pieces so the request size
    // stays below common PHP and reverse-proxy limits, then assemble server-side.
    (function () {
        const form = document.getElementById('kiwiUploadForm');
        if (!form) return;
        const fileInput = document.getElementById('kiwiFile');
        const versionInput = document.getElementById('kiwiVersion');
        const button = document.getElementById('kiwiUploadButton');
        const progress = document.getElementById('kiwiUploadProgress');
        const bar = document.getElementById('kiwiUploadBar');
        const status = document.getElementById('kiwiUploadStatus');
        const csrf = form.querySelector('input[name="_token"]')?.value || '';
        const chunkUrl = @json(route('admin.extension.upload.chunk'));
        const completeUrl = @json(route('admin.extension.upload.complete'));
        const chunkSize = 1 * 1024 * 1024;

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            const file = fileInput.files[0];
            if (!file) return;
            const extension = file.name.split('.').pop().toLowerCase();
            if (!['apk', 'xapk'].includes(extension)) {
                status.textContent = 'Please select an APK or XAPK file.';
                return;
            }
            if (!versionInput.value.trim()) {
                status.textContent = 'Enter the Kiwi Browser version first.';
                return;
            }

            const uploadId = (crypto.randomUUID ? crypto.randomUUID() : Date.now() + '-' + Math.random().toString(36).slice(2));
            const totalChunks = Math.ceil(file.size / chunkSize);
            button.disabled = true;
            progress.style.display = 'flex';
            bar.style.width = '0%';
            status.textContent = `Uploading 0 of ${totalChunks} chunks...`;

            try {
                for (let index = 0; index < totalChunks; index++) {
                    const data = new FormData();
                    data.append('_token', csrf);
                    data.append('extension_type', 'kiwi');
                    data.append('upload_id', uploadId);
                    data.append('chunk_index', String(index));
                    data.append('total_chunks', String(totalChunks));
                    data.append('original_name', file.name);
                    data.append('chunk', file.slice(index * chunkSize, Math.min((index + 1) * chunkSize, file.size)), file.name + '.part');
                    const response = await fetch(chunkUrl, { method: 'POST', body: data, credentials: 'same-origin' });
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(result.message || `Chunk ${index + 1} failed.`);
                    const percent = Math.round(((index + 1) / totalChunks) * 100);
                    bar.style.width = percent + '%';
                    status.textContent = `Uploading ${index + 1} of ${totalChunks} chunks...`;
                }

                const complete = new FormData();
                complete.append('_token', csrf);
                complete.append('extension_type', 'kiwi');
                complete.append('upload_id', uploadId);
                complete.append('total_chunks', String(totalChunks));
                complete.append('original_name', file.name);
                complete.append('min_extension_version', versionInput.value.trim());
                const response = await fetch(completeUrl, { method: 'POST', body: complete, credentials: 'same-origin' });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(result.message || 'Could not complete the upload.');
                status.textContent = 'Upload complete. Refreshing...';
                window.location.reload();
            } catch (error) {
                status.textContent = error.message || 'Upload failed. Please try again.';
                button.disabled = false;
            }
        });
    })();
</script>
@endpush
