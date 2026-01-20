<?php
require_once "../config.php";

if (!isset($_SESSION["admin"]) || $_SESSION["admin"] !== true) {
    header("Location: login.php");
    exit();
}

$shows = getShows();
$stats = [];
if ($shows) {
    $totalTickets = 0;
    $totalAvailable = 0;
    $totalSold = 0;
    $totalIncome = 0;
    $soldByDate = [];
    $availableByDate = [];
    foreach ($shows["dates"] as $dateId => $dateData) {
        $totalTickets += $dateData["tickets"];
        $totalAvailable += $dateData["tickets_available"];
        $sold = $dateData["tickets"] - $dateData["tickets_available"];
        $totalSold += $sold;
        $totalIncome += $sold * floatval($dateData["price"]);
        $soldByDate[$dateData["date"]] = $sold;
        $availableByDate[$dateData["date"]] = $dateData["tickets_available"];
    }
    $stats = [
        "totalTickets" => $totalTickets,
        "totalAvailable" => $totalAvailable,
        "totalSold" => $totalSold,
        "totalIncome" => $totalIncome,
        "soldByDate" => $soldByDate,
        "availableByDate" => $availableByDate,
    ];
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QRGate Admin Panel</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/basecoat.cdn.min.css">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400..700&display=swap" rel="stylesheet">


    <style>
        body {
            font-family: 'Quicksand', sans-serif;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .sidebar span {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        #sidebar-title {
            font-size: initial;
            font-weight: initial;
            margin-bottom: initial;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 10%;
        }

        header h2 {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }


        canvas {
            max-height: 300px;
        }

        main {
            margin-left: 260px;
            padding: 1.5rem;
            margin-right: auto;
            margin-top: 0;
        }

        .pie-charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chartjs-render-monitor {
            max-height: 300px;
        }

        @media (max-width: 768px) {
            main {
                margin-left: 0 !important;
                padding: 1rem;
            }

            aside.sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
            }

            aside.sidebar.active {
                left: 0;
            }

            body.page {
                overflow-x: hidden;
            }

            .stats-grid,
            .pie-charts-container,
            .days-table-container,
            .form,
            table {
                grid-template-columns: 1fr !important;
                width: 100% !important;
            }

            .card {
                width: 100% !important;
            }

            canvas {
                max-width: 100% !important;
                height: auto !important;
            }

            h1 {
                font-size: 1.5rem !important;
            }

            h2 {
                font-size: 1.25rem !important;
            }

            button[onclick*="basecoat:sidebar"] {
                display: block !important;
                margin-bottom: 1rem;
                width: fit-content;
            }

            @media (max-width: 768px) {

                #incomeChart,
                #totalSalesChart {
                    height: 220px !important;
                    width: 100% !important;
                    max-width: 100%;
                }

                #statistics .card>section {
                    height: 220px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    padding: 0;
                }

                .days-table-container {
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                    margin: 0;
                    padding: 0;
                }

                .days-table-container table {
                    min-width: 600px;
                    width: auto;
                }

                #statistics .card,
                #days .card {
                    padding: 1rem;
                }
            }

            .days-table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 0 -1.5rem;
                padding: 0 1.5rem;
            }

            /* Image Management Styles */
            #bannerPreview img,
            #logoPreview img,
            #wallpaperPreview img {
                max-width: 100%;
                max-height: 300px;
                object-fit: contain;
                display: block;
                margin: 0 auto;
            }

            .image-preview-container {
                border: 1px solid var(--color-border);
                border-radius: var(--radius-md);
                padding: 1rem;
                min-height: 200px;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: var(--color-surface-1);
            }
        }
    </style>
</head>

