<section class="dashboard-cards">
    <div class="card stat-card" id="stat-users">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>Total Users</h3>
            <p><?php echo $stats['users']; ?></p>
        </div>
    </div>
    <div class="card stat-card" id="stat-providers">
        <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
        <div class="stat-info">
            <h3>Service Providers</h3>
            <p><?php echo $stats['providers']; ?></p>
        </div>
    </div>
    <div class="card stat-card" id="stat-orders">
        <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-info">
            <h3>Total Orders</h3>
            <p><?php echo $stats['orders']; ?></p>
        </div>
    </div>
    <div class="card stat-card" id="stat-feedback">
        <div class="stat-icon"><i class="fas fa-comments"></i></div>
        <div class="stat-info">
            <h3>Feedback</h3>
            <p><?php echo $stats['feedback']; ?></p>
        </div>
    </div>
</section>

<section class="recent-activity">
    <h2>Recent Activity</h2>
    <div class="activity-list">
        <?php if (empty($recentActivity)): ?>
            <p class="no-activity">No recent activity</p>
        <?php else: ?>
            <?php foreach ($recentActivity as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php if ($activity['type'] === 'order'): ?>
                            <i class="fas fa-shopping-cart"></i>
                        <?php endif; ?>
                    </div>
                    <div class="activity-details">
                        <p class="activity-title">
                            <?php if ($activity['type'] === 'order'): ?>
                                New Order #<?php echo $activity['id']; ?>
                            <?php endif; ?>
                        </p>
                        <p class="activity-time">
                            <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section> 