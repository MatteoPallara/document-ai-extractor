<aside id="leftsidebar" class="sidebar">
    <div class="user-info">
        <div class="image">
            <img src="{{ asset('images/user.png') }}" width="48" height="48" alt="User"
                style="border-radius: 0px; box-shadow: 0px 2px 5px #dedede;" />
        </div>
        <div class="info-container">
            <div class="name" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Ciao <span class="name">{{ Auth::user()->name }}</span>! </div>

            <div class="email">
                Email: <span class="name">{{ Auth::user()->email }} </span><br />
                Il tuo ruolo è: <span class="role" id="role">{{ Auth::user()->getRoleName() }}</span></div>

            <div class="btn-group user-helper-dropdown">
                <i class="material-icons" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="true">keyboard_arrow_down</i>
                <ul class="dropdown-menu pull-right">
                    <li><a href="{{ route('logout') }}"><i class="material-icons">input</i>Esci</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="menu">
        <ul class="list">

            <!-- Admin Menu -->
            @role(['admin'])
                <li class="{{ isSectionActive(['admin.stats'], 1) ? 'active' : '' }}">
                    <a href="{{ route('admin.stats') }}">
                        <i class="material-icons">dashboard</i>
                        <span>Statistiche</span>
                    </a>
                </li>

                <li class="{{ isSectionActive(['admin.users'], 1) ? 'active' : '' }}">
                    <a href="{{ route('admin.users') }}">
                        <i class="material-icons">people</i>
                        <span>Utenti</span>
                    </a>
                </li>
                <hr />
            @endrole

            <!-- User Menu -->
            @role(['user'])
                <li class="{{ isSectionActive(['user.home'], 1) ? 'active' : '' }}">
                    <a href="{{ route('user.home') }}">
                        <i class="material-icons">home</i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="{{ isSectionActive(['user.config'], 1) ? 'active' : '' }}">
                    <a href="{{ route('user.config') }}">
                        <i class="material-icons">settings</i>
                        <span>Configurazione</span>
                    </a>
                </li>

                <li class="{{ isSectionActive(['user.documents'], 1) ? 'active' : '' }}">
                    <a href="{{ route('user.documents') }}">
                        <i class="material-icons">description</i>
                        <span>Documenti</span>
                    </a>
                </li>

                <li class="{{ isSectionActive(['user.vectors'], 1) ? 'active' : '' }}">
                    <a href="{{ route('user.vectors') }}">
                        <i class="material-icons">schema</i>
                        <span>Vettori</span>
                    </a>
                </li>

                <li class="{{ isSectionActive(['user.elaborate'], 1) ? 'active' : '' }}">
                    <a href="{{ route('user.elaborate') }}">
                        <i class="material-icons">psychology</i>
                        <span>Elabora</span>
                    </a>
                </li>

                <li class="{{ isSectionActive(['user.results'], 1) ? 'active' : '' }}">
                    <a href="{{ route('user.results') }}">
                        <i class="material-icons">assessment</i>
                        <span>Risultati</span>
                    </a>
                </li>
            @endrole
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="text-center p-3 text-muted small">
            v1.0.0
        </div>
    </div>
</aside>