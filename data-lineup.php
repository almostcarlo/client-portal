<?php
    require_once 'includes/auth_check.php';
    require_once 'includes/mysqli_helper.php';
    require_once 'includes/functions.php';

    // $q_manpower_rid = " and mr.manpower_rid in (6381,6399,6339)";
    // $q_manpower_rid = " and mr.manpower_rid in (6381)";
    $q_manpower_rid = " and mr.manpower_rid in ({$_SESSION['iris-clients']['manpower_rid']})";
    $_GET['mr_id'] = $_SESSION['iris-clients']['manpower_rid'];

	$q = "select mr.manpower_rid, mr.ref_no, prin.name as principal, princo.name as company, mr.act1, mr.act2, mr.act3, mr.pm, v.name as venue, v.venue_id, v.venue_date, v.date_status,
			mr.client_ref, mr.rm, mr.pm, mr.transmittal_auto_send, mr.transmittal_auto_send_day, mr.ra, mp.mr_pos_id, mp.position_id, pos.name as position
			from manpower_r mr
			left join principals prin on mr.principal_id = prin.principal_id
			left join prin_company princo on mr.prin_company_id = princo.prin_company_id
			left join venue v on mr.manpower_rid = v.manpower_rid
            left join mr_position mp on mr.manpower_rid = mp.manpower_rid
            left join positions pos on mp.position_id = pos.position_id
			where 1
			and mr.status = 'Active'
			and mr.pldb = 0
            {$q_manpower_rid}
            group by mr.manpower_rid
			order by mr.ref_no, v.venue_date, pos.name";
    $r = fetch_data($q);

    $manpower_option = array();
    if($r){
        foreach ($r as $mr_info){
            $company = $mr_info['principal'];
            $manpower_option[$mr_info['manpower_rid']] = array('manpower_rid'=>$mr_info['manpower_rid'], 'ref_no'=>$company .' - ('. str_replace("SBG.FR","SBG.PL",$mr_info['ref_no']) . ' '. $mr_info['client_ref'] .')');
        }
    }

    if($_GET['mr_id'] <> ''){
        $q_position = "";
        if($_GET['mr_pos_id'] <> ''){
            $q_position = "and l.mr_pos_id = {$_GET['mr_pos_id']}";
        }

        if(!empty($_SESSION['iris-clients']['branch'])){
            $q_branch = "and p.branch_id in  ({$_SESSION['iris-clients']['branch']})";
        }else{
            $q_branch = "";
        }
        

        $q_lineup = "select l.applicant_id, l.line_up_id, p.fname, p.mname, p.lname, p.status, p.cellphone, p.birthdate, p.email, p.branch_id, pos.name as position, l.date_create, br.name as branch,
                    l.mob_result, l.confirm_rsr, l.evaluator, l.creator, l.for_confirm_initial, mr.pm, l.cv_sent_date, mr.act3, l.transmittal_no, l.transmittal_prep, p.cv_applicant, p.cv_applicant_pdf,
                    mp.mr_pos_rso as te_incharge, p.height1, p.height2, p.weight1, p.weight2, ppt.passportno, ppt.passport_exp, mr.principal_id, ppt.passport_date, mr.prin_company_id, l.mr_pos_id
                    From line_up l
                    left join manpower_r mr on l.manpower_rid = mr.manpower_rid
                    left join personal p on l.applicant_id = p.applicant_id
                    left join mr_position mp on l.mr_pos_id = mp.mr_pos_id
                    left join positions pos on mp.position_id = pos.position_id
                    left join branch br on p.branch_id = br.id
                    left join doc_library ppt on l.applicant_id = ppt.applicant_id and ppt.type_id = 3
                    where 1
                    #and (l.lineup_status = 'FL' OR (mr.act3 = 'CV' and l.lineup_status = 'CL' and l.transmittal_prep is null))
                    #and l.lineup_status = 'FL'
                    #and l.lineup_status in ('FL','CL','CR')
                    and l.lineup_status in ('CR')
                    and l.manpower_rid = {$_GET['mr_id']}
                    and p.status in ('ACTIVE','RESERVED','EXCESS','POOLING') 
                    #and l.mob_result = 'AV'
                    and l.mob_result not in ('NI','NA')
                    and mp.status_mr = 'Active'
                    #and l.for_confirm_initial != 'yes'
                    and l.standby = 0
                    and l.line_up_id not in (select line_up_id
                                            From line_up
                                            where applicant_id = l.applicant_id
                                            and ((interview_status in ('Selected') and acceptance in ('Accepted','Standby','Back Up','Negotiate','Declined')) or interview_status in ('Rejected','Not Interviewed') ))
                    #and (p.branch_id in (select branch_id From manpower_branch where manpower_rid = l.manpower_rid and branch_status = 1) or p.branch_id = 0)
                    and (br.status = 1 or p.branch_id = 0)
                    {$q_position}
                    {$q_branch}";
        $r_lineup = fetch_data($q_lineup);

        if($r_lineup){
            $aApp_ids = [];
            $cat_list = [];
            foreach($r_lineup as $item){
                array_push($aApp_ids, "'".$item['applicant_id']."'");
                $cat_list[$item['mr_pos_id']] = $item['position'];
            }

            $sApp_ids = implode(',', $aApp_ids);

            $q_cl = "select * from applicant_remarks
                    where 1
                    and applicant_id in ({$sApp_ids})
                    and remark_type = 'CL'
                    group by applicant_id
                    order by add_date desc";
            $r_cl = fetch_data($q_cl,[],'',false,'applicant_id');

            asort($cat_list);

            if($_GET['mr_pos_id'] == ''){
                $_SESSION['iris-clients']['mr-categories'] = $cat_list;
            }

            $cat_list = $_SESSION['iris-clients']['mr-categories'];
        }
    }

    if(isset($_GET['excel'])){
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=lineup-report.xls");
        header("Cache-Control: public");
    }
