<li class="nav-item">
    <a href="{{ route('utilities.index') }}"
       class="nav-link {{ Request::is('utilities*') ? 'active' : '' }}">
        <p>Utilities</p>
    </a>
</li>


<li class="nav-item">
    <a href="{{ route('exchangeSources.index') }}"
       class="nav-link {{ Request::is('exchangeSources*') ? 'active' : '' }}">
        <p>Exchange Sources</p>
    </a>
</li>


<li class="nav-item">
    <a href="{{ route('exchangeRates.index') }}"
       class="nav-link {{ Request::is('exchangeRates*') ? 'active' : '' }}">
        <p>Exchange Rates</p>
    </a>
</li>


<li class="nav-item">
    <a href="{{ route('alerts.index') }}"
       class="nav-link {{ Request::is('alerts*') ? 'active' : '' }}">
        <p>Alerts</p>
    </a>
</li>


<li class="nav-item">
    <a href="{{ route('subscriptions.index') }}"
       class="nav-link {{ Request::is('subscriptions*') ? 'active' : '' }}">
        <p>Subscriptions</p>
    </a>
</li>


