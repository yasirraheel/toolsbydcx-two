@php
    $sideBarLinks = json_decode($sidenav);
@endphp

<nav class="nav nav-pills flex-column gap-1" id="sidebar__menuWrapper">
    @foreach($sideBarLinks as $key => $data)
        @if(@$data->submenu)
            <div class="sidebar-dropdown-group">
                <a href="javascript:void(0)" class="nav-link d-flex align-items-center justify-content-between sidebar-dropdown-toggle {{ menuActive(@$data->menu_active, 3) }}">
                    <div class="d-flex align-items-center gap-2">
                        <i class="{{ @$data->icon }} fs-5"></i>
                        <span>{{ __(@$data->title) }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        @foreach(@$data->counters ?? [] as $counter)
                            @if($counter && isset($$counter) && $$counter > 0)
                                <span class="badge bg-warning text-dark px-1.5 py-0.5" style="font-size: 10px;">
                                    <i class="fas fa-exclamation"></i>
                                </span>
                                @break
                            @endif
                        @endforeach
                        <i class="las la-angle-down dropdown-arrow transition-all {{ menuActive(@$data->menu_active, 2) ? 'rotate-180' : '' }}" style="font-size: 12px;"></i>
                    </div>
                </a>
                <div class="sidebar-submenu-box ps-2 pt-1" style="{{ menuActive(@$data->menu_active, 2) ? 'display: block;' : 'display: none;' }}">
                    <div class="nav flex-column gap-1 ps-2 border-start border-secondary border-opacity-25 my-1">
                        @foreach($data->submenu as $menu)
                            @php
                                $submenuParams = null;
                                if (@$menu->params) {
                                    foreach ($menu->params as $submenuParamVal) {
                                        $submenuParams[] = array_values((array)$submenuParamVal)[0];
                                    }
                                }
                            @endphp
                            <a href="{{ route(@$menu->route_name, $submenuParams) }}" class="nav-link py-1 px-2 d-flex align-items-center justify-content-between {{ menuActive(@$menu->menu_active) }}" style="font-size: 0.85rem;">
                                <span><i class="las la-dot-circle me-1.5" style="font-size: 10px;"></i>{{ __($menu->title) }}</span>
                                @php $counter = @$menu->counter; @endphp
                                @if($counter && isset($$counter) && $$counter > 0)
                                    <span class="badge bg-info ms-auto" style="font-size: 10px;">{{ $$counter }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            @php
                $mainParams = null;
                if (@$data->params) {
                    foreach ($data->params as $paramVal) {
                        $mainParams[] = array_values((array)$paramVal)[0];
                    }
                }
            @endphp
            <a href="{{ route(@$data->route_name, $mainParams) }}" class="nav-link d-flex align-items-center justify-content-between {{ menuActive(@$data->menu_active) }}">
                <div class="d-flex align-items-center gap-2">
                    <i class="{{ $data->icon }} fs-5"></i>
                    <span>{{ __(@$data->title) }}</span>
                </div>
                @php $counter = @$data->counter; @endphp
                @if (@$$counter)
                    <span class="badge bg-info ms-auto" style="font-size: 10px;">{{ @$$counter }}</span>
                @endif
            </a>
        @endif
    @endforeach
</nav>
