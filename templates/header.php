
<!-- Header -->
  <link href="../assets/css/header.css" rel="stylesheet">

<header class="header bg-light shadow-sm px-4 py-3" >
    <div class="d-flex justify-content-between align-items-center">
        <!-- Dashboard Title -->
        <h1 class="m-0">Department Dashboard</h1>
        
        <!-- Profile Dropdown -->
        <div class="dropdown">
            <button 
                class="btn btn-light dropdown-toggle" 
                type="button" 
                id="profileDropdown" 
                data-bs-toggle="dropdown" 
                aria-expanded="false"
            >
                <img 
                    src="user-icon.png" 
                    width="30" 
                    height="30" 
                    class="rounded-circle" 
                    alt="User"
                > 
            </button>
            <ul 
                class="dropdown-menu dropdown-menu-end" 
                aria-labelledby="profileDropdown"
            >
                <li><a class="dropdown-item" href="notification">Notification</a></li>
                <li><a class="dropdown-item" href="accountselection">Change Account</a></li>
                <li><a class="dropdown-item" href="logout">Logout</a></li>
            </ul>
        </div>
    </div>
</header>
