@php

$currentRoute = request()->route()->getName();

// Define route groups for active states
$isDashboard = Str::is('home*', $currentRoute);
$isMasterSetting = Str::is(['country.*', 'bank.*', 'user.*', 'purpose.*', 'bank_setting.*'], $currentRoute);
$isBankPhoneNumber = Str::is('bank_phone_number.*', $currentRoute);
$isTransaction = Str::is('transaction.*', $currentRoute);
$isProviderSettlement = Str::is('provider_settlement.*', $currentRoute);
$isBankSnapshot = Str::is('bank_snapshot.*', $currentRoute);


@endphp
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('home') }}" class="app-brand-link">
            <span class="app-brand-logo demo d-flex align-items-center gap-2" style="width: auto; height: auto;">
                <img src="{{ asset('assets/img/branding/xcord-logo.png') }}" alt="Xcord Logo" style="max-height: 35px; width: auto;" />
                <span class="app-brand-text demo menu-text fw-bold ms-1" style="font-size: 1.25rem; color: #1e2229; letter-spacing: 0.5px;">XCORD</span>
            </span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="bx menu-toggle-icon d-none d-xl-block fs-4 align-middle"></i>
            <i class="bx bx-x d-block d-xl-none bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-divider mt-0"></div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboards -->
        <li class="menu-item {{ $isDashboard ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Dashboards">Dashboards</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ $isDashboard ? 'active' : '' }}">
                    <a href="{{ route('home') }}" class="menu-link">
                        <div data-i18n="Analytics">Analytics</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Master Settings Dropdown (Hidden for Company Staff) -->
        @if(auth()->check() && !auth()->user()->isAn('company_staff'))
        <li class="menu-item {{ $isMasterSetting ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div data-i18n="Master Settings">Master Settings</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('country.*') ? 'active' : '' }}">
                    <a href="{{ route('country.index') }}" class="menu-link">
                        <div data-i18n="Country">Country</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('purpose.*') ? 'active' : '' }}">
                    <a href="{{ route('purpose.index') }}" class="menu-link">
                        <div data-i18n="Purpose">Purpose</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('user.*') ? 'active' : '' }}">
                    <a href="{{ route('user.index') }}" class="menu-link">
                        <div data-i18n="Company Staff">Company Staff</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('bank.*') ? 'active' : '' }}">
                    <a href="{{ route('bank.index') }}" class="menu-link">
                        <div data-i18n="Bank">Bank</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('bank_setting.*') ? 'active' : '' }}">
                    <a href="{{ route('bank_setting.index') }}" class="menu-link">
                        <div data-i18n="Bank Setting">Bank Setting</div>
                    </a>
                </li>
            </ul>
        </li>
        @endif

        <li class="menu-item {{ $isBankPhoneNumber ? 'active' : '' }}">
            <a href="{{ route('bank_phone_number.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-phone"></i>
                <div data-i18n="Phone Number">Phone Number</div>
            </a>
        </li>

        <!-- Transactions -->
        <li class="menu-item {{ $isTransaction ? 'active' : '' }}">
            <a href="{{ route('transaction.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                <div data-i18n="Transaction">Transaction</div>
            </a>
        </li>

        <!-- Provider Settlement -->
        <li class="menu-item {{ $isProviderSettlement ? 'active' : '' }}">
            <a href="{{ route('provider_settlement.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-receipt"></i>
                <div data-i18n="Provider Settlement">Provider Settlement</div>
            </a>
        </li>

        <!-- Bank Snapshot Settlement -->
        <li class="menu-item {{ $isBankSnapshot ? 'active' : '' }}">
            <a href="{{ route('bank_snapshot.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-calendar-check"></i>
                <div data-i18n="Daily Bank Account Balance">Daily Bank Account Balance</div>
            </a>
        </li>

    </ul>
</aside>
<!-- end: sidebar -->
