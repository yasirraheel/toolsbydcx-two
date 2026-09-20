@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0">
                        <i class="las la-user-circle me-1"></i> @lang('Edit Profile')
                    </h5>
                    <a href="{{ route('user.change.password') }}" class="btn btn-sm btn-outline-light">
                        <i class="las la-key"></i> @lang('Change Password')
                    </a>
                </div>
                <div class="card-body p-4">
                    <form method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row align-items-center mb-4 pb-3 border-bottom">
                            <div class="col-auto">
                                <div class="position-relative" style="width: 90px; height: 90px;">
                                    @if ($user->image)
                                        <img class="showProfilePhoto rounded-circle border" src="{{ getImage(getFilePath('userProfile') . '/' . old('image', $user->image), getFileSize('userProfile')) }}" style="width: 90px; height: 90px; object-fit: cover;">
                                    @else
                                        <img class="showProfilePhoto rounded-circle border" src="{{ getImage($activeTemplateTrue . 'images/avatar.png') }}" style="width: 90px; height: 90px; object-fit: cover;">
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <h5 class="mb-1 text-dark">{{ $user->fullname ?: $user->username }}</h5>
                                <p class="text-muted mb-2">{{ $user->email }}</p>
                                <div>
                                    <label class="btn btn-sm btn-outline--primary mb-0" for="profile-image">
                                        <i class="las la-camera me-1"></i> @lang('Upload Photo')
                                    </label>
                                    <input class="d-none profilePicUpload" id="profile-image" name="image" type="file" accept="image/*">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 form-group mb-3">
                                <label class="fw-bold mb-1 required">@lang('First Name')</label>
                                <input class="form-control" name="firstname" type="text" value="{{ $user->firstname }}" required>
                            </div>
                            <div class="col-sm-6 form-group mb-3">
                                <label class="fw-bold mb-1 required">@lang('Last Name')</label>
                                <input class="form-control" name="lastname" type="text" value="{{ $user->lastname }}" required>
                            </div>
                            <div class="col-sm-6 form-group mb-3">
                                <label class="fw-bold mb-1">@lang('Address')</label>
                                <input class="form-control" name="address" type="text" value="{{ @$user->address }}">
                            </div>
                            <div class="col-sm-6 form-group mb-3">
                                <label class="fw-bold mb-1">@lang('State')</label>
                                <input class="form-control" name="state" type="text" value="{{ @$user->state }}">
                            </div>
                            <div class="col-sm-6 form-group mb-3">
                                <label class="fw-bold mb-1">@lang('Zip Code')</label>
                                <input class="form-control" name="zip" type="text" value="{{ @$user->zip }}">
                            </div>
                            <div class="col-sm-6 form-group mb-3">
                                <label class="fw-bold mb-1">@lang('City')</label>
                                <input class="form-control" name="city" type="text" value="{{ @$user->city }}">
                            </div>
                            <div class="col-sm-12 form-group mb-4">
                                <label class="fw-bold mb-1">@lang('Description')</label>
                                <textarea class="form-control" name="description" rows="3">{{ @$user->description }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button class="btn btn--primary btn-lg px-4" type="submit">
                                <i class="las la-save me-1"></i> @lang('Save Changes')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        'use strict';
        $(".profilePicUpload").on('change', function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('.showProfilePhoto').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
@endpush