?>

<?php if(!isset($_GET['excel'])):?>
    <!-- Filter Section -->
    <div class="card shadow-sm border-0 p-3 mb-4 bg-white">
        <span class="fw-bold mb-2 text-dark">EWPCI Client Portal - Confirmed Line up</span>
        <div class="row g-3 align-items-center">
            <div class="col-md-1">
                <label for="categoryFilter" class="col-form-label fw-bold small">MR Reference:</label>
            </div>
            <div class="col-md-4 col-sm-12">
                <select id="list-mr" name="manpower_rid" class="form-select form-select-sm">
                    <option value="">Please select</option>
                    <?php foreach($manpower_option as $this_mr):?>
                        <option value="<?=$this_mr['manpower_rid']?>" <?=($_GET['mr_id'] && $_GET['mr_id']==$this_mr['manpower_rid'])?"selected":""?>><?=$this_mr['ref_no']?></option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>

        <div class="row g-3 align-items-center">
            <div class="col-md-1">
                <label for="categoryFilter" class="col-form-label fw-bold small">Category:</label>
            </div>
            <div class="col-md-4 col-sm-12">
                <select id="list-category" name="mr_pos_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach($cat_list as $mr_pos_id => $category):?>
                        <option value="<?=$mr_pos_id?>" <?=($_GET['mr_pos_id'] && $_GET['mr_pos_id']==$mr_pos_id)?"selected":""?>><?=$category?></option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>

        <div class="row g-3 align-items-center">
            <div class="col-md-1">
                <label for="categoryFilter" class="col-form-label fw-bold small">&nbsp;</label>
            </div>
            <div class="col-auto">
                <!-- <button class="btn btn-sm btn-iris-blue px-3" id="btn-search"><i class="bi bi-search me-1"></i> Search</button> -->
                <button class="btn btn-sm btn-iris-red px-3" onclick="generate_lineup();"><i class="bi bi-x-circle me-1"></i> Clear</button>
            </div>
        </div>
    </div>
<?php endif;?>

<?php
    if($_GET['mr_id'] <> ''):
