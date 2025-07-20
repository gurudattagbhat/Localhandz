<div class="section-header">
    <h2>Orders</h2>
    <div class="filters">
        <select id="status-filter">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
        <input type="date" id="date-filter" placeholder="Filter by date">
    </div>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Provider</th>
                <th>Service</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="7" class="text-center">No orders found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['provider_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($order['request_date'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info view-order" data-id="<?php echo $order['id']; ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary update-order" data-id="<?php echo $order['id']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Order Details Modal -->
<div id="order-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Order Details</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value" id="order-id"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Customer:</span>
                    <span class="detail-value" id="customer-name"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Provider:</span>
                    <span class="detail-value" id="provider-name"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Service:</span>
                    <span class="detail-value" id="service-name"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value" id="order-date"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value" id="order-status"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Notes:</span>
                    <span class="detail-value" id="order-notes"></span>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary close-modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="status-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Order Status</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="status-form">
                <input type="hidden" id="order-id">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div> 