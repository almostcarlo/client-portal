<?php
    session_start();

    if (
        !isset($_SESSION['iris-clients']['logged_in']) ||
        $_SESSION['iris-clients']['logged_in'] !== true
    ) {
        header('Location: login.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWPCI - Client Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        /* Color Scheme Inspired by image_60e683.png and image_60e69e.png */
        :root {
            --iris-primary: #0a5485;     /* Main header dark blue */
            --iris-button-blue: #3386b7; /* Action/Save blue */
            --iris-button-red: #d9534f;  /* Close/Clear red */
            --iris-success: #00a65a;     /* Excel/Selection green */
            --iris-bg: #eef2f5;          /* Soft gray background */
        }

        body {
            background-color: var(--iris-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .navbar-brand-custom {
            background-color: var(--iris-primary);
            color: white !important;
            padding: 12px 20px;
        }

        .btn-iris-blue {
            background-color: var(--iris-button-blue);
            color: white;
            border: none;
        }
        .btn-iris-blue:hover {
            background-color: #286fa3;
            color: white;
        }

        .btn-iris-red {
            background-color: var(--iris-button-red);
            color: white;
            border: none;
        }
        .btn-iris-red:hover {
            background-color: #c9302c;
            color: white;
        }

        .btn-iris-green {
            background-color: var(--iris-success);
            color: white;
            border: none;
        }
        .btn-iris-green:hover {
            background-color: #008d4c;
            color: white;
        }

        .card-counter {
            border-left: 5px solid var(--iris-primary);
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .table-responsive {
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .nav-tabs .nav-link.active {
            border-top: 3px solid var(--iris-primary);
            font-weight: bold;
        }

        /* Dynamic venue grid layout */
        .venue-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1rem;
        }

        /* Equal height venue card */
        .venue-card {
            border-left: 4px solid var(--iris-button-blue);
            display: flex;
            justify-content: space-between;
            align-items: stretch; /* Forces left and right columns to match height */
            min-height: 100px;
            background: #f8f9fa;
            border-radius: 6px;
            overflow: hidden;
        }

        .venue-info {
            flex: 1;
            min-width: 0; /* Critical for CSS text-truncation inside flexbox */
            padding: 0.85rem 1rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Address handling - maximum 2 lines with ellipsis */
        .venue-address {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.75rem;
            color: #6c757d;
            line-height: 1.25;
        }

        /* Applicant badge box - ensures uniform alignment */
        .venue-count-badge {
            background: #ffffff;
            min-width: 90px;
            padding: 0.5rem 0.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; /* Centers number vertically */
            border-left: 1px solid #e9ecef;
        }
    </style>

    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.bootstrap5.min.css">
</head>
<body>

    <!-- TOP HEADER (Mirroring image_60e683.png) -->
    <nav class="navbar navbar-dark shadow-sm p-0" style="background-color: var(--iris-primary);">
        <div class="container-fluid d-flex justify-content-between align-items-center py-2 px-4">
            <span class="navbar-brand mb-0 h1 d-flex align-items-center">
                <!-- <i class="bi bi-gear-fill me-2"></i> 
                IRIS <span class="fw-light fs-6 ms-2 d-none d-md-inline">| Interactive Recruitment Information System</span> -->
                <i class="bi bi-building-gear me-2 text-danger"></i>
                EAST WEST PLACEMENT CENTER, INC. <span class="fw-light fs-6 ms-2 d-none d-md-inline">| Client Portal</span>
            </span>
            <div class="text-white fs-6">
                <i class="bi bi-person-circle me-1"></i> Welcome, <span class="fw-bold"><?=ucwords(strtolower($_SESSION['iris-clients']['username']))?></span>
                <button class="btn btn-sm btn-link text-white ms-2 text-decoration-none" id="logoutBtn"><i class="bi bi-box-arrow-right"></i> Log out</button>
            </div>
        </div>
    </nav>

    <div class="container-fluid my-4 px-4">
        
        <!-- MAIN NAVIGATION TABS FOR EASY USE -->
        <ul class="nav nav-tabs mb-4" id="portalTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active text-dark" id="dashboard-tab" onclick="generate_dashboard();" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab"><i class="bi bi-speedometer2 me-2"></i>Dashboard Summary</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-dark" id="candidates-tab" onclick="generate_lineup();" data-bs-toggle="tab" data-bs-target="#candidates" type="button" role="tab"><i class="bi bi-people-fill me-2"></i>Line-up Report</button>
            </li>
        </ul>

        <div class="tab-content" id="portalTabsContent">
            
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <div id="cont-dashboard"></div>
            </div>

            <div class="tab-pane fade" id="candidates" role="tabpanel">
                <div id="cont-lineup"></div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(function () {

            $('#logoutBtn').on('click', function () {
                window.location.href = 'logout.php';
            });

            generate_dashboard();
        });

        function generate_dashboard(){
            $.get('data-dashboard.php', function (dashboard_data) {

                $('#cont-dashboard').html(dashboard_data);

                var dashboardTab = new bootstrap.Tab(
                    document.querySelector('[data-bs-target="#dashboard"]')
                );

                dashboardTab.show();

            }).fail(function (xhr) {

                if (xhr.status === 401) {
                    window.location.href = 'index.php';
                    return;
                }

                $('#cont-dashboard').html(
                    '<div class="alert alert-danger">' +
                    'Unable to load dashboard.' +
                    '</div>'
                );

                console.error(xhr.responseText);
            });
        }

        function generate_lineup(mr_id=null, mr_pos_id=null, venue_id=null){
            $.get('data-lineup.php', {mr_id:mr_id, mr_pos_id:mr_pos_id, venue_id:venue_id}, function (lineup_data) {

                $('#cont-lineup').html(lineup_data);

                // Initialize DataTables AFTER the table has been inserted
                $('#lineupTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [
                        [10, 20, 50, 100, -1],
                        [10, 20, 50, 100, 'All']
                    ],
                    columnDefs: [
                        {
                            targets: -1,
                            orderable: false
                        }
                    ]
                });

                var lineupTab = new bootstrap.Tab(
                    document.querySelector('[data-bs-target="#candidates"]')
                );

                lineupTab.show();

            }).fail(function (xhr) {

                if (xhr.status === 401) {
                    window.location.href = 'index.php';
                    return;
                }

                $('#cont-lineup').html(
                    '<div class="alert alert-danger">' +
                    'Unable to load Line up.' +
                    '</div>'
                );

                console.error(xhr.responseText);
            });
        }
    </script>

    <?php include("modals/add-remarks.php");?>
</body>
</html>