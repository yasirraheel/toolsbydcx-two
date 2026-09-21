<ul class="nav nav-tabs mb-4 topTap breadcrumb-nav border-0 d-flex gap-2" role="tablist">
    <li class="nav-item" role="presentation">
        <a href="{{ route('admin.gateway.automatic.index') }}" class="nav-link {{ menuActive(['admin.gateway.automatic.index','admin.gateway.automatic.edit'], 2) ? 'active' : '' }}" type="button">
            <i class="las la-credit-card me-1"></i> @lang('Automatic Gateway')
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a href="{{ route('admin.gateway.manual.index') }}" class="nav-link {{ menuActive(['admin.gateway.manual.index','admin.gateway.manual.edit','admin.gateway.manual.create'], 2) ? 'active' : '' }}" type="button">
            <i class="las la-wallet me-1"></i> @lang('Manual Gateway')
        </a>
    </li>
</ul>
