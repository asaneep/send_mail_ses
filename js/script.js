document.addEventListener('DOMContentLoaded', function() {
    // Handle recipient type selection
    const recipientTypeSelect = document.getElementById('recipientType');
    const textareaRecipients = document.getElementById('textareaRecipients');
    const fileRecipients = document.getElementById('fileRecipients');
    
    recipientTypeSelect.addEventListener('change', function() {
        if (this.value === 'textarea') {
            textareaRecipients.style.display = 'block';
            fileRecipients.style.display = 'none';
        } else {
            textareaRecipients.style.display = 'none';
            fileRecipients.style.display = 'block';
        }
    });
    
    // Handle email form submission
    const emailForm = document.getElementById('emailForm');
    const sendButton = document.getElementById('sendButton');
    const sendProgress = document.getElementById('sendProgress');
    const progressBar = sendProgress.querySelector('.progress-bar');
    const sendResults = document.getElementById('sendResults');
    
    emailForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Prepare form data
        const formData = new FormData(emailForm);
        
        // Show progress bar
        sendButton.disabled = true;
        sendProgress.style.display = 'block';
        progressBar.style.width = '0%';
        progressBar.setAttribute('aria-valuenow', 0);
        sendResults.innerHTML = '<div class="alert alert-info">Sending emails... Please wait.</div>';
        
        // Send AJAX request
        fetch('send_mail.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Update progress bar to 100%
                updateProgressBar(100);
                
                // Display success message
                let resultHtml = '<div class="alert alert-success">' + data.message + '</div>';
                
                // Display detailed results if available
                if (data.details && data.details.length > 0) {
                    resultHtml += '<h5 class="mt-3">Detailed Results:</h5>';
                    resultHtml += '<div class="list-group">';
                    
                    data.details.forEach(detail => {
                        const statusClass = detail.status === 'success' ? 'email-status-success' : 'email-status-error';
                        resultHtml += `<div class="list-group-item">
                            <strong>${detail.email}</strong>: 
                            <span class="${statusClass}">${detail.message}</span>
                        </div>`;
                    });
                    
                    resultHtml += '</div>';
                }
                
                sendResults.innerHTML = resultHtml;
                
                // Update history tab
                loadEmailHistory();
            } else {
                // Display error message
                sendResults.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            sendResults.innerHTML = '<div class="alert alert-danger">An error occurred while sending emails. Please try again.</div>';
        })
        .finally(() => {
            sendButton.disabled = false;
        });
    });
    
    // Function to validate the form
    function validateForm() {
        const sender = document.getElementById('sender').value.trim();
        const subject = document.getElementById('subject').value.trim();
        const message = document.getElementById('message').value.trim();
        const recipientType = recipientTypeSelect.value;
        
        if (!sender || !subject || !message) {
            sendResults.innerHTML = '<div class="alert alert-danger">Please fill in all required fields.</div>';
            return false;
        }
        
        if (recipientType === 'textarea') {
            const recipients = document.getElementById('recipients').value.trim();
            if (!recipients) {
                sendResults.innerHTML = '<div class="alert alert-danger">Please enter at least one recipient email address.</div>';
                return false;
            }
        } else {
            const recipientFile = document.getElementById('recipientFile').files;
            if (recipientFile.length === 0) {
                sendResults.innerHTML = '<div class="alert alert-danger">Please upload a file with recipient email addresses.</div>';
                return false;
            }
        }
        
        return true;
    }
    
    // Function to update progress bar
    function updateProgressBar(percentage) {
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
    }
    
    // Handle settings form submission
    const settingsForm = document.getElementById('settingsForm');
    
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(settingsForm);
            
            fetch('save_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let alertClass = data.status === 'success' ? 'alert-success' : 'alert-danger';
                
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert ${alertClass} mt-3`;
                alertDiv.textContent = data.message;
                
                settingsForm.appendChild(alertDiv);
                
                setTimeout(() => {
                    alertDiv.remove();
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger mt-3';
                alertDiv.textContent = 'An error occurred while saving settings. Please try again.';
                
                settingsForm.appendChild(alertDiv);
                
                setTimeout(() => {
                    alertDiv.remove();
                }, 3000);
            });
        });
    }
    
    // Function to load email history
    function loadEmailHistory() {
        const historyTableBody = document.getElementById('historyTableBody');
        const historyPagination = document.getElementById('historyPagination');
        
        if (!historyTableBody) return;
        
        fetch('get_history.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.history && data.history.length > 0) {
                    let tableHtml = '';
                    
                    data.history.forEach(item => {
                        tableHtml += `<tr>
                            <td>${item.date}</td>
                            <td>${item.subject}</td>
                            <td>${item.recipients}</td>
                            <td><span class="badge ${item.status === 'success' ? 'bg-success' : 'bg-danger'}">${item.status}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary view-details" data-id="${item.id}">View</button>
                                <button class="btn btn-sm btn-outline-secondary resend" data-id="${item.id}">Resend</button>
                            </td>
                        </tr>`;
                    });
                    
                    historyTableBody.innerHTML = tableHtml;
                    
                    // Add pagination if provided
                    if (data.pagination) {
                        historyPagination.innerHTML = data.pagination;
                    } else {
                        historyPagination.innerHTML = '';
                    }
                    
                    // Add event listeners for view and resend buttons
                    document.querySelectorAll('.view-details').forEach(button => {
                        button.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            viewEmailDetails(id);
                        });
                    });
                    
                    document.querySelectorAll('.resend').forEach(button => {
                        button.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            resendEmail(id);
                        });
                    });
                } else {
                    historyTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No email history found</td></tr>';
                    historyPagination.innerHTML = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                historyTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading email history</td></tr>';
                historyPagination.innerHTML = '';
            });
    }
    
    // Function to view email details
    function viewEmailDetails(id) {
        fetch(`get_email_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Create modal to display email details
                    const modalHtml = `
                        <div class="modal fade" id="emailDetailsModal" tabindex="-1" aria-labelledby="emailDetailsModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="emailDetailsModalLabel">Email Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <strong>Date:</strong> ${data.details.date}
                                        </div>
                                        <div class="mb-3">
                                            <strong>From:</strong> ${data.details.sender}
                                        </div>
                                        <div class="mb-3">
                                            <strong>Subject:</strong> ${data.details.subject}
                                        </div>
                                        <div class="mb-3">
                                            <strong>Recipients:</strong> ${data.details.recipients}
                                        </div>
                                        <div class="mb-3">
                                            <strong>Message:</strong>
                                            <div class="border p-3 mt-2">${data.details.message}</div>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Status:</strong> 
                                            <span class="badge ${data.details.status === 'success' ? 'bg-success' : 'bg-danger'}">${data.details.status}</span>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary" id="resendModalBtn" data-id="${id}">Resend</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Append modal to body
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('emailDetailsModal'));
                    modal.show();
                    
                    // Add event listener for resend button
                    document.getElementById('resendModalBtn').addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        modal.hide();
                        resendEmail(id);
                    });
                    
                    // Remove modal from DOM when hidden
                    document.getElementById('emailDetailsModal').addEventListener('hidden.bs.modal', function() {
                        this.remove();
                    });
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while loading email details.');
            });
    }
    
    // Function to resend email
    function resendEmail(id) {
        if (confirm('Are you sure you want to resend this email?')) {
            fetch(`resend_email.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Email has been queued for resending.');
                        loadEmailHistory();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while resending the email.');
                });
        }
    }
    
    // Load email history when history tab is shown
    const historyTab = document.getElementById('history-tab');
    if (historyTab) {
        historyTab.addEventListener('shown.bs.tab', function() {
            loadEmailHistory();
        });
    }
    
    // Load settings from server
    const settingsTab = document.getElementById('settings-tab');
    if (settingsTab) {
        settingsTab.addEventListener('shown.bs.tab', function() {
            fetch('get_settings.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('awsRegion').value = data.settings.awsRegion || '';
                        document.getElementById('awsAccessKey').value = data.settings.awsAccessKey || '';
                        document.getElementById('awsSecretKey').value = data.settings.awsSecretKey || '';
                        document.getElementById('batchSize').value = data.settings.batchSize || 10;
                        document.getElementById('delayBetweenBatches').value = data.settings.delayBetweenBatches || 1;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    }
});