// local_securecoursehub/amd/src/dashboard.js
export const init = () => {
    document.querySelectorAll('.update-status-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            const requestId = e.target.getAttribute('data-id');
            const newStatus = 'resolved'; // Hardcoded for this example
            
            try {
                const response = await fetch(M.cfg.wwwroot + '/local/securecoursehub/ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_teacher_request',
                        id: requestId,
                        status: newStatus,
                        response: 'Addressed by instructor.',
                        sesskey: M.cfg.sesskey
                    })
                });
                
                const result = await response.json();
                
                if (!response.ok || !result.success) {
                    throw new Error(result.error || 'The request failed.');
                }
                
                // Update the visible page dynamically without a full reload
                document.getElementById('status-' + requestId).textContent = newStatus;
                document.getElementById('response-' + requestId).textContent = 'Addressed by instructor.';
            } catch (error) {
                alert(error.message);
            }
        });
    });
};