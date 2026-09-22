<nav class="navbar-wrapper d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
        <a class="navbar-brand me-1" href="{{ route('user.home') }}">
            <img src="{{ siteLogo() }}" alt="{{ gs('site_name') }}" style="max-height: 38px;">
        </a>
        <div class="d-none d-md-flex align-items-center">
            <span class="text-white fw-bold">
                <i class="las la-user-circle text--primary me-1" style="font-size: 18px;"></i>
                @lang('Hi'), <span class="text--primary">{{ auth()->user()->fullname ?: auth()->user()->username }}</span>
            </span>
            @if(auth()->user()->is_trial)
                <span class="badge badge--warning ms-2">@lang('Trial')</span>
            @elseif(auth()->user()->plan)
                <span class="badge badge--success ms-2">{{ __(auth()->user()->plan->name) }}</span>
            @endif
        </div>
    </div>
    <div class="navbar__right">
        <ul class="navbar__action-list">

            {{-- Visit Website --}}
            <li>
                <button type="button" class="primary--layer" data-bs-toggle="tooltip" data-bs-placement="bottom" title="@lang('Visit Website')">
                    <a href="{{ route('home') }}" target="_blank"><i class="las la-globe"></i></a>
                </button>
            </li>

            {{-- User Profile Dropdown --}}
            <li class="dropdown d-flex profile-dropdown">
                <button type="button" data-bs-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
                    <span class="navbar-user">
                        <span class="navbar-user__thumb">
                            @if(auth()->user()->image)
                                <img src="{{ getImage(getFilePath('userProfile') . '/' . auth()->user()->image, getFileSize('userProfile')) }}" alt="user">
                            @else
                                <img src="{{ getImage($activeTemplateTrue . 'images/avatar.png') }}" alt="user">
                            @endif
                        </span>
                        <span class="navbar-user__info">
                            <span class="navbar-user__name">{{ auth()->user()->username }}</span>
                        </span>
                        <span class="icon"><i class="las la-chevron-circle-down"></i></span>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu--sm p-0 border-0 box--shadow1 dropdown-menu-right">
                    <a href="{{ route('user.profile.setting') }}" class="dropdown-menu__item d-flex align-items-center px-3 py-2">
                        <i class="dropdown-menu__icon las la-user-circle"></i>
                        <span class="dropdown-menu__caption">@lang('Profile Details')</span>
                    </a>

                    <a href="{{ route('user.change.password') }}" class="dropdown-menu__item d-flex align-items-center px-3 py-2">
                        <i class="dropdown-menu__icon las la-key"></i>
                        <span class="dropdown-menu__caption">@lang('Change Password')</span>
                    </a>

                    <a href="{{ route('user.logout') }}" class="dropdown-menu__item d-flex align-items-center px-3 py-2 text-danger">
                        <i class="dropdown-menu__icon las la-sign-out-alt text-danger"></i>
                        <span class="dropdown-menu__caption text-danger">@lang('Logout')</span>
                    </a>
                </div>
            </li>
        </ul>
    </div>
</nav>
