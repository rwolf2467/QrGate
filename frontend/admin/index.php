<?php
require_once '../config.php';

// Check if user is authorized to access admin panel
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Get shows data
$shows = getShows();
$stats = [];
if ($shows) {
    // Calculate statistics
    $totalTickets = 0;
    $totalAvailable = 0;
    $totalSold = 0;
    $totalIncome = 0;
    $soldByDate = [];
    $availableByDate = [];
    
    foreach ($shows['dates'] as $dateId => $dateData) {
        $totalTickets += $dateData['tickets'];
        $totalAvailable += $dateData['tickets_available'];
        $sold = $dateData['tickets'] - $dateData['tickets_available'];
        $totalSold += $sold;
        $totalIncome += $sold * floatval($dateData['price']);
        
        $soldByDate[$dateData['date']] = $sold;
        $availableByDate[$dateData['date']] = $dateData['tickets_available'];
    }
    
    $stats = [
        'totalTickets' => $totalTickets,
        'totalAvailable' => $totalAvailable,
        'totalSold' => $totalSold,
        'totalIncome' => $totalIncome,
        'soldByDate' => $soldByDate,
        'availableByDate' => $availableByDate
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QRGate Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #9333ea;
            --secondary: #ec4899;
            --dark: #0a0a0a;
            --darker: #111111;
            --border: #222222;
        }
        
        body {
            background-color: var(--dark);
            color: white;
            font-family: 'Quicksand', sans-serif;
        }
        
        .sidebar {
            background-color: var(--darker);
            border-right: 1px solid var(--border);
        }
        
        .card {
            background-color: var(--darker);
            border: 1px solid var(--border);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            border-color: var(--primary);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #1a1a1a, #222222);
            border: 1px solid var(--border);
        }
        
        .nav-link {
            transition: all 0.3s ease;
            padding: 14px 20px;
            border-radius: 8px;
            margin: 8px 0;
            display: flex;
            align-items: center;
            width: 100%;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(147, 51, 234, 0.2);
            color: #d8b4fe;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: opacity 0.2s;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .btn-danger {
            background-color: #dc2626;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-danger:hover {
            background-color: #b91c1c;
        }
        
        .section {
            display: none;
        }
        
        .section.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .days-table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        
        .days-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .days-table th,
        .days-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .days-table th {
            background-color: rgba(147, 51, 234, 0.1);
            font-weight: 600;
        }
        
        .days-table tr:last-child td {
            border-bottom: none;
        }
        
        .actions-cell {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-update {
            background-color: rgba(147, 51, 234, 0.2);
            color: #c084fc;
            border: 1px solid rgba(147, 51, 234, 0.5);
        }
        
        .btn-update:hover {
            background-color: rgba(147, 51, 234, 0.3);
        }
        
        .btn-delete {
            background-color: rgba(220, 38, 38, 0.2);
            color: #f87171;
            border: 1px solid rgba(220, 38, 38, 0.5);
        }
        
        .btn-delete:hover {
            background-color: rgba(220, 38, 38, 0.3);
        }
        
        .input-field {
            background-color: var(--dark);
            border: 1px solid var(--border);
            color: white;
            padding: 10px;
            border-radius: 4px;
            width: 100%;
        }
        
        .input-field:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        th {
            background-color: rgba(147, 51, 234, 0.1);
        }
    </style>
    <style>
        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification.success {
            background: linear-gradient(90deg, #10b981, #059669);
        }
        
        .notification.error {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }
        
        .notification.info {
            background: linear-gradient(90deg, #3b82f6, #2563eb);
        }
        
        .notification-icon {
            font-size: 18px;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            margin-left: 16px;
            font-size: 16px;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .notification-close:hover {
            opacity: 1;
        }
        
        /* Button loading state */
        .btn-loading {
            position: relative;
            pointer-events: none;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        /* Button success state */
        .btn-success {
            background: linear-gradient(90deg, #10b981, #059669) !important;
        }
        
        .btn-success i {
            animation: bounce 0.5s;
        }
        
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
    </style>
</head>
<body class="flex h-screen">
    <!-- Notification Container -->
    <div id="notificationContainer" class="fixed top-4 right-4 z-50 w-80"></div>
    <!-- Sidebar -->
    <div class="sidebar w-64 flex flex-col">
        <div class="p-6">
            <h1 class="text-2xl font-bold flex items-center">
                <i class="fas fa-qrcode mr-2"></i> QRGate Admin
            </h1>
        </div>
        
        <nav class="flex-1 px-4">
            <a href="#" class="nav-link active" data-section="dashboard">
                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
            </a>
            <a href="#" class="nav-link" data-section="statistics">
                <i class="fas fa-chart-bar mr-2"></i> Statistics
            </a>
            <a href="#" class="nav-link" data-section="event">
                <i class="fas fa-calendar-alt mr-2"></i> Manage Event
            </a>
            <a href="#" class="nav-link" data-section="days">
                <i class="fas fa-calendar-day mr-2"></i> Manage Days
            </a>
        </nav>
        
        <div class="p-4 border-t border-gray-800">
            <button id="logoutBtn" class="w-full btn-danger">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </button>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="flex-1 overflow-auto p-6">
        <!-- Dashboard Section -->
        <div id="dashboard" class="section active">
            <h2 class="text-3xl font-bold mb-6">Dashboard</h2>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-900/50 mr-4">
                            <i class="fas fa-ticket-alt text-purple-400 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-400">Total Tickets</p>
                            <p class="text-2xl font-bold"><?php echo $stats['totalTickets'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-900/50 mr-4">
                            <i class="fas fa-check-circle text-green-400 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-400">Sold Tickets</p>
                            <p class="text-2xl font-bold"><?php echo $stats['totalSold'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-900/50 mr-4">
                            <i class="fas fa-clock text-blue-400 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-400">Tickets Left</p>
                            <p class="text-2xl font-bold"><?php echo $stats['totalAvailable'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-900/50 mr-4">
                            <i class="fas fa-euro-sign text-yellow-400 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-400">Total Income</p>
                            <p class="text-2xl font-bold">€<?php echo number_format($stats['totalIncome'] ?? 0, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="card p-6">
                    <h3 class="text-xl font-bold mb-4">Event Overview</h3>
                    <?php if ($shows): ?>
                        <p><strong>Organization:</strong> <?php echo htmlspecialchars($shows['orga_name']); ?></p>
                        <p><strong>Event Title:</strong> <?php echo htmlspecialchars($shows['title']); ?></p>
                        <p><strong>Subtitle:</strong> <?php echo htmlspecialchars($shows['subtitle']); ?></p>
                        <p><strong>Active Dates:</strong> <?php echo count($shows['dates']); ?></p>
                        <p><strong>Store Status:</strong> 
                            <span class="<?php echo $shows['store_lock'] ? 'text-red-500' : 'text-green-500'; ?>">
                                <?php echo $shows['store_lock'] ? 'LOCKED' : 'OPEN'; ?>
                            </span>
                        </p>
                    <?php else: ?>
                        <p class="text-red-500">Error loading event data</p>
                    <?php endif; ?>
                </div>
                
                <div class="card p-6">
                    <h3 class="text-xl font-bold mb-4">Recent Activity</h3>
                    <div class="space-y-3">
                        <div class="flex items-center p-3 bg-gray-900/50 rounded">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                            <span>System operational</span>
                        </div>
                        <div class="flex items-center p-3 bg-gray-900/50 rounded">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                            <span>Last backup: Today</span>
                        </div>
                        <div class="flex items-center p-3 bg-gray-900/50 rounded">
                            <div class="w-2 h-2 bg-purple-500 rounded-full mr-3"></div>
                            <span>New tickets sold: <?php echo $stats['totalSold'] ?? 0; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Section -->
        <div id="statistics" class="section">
            <h2 class="text-3xl font-bold mb-6">Statistics</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="card p-6">
                    <h3 class="text-xl font-bold mb-4">Ticket Sales Overview</h3>
                    <canvas id="salesChart"></canvas>
                </div>
                
                <div class="card p-6">
                    <h3 class="text-xl font-bold mb-4">Tickets Available Per Day</h3>
                    <canvas id="availabilityChart"></canvas>
                </div>
            </div>
            
            <div class="card p-6 mb-8">
                <h3 class="text-xl font-bold mb-4">Detailed Statistics</h3>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Tickets</th>
                                <th>Sold</th>
                                <th>Available</th>
                                <th>Price</th>
                                <th>Income</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($shows): ?>
                                <?php foreach ($shows['dates'] as $dateId => $dateData): 
                                    $sold = $dateData['tickets'] - $dateData['tickets_available'];
                                    $income = $sold * floatval($dateData['price']);
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($dateData['date']); ?></td>
                                        <td><?php echo $dateData['tickets']; ?></td>
                                        <td><?php echo $sold; ?></td>
                                        <td><?php echo $dateData['tickets_available']; ?></td>
                                        <td>€<?php echo $dateData['price']; ?></td>
                                        <td>€<?php echo number_format($income, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Manage Event Section -->
        <div id="event" class="section">
            <h2 class="text-3xl font-bold mb-6">Manage Current Event</h2>
            
            <div class="card p-6">
                <form id="eventForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block mb-2">Organization Name</label>
                            <input type="text" id="orgaName" class="input-field" value="<?php echo $shows ? htmlspecialchars($shows['orga_name']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label class="block mb-2">Event Title</label>
                            <input type="text" id="eventTitle" class="input-field" value="<?php echo $shows ? htmlspecialchars($shows['title']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label class="block mb-2">Subtitle</label>
                            <input type="text" id="eventSubtitle" class="input-field" value="<?php echo $shows ? htmlspecialchars($shows['subtitle']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label class="block mb-2">Banner URL</label>
                            <input type="text" id="bannerUrl" class="input-field" value="<?php echo $shows ? htmlspecialchars($shows['banner']) : ''; ?>">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" id="storeLock" <?php echo $shows && $shows['store_lock'] ? 'checked' : ''; ?> class="mr-2">
                                <span>Lock ticket store (prevent new purchases)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save mr-2"></i> Save Event Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
        

        <!-- Manage Days Section -->
        <div id="days" class="section">
            <h2 class="text-3xl font-bold mb-6">Manage Days</h2>
            
            <div class="card p-6 mb-6">
                <h3 class="text-xl font-bold mb-4">Add New Day</h3>
                <form id="addDayForm">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block mb-2">Date</label>
                            <input type="date" id="newDate" class="input-field">
                        </div>
                        
                        <div>
                            <label class="block mb-2">Time</label>
                            <input type="time" id="newTime" class="input-field" value="20:00">
                        </div>
                        
                        <div>
                            <label class="block mb-2">Total Tickets</label>
                            <input type="number" id="newTickets" class="input-field" value="100">
                        </div>
                        
                        <div>
                            <label class="block mb-2">Price (€)</label>
                            <input type="number" step="0.01" id="newPrice" class="input-field" value="15.00">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-plus mr-2"></i> Add Day
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card p-6">
                <h3 class="text-xl font-bold mb-4">Current Days</h3>
                <div class="days-table-container">
                    <table class="days-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Total Tickets</th>
                                <th>Available</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="daysTableBody">
                            <?php if ($shows): ?>
                                <?php foreach ($shows['dates'] as $dateId => $dateData): ?>
                                    <tr data-date-id="<?php echo $dateId; ?>">
                                        <td>
                                            <input type="date" class="input-field day-date" value="<?php echo $dateData['date']; ?>">
                                        </td>
                                        <td>
                                            <input type="time" class="input-field day-time" value="<?php echo $dateData['time']; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="input-field day-tickets" value="<?php echo $dateData['tickets']; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="input-field day-available" value="<?php echo $dateData['tickets_available']; ?>">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="input-field day-price" value="<?php echo $dateData['price']; ?>">
                                        </td>
                                        <td class="actions-cell">
                                            <button class="btn-icon btn-update update-day-btn" data-date-id="<?php echo $dateId; ?>">
                                                <i class="fas fa-save"></i>
                                            </button>
                                            <button class="btn-icon btn-delete delete-day-btn" data-date-id="<?php echo $dateId; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Define API constants
        const API_BASE_URL = '<?php echo API_BASE_URL; ?>';
        const API_KEY = '<?php echo API_KEY; ?>';
        
        // Notification system
        function showNotification(message, type = 'success', duration = 3000) {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            
            notification.className = `notification ${type}`;
            
            // Icon based on type
            let icon = '';
            if (type === 'success') {
                icon = '<i class="fas fa-check-circle notification-icon"></i>';
            } else if (type === 'error') {
                icon = '<i class="fas fa-exclamation-circle notification-icon"></i>';
            } else {
                icon = '<i class="fas fa-info-circle notification-icon"></i>';
            }
            
            notification.innerHTML = `
                ${icon}
                <span>${message}</span>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(notification);
            
            // Trigger animation
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            // Auto-remove after duration
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, duration);
        }
        
        // Button state management
        function setButtonLoading(button, loading = true) {
            if (loading) {
                button.classList.add('btn-loading');
                button.disabled = true;
                button.innerHTML = '<span>' + button.innerHTML + '</span>';
            } else {
                button.classList.remove('btn-loading');
                button.disabled = false;
            }
        }
        
        function setButtonSuccess(button) {
            button.classList.add('btn-success');
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check mr-2"></i>' + originalHtml;
            
            setTimeout(() => {
                button.classList.remove('btn-success');
                button.innerHTML = originalHtml;
            }, 2000);
        }
        
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active nav link
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                // Store current section in localStorage
                localStorage.setItem('currentAdminSection', this.getAttribute('data-section'));
                
                // Show selected section
                const sectionId = this.getAttribute('data-section');
                document.querySelectorAll('.section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(sectionId).classList.add('active');
            });
        });
        
        // Restore last active section from localStorage
        const savedSection = localStorage.getItem('currentAdminSection');
        if (savedSection) {
            const savedLink = document.querySelector(`.nav-link[data-section="${savedSection}"]`);
            const savedSectionElement = document.getElementById(savedSection);
            
            if (savedLink && savedSectionElement) {
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                savedLink.classList.add('active');
                document.querySelectorAll('.section').forEach(section => {
                    section.classList.remove('active');
                });
                savedSectionElement.classList.add('active');
            }
            
            // Clear the stored section after restoring
            localStorage.removeItem('currentAdminSection');
        }
        
        // Initialize charts when statistics section is active
        function initCharts() {
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const availabilityCtx = document.getElementById('availabilityChart').getContext('2d');
            
            // Sample data - in a real app, this would come from your backend
            const dates = <?php echo json_encode(array_keys($stats['soldByDate'] ?? [])); ?>;
            const soldData = <?php echo json_encode(array_values($stats['soldByDate'] ?? [])); ?>;
            const availableData = <?php echo json_encode(array_values($stats['availableByDate'] ?? [])); ?>;
            
            // Sales Chart
            new Chart(salesCtx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Tickets Sold',
                        data: soldData,
                        backgroundColor: 'rgba(147, 51, 234, 0.6)',
                        borderColor: 'rgba(147, 51, 234, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#ffffff'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#ffffff'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#ffffff'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    }
                }
            });
            
            // Availability Chart
            new Chart(availabilityCtx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Tickets Available',
                        data: availableData,
                        borderColor: 'rgba(74, 222, 128, 1)',
                        backgroundColor: 'rgba(74, 222, 128, 0.2)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#ffffff'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#ffffff'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#ffffff'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    }
                }
            });
        }
        
        // Show statistics section and initialize charts when first accessed
        const statsLink = document.querySelector('.nav-link[data-section="statistics"]');
        const statsSection = document.getElementById('statistics');
        
        statsLink.addEventListener('click', function() {
            // Initialize charts after a short delay to ensure DOM is ready
            setTimeout(initCharts, 100);
        });
        
        // Initialize charts on page load if dashboard is active
        if (document.getElementById('dashboard').classList.contains('active')) {
            setTimeout(initCharts, 100);
        }
        
        // Event form submission
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = e.submitter;
            setButtonLoading(submitButton, true);
            
            const eventData = {
                orga_name: document.getElementById('orgaName').value,
                title: document.getElementById('eventTitle').value,
                subtitle: document.getElementById('eventSubtitle').value,
                banner: document.getElementById('bannerUrl').value,
                store_lock: document.getElementById('storeLock').checked
            };
            
            // Add dates to event data
            eventData.dates = {};
            <?php if ($shows): ?>
                <?php foreach ($shows['dates'] as $dateId => $dateData): ?>
                    eventData.dates["<?php echo $dateId; ?>"] = {
                        date: "<?php echo $dateData['date']; ?>",
                        time: "<?php echo $dateData['time']; ?>",
                        tickets: <?php echo $dateData['tickets']; ?>,
                        tickets_available: <?php echo $dateData['tickets_available']; ?>,
                        price: "<?php echo $dateData['price']; ?>"
                    };
                <?php endforeach; ?>
            <?php endif; ?>
            
            // Make API call to update show
            console.log('Sending event data:', eventData);
            
            fetch(`${API_BASE_URL}/api/show/edit`, {
                method: 'POST',
                headers: {
                    'Authorization': API_KEY,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(eventData)
            })
            .then(response => {
                console.log('API Response status:', response.status);
                return response.json();
            })
            .then(data => {
                const submitButton = e.submitter;
                setButtonLoading(submitButton, false);
                
                console.log('API Response data:', data);

                if (data.status === 'success') {
                    showNotification('Event updated successfully!', 'success');
                    setButtonSuccess(submitButton);
                    // Store current section before reload
                    localStorage.setItem('currentAdminSection', 'event');
                    // Refresh the page to ensure all data is synchronized
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    showNotification('Error updating event: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                setButtonLoading(e.submitter, false);
                showNotification('Error updating event: ' + error.message, 'error');
            });
        });
        
        // Add day form submission
        document.getElementById('addDayForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = e.submitter;
            setButtonLoading(submitButton, true);
            
            const newDate = document.getElementById('newDate').value;
            const newTime = document.getElementById('newTime').value;
            const newTickets = document.getElementById('newTickets').value;
            const newPrice = document.getElementById('newPrice').value;
            
            if (!newDate) {
                showNotification('Please enter a date', 'error');
                return;
            }
            
            // Generate a unique ID for the new day (timestamp-based)
            const newDateId = 'day_' + Date.now();
            
            // Make API call to add the day with fixed ID
            fetch('api.php?action=add_day', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    dateId: newDateId,
                    date: newDate,
                    time: newTime,
                    tickets: newTickets,
                    price: newPrice
                })
            })
            .then(response => response.json())
            .then(data => {
                const submitButton = e.submitter;
                setButtonLoading(submitButton, false);
                
                if (data.status === 'success') {
                    showNotification('Day added successfully!', 'success');
                    setButtonSuccess(submitButton);
                    // Store current section before reload
                    localStorage.setItem('currentAdminSection', 'days');
                    // Refresh the page to ensure all data is synchronized
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    showNotification('Error adding day: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding day');
            });
        });
        
        // Use event delegation for update and delete buttons to handle dynamically added rows
        document.getElementById('daysTableBody').addEventListener('click', function(e) {
            // Handle update day button
            if (e.target.closest('.update-day-btn')) {
                const button = e.target.closest('.update-day-btn');
                const dateId = button.getAttribute('data-date-id');
                const row = button.closest('tr');
                
                const date = row.querySelector('.day-date').value;
                const time = row.querySelector('.day-time').value;
                const tickets = row.querySelector('.day-tickets').value;
                const available = row.querySelector('.day-available').value;
                const price = row.querySelector('.day-price').value;
                
                // Make API call to update the day
                fetch('api.php?action=update_day', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        dateId: dateId,
                        date: date,
                        time: time,
                        tickets: tickets,
                        available: available,
                        price: price
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showNotification('Day updated successfully!', 'success');
                        setButtonSuccess(button);
                        // Store current section before reload
                        localStorage.setItem('currentAdminSection', 'days');
                        // Refresh the page to ensure all data is synchronized
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    } else {
                        showNotification('Error updating day: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating day');
                });
            }
            // Handle delete day button
            else if (e.target.closest('.delete-day-btn')) {
                const button = e.target.closest('.delete-day-btn');
                const dateId = button.getAttribute('data-date-id');
                
                if (confirm('Are you sure you want to delete this day?')) {
                const button = e.target.closest('.delete-day-btn');
                const dateId = button.getAttribute('data-date-id');
                console.log('Deleting day with ID:', dateId);
                setButtonLoading(button, true);
                    // Make API call to delete the day
                    fetch('api.php?action=delete_day', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            dateId: dateId
                        })
                    })
                    .then(response => {
                        console.log('Delete API response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Delete API response data:', data);
                        
                        if (data.status === 'success') {
                            showNotification('Day deleted successfully!', 'success');
                            // Remove the row from the table
                            const row = document.querySelector(`tr[data-date-id="${dateId}"]`);
                            if (row) {
                                row.remove();
                            }
                            // Store current section before reload
                            localStorage.setItem('currentAdminSection', 'days');
                            // Refresh the page to ensure all data is synchronized
                            setTimeout(() => {
                                location.reload();
                            }, 500);
                        } else {
                            showNotification('Error deleting day: ' + (data.message || 'Unknown error'), 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error deleting day: ' + error.message, 'error');
                    });
                }
            }
        });
        
        // Logout button
        document.getElementById('logoutBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to logout?')) {
                // Clear admin session
                fetch('logout.php')
                    .then(() => {
                        window.location.href = 'login.php';
                    })
                    .catch(() => {
                        window.location.href = 'login.php';
                    });
            }
        });
        
        // Handle API calls for updating show data
        async function updateShowData(showData) {
            try {
                const response = await fetch(`${API_BASE_URL}/api/show/edit`, {
                    method: 'POST',
                    headers: {
                        'Authorization': API_KEY,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(showData)
                });
                
                const result = await response.json();
                return result;
            } catch (error) {
                console.error('Error updating show:', error);
                return { status: 'error', message: error.message };
            }
        }
    </script>
</body>
</html>