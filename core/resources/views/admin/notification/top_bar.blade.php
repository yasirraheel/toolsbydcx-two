<ul class="nav nav-tabs mb-4 topTap breadcrumb-nav border-0 d-flex gap-2" role="tablist">
    <li class="nav-item" role="presentation">
        <a href="{{ route('admin.setting.notification.global.email') }}" class="nav-link {{ menuActive(['admin.setting.notification.global.email','admin.setting.notification.global.sms','admin.setting.notification.global.push'], 2) ? 'active' : '' }}" type="button">
            <i class="las la-globe me-1"></i> @lang('Global Template')
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a href="{{ route('admin.setting.notification.email') }}" class="nav-link {{ menuActive('admin.setting.notification.email', 2) ? 'active' : '' }}" type="button">
            <i class="las la-envelope me-1"></i> @lang('Email Setting')
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a href="{{ route('admin.setting.notification.sms') }}" class="nav-link {{ menuActive('admin.setting.notification.sms', 2) ? 'active' : '' }}" type="button">
            <i class="las la-sms me-1"></i> @lang('SMS Setting')
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a href="{{ route('admin.setting.notification.push') }}" class="nav-link {{ menuActive('admin.setting.notification.push', 2) ? 'active' : '' }}" type="button">
            <i class="las la-bell me-1"></i> @lang('Push Notification Setting')
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a href="{{ route('admin.setting.notification.templates') }}" class="nav-link {{ menuActive(['admin.setting.notification.templates','admin.setting.notification.template.edit'], 2) ? 'active' : '' }}" type="button">
            <i class="las la-list me-1"></i> @lang('Notification Templates')
        </a>
    </li>
</ul>
