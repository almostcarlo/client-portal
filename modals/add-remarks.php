<!-- MODAL 1: UPDATE STATUS (Replicated from image_60e6a2.png) -->
<div class="modal fade" id="addRemarksModal" tabindex="-1" aria-labelledby="addRemarksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 bg-light">
                <h5 class="modal-title fw-bold text-dark" id="addRemarksModalLabel">Add Remarks</h5>
                <button type="button" class="btn-close" data-bs-close="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="addRemarksAlert" class="alert alert-danger d-none d-flex align-items-center rounded-1 border-0 shadow-sm" role="alert" style="background-color: #fdf2f2; color: var(--iris-button-red);">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <div id="addRemarksAlertText" class="small fw-semibold">
                        Invalid username or password. Please try again.
                    </div>
                </div>

                <div id="addRemarksSuccessAlert"
                    class="alert alert-success d-none d-flex align-items-center rounded-1 border-0 shadow-sm"
                    role="alert"
                    style="background-color: #f2fdf5; color: var(--iris-button-green);">

                    <i class="bi bi-check-circle-fill fs-5 me-2"></i>

                    <div id="addRemarksSuccessAlertText" class="small fw-semibold">
                        Remarks saved successfully.
                    </div>

                </div>

                <form id="form-remarks" method="POST">
                    <input type="hidden" name="" id="remarks-cont-id" value="">
                    <input type="hidden" name="applicant_id" id="remarks-applicant-id" value="">
                    <input type="hidden" name="action" value="add-remarks">
                    <!-- <div class="mb-3">
                        <label for="cvStatus" class="form-label fw-bold small text-secondary">CV Status</label>
                        <select id="cvStatus" class="form-select">
                            <option value="" selected>Please select</option>
                            <option value="Shortlisted">Shortlisted</option>
                            <option value="Selected">Selected</option>
                            <option value="Rejected">Rejected</option>
                            <option value="On Hold">On Hold</option>
                        </select>
                    </div> -->
                    <div class="mb-3">
                        <!-- <label for="remarks" class="form-label fw-bold small text-secondary">Remarks</label> -->
                        <textarea id="remarks" name="remarks" class="form-textarea form-control" rows="4" placeholder="enter your remarks here"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-center border-top-0 bg-light">
                <button type="button" id="save-remarks-btn" class="btn btn-iris-blue px-4"><i class="bi bi-floppy-fill me-1"></i> Save</button>
                <button type="button" class="btn btn-iris-red px-4" data-bs-dismiss="modal"><i class="bi bi-x-circle-fill me-1"></i> Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {

        $('#addRemarksModal').on('hidden.bs.modal', function () {
            // CLEAR FORM
            $('#remarks, #remarks-cont-id').val('');

            // HIDE ALERT
            $('#addRemarksAlert, #addRemarksSuccessAlert').addClass('d-none');
        });

        $('#save-remarks-btn').on('click', function(){
            $('#addRemarksAlert, #addRemarksSuccessAlert').addClass('d-none');

            if($('#remarks').val() == ''){
                $('#addRemarksAlert').removeClass('d-none');
                $('#addRemarksAlertText').text('Remarks must not be empty.');
                $('#remarks').focus();
            }else{
                $.post('process.php', $('#form-remarks').serialize(), function (response) {
                    if(response.success){
                        // POPULATE REMARKS COLUMN
                        let remarks_cont = $('#remarks-cont-id').val();
                        $(remarks_cont).html($('#remarks').val()+'<br><span class="text-muted"><small><?=$_SESSION['iris-clients']['username']?> &bull; <?=date("F d, Y h:ia")?></small></span>');

                        $('#addRemarksSuccessAlert').removeClass('d-none');

                        setTimeout(function() {
                            bootstrap.Modal.getInstance(document.getElementById('addRemarksModal')).hide();
                        }, 1600);
                    }else{
                        $('#addRemarksAlert').removeClass('d-none');
                        $('#addRemarksAlertText').text(response.message);
                    }
                });


            }
        });

    });
</script>