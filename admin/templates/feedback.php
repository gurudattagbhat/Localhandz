<div class="section-header">
    <h2>Feedback</h2>
    <div class="filters">
        <select id="rating-filter">
            <option value="">All Ratings</option>
            <option value="5">5 Stars</option>
            <option value="4">4 Stars</option>
            <option value="3">3 Stars</option>
            <option value="2">2 Stars</option>
            <option value="1">1 Star</option>
        </select>
    </div>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Provider</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($feedback)): ?>
                <tr>
                    <td colspan="7" class="text-center">No feedback found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($feedback as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['provider_name']); ?></td>
                        <td>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $item['rating'] ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($item['comment']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-info view-feedback" data-id="<?php echo $item['id']; ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-feedback" data-id="<?php echo $item['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Feedback Details Modal -->
<div id="feedback-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Feedback Details</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="feedback-details">
                <div class="detail-row">
                    <span class="detail-label">User:</span>
                    <span class="detail-value" id="user-name"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Provider:</span>
                    <span class="detail-value" id="provider-name"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Rating:</span>
                    <span class="detail-value">
                        <div class="rating" id="feedback-rating"></div>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Comment:</span>
                    <span class="detail-value" id="feedback-comment"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value" id="feedback-date"></span>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary close-modal">Close</button>
            </div>
        </div>
    </div>
</div> 