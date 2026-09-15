<?php
    include("includes/config.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWPCI - Client Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        /* Color Scheme Inspired by original layouts */
        :root {
            --iris-primary: #0a5485;     /* Main header dark blue */
            --iris-button-blue: #3386b7; /* Action/Save blue */
            --iris-button-red: #d9534f;  /* Close/Clear/Error red */
            --iris-success: #00a65a;     /* Excel/Selection green */
            --iris-bg: #eef2f5;          /* Soft gray background */
        }

        body {
            background-color: var(--iris-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
        }

        /* Login Screen Specific Styles */
        .login-container {
            max-width: 450px;
            margin-top: 10%;
        }
        .login-card {
            background: white;
            border-radius: 0px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: none;
        }
        .input-group-text {
            background-color: #fff;
            color: #6c757d;
        }

        /* App Layout Styles */
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
    </style>

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>

    <nav class="navbar navbar-dark shadow-sm p-0" style="background-color: var(--iris-primary);">
        <div class="container-fluid d-flex justify-content-between align-items-center py-2 px-4">
            <span class="navbar-brand mb-0 h1 d-flex align-items-center">
                <!-- <i class="bi bi-gear-fill me-2"></i>  -->
                <!-- <i class="bi bi-globe"></i> -->
                <i class="bi bi-building-gear me-2 text-danger"></i>
                EAST WEST PLACEMENT CENTER, INC. <span class="fw-light fs-6 ms-2 d-none d-md-inline">| Client Portal</span>
            </span>
            <div id="userProfileHeader" class="text-white fs-6 d-none">
                <i class="bi bi-person-circle me-1"></i> Welcome, <span class="fw-bold">Indman</span>
                <button class="btn btn-sm btn-link text-white ms-2 text-decoration-none" onclick="logout()"><i class="bi bi-box-arrow-right"></i> Log out</button>
            </div>
        </div>
    </nav>

    <div id="loginSection" class="container d-flex flex-column align-items-center">
        <div class="login-container w-100 px-3">
            <h2 class="text-center fw-normal mb-4" style="color: var(--iris-primary); font-size: 2.5rem;">Client Login</h2>
            
            <div class="card login-card p-4">
                <p class="text-muted text-center small mb-3">Please enter your username and password.</p>
                
                <div id="loginAlert" class="alert alert-danger d-none d-flex align-items-center rounded-1 border-0 shadow-sm" role="alert" style="background-color: #fdf2f2; color: var(--iris-button-red);">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <div id="loginAlertText" class="small fw-semibold">
                        Invalid username or password. Please try again.
                    </div>
                </div>

                <form id="loginForm" onsubmit="handleLogin(event)" method="POST">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control text-dark" placeholder="Username" required id="username" name="username">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    </div>
                    
                    <div class="input-group mb-4">
                        <input type="password" class="form-control text-dark" placeholder="Password" required id="password" name="password">
                        <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                    </div>

                    <div class="col-md-10 col-md-offset-1 col-sm-11 col-sm-offset-1">
                        <div class="cf-turnstile" data-sitekey="<?php echo CF_SITE_KEY;?>"></div>
                        <br>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-iris-blue px-4 py-2 rounded-1">Sign In</button>
                        &nbsp;
                        <a href="login.php" class="btn btn-iris-red px-4 py-2 rounded-1">Clear</a>
                    </div>
                </form>
            </div>
            <!-- <div class="text-center mt-3 text-muted small">
                Tip: Enter <span class="font-monospace fw-bold text-dark">invalid</span> as the password to test error banner styling.
            </div> -->
        </div>
    </div>

    <div id="mainPortalSection" class="container-fluid my-4 px-4 d-none">
        <ul class="nav nav-tabs mb-4" id="portalTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active text-dark" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab"><i class="bi bi-speedometer2 me-2"></i>Dashboard Summary</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-dark" id="candidates-tab" data-bs-toggle="tab" data-bs-target="#candidates" type="button" role="tab"><i class="bi bi-people-fill me-2"></i>Candidate Line-up</button>
            </li>
        </ul>

        <div class="tab-content" id="portalTabsContent">
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <h3 class="mb-4 text-secondary">Recruitment Dashboard</h3>
                
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card card-counter bg-white p-3">
                            <div class="text-muted small uppercase fw-bold">Total Candidates</div>
                            <div class="fs-2 fw-bold text-dark">1,245</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card card-counter bg-white p-3" style="border-left-color: #ffc107;">
                            <div class="text-muted small uppercase fw-bold">Pending Review</div>
                            <div class="fs-2 fw-bold text-warning">48</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card card-counter bg-white p-3" style="border-left-color: var(--iris-success);">
                            <div class="text-muted small uppercase fw-bold">Selected / Shortlisted</div>
                            <div class="fs-2 fw-bold text-success">182</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card card-counter bg-white p-3" style="border-left-color: var(--iris-button-red);">
                            <div class="text-muted small uppercase fw-bold">Rejected</div>
                            <div class="fs-2 fw-bold text-danger">95</div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="card shadow-sm border-0 p-4 bg-white">
                            <h5 class="card-title mb-3 fw-bold text-dark"><i class="bi bi-clock-history me-2"></i>Recently Added Candidates</h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Candidate Name</th>
                                            <th>Position</th>
                                            <th>Date Added</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>VICENTE, MARLON GALLEGO</strong></td>
                                            <td>C/H, PIPING</td>
                                            <td>2026-06-23</td>
                                            <td><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewCandidateModal">View Profile</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm border-0 p-4 bg-white">
                            <h5 class="card-title mb-3 fw-bold text-dark"><i class="bi bi-pie-chart-fill me-2"></i>Quick Actions</h5>
                            <div class="d-grid gap-2">
                                <button class="btn btn-iris-blue text-start py-2" onclick="document.getElementById('candidates-tab').click();">
                                    <i class="bi bi-search me-2"></i> Search Candidate Line-up
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="candidates" role="tabpanel">
                <div class="card shadow-sm border-0 p-3 mb-4 bg-white">
                    <span class="fw-bold mb-2 text-dark">EWPCI Client Portal - Confirmed Line up</span>
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label for="categoryFilter" class="col-form-label fw-bold small">Category:</label>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <select id="categoryFilter" class="form-select form-select-sm">
                                <option value="C/H,PIPING" selected>C/H, PIPING</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-iris-blue px-3"><i class="bi bi-search me-1"></i> Search</button>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 p-3 bg-white">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="table-light text-uppercase text-secondary" style="font-size: 0.8rem;">
                                <tr>
                                    <th>SN.</th>
                                    <th>Applicant Name</th>
                                    <th>Position</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1.</td>
                                    <td><strong>VICENTE, MARLON GALLEGO</strong></td>
                                    <td><span class="badge bg-secondary">C/H, PIPING</span></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-iris-blue" data-bs-toggle="modal" data-bs-target="#updateStatusModal"><i class="bi bi-pencil-square"></i> Status</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark">CV Status Updating</h5>
                    <button type="button" class="btn-close" data-bs-close="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">CV Status</label>
                            <select class="form-select">
                                <option value="">Please select</option>
                                <option value="Selected">Selected</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Remarks</label>
                            <textarea class="form-control" rows="3" placeholder="enter your remarks here (optional)"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-center bg-light">
                    <button type="button" class="btn btn-iris-blue px-4" data-bs-dismiss="modal">Save</button>
                    <button type="button" class="btn btn-iris-red px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // function handleLogin(event) {
        //     event.preventDefault();
            
        //     const passwordInput = document.getElementById('password').value;
        //     const alertBox = document.getElementById('loginAlert');
        //     const alertText = document.getElementById('loginAlertText');

        //     // Trigger conditional error state if user enters "invalid" as the password
        //     if (passwordInput.toLowerCase() === 'invalid') {
        //         alertText.innerText = "Invalid password. Access Denied. Please double-check your credentials.";
        //         alertBox.classList.remove('d-none');
        //         return;
        //     }

        //     // Hide alert if successful
        //     alertBox.classList.add('d-none');

        //     // Transition UI views
        //     document.getElementById('loginSection').classList.add('d-none');
        //     document.getElementById('mainPortalSection').classList.remove('d-none');
        //     document.getElementById('userProfileHeader').classList.remove('d-none');
        // }

        async function handleLogin(event) {

            event.preventDefault();

            const form = document.getElementById('loginForm');
            const alertBox = document.getElementById('loginAlert');
            const alertText = document.getElementById('loginAlertText');

            const formData = new FormData(form);

            try {

                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {

                    window.location.href = result.redirect;

                } else {

                    alertText.textContent = result.message;

                    alertBox.classList.remove('d-none');

                    document.getElementById('password').value = '';
                    document.getElementById('password').focus();
                }

            } catch (error) {

                console.error(error);

                alertText.textContent =
                    'Unable to connect to the server. Please try again.';

                alertBox.classList.remove('d-none');
            }
        }

        function logout() {
            document.getElementById('loginSection').classList.remove('d-none');
            document.getElementById('mainPortalSection').classList.add('d-none');
            document.getElementById('userProfileHeader').classList.add('d-none');
            document.getElementById('loginForm').reset();
        }
    </script>
</body>
</html>