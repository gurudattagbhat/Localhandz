<div class="section-header">
    <h2>Service Providers</h2>
    <button class="btn btn-primary" id="add-provider-btn">
        <i class="fas fa-plus"></i> Add Provider
    </button>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Business Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($providers)): ?>
                <tr>
                    <td colspan="7" class="text-center">No providers found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($providers as $provider): ?>
                    <tr>
                        <td><?php echo $provider['id']; ?></td>
                        <td><?php echo htmlspecialchars($provider['business_name']); ?></td>
                        <td><?php echo htmlspecialchars($provider['username']); ?></td>
                        <td><?php echo htmlspecialchars($provider['email']); ?></td>
                        <td><?php echo htmlspecialchars($provider['phone']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $provider['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo ucfirst($provider['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info edit-provider" data-id="<?php echo $provider['id']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-provider" data-id="<?php echo $provider['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Provider Modal -->
<div id="provider-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Provider Details</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="provider-form">
                <input type="hidden" id="provider-id">
                <div class="form-group">
                    <label for="business-name">Business Name</label>
                    <input type="text" id="business-name" name="business_name" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div> 