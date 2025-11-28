<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sidebar RLEGS</title>
    <link href="https://cdn.lineicons.com/5.0/lineicons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="wrapper">
        <aside id="sidebar">
            <div class="d-flex">
                <button id="toggle-btn" type="button" aria-label="Toggle sidebar">
                    <img src="{{ asset('img/logo-outline.png') }}" class="avatar rounded-circle" alt="Logo" width="35" height="35" >
                </button>
                <div class="sidebar-logo">
                    <a href="#">RLEGS</a>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="{{ route('dashboard') }}" class="sidebar-link">
                        <i class="lni lni-dashboard-square-1"></i><span>Overview Data</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('revenue.index') }}" class="sidebar-link">
                        <i class="lni lni-file-pencil"></i><span>Data Revenue</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('dashboard.treg3') }}" class="sidebar-link">
                        <i class="lni lni-react"></i><span>TREG-3 React Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link has-dropdown collapsed" data-bs-toggle="collapse" data-bs-target="#performance" aria-expanded="false" aria-controls="performance">
                        <i class="lni lni-bar-chart-dollar"></i><span>Performansi</span>
                    </a>
                    <ul id="performance" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                        <li class="sidebar-item">
                            <a href="{{ url('witel-perform') }}" class="sidebar-link">
                                <i class="lni lni-react"></i><span>Witel</span>
                            </a>
                        </li>
                        
                        <li class="sidebar-item">
                            <a href="{{ route('dashboard.treg3') }}" class="sidebar-link">
                                <i class="lni lni-react"></i><span>TREG-3 React Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </li>
                {{-- <li class="sidebar-item">
                    <a href="{{ route('monitoring-LOP') }}" class="sidebar-link">
                        <i class="lni lni-user-multiple-4"></i><span>Top LOP</span>
                    </a>
                </li> --}}
                {{-- <li class="sidebar-item">
                    <a href="#" class="sidebar-link">
                        <i class="lni lni-chromecast"></i><span>Aosodomoro</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link">
                        <i class="lni lni-target-user"></i><span>EDK</span>
                    </a>
                </li>
            </ul> --}}
            <div class="sidebar-footer">w
                <a href="{{ route('profile.edit') }}" class="sidebar-link">
                    <i class="lni lni-gear-1"></i><span>Settings</span>
                </a>
            </div>
            <div class="sidebar-footer">
                <a href="{{ route('logout') }}" class="sidebar-link">
                    <i class="lni lni-exit"></i><span>Logout</span>
                </a>
            </div>
        </aside>
        <div class="main p-0">
            <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
                <div class="container-fluid">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                        <ul class="navbar-nav align-items-center">
                            {{-- <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="lni lni-bell-1 fs-4 mt-2"></i>
                                </a>
                            </li> --}}
                            <li class="nav-item dropdown ms-1">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="avatar-container me-2">
                                        @if(Auth::user()->profile_image)
                                            <img src="{{ asset('storage/' . Auth::user()->profile_image) }}" alt="{{ Auth::user()->name }}">
                                        @else
                                            <img src="{{ asset('img/profile.png') }}" alt="Default Profile">
                                        @endif
                                    </div>
                                    <span>{{ Auth::user()->name }}</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                            {{ __('Settings') }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                {{ __('Log Out') }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <script src="{{ asset('sidebar/script.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
</body>
</html>
