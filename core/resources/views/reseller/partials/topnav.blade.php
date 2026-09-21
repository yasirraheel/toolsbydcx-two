<nav class="navbar-wrapper bg--dark d-flex flex-wrap">
    <div class="navbar__left">
        <button type="button" class="res-sidebar-open-btn me-3"><i class="las la-bars"></i></button>
        <div class="d-none d-md-flex align-items-center">
            <span class="text-white fw-bold">
                <i class="las la-handshake text--primary me-1" style="font-size: 18px;"></i>
                @lang('Hi'), <span class="text--primary">{{ auth()->user()->fullname ?: auth()->user()->username }}</span>
            </span>
            <span class="badge badge--primary ms-2">@lang('Reseller Partner')</span>
        </div>
    </div>
    <div class="navbar__right">
        <ul class="navbar__action-list">

            {{-- Wallet Balance & Quick Recharge --}}
            <li class="d-flex align-items-center me-2">
                <a href="{{ route('reseller.deposit') }}" class="btn btn-sm btn--success d-inline-flex align-items-center gap-2 px-3 py-1" style="border-radius: 20px; font-weight: 600;">
                    <i class="las la-wallet"></i>
                    <span>{{ showAmount(auth()->user()->balance) }} {{ gs('cur_text') }}</span>
                    <span class="badge bg-white text-success ms-1" style="font-size: 11px;">+ @lang('Add')</span>
                </a>
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
                    <a href="{{ route('reseller.pricing') }}" class="dropdown-menu__item d-flex align-items-center px-3 py-2">
                        <i class="dropdown-menu__icon las la-tags"></i>
                        <span class="dropdown-menu__caption">@lang('Account Rates')</span>
                    </a>

                    <a href="{{ route('reseller.transactions') }}" class="dropdown-menu__item d-flex align-items-center px-3 py-2">
                        <i class="dropdown-menu__icon las la-history"></i>
                        <span class="dropdown-menu__caption">@lang('Transactions')</span>
                    </a>

                    <a href="{{ route('reseller.deposit.history') }}" class="dropdown-menu__item d-flex align-items-center px-3 py-2">
                        <i class="dropdown-menu__icon las la-file-invoice-dollar"></i>
                        <span class="dropdown-menu__caption">@lang('Deposit History')</span>
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
