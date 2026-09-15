<?php
    require_once 'includes/auth_check.php';
    require_once 'includes/mysqli_helper.php';

    $manpower_rid = $_SESSION['iris-clients']['manpower_rid'];
    // $q_branch = "and p.branch_id in  (2)";

    if(!empty($_SESSION['iris-clients']['branch'])){
        $q_branch = "and p.branch_id in  ({$_SESSION['iris-clients']['branch']})";
    }else{
        $q_branch = "";
    }

    $q = "select l.mr_pos_id, l.applicant_id, l.lineup_status, mp.no_required, l.line_up_id, p.fname, p.mname, p.lname, p.status, p.cellphone, p.birthdate, p.email,
            p.branch_id, mp.position_id, l.date_create, l.mob_result, l.confirm_rsr, l.evaluator, l.creator,
            l.for_confirm_initial, mr.pm, mr.act3, l.transmittal_prep, l.cv_status, l.clientinterview, l.venue_id, l.date_reported, l.interview_date, l.interview_status,
            l.acceptance, p.reporting_status, l.remark_interview, l.contract_type, l.salary, l.currency_per, l.currency_id, l.grade, p.religion, l.lodging, l.transport,
            l.food, l.project, p.work_exp_abroad, p.work_exp_local, p.method_report, p.source_id, l.tradetest, p.sex, l.interview_rating, l.ex_cc,
            l.badge_no, l.badge_area, l.probation_period, l.contract_period, l.hours_work, l.overtime,
            l.currency_id_accept, l.currency_per_accept, l.currency_salary_accept, l.vacation_pay, l.total_package, l.leave_probition, l.leave_ent, l.ticket_ent,
            l.special_con, l.insurance, l.gratuity, l.bonus, l.subcon_division, l.remark, pos.name as position, l.for_confirm_date
            From line_up l
            left join manpower_r mr on l.manpower_rid = mr.manpower_rid
            left join personal p on l.applicant_id = p.applicant_id
            left join mr_position mp on l.mr_pos_id = mp.mr_pos_id
            left join positions pos on mp.position_id = pos.position_id
            where 1
            and l.lineup_status in ('LU','FL','CL','CR')
            and l.manpower_rid in ({$manpower_rid})
            and mp.status_mr = 'Active'
            and l.standby = 0
            #and (p.branch_id in (select branch_id From manpower_branch where manpower_rid = l.manpower_rid and branch_status = 1) or p.branch_id = 0)
            {$q_branch}
            and l.mob_result not in ('NI','NA')
            order by l.for_confirm_date desc";
    $lineup = fetch_data($q);

    $categories = fetch_data("select mp.mr_pos_id, mp.position_id, pos.name as position, mp.no_required, mp.status_mr
                                from mr_position mp
                                left join positions pos on mp.position_id = pos.position_id
                                where mp.manpower_rid in ({$manpower_rid})
                                and mp.status_mr = 'Active'
                                order by pos.name");

    // $lineup_branches[0] = array('name' => 'No Branch', 'color' => '');
    $list_lu = [];
    $list_fl = [];
    $list_cl = [];
    $list_cr = [];
    $list_rp = [];
    $list_awi = [];
    $recently_added = [];

    // $list_cl_per_venue = [];
    // $list_cr_per_venue = [];
    // $list_rp_per_venue = [];
    // $list_awi_per_venue = [];
    // $list_selected_per_venue = [];
    // $list_accepted_per_venue = [];
    // $list_negotiate_per_venue = [];
    // $list_declined_per_venue = [];
    // $list_standby_per_venue = [];
    // $list_backup_per_venue = [];
    // $list_rejected_per_venue = [];
    // $list_notint_per_venue = [];

    $list_selected = [];
    $list_accepted = [];
    $list_negotiate = [];
    $list_declined = [];
    $list_standby = [];
    $list_backup = [];
    $list_rejected = [];
    $list_notint = [];

    $total_lu = 0;
    $total_fl = 0;
    $total_cl = 0;
    $total_cr = 0;
    $total_rp = 0;
    $total_awi = 0;
    $total_selected = 0;
    $total_accepted = 0;
    $total_negotiate = 0;
    $total_declined = 0;
    $total_standby = 0;
    $total_backup = 0;
    $total_rejected = 0;
    $total_notint = 0;
    $total_per_venue = [];

    if($lineup){
        foreach($lineup as $info){
            // if($mr_info[1]['act1'] == 'FR'){
            //     // $venue_id = $info['venue_id']."|".$info['interview_date'];
            // }else{
            //     // $venue_id = $info['venue_id'];
            // }

            // if($info['branch_id']<>0){
            //     // $lineup_branches[$info['branch_id']] = array('name' => $info['branch'], 'color' => $info['background-color']);
            // }else{
            //     /* UPDATE BRANCH */
            //     // $this_branch = check_applicant_branch($info['applicant_id'], '');
            //     // $info['branch_id'] = ($this_branch['applied_branch']<>'')?$this_branch['applied_branch']:0;
            // }

            // RECENTLY ADDED
            if(!empty($info['for_confirm_date'])){
                $recently_added[$info['applicant_id']] = ['name' => $info['lname'].", ".$info['fname']." ".$info['mname'],
                                                          'pos' => $info['position'],
                                                          'date' => date("M d, Y", strtotime($info['for_confirm_date'])),];
            }

            if((in_array($info['interview_status'], ['Selected',]) && in_array($info['acceptance'], ['Accepted','Standby','Back Up','Negotiate','Declined'])) || in_array($info['interview_status'],['Rejected','Not Interviewed'])){
                /* GET REPORTED APPLICANTS */
                if($info['interview_date'] <> '0000-00-00' && $info['date_reported'] <> '0000-00-00' && $info['clientinterview'] && $info['venue_id'] <> 0){
                    if($info['date_create'] == $info['interview_date']){
                        $list_awi[$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                        $total_awi++;

                        // $list_awi_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                    }else{
                        if($info['reporting_status'] == '4' || $info['date_reported'] == $info['interview_date']){
                            $list_rp[$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                            $total_rp++;

                            // $list_rp_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                        }
                    }
                }

                if($info['interview_status'] == 'Selected'){
                    $list_selected[$info['mr_pos_id']][$info['line_up_id']] = $info;
                    // $list_selected_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                    // $total_selected++;
                    $app_ids[] = $info['applicant_id'];

                    if($info['acceptance'] == 'Accepted'){
                        $list_accepted[$info['mr_pos_id']][$info['line_up_id']] = $info;
                        // $list_accepted_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                        $total_accepted++;
                        $app_ids[] = $info['applicant_id'];
                    }else if($info['acceptance'] == 'Negotiate'){
                        $list_negotiate[$info['mr_pos_id']][$info['line_up_id']] = $info;
                        // $list_negotiate_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                        $total_negotiate++;
                        $app_ids[] = $info['applicant_id'];
                    }else if($info['acceptance'] == 'Declined'){
                        $list_declined[$info['mr_pos_id']][$info['line_up_id']] = $info;
                        // $list_declined_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                        $total_declined++;
                        $app_ids[] = $info['applicant_id'];
                    }else if($info['acceptance'] == 'Standby'){
                        $list_standby[$info['mr_pos_id']][$info['line_up_id']] = $info;
                        // $list_standby_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                        $total_standby++;
                        $app_ids[] = $info['applicant_id'];
                    }else if($info['acceptance'] == 'Back Up'){
                        $list_backup[$info['mr_pos_id']][$info['line_up_id']] = $info;
                        // $list_backup_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                        $total_backup++;
                        $app_ids[] = $info['applicant_id'];
                    }
                }else if($info['interview_status'] == 'Rejected'){
                    $list_rejected[$info['mr_pos_id']][$info['line_up_id']] = $info;
                    // $list_rejected_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                    $total_rejected++;
                    $app_ids[] = $info['applicant_id'];
                }else if($info['interview_status'] == 'Not Interviewed'){
                    $list_notint[$info['mr_pos_id']][$info['line_up_id']] = $info;
                    // $list_notint_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                    $total_notint++;
                    $app_ids[] = $info['applicant_id'];
                }

                $total_per_venue[$info['venue_id']][$info['line_up_id']] = $info['line_up_id'];
            }else{
                /* NO INTERVIEW STATUS YET */
                if(in_array($info['status'], ['ACTIVE','RESERVED','EXCESS','POOLING'])){
                    if($info['lineup_status'] == 'LU' && $info['for_confirm_initial'] != 'yes'){
                        $list_lu[$info['position_id']][$info['line_up_id']] = $info;
                        $total_lu++;
                    }

                    if($info['lineup_status'] == 'FL'){
                        $list_fl[$info['position_id']][$info['line_up_id']] = $info;
                        $total_fl++;
                    }

                    if($info['lineup_status'] == 'CL'){
                        // if($info['act3'] == 'CV' && $info['transmittal_prep'] != 'Y'){
                            /* COUNT AS FL IF TRANMITTAL NOT PREPPED */
                            // $list_fl[$info['position_id']][$info['line_up_id']] = $info;
                            // $total_fl++;
                        // }else{
                            $list_cl[$info['position_id']][$info['line_up_id']] = $info;
                            $total_cl++;

                            // $list_cl_per_venue[$venue_id][$info['position_id']][$info['line_up_id']] = $info;
                        // }
                    }

                    if($info['lineup_status'] == 'CR'){
                        if($info['act3'] == 'CV'){
                            if($info['cv_status'] == 'Sent'){
                                $list_cr[$info['position_id']][$info['line_up_id']] = $info;
                                // $total_cr++;

                                // $list_cr_per_venue[$venue_id][$info['position_id']][$info['line_up_id']] = $info;
                            }else{
                                continue;
                            }
                        }else{
                            $list_cr[$info['position_id']][$info['line_up_id']] = $info;
                            // $total_cr++;

                            // $list_cr_per_venue[$venue_id][$info['position_id']][$info['line_up_id']] = $info;
                        }
// if($info['mr_pos_id']==55769){
// 	var_dump($info['applicant_id']);
// }

                        /* GET REPORTED APPLICANTS */
                        if($info['interview_date'] <> '0000-00-00' && $info['date_reported'] <> '0000-00-00' && $info['clientinterview'] && $info['venue_id'] <> 0){
                            if($info['date_create'] == $info['interview_date']){
                                $list_awi[$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                                $total_awi++;

                                // $list_awi_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                            }else{
                                if($info['reporting_status'] == '4' || $info['date_reported'] == $info['interview_date']){
                                    $list_rp[$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                                    $total_rp++;

                                    // $list_rp_per_venue[$venue_id][$info['mr_pos_id']][$info['line_up_id']] = $info['applicant_id'];
                                }
                            }
                        }

                        $total_per_venue[$info['venue_id']][$info['line_up_id']] = $info['line_up_id'];
                    }
                }
            }
        }
// var_dump($categories);
// var_dump($list_cr);
        foreach($categories as $key => $pos_item){
            // /* COMPUTE SHORTAGE */
            // $shortage = $pos_item['no_required']-count($list_accepted[$pos_item['mr_pos_id']]);
            // $shortage = ($shortage<0)?0:$shortage;

            // /* COMPUTE EXCESS */
            // $excess = count($list_accepted[$pos_item['mr_pos_id']])-$pos_item['no_required'];
            // $excess = ($excess>0)?$excess:0;
            // $total_qty_req += $pos_item['no_required'];
            // $total_shortage += $shortage;
            // $total_excess += $excess;
            // $total_accepted += count($list_accepted[$pos_item['mr_pos_id']]);
            $total_selected += (array_key_exists($pos_item['mr_pos_id'], $list_selected))?count($list_selected[$pos_item['mr_pos_id']]):0;
            // $total_rp += count($list_rp[$pos_item['mr_pos_id']]);
            $total_cr += (array_key_exists($pos_item['position_id'], $list_cr))?count($list_cr[$pos_item['position_id']]):0;
        }

        /* GET VENUE */
        $venues = fetch_data("select venue_id, name, venue_date, address from venue where manpower_rid = ? and date_status = ? order by venue_date asc", [$manpower_rid, 1], 'ss', false, "venue_id");
        // var_dump($venues);
    }

    $recently_added = array_slice($recently_added, 0, 5);
?>
<h3 class="mb-4 text-secondary">Recruitment Dashboard</h3>

<!-- Counters Row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-counter bg-white p-3">
            <div class="text-muted small uppercase fw-bold">Total Applicants</div>
            <div class="fs-2 fw-bold text-dark"><?=($total_cr + $total_selected + $total_rejected)?></div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-counter bg-white p-3 style" style="border-left-color: #ffc107;">
            <div class="text-muted small uppercase fw-bold">Pending Interview</div>
            <div class="fs-2 fw-bold text-warning"><?=$total_cr?></div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-counter bg-white p-3" style="border-left-color: var(--iris-success);">
            <div class="text-muted small uppercase fw-bold">Selected / Shortlisted</div>
            <div class="fs-2 fw-bold text-success"><?=$total_selected?></div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-counter bg-white p-3" style="border-left-color: var(--iris-button-red);">
            <div class="text-muted small uppercase fw-bold">Rejected</div>
            <div class="fs-2 fw-bold text-danger"><?=$total_rejected?></div>
        </div>
    </div>
</div>

<?php if(!empty($venues)):?>
<!-- NEW: SUMMARY PER VENUE PLACEHOLDER -->
    <div class="card shadow-sm border-0 p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title m-0 fw-bold text-dark">
                <i class="bi bi-geo-alt-fill me-2 text-danger"></i>Summary per Venue
            </h5>
            <!-- <span class="badge bg-light text-dark border">Dynamic Location Breakdown</span> -->
        </div>

        <div class="venue-summary-grid" id="venueSummaryContainer">
                
            <?php foreach($venues as $venue_id => $vItem):?>
                <!-- Venue 1 -->
                <div class="venue-card shadow-sm">
                    <div class="venue-info">
                        <div class="fw-bold text-uppercase text-dark small text-truncate"><?=$vItem['name']?></div>
                        <div class="text-muted small my-1">
                            <i class="bi bi-calendar3 me-1"></i><?=date("M d, Y", strtotime($vItem['venue_date']))?>
                        </div>
                        <?php if(!empty($vItem['address'])):?>
                            <div class="venue-address" title="<?=$vItem['address']?>">
                                <i class="bi bi-building me-1"></i><?=$vItem['address']?>
                            </div>
                        <?php endif;?>
                    </div>
                    <div class="venue-count-badge">
                        <span class="fs-2 fw-bold text-primary lh-1"><?=(!empty($total_per_venue[$venue_id])) ? count($total_per_venue[$venue_id]) : 0?></span>
                        <span class="text-muted small mt-1" style="font-size: 0.75rem;">Applicants</span>
                    </div>
                </div>
            <?php endforeach;?>

        </div>
    </div>
<?php endif;?>

<!-- Recent Activity & Summary Tables -->
<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0 p-4 bg-white">
            <h5 class="card-title mb-3 fw-bold text-dark"><i class="bi bi-clock-history me-2"></i>Recently Added Applicants</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Date Added</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recently_added as $id => $info):?>
                            <tr>
                                <td><strong><?=$info['name']?></strong></td>
                                <td><?=$info['pos']?></td>
                                <td><?=$info['date']?></td>
                                <td>
                                    <!-- <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewCandidateModal">View Profile</button> -->
                                     -
                                </td>
                            </tr>
                        <?php endforeach;?>
                        <!-- <tr>
                            <td><strong>VICENTE, MARLON GALLEGO</strong></td>
                            <td>C/H, PIPING</td>
                            <td>2026-06-23</td>
                            <td><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewCandidateModal">View Profile</button></td>
                        </tr>
                        <tr>
                            <td><strong>CABALE, ROMMEL MONDIJAR</strong></td>
                            <td>F/M, SCAFFOLDING</td>
                            <td>2026-06-22</td>
                            <td><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewCandidateModal">View Profile</button></td>
                        </tr> -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 p-4 bg-white">
            <h5 class="card-title mb-3 fw-bold text-dark"><i class="bi bi-pie-chart-fill me-2"></i>Quick Actions</h5>
            <p class="text-muted small">Select an action below to manage your requirements easily.</p>
            <div class="d-grid gap-2">
                <button class="btn btn-iris-blue text-start py-2" onclick="document.getElementById('candidates-tab').click();">
                    <i class="bi bi-search me-2"></i> Search Applicant - Line-up Report
                </button>
                <button class="btn btn-iris-green text-start py-2" onclick="document.getElementById('candidates-tab').click();">
                    <i class="bi bi-file-earmark-excel me-2"></i> Generate Excel file
                </button>
            </div>
        </div>
    </div>
</div>