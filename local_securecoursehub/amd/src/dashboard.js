define(['core/config'], function(cfg) {
    return {
        init: function() {
            document.addEventListener('click', async function(e) {

                // Safely resolve wwwroot and sesskey
                const wwwroot = (cfg && cfg.wwwroot) ? cfg.wwwroot : (window.M && window.M.cfg ? window.M.cfg.wwwroot : '');
                const sesskey = (cfg && cfg.sesskey) ? cfg.sesskey : (window.M && window.M.cfg ? window.M.cfg.sesskey : '');

                // --- TEACHER: UPDATE STATUS / RESPONSE VIA AJAX ---
                if (e.target && e.target.classList.contains('sch-update-btn')) {
                    e.preventDefault();
                    const btn = e.target;
                    const id = btn.getAttribute('data-id');
                    const statusSelect = document.getElementById('status-select-' + id);
                    const responseInput = document.getElementById('response-input-' + id);
                    const feedbackEl = document.getElementById('feedback-' + id);

                    const newStatus = statusSelect.value;
                    const newResponse = responseInput.value;

                    btn.disabled = true;
                    if (feedbackEl) {
                        feedbackEl.textContent = 'Saving...';
                        feedbackEl.className = 'text-info small';
                    }

                    try {
                        const res = await fetch(wwwroot + '/local/securecoursehub/ajax.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'update_teacher_request',
                                id: parseInt(id, 10),
                                status: newStatus,
                                response: newResponse,
                                sesskey: sesskey
                            })
                        });

                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            throw new Error(data.error || 'Server rejected the update.');
                        }

                        // Update DOM elements dynamically without page reload
                        const badge = document.getElementById('badge-status-' + id);
                        if (badge) {
                            badge.textContent = data.status;
                            badge.className = 'badge ' + (data.status === 'resolved' ? 'badge-success' : (data.status === 'inprogress' ? 'badge-warning' : 'badge-secondary'));
                        }

                        if (feedbackEl) {
                            feedbackEl.textContent = 'Updated successfully!';
                            feedbackEl.className = 'text-success small';
                        }
                    } catch (err) {
                        if (feedbackEl) {
                            feedbackEl.textContent = 'Error: ' + err.message;
                            feedbackEl.className = 'text-danger small';
                        } else {
                            alert('Error: ' + err.message);
                        }
                    } finally {
                        btn.disabled = false;
                    }
                }

                // --- STUDENT: DELETE OPEN REQUEST VIA AJAX ---
                if (e.target && e.target.classList.contains('sch-delete-btn')) {
                    e.preventDefault();
                    if (!confirm('Are you sure you want to delete this open request?')) {
                        return;
                    }

                    const btn = e.target;
                    const id = btn.getAttribute('data-id');
                    const row = document.getElementById('request-row-' + id);

                    try {
                        const res = await fetch(wwwroot + '/local/securecoursehub/ajax.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'delete_student_request',
                                id: parseInt(id, 10),
                                sesskey: sesskey
                            })
                        });

                        const data = await res.json();
                        if (!res.ok || !data.success) {
                            throw new Error(data.error || 'Could not delete request.');
                        }

                        // Smoothly remove table row from page
                        if (row) {
                            row.remove();
                        }

                        else{ //if table is empty/there are no rows
                            const tbody = document.querySelector('tbody');

                            if(tbody && tbody.children.length === 0){

                                const table = tbody.closest('table');
                                if(table){
                                    table.remove();
                                }

                                const message = document.createElement('p');
                                message.className = 'alert alert-info';
                                message.textContent = 'No help requests found.';

                                document.body.appendChild(message);
                            }

                        }

                    } catch (err) {
                        alert('Delete failed: ' + err.message);
                    }
                }
            });
        }
    };
});