?>
    <!-- Main Candidates Table -->
    <div class="card shadow-sm border-0 p-3 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <?php if(!isset($_GET['excel'])):?>
                <h5 class="m-0 fw-bold text-dark">List of Applicants</h5>
                <a href="data-lineup.php?excel=true&mr_id=<?=$_GET['mr_id']?>&mr_pos_id=<?=$_GET['mr_pos_id']?>" target="_blank" class="btn btn-sm btn-iris-green" id="btnExportExcel"><i class="bi bi-file-earmark-excel me-1"></i> Extract to Excel</a>
            <?php else:?>
                <h5 class="m-0 fw-bold text-dark">EWPCI - Client Portal - Line-up Report</h5>
            <?php endif;?>
        </div>

        <div class="table-responsive">
            <table id="lineupTable" class="table table-bordered table-striped align-middle mb-0" style="font-size: 0.9rem;">
                <thead class="table-light text-uppercase text-secondary" style="font-size: 0.8rem;">
                    <tr>
                        <th style="width: 40px;">SN.</th>
                        <th class="text-center">Comp. No.</th>
                        <th>Applicant Name</th>
                        <th>CV Link</th>
                        <th class="text-center">Mobile No.</th>
                        <th>DOB / Age</th>
                        <th>Email</th>
                        <th>Position</th>
                        <th>Branch</th>
                        <th>Remarks</th>
                        <!-- <th>Education</th>
                        <th>Employment History</th>
                        <th class="text-center">Work Exp. (Yrs)</th> -->
                        <?php if(!isset($_GET['excel'])):?>
                            <th class="text-center" style="width: 120px;">Action</th>
                        <?php endif;?>
                    </tr>
                </thead>

                <?php if($r_lineup && count($r_lineup) > 0):?>
                    <tbody>
                        <?php
                            $n = 0;
                            foreach($r_lineup as $lineup):
                                $link_cv = "";
                                if($lineup['cv_applicant'] <> ''){
                                    $link_cv = IRIS_CV_URL.$lineup['cv_applicant'];
                                }

                                $link_pdf = "";
                                if($lineup['cv_applicant_pdf'] <> ''){
                                    $link_pdf = IRIS_CV_URL.$lineup['cv_applicant_pdf'];
                                }


                            ?>
                            <tr>
                                <td><?=$n+=1?>.</td>
                                <td class="text-center"><?=$lineup['applicant_id']?></td>
                                <td> <?=strtoupper($lineup['lname'])?>, <?=strtoupper($lineup['fname'])?> <?=strtoupper($lineup['mname'])?></td>
                                <td>
                                    <a href="<?=$link_cv?>" target="_blank"><?=basename($link_cv)?></a>
                                    <br>
                                    <a href="<?=$link_pdf?>" target="_blank"><?=basename($link_pdf)?></a>
                                </td>
                                <td class="text-center"><?=$lineup['cellphone']?></td>
                                <td><?=$lineup['birthdate']?> <br><span class="text-muted">(<?=calculate_age($lineup['birthdate'])?> Yrs)</span></td>
                                <td><?=$lineup['email']?></td>
                                <td><span class="badge bg-secondary"><?=$lineup['position']?></span></td>
                                <td><?=$lineup['branch']?></td>
                                <td id="cont-remarks-<?=$n?>">
                                    <?php if(array_key_exists($lineup['applicant_id'], $r_cl) && $r_cl[$lineup['applicant_id']]['remarks'] <> ''):?>
                                        <?=$r_cl[$lineup['applicant_id']]['remarks']?>
                                        <br>
                                        <span class="text-muted">
                                            <small><?=$r_cl[$lineup['applicant_id']]['added_by']?> &bull; <?=date("F d, Y h:ia", strtotime($r_cl[$lineup['applicant_id']]['add_date']))?></small>
                                        </span>
                                    <?php endif;?>
                                </td>
                                <!-- <td>College Level (Undergraduate)</td>
                                <td>
                                    <small>
                                        <strong>Pipe fitter</strong> - ANDRITZ hydro GMBH (Liberia)<br>
                                        <strong>Pipe fitter</strong> - ANDRITZ hydro GMBH (Malawi)
                                    </small>
                                </td>
                                <td class="text-center fw-bold text-muted">9.3</td> -->
                                <?php if(!isset($_GET['excel'])):?>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <!-- <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewCandidateModal" title="View Full Details"><i class="bi bi-eye"></i></button> -->
                                            <button class="btn btn-iris-blue" onclick="remarks_modal('<?=$lineup['applicant_id']?>','cont-remarks-<?=$n?>');" title="Add Remarks"><i class="bi bi-pencil-square"></i> Remarks</button>
                                        </div>
                                    </td>
                                <?php endif;?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php else:?>
                    <tbody>
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i class="bi bi-search fs-4 d-block mb-2"></i>
                                No candidates found.
                            </td>
                        </tr>
                    </tbody>
                <?php endif;?>
            </table>
        </div>
    </div>
<?php endif;?>

<script>
    $(function () {
        // $('#btn-search').on('click', function(){
        //     let mr_id = $('#list-mr').val();
        //     let mr_pos_id = $('#list-category').val();

        //     generate_lineup(mr_id, mr_pos_id);
        // });

        $('#list-mr, #list-category').on('change', function(){

            if($(this).attr('id') == 'list-mr'){
                $('#list-category').val('');
            }

            let mr_id = $('#list-mr').val();
            let mr_pos_id = $('#list-category').val();

            generate_lineup(mr_id, mr_pos_id);
        });
    });

    function remarks_modal(applicant_id, remarks_id){
        $('#remarks-cont-id').val('#'+remarks_id);
        $('#remarks-applicant-id').val(applicant_id);
        var modal = new bootstrap.Modal(document.getElementById('addRemarksModal'));
        modal.show();
    }
</script>