<body class="page">
    <div id="toaster" class="toaster" data-align="center"></div>

    <aside class="sidebar" data-side="left" aria-hidden="false">
        <nav aria-label="Navigation Menu">

            <section class="scrollbar">
                <div role="group" aria-labelledby="group-label-content-1">
                    <ul>
                        <li>
                            <h1 id="sidebar-title"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-scan-qr-code-icon lucide-scan-qr-code">
                                    <path d="M17 12v4a1 1 0 0 1-1 1h-4" />
                                    <path d="M17 3h2a2 2 0 0 1 2 2v2" />
                                    <path d="M17 8V7" />
                                    <path d="M21 17v2a2 2 0 0 1-2 2h-2" />
                                    <path d="M3 7V5a2 2 0 0 1 2-2h2" />
                                    <path d="M7 17h.01" />
                                    <path d="M7 21H5a2 2 0 0 1-2-2v-2" />
                                    <rect x="7" y="7" width="5" height="5" rx="1" />
                                </svg>QRGate Admin Panel</h1>
                        </li>
                    </ul>
                </div>
                <div role="group" aria-labelledby="group-label-content-2">

                    <ul>
                        <li><a href="#" data-section="dashboard"><span><svg xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-layout-dashboard-icon lucide-layout-dashboard">
                                        <rect width="7" height="9" x="3" y="3" rx="1" />
                                        <rect width="7" height="5" x="14" y="3" rx="1" />
                                        <rect width="7" height="9" x="14" y="12" rx="1" />
                                        <rect width="7" height="5" x="3" y="16" rx="1" />
                                    </svg> Dashboard</span></a></li>
                        <li><a href="#" data-section="statistics"><span><svg xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-chart-column-big-icon lucide-chart-column-big">
                                        <path d="M3 3v16a2 2 0 0 0 2 2h16" />
                                        <rect x="15" y="5" width="4" height="12" rx="1" />
                                        <rect x="7" y="8" width="4" height="9" rx="1" />
                                    </svg> Statistics</span></a></li>
                        <li><a href="#" data-section="event"><span><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-file-pen-icon lucide-file-pen">
                                        <path
                                            d="M12.659 22H18a2 2 0 0 0 2-2V8a2.4 2.4 0 0 0-.706-1.706l-3.588-3.588A2.4 2.4 0 0 0 14 2H6a2 2 0 0 0-2 2v9.34" />
                                        <path d="M14 2v5a1 1 0 0 0 1 1h5" />
                                        <path
                                            d="M10.378 12.622a1 1 0 0 1 3 3.003L8.36 20.637a2 2 0 0 1-.854.506l-2.867.837a.5.5 0 0 1-.62-.62l.836-2.869a2 2 0 0 1 .506-.853z" />
                                    </svg> Manage Event</span></a>
                        </li>
                        <li><a href="#" data-section="days"><span><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-calendar-days-icon lucide-calendar-days">
                                        <path d="M8 2v4" />
                                        <path d="M16 2v4" />
                                        <rect width="18" height="18" x="3" y="4" rx="2" />
                                        <path d="M3 10h18" />
                                        <path d="M8 14h.01" />
                                        <path d="M12 14h.01" />
                                        <path d="M16 14h.01" />
                                        <path d="M8 18h.01" />
                                        <path d="M12 18h.01" />
                                        <path d="M16 18h.01" />
                                    </svg> Manage Dates</span></a>
                        </li>
                        <li><a href="#" data-section="images"><span><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-image-icon lucide-image">
                                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                                        <circle cx="9" cy="9" r="2" />
                                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                                    </svg> Image Management</span></a>
                        </li>

                    </ul>
                </div>
                <div role="group" aria-labelledby="group-label-content-3">
                    <ul>
                        <li><a href="/logout.php" data-section="logout"><span><svg xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-log-out-icon lucide-log-out">
                                        <path d="m16 17 5-5-5-5" />
                                        <path d="M21 12H9" />
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    </svg> Logout</span></a></li>
                    </ul>
                </div>
            </section>
        </nav>
    </aside>
    <dialog id="confirmation-dialog" class="dialog" aria-labelledby="confirmation-dialog-title"
        aria-describedby="confirmation-dialog-description">
        <div>
            <header>
                <h2 id="confirmation-dialog-title">Confirm Action</h2>
                <p id="confirmation-dialog-description"></p>
            </header>
            <footer>
                <button class="btn-outline"
                    onclick="document.getElementById('confirmation-dialog').close()">Cancel</button>
                <button class="btn-destructive" id="confirmation-dialog-confirm">Confirm</button>
            </footer>
        </div>
    </dialog>

    <main>

        <button type="button" class="btn-outline"
            onclick="document.dispatchEvent(new CustomEvent('basecoat:sidebar'));"><svg
                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-panel-right-icon lucide-panel-right">
                <rect width="18" height="18" x="3" y="3" rx="2" />
                <path d="M15 3v18" />
            </svg></button>
        <!-- Dashboard -->
        <div id="dashboard" class="active" style="display: none">
            <h1>Dashboard</h1>
            <div class="stats-grid">
                <div class="card">
                    <header>
                        <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-ticket-icon lucide-ticket">
                                <path
                                    d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                                <path d="M13 5v2" />
                                <path d="M13 17v2" />
                                <path d="M13 11v2" />
                            </svg> Total tickets</h2>
                        <p>The total number of tickets available for all days of this event. </p>
                    </header>
                    <section><?php echo $stats["totalTickets"] ??
                        0; ?> Tickets</section>
                </div>
                <div class="card">
                    <header>
                        <h2 class="flex justify-between items-center">
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-ticket-check-icon lucide-ticket-check">
                                    <path
                                        d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                                    <path d="m9 12 2 2 4-4" />
                                </svg>
                                Sold Tickets
                            </span>
                            <span class="badge-outline flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-percent-icon lucide-percent">
                                    <line x1="19" x2="5" y1="5" y2="19" />
                                    <circle cx="6.5" cy="6.5" r="2.5" />
                                    <circle cx="17.5" cy="17.5" r="2.5" />
                                </svg>
                                <?php
                                $percentage = 0;
                                if ($stats["totalTickets"] > 0) {
                                    $percentage = ($stats["totalSold"] / $stats["totalTickets"]) * 100;
                                }
                                echo number_format($percentage, 2); ?>
                            </span>
                        </h2>
                        <p>The number of tickets remaining for the entire event. This also includes reserved tickets.
                        </p>
                    </header>
                    <section><?php echo $stats["totalSold"] ??
                        0; ?> Tickets</section>
                </div>
                <div class="card">
                    <header>
                        <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-ticket-minus-icon lucide-ticket-minus">
                                <path
                                    d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                                <path d="M9 12h6" />
                            </svg>Tickets Left</h2>
                        <p>The number of tickets that are still available for the entire event and are for sale.</p>
                    </header>
                    <section><?php echo $stats["totalAvailable"] ??
                        0; ?> Tickets</section>
                </div>
                <div class="card">
                    <header>
                        <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-banknote-icon lucide-banknote">
                                <rect width="20" height="12" x="2" y="6" rx="2" />
                                <circle cx="12" cy="12" r="2" />
                                <path d="M6 12h.01M18 12h.01" />
                            </svg> Estimated income</h2>
                        <p>Estimated income in the best-case scenario. This means that all tickets booked were paid for
                            and used.</p>
                    </header>
                    <section><?php echo number_format(
                        $stats["totalIncome"] ?? 0,
                        2
                    ); ?> €</section>
                </div>
            </div>

            <div class="card">
                <header>
                    <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-activity-icon lucide-activity">
                            <path
                                d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2" />
                        </svg> Event Overview</h2>
                </header>
                <section>
                    <?php if ($shows): ?>
                        <p><strong>Organization:</strong> <?php echo htmlspecialchars(
                            $shows["orga_name"]
                        ); ?></p>
                        <p><strong>Event Title:</strong> <?php echo htmlspecialchars(
                            $shows["title"]
                        ); ?></p>
                        <p><strong>Subtitle:</strong> <?php echo htmlspecialchars(
                            $shows["subtitle"]
                        ); ?></p>
                        <p><strong>Active Dates:</strong> <?php echo count(
                            $shows["dates"]
                        ); ?></p>
                        <p><strong>Store Status:</strong>
                            <span style="color: <?php echo $shows[
                                "store_lock"
                            ]
                                ? "var(#ed3939)"
                                : "var(#40e45b)"; ?>">
                                <?php echo $shows["store_lock"]
                                    ? "LOCKED"
                                    : "OPEN"; ?>
                            </span>
                        </p>
                    <?php else: ?>
                        <p style="color: var(--color-error);">Error loading event data</p>
                    <?php endif; ?>
                </section>
            </div>

        </div>

        <!-- Statistics -->
        <div id="statistics" style="display: none;">
            <h1><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-chart-column-big-icon lucide-chart-column-big" style="margin-right: 10px;">
                    <path d="M3 3v16a2 2 0 0 0 2 2h16" />
                    <rect x="15" y="5" width="4" height="12" rx="1" />
                    <rect x="7" y="8" width="4" height="9" rx="1" />
                </svg> Statistics</h1>
            <div class="pie-charts-container">
                <div class="card">
                    <header>
                        <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-badge-euro-icon lucide-badge-euro">
                                <path
                                    d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" />
                                <path d="M7 12h5" />
                                <path d="M15 9.4a4 4 0 1 0 0 5.2" />
                            </svg> Ticket Sales Overview</h2>
                    </header>
                    <section><canvas id="salesChart"></canvas></section>
                </div>

                <div class="card">
                    <header>
                        <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-tickets-icon lucide-tickets">
                                <path d="m3.173 8.18 11-5a2 2 0 0 1 2.647.993L18.56 8" />
                                <path d="M6 10V8" />
                                <path d="M6 14v1" />
                                <path d="M6 19v2" />
                                <rect x="2" y="8" width="20" height="13" rx="2" />
                            </svg> Tickets Available Per Day</h2>
                    </header>
                    <section><canvas id="availabilityChart"></canvas></section>
                </div>
            </div>
            <div class="card">
                <header>
                    <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-badge-euro-icon lucide-badge-euro">
                            <path
                                d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" />
                            <path d="M7 12h5" />
                            <path d="M15 9.4a4 4 0 1 0 0 5.2" />
                        </svg> Income Over Time</h2>
                </header>
                <section><canvas id="incomeChart"></canvas></section>
            </div>
            <br>
            <div class="card">
                <header>
                    <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-tickets-icon lucide-tickets">
                            <path d="m3.173 8.18 11-5a2 2 0 0 1 2.647.993L18.56 8" />
                            <path d="M6 10V8" />
                            <path d="M6 14v1" />
                            <path d="M6 19v2" />
                            <rect x="2" y="8" width="20" height="13" rx="2" />
                        </svg> Total Tickets Sold Over Time</h2>
                </header>
                <section><canvas id="totalSalesChart"></canvas></section>
            </div>
            <br>
            <div class="card">
                <header>
                    <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-chart-column-big-icon lucide-chart-column-big">
                            <path d="M3 3v16a2 2 0 0 0 2 2h16" />
                            <rect x="15" y="5" width="4" height="12" rx="1" />
                            <rect x="7" y="8" width="4" height="9" rx="1" />
                        </svg> Detailed Statistics</h2>
                </header>
                <section>
                    <div class="days-table-container">
                        <table class="table">
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
                                <?php if ($shows):
                                    foreach (
                                        $shows["dates"]
                                        as $dateId => $dateData
                                    ):

                                        $sold =
                                            $dateData["tickets"] -
                                            $dateData["tickets_available"];
                                        $income =
                                            $sold *
                                            floatval($dateData["price"]);
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(
                                                $dateData["date"]
                                            ); ?></td>
                                            <td><?php echo $dateData[
                                                "tickets"
                                            ]; ?></td>
                                            <td><?php echo $sold; ?></td>
                                            <td><?php echo $dateData[
                                                "tickets_available"
                                            ]; ?></td>
                                            <td>€<?php echo $dateData[
                                                "price"
                                            ]; ?></td>
                                            <td>€<?php echo number_format(
                                                $income,
                                                2
                                            ); ?></td>
                                        </tr>
                                        <?php
                                    endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <!-- Manage Event -->
        <div id="event" style="display: none">
            <div class="card">
                <header>
                    <h1>Manage Current Event</h1>
                </header>
                <section>
                    <form id="eventForm" class="form grid gap-6">
                        <div class="grid gap-2">
                            <label class="label" for="orgaName">Organization Name</label>
                            <input type="text" id="orgaName"
                                value="<?php echo $shows ? htmlspecialchars($shows['orga_name']) : ''; ?>">
                            <p class="text-muted-foreground text-sm">This is your public display name.</p>
                        </div>
                        <div class="grid gap-2">
                            <label class="label" for="eventTitle">Event Title</label>
                            <input type="text" id="eventTitle"
                                value="<?php echo $shows ? htmlspecialchars($shows['title']) : ''; ?>">
                        </div>
                        <div class="grid gap-2">
                            <label class="label" for="eventSubtitle">Subtitle</label>
                            <input type="text" id="eventSubtitle"
                                value="<?php echo $shows ? htmlspecialchars($shows['subtitle']) : ''; ?>">
                        </div>
                        <div class="grid gap-2">
                            <label class="label" for="bannerUrl">Banner URL</label>
                            <input type="text" id="bannerUrl"
                                value="<?php echo $shows ? htmlspecialchars($shows['banner']) : ''; ?>">
                        </div>
                        <div class="flex items-start gap-3">
                            <input type="checkbox" id="storeLock" <?php echo $shows && $shows['store_lock'] ? 'checked' : ''; ?>>
                            <div class="flex flex-col gap-1">
                                <label class="leading-snug" for="storeLock">Store Lock</label>
                                <p class="text-muted-foreground text-sm">This will lock the store
                                    frontend and prevent new purchases.</p>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <label class="label" for="paymentMethods">Payment Methods</label>
                            <select id="paymentMethods" class="input">
                                <option value="both" <?php echo ($shows && isset($shows['payment_methods']) && $shows['payment_methods'] === 'both') ? 'selected' : ''; ?>>
                                    Both (Cash & Online)</option>
                                <option value="cash" <?php echo ($shows && isset($shows['payment_methods']) && $shows['payment_methods'] === 'cash') ? 'selected' : ''; ?>>
                                    Cash only</option>
                                <option value="online" <?php echo ($shows && isset($shows['payment_methods']) && $shows['payment_methods'] === 'online') ? 'selected' : ''; ?>>
                                    Online only</option>
                            </select>
                            <p class="text-muted-foreground text-sm">Select which payment methods should be available in
                                the store.</p>
                        </div>

                        <div style="margin-top: 1.5rem;">
                            <button class="btn-outline" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-save-icon lucide-save">
                                    <path
                                        d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
                                    <path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7" />
                                    <path d="M7 3v4a1 1 0 0 0 1 1h7" />
                                </svg>
                                Save changes
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>

        <!-- Manage Days -->
        <div id="days" style="display: none;">
            <h1>Manage Days</h1>
            <div class="card">
                <header>
                    <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-calendar-plus-icon lucide-calendar-plus">
                            <path d="M16 19h6" />
                            <path d="M16 2v4" />
                            <path d="M19 16v6" />
                            <path d="M21 12.598V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8.5" />
                            <path d="M3 10h18" />
                            <path d="M8 2v4" />
                        </svg> Add New Day</h2>
                </header>
                <section>
                    <form id="addDayForm" class="form grid gap-6">
                        <div class="grid gap-3"><label class="label" for="newDate">Date</label><input type="date"
                                id="newDate" class="input"></div>
                        <div class="grid gap-3"><label class="label" for="newTime">Time</label><input type="time"
                                id="newTime" class="input" value="20:00"></div>
                        <div class="grid gap-3"><label class="label" for="newTickets">Total Tickets</label><input
                                type="number" id="newTickets" class="input" value="100"></div>
                        <div class="grid gap-3"><label class="label" for="newPrice">Price (€)</label><input
                                type="number" step="0.01" id="newPrice" class="input" value="15.00"></div>

                        <div style="margin-top: 1rem;">
                            <button type="submit" class="btn-outline"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-circle-plus-icon lucide-circle-plus">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M8 12h8" />
                                    <path d="M12 8v8" />
                                </svg> Add
                                Day</button>
                        </div>
                    </form>
                </section>
            </div>
            <br>
            <div class="card gap-6">
                <header>
                    <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-calendar-days-icon lucide-calendar-days">
                            <path d="M8 2v4" />
                            <path d="M16 2v4" />
                            <rect width="18" height="18" x="3" y="4" rx="2" />
                            <path d="M3 10h18" />
                            <path d="M8 14h.01" />
                            <path d="M12 14h.01" />
                            <path d="M16 14h.01" />
                            <path d="M8 18h.01" />
                            <path d="M12 18h.01" />
                            <path d="M16 18h.01" />
                        </svg> Current Days</h2>
                </header>
                <section>
                    <div class="days-table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Total</th>
                                    <th>Available</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="daysTableBody">
                                <?php if ($shows):
                                    foreach (
                                        $shows["dates"]
                                        as $dateId => $dateData
                                    ): ?>
                                        <tr data-date-id="<?php echo $dateId; ?>">
                                            <td><input type="date" class="input" value="<?php echo $dateData[
                                                "date"
                                            ]; ?>"></td>
                                            <td><input type="time" class="input" value="<?php echo $dateData[
                                                "time"
                                            ]; ?>"></td>
                                            <td><input type="number" class="input" value="<?php echo $dateData[
                                                "tickets"
                                            ]; ?>"></td>
                                            <td><input type="number" class="input" value="<?php echo $dateData[
                                                "tickets_available"
                                            ]; ?>">
                                            </td>
                                            <td><input type="number" step="0.01" class="input" value="<?php echo $dateData[
                                                "price"
                                            ]; ?>"></td>
                                            <td>
                                                <button class="btn-icon-outline" type="submit" action-type="update-day"
                                                    data-date-id="<?php echo $dateId; ?>"><svg
                                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round"
                                                        class="lucide lucide-save-icon lucide-save">
                                                        <path
                                                            d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
                                                        <path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7" />
                                                        <path d="M7 3v4a1 1 0 0 0 1 1h7" />
                                                    </svg></button>
                                                <button class="btn-icon-destructive" type="submit" action-type="delete-day"
                                                    data-date-id="<?php echo $dateId; ?>"><svg
                                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round"
                                                        class="lucide lucide-trash2-icon lucide-trash-2">
                                                        <path d="M10 11v6" />
                                                        <path d="M14 11v6" />
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                                        <path d="M3 6h18" />
                                                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                    </svg></button>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <!-- Image Management -->
        <div id="images" style="display: none">
            <h1><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-image-icon lucide-image" style="margin-right: 10px;">
                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                    <circle cx="9" cy="9" r="2" />
                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                </svg> Image Management</h1>

            <div class="card">
                <header>
                    <h2><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-images-icon lucide-images">
                            <path d="M18 22H4a2 2 0 0 1-2-2V6" />
                            <path d="m22 13-1.296-1.296a2.41 2.41 0 0 0-3.408 0L14 16" />
                            <circle cx="12" cy="8" r="2" />
                            <path d="m20 6 2 2-3-3-2 2 3 3Z" />
                            <rect width="12" height="16" x="2" y="2" rx="2" />
                        </svg> Current Images</h2>
                </header>
                <section>
                    <div class="grid gap-6">
                        <div class="grid gap-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">Banner Image</h3>
                                <button class="btn-outline" onclick="document.getElementById('bannerUpload').click()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-upload-icon lucide-upload">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="17,8 12,3 7,8" />
                                        <line x1="12" x2="12" y1="3" y2="15" />
                                    </svg>
                                    Upload Banner
                                </button>
                                <input type="file" id="bannerUpload" style="display: none;" accept="image/*"
                                    onchange="uploadImage('banner')">
                            </div>
                            <div id="bannerPreview" class="image-preview-container">
                                <p class="text-muted-foreground">No banner image uploaded</p>
                            </div>
                        </div>

                        <div class="grid gap-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">Logo Image</h3>
                                <button class="btn-outline" onclick="document.getElementById('logoUpload').click()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-upload-icon lucide-upload">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="17,8 12,3 7,8" />
                                        <line x1="12" x2="12" y1="3" y2="15" />
                                    </svg>
                                    Upload Logo
                                </button>
                                <input type="file" id="logoUpload" style="display: none;" accept="image/*"
                                    onchange="uploadImage('logo')">
                            </div>
                            <div id="logoPreview" class="image-preview-container">
                                <p class="text-muted-foreground">No logo image uploaded</p>
                            </div>
                        </div>


                    </div>
                </section>
            </div>
        </div>
    </main>

    <div id="notificationContainer" class="notification-container"></div>

    <script>
        const API_BASE_URL = '<?php echo API_BASE_URL; ?>';
        const API_KEY = '<?php echo API_KEY; ?>';

        function showToast(message, type = 'success', duration = 3000) {
            const category = type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'success';
            const title = type === 'error' ? 'Error' : type === 'warning' ? 'Warning' : 'Success';

            document.dispatchEvent(new CustomEvent('basecoat:toast', {
                detail: {
                    config: {
                        category: category,
                        title: title,
                        description: message,
                        duration: duration,
                        cancel: {
                            label: 'Dismiss'
                        }
                    }
                }
            }));
        }
        function switchSection(targetId) {
            if (targetId === 'logout') {
                window.location.href = 'logout.php';
                return;
            }

            document.querySelectorAll('main > div').forEach(s => s.style.display = 'none');
            document.getElementById(targetId).style.display = 'block';
            localStorage.setItem('currentAdminSection', targetId);
        }

        document.querySelectorAll('aside a').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const target = e.target.closest('a').getAttribute('data-section');
                switchSection(target);

                setTimeout(() => location.reload(), 100);
            });
        });

        const saved = localStorage.getItem('currentAdminSection');
        if (saved && document.getElementById(saved)) switchSection(saved);

        
        function initCharts() {
            const dates = <?php echo json_encode(array_keys($stats["soldByDate"] ?? [])); ?>;
            const soldData = <?php echo json_encode(array_values($stats["soldByDate"] ?? [])); ?>;
            const availableData = <?php echo json_encode(array_values($stats["availableByDate"] ?? [])); ?>;

            if (dates.length === 0) return;

            
            const generateColors = (baseColor, count) => {
                const colors = [];
                for (let i = 0; i < count; i++) {
                    const shade = 100 - Math.min(80, i * 15); 
                    colors.push(`hsl(${baseColor}, 70%, ${shade}%)`);
                }
                return colors;
            };

            
            const salesCtx = document.getElementById('salesChart')?.getContext('2d');
            if (salesCtx) {
                new Chart(salesCtx, {
                    type: 'pie',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Tickets Sold',
                            data: soldData,
                            backgroundColor: generateColors(270, dates.length), 
                            borderColor: '#ffffff20',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'white',
                                    font: { size: 13 }
                                }
                            },
                            tooltip: {
                                titleColor: 'white',
                                bodyColor: 'white',
                                backgroundColor: 'rgba(30, 30, 40, 0.9)',
                                padding: 10,
                                callbacks: {
                                    label: function (context) {
                                        return `${context.label}: ${context.parsed} tickets sold`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            
            const availCtx = document.getElementById('availabilityChart')?.getContext('2d');
            if (availCtx) {
                new Chart(availCtx, {
                    type: 'pie',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Available Tickets',
                            data: availableData,
                            backgroundColor: generateColors(140, dates.length), 
                            borderColor: '#ffffff20',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'white',
                                    font: { size: 13 }
                                }
                            },
                            tooltip: {
                                titleColor: 'white',
                                bodyColor: 'white',
                                backgroundColor: 'rgba(30, 30, 40, 0.9)',
                                padding: 10,
                                callbacks: {
                                    label: function (context) {
                                        return `${context.label}: ${context.parsed} tickets available`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            
            loadStatsData();
        }

        async function loadStatsData() {
            try {
                const response = await fetch(`${API_BASE_URL}/api/stats`, {
                    headers: { 'Authorization': API_KEY }
                });
                const data = await response.json();

                if (data.status === 'success' && data.data) {
                    const stats = data.data;

                    
                    const incomeDates = Object.keys(stats.income_by_date || {});
                    const incomeValues = Object.values(stats.income_by_date || {});

                    const incomeCtx = document.getElementById('incomeChart')?.getContext('2d');
                    if (incomeCtx && incomeDates.length > 0) {
                        new Chart(incomeCtx, {
                            type: 'line',
                            data: {
                                labels: incomeDates,
                                datasets: [{
                                    label: 'Income (€)',
                                    data: incomeValues,
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { labels: { color: 'white' } },
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                return '€' + context.parsed.y.toFixed(2);
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            color: 'white',
                                            callback: function (value) {
                                                return '€' + value.toFixed(2);
                                            }
                                        },
                                        grid: { color: 'rgba(255,255,255,0.1)' }
                                    },
                                    x: {
                                        ticks: { color: 'white' },
                                        grid: { color: 'rgba(255,255,255,0.1)' }
                                    }
                                }
                            }
                        });
                    }

                    
                    const salesDates = Object.keys(stats.sales_by_date || {});
                    const salesValues = Object.values(stats.sales_by_date || {});

                    const salesCtx = document.getElementById('totalSalesChart')?.getContext('2d');
                    if (salesCtx && salesDates.length > 0) {
                        new Chart(salesCtx, {
                            type: 'line',
                            data: {
                                labels: salesDates,
                                datasets: [{
                                    label: 'Tickets Sold',
                                    data: salesValues,
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    borderColor: 'rgb(16, 185, 129)',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { labels: { color: 'white' } },
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                return context.parsed.y + ' tickets';
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { color: 'white' },
                                        grid: { color: 'rgba(255,255,255,0.1)' }
                                    },
                                    x: {
                                        ticks: { color: 'white' },
                                        grid: { color: 'rgba(255,255,255,0.1)' }
                                    }
                                }
                            }
                        });
                    }
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        if (document.getElementById('statistics').style.display !== 'none' || localStorage.getItem('currentAdminSection') === 'statistics') {
            setTimeout(initCharts, 200);
        }

        document.querySelector('aside a[data-section="statistics"]').addEventListener('click', () => setTimeout(initCharts, 200));

        
        document.getElementById('eventForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = e.submitter;
            btn.disabled = true;
            const data = {
                orga_name: document.getElementById('orgaName').value,
                title: document.getElementById('eventTitle').value,
                subtitle: document.getElementById('eventSubtitle').value,
                banner: document.getElementById('bannerUrl').value,
                store_lock: document.getElementById('storeLock').checked,
                payment_methods: document.getElementById('paymentMethods').value,
                dates: {}
            };
            <?php if ($shows) {
                foreach ($shows["dates"] as $id => $d): ?>
                    data.dates["<?php echo $id; ?>"] = {
                        date: "<?php echo $d[
                            "date"
                        ]; ?>", time: "<?php echo $d["time"]; ?>", tickets: <?php echo $d[
                                "tickets"
                            ]; ?>, tickets_available: <?php echo $d[
                                 "tickets_available"
                             ]; ?>, price: "<?php echo $d["price"]; ?>"
                    };
                <?php endforeach;
                ;
            } ?>

            fetch(`${API_BASE_URL}/api/show/edit`, {
                method: 'POST',
                headers: { 'Authorization': API_KEY, 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    if (res.status === 'success') {
                        showToast('Event updated!');
                        localStorage.setItem('currentAdminSection', 'event');
                        //setTimeout(() => location.reload(), 500);
                    } else showToast('Error: ' + (res.message || 'Unknown'), 'error');
                })
                .catch(err => {
                    btn.disabled = false;
                    showToast('Network error', 'error');
                });
        });

        
        document.getElementById('addDayForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const data = {
                dateId: 'day_' + Date.now(),
                date: document.getElementById('newDate').value,
                time: document.getElementById('newTime').value,
                tickets: document.getElementById('newTickets').value,
                price: document.getElementById('newPrice').value
            };
            fetch('api.php?action=add_day', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        showToast('Day added!');
                        localStorage.setItem('currentAdminSection', 'days');
                        setTimeout(() => location.reload(), 1000);
                    } else showToast('Error: ' + res.message, 'error');
                });
        });

        function showConfirmationDialog(message) {
            const dialog = document.getElementById('confirmation-dialog');
            const description = dialog.querySelector('#confirmation-dialog-description');
            const confirmBtn = dialog.querySelector('#confirmation-dialog-confirm');

            return new Promise((resolve) => {
                description.textContent = message;
                confirmBtn.onclick = () => {
                    dialog.close();
                    resolve(true);
                };
                dialog.addEventListener('close', () => resolve(false), { once: true });
                dialog.showModal();
            });
        }

        
        document.getElementById('daysTableBody').addEventListener('click', async function (e) {
            const btn = e.target.closest('button');
            if (!btn) return;
            const dateId = btn.getAttribute('data-date-id');
            const row = btn.closest('tr');

            if (btn.getAttribute('action-type') === "delete-day") {
                const confirmed = await showConfirmationDialog('Delete this day? This action cannot be undone.');
                if (!confirmed) return;

                fetch('api.php?action=delete_day', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ dateId })
                })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            showToast('Day deleted!');
                            row.remove();
                        } else {
                            showToast('Error: ' + res.message, 'error');
                        }
                    })
                    .catch(err => {
                        showToast('Network error', 'error');
                    });

            } else if (btn.getAttribute('action-type') === "update-day") {
                const row = btn.closest('tr');
                const dateId = btn.getAttribute('data-date-id');

                const dateInput = row.querySelector('input[type="date"]').value;
                const timeInput = row.querySelector('input[type="time"]').value;
                const numberInputs = row.querySelectorAll('input[type="number"]');

                if (numberInputs.length < 3) {
                    showToast('Input fields incomplete.', 'error');
                    return;
                }

                const ticketsInput = parseInt(numberInputs[0].value, 10);     
                const availableInput = parseInt(numberInputs[1].value, 10);  
                const priceInput = parseFloat(numberInputs[2].value);        

                
                if (!dateInput || !timeInput || isNaN(ticketsInput) || isNaN(availableInput) || isNaN(priceInput)) {
                    showToast('Please fill in all fields correctly.', 'error');
                    return;
                }

                if (availableInput > ticketsInput || availableInput < 0 || ticketsInput < 0) {
                    showToast('Invalid input. Please check the values you entered.', 'error');
                    return;
                }

                const updateData = {
                    dateId: dateId,
                    date: dateInput,
                    time: timeInput,
                    tickets: ticketsInput,
                    available: availableInput,
                    price: priceInput.toFixed(2)
                };

                fetch('api.php?action=update_day', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updateData)
                })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            showToast('Day updated successfully!');
                        } else {
                            showToast('Error: ' + (res.message || 'Unknown error'), 'error');
                        }
                    })
                    .catch(err => {
                        console.error('Update error:', err);
                        showToast('Network or server error.', 'error');
                    });
            }
        });

        
        async function loadCurrentImages() {
            const timestamp = new Date().getTime();

            try {
                
                const bannerPreview = document.getElementById('bannerPreview');
                bannerPreview.innerHTML = `<img src="${API_BASE_URL}/api/image/get/banner.png?t=${timestamp}" alt="Banner" class="max-w-full max-h-[300px] object-contain" onerror="this.onerror=null; this.src='${API_BASE_URL}/api/image/get/banner.png';">`;

                
                const logoPreview = document.getElementById('logoPreview');
                logoPreview.innerHTML = `<img src="${API_BASE_URL}/api/image/get/logo.png?t=${timestamp}" alt="Logo" class="max-w-full max-h-[200px] object-contain" onerror="this.onerror=null; this.src='${API_BASE_URL}/api/image/get/logo.png';">`;
            } catch (error) {
                console.error('Error loading images:', error);
                showToast('Error loading images', 'error');
            }
        }


        async function uploadImage(type) {
            const fileInput = document.getElementById(`${type}Upload`);
            const file = fileInput.files[0];

            if (!file) {
                showToast('Keine Datei ausgewählt', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', type);

            try {
                const response = await fetch(`${API_BASE_URL}/api/image/upload`, {
                    method: 'POST',
                    headers: {
                        'Authorization': API_KEY,
                    },
                    body: formData,
                });

                const responseText = await response.text();
                console.log('Server-Antwort:', responseText);

                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    throw new Error(`Server antwortete nicht mit JSON: ${responseText.substring(0, 100)}...`);
                }

                if (data.status === 'success') {
                    showToast(`${type} erfolgreich hochgeladen!`, 'success');
                    await loadCurrentImages();
                } else {
                    throw new Error(data.message || 'Unbekannter Fehler');
                }
            } catch (error) {
                console.error('Upload-Fehler:', error);
                showToast(`Fehler: ${error.message}`, 'error');
            }
        }


        
        document.querySelector('aside a[data-section="images"]').addEventListener('click', () => {
            setTimeout(loadCurrentImages, 200);
        });

        
        if (window.location.hash === '#images' || localStorage.getItem('currentAdminSection') === 'images') {
            setTimeout(loadCurrentImages, 200);
        }
    </script>
</body>

</html>