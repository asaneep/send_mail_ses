<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Email Sender - Amazon SES</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Bulk Email Sender - Amazon SES</h3>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="emailTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="send-tab" data-bs-toggle="tab" data-bs-target="#send" type="button" role="tab" aria-controls="send" aria-selected="true">Send Emails</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">Email History</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false">Settings</button>
                            </li>
                        </ul>
                        <div class="tab-content mt-3" id="emailTabsContent">
                            <!-- Send Emails Tab -->
                            <div class="tab-pane fade show active" id="send" role="tabpanel" aria-labelledby="send-tab">
                                <form id="emailForm" action="send_mail.php" method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="sender" class="form-label">From Email:</label>
                                        <input type="email" class="form-control" id="sender" name="sender" required>
                                        <small class="form-text text-muted">Must be a verified email in your Amazon SES account</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject:</label>
                                        <input type="text" class="form-control" id="subject" name="subject" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="recipients" class="form-label">Recipients:</label>
                                        <select class="form-select mb-2" id="recipientType" name="recipientType">
                                            <option value="textarea">Enter emails in textarea</option>
                                            <option value="file">Upload CSV file</option>
                                        </select>
                                        <div id="textareaRecipients">
                                            <textarea class="form-control" id="recipients" name="recipients" rows="5" placeholder="Enter email addresses (one per line or comma-separated)"></textarea>
                                        </div>
                                        <div id="fileRecipients" style="display: none;">
                                            <input type="file" class="form-control" id="recipientFile" name="recipientFile" accept=".csv,.txt">
                                            <small class="form-text text-muted">Upload a CSV file with email addresses in the first column</small>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="messageFormat" class="form-label">Message Format:</label>
                                        <select class="form-select" id="messageFormat" name="messageFormat">
                                            <option value="text">Plain Text</option>
                                            <option value="html" selected>HTML</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message:</label>
                                        <textarea class="form-control" id="message" name="message" rows="10" required></textarea>
                                        <small class="form-text text-muted">Use {name}, {email}, etc. as placeholders for personalization</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="attachments" class="form-label">Attachments (optional):</label>
                                        <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary" id="sendButton">Send Emails</button>
                                    </div>
                                </form>
                                <div class="progress mt-3" style="display: none;" id="sendProgress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
                                </div>
                                <div id="sendResults" class="mt-3"></div>
                            </div>
                            
                            <!-- Email History Tab -->
                            <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Subject</th>
                                                <th>Recipients</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="historyTableBody">
                                            <!-- History data will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                                <div id="historyPagination" class="d-flex justify-content-center mt-3">
                                    <!-- Pagination will be added here -->
                                </div>
                            </div>
                            
                            <!-- Settings Tab -->
                            <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                                <form id="settingsForm" action="save_settings.php" method="post">
                                    <div class="mb-3">
                                        <label for="awsRegion" class="form-label">AWS Region:</label>
                                        <input type="text" class="form-control" id="awsRegion" name="awsRegion" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="awsAccessKey" class="form-label">AWS Access Key:</label>
                                        <input type="text" class="form-control" id="awsAccessKey" name="awsAccessKey" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="awsSecretKey" class="form-label">AWS Secret Key:</label>
                                        <input type="password" class="form-control" id="awsSecretKey" name="awsSecretKey" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="batchSize" class="form-label">Batch Size:</label>
                                        <input type="number" class="form-control" id="batchSize" name="batchSize" min="1" max="50" value="10">
                                        <small class="form-text text-muted">Number of emails to send in each batch (max 50)</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="delayBetweenBatches" class="form-label">Delay Between Batches (seconds):</label>
                                        <input type="number" class="form-control" id="delayBetweenBatches" name="delayBetweenBatches" min="0" value="1">
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">Save Settings</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/script.js"></script>
</body>
</html>