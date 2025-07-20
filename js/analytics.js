// Analytics Module
const Analytics = {
    charts: {},
    isLoading: false,

    // Initialize analytics
    init() {
        this.setupEventListeners();
        this.initializeCharts();
    },

    // Set up event listeners
    setupEventListeners() {
        // Refresh button
        const refreshBtn = document.querySelector('.refresh-analytics');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadData());
        }

        // Tab visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && document.querySelector('#analytics.active-section')) {
                this.loadData();
            }
        });
    },

    // Initialize Chart.js instances
    initializeCharts() {
        // Revenue Trend Chart
        const revenueTrendCtx = document.getElementById('revenueChart');
        if (revenueTrendCtx) {
            this.charts.revenueTrend = new Chart(revenueTrendCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Revenue',
                        data: [],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'var(--bg-secondary)',
                            titleColor: 'var(--text-primary)',
                            bodyColor: 'var(--text-primary)',
                            borderColor: 'var(--border-color)',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'var(--border-color)'
                            }
                        }
                    }
                }
            });
        }

        // Orders Status Chart
        const ordersStatusCtx = document.getElementById('ordersStatusChart');
        if (ordersStatusCtx) {
            this.charts.ordersStatus = new Chart(ordersStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#2ecc71',
                            '#f1c40f',
                            '#e74c3c'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: 'var(--text-primary)',
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }

        // Service Popularity Chart
        const servicePopularityCtx = document.getElementById('servicePopularityChart');
        if (servicePopularityCtx) {
            this.charts.servicePopularity = new Chart(servicePopularityCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: '#9b59b6',
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'var(--border-color)'
                            }
                        }
                    }
                }
            });
        }

        // Ratings Chart
        const ratingsCtx = document.getElementById('ratingsChart');
        if (ratingsCtx) {
            this.charts.ratings = new Chart(ratingsCtx, {
                type: 'bar',
                data: {
                    labels: ['5★', '4★', '3★', '2★', '1★'],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#2ecc71',
                            '#27ae60',
                            '#f1c40f',
                            '#e67e22',
                            '#e74c3c'
                        ],
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'var(--border-color)'
                            }
                        }
                    }
                }
            });
        }
    },

    // Load analytics data from API
    async loadData() {
        if (this.isLoading) return;
        this.isLoading = true;

        try {
            // Show loading state
            this.showLoading();

            const response = await fetch('api/analytics.php', {
                method: 'POST',
                credentials: 'include'
            });
            if (!response.ok) throw new Error('Failed to fetch analytics data');
            
            const data = await response.json();
            if (!data.success) throw new Error(data.error || 'Failed to load analytics');

            // Update UI with new data
            this.updateSummaryCards(data.summary);
            this.updateCharts(data.charts);
            this.updateRecentBookings(data.recentBookings);

            // Hide loading state
            this.hideLoading();
        } catch (error) {
            console.error('Analytics Error:', error);
            this.showError(error.message);
        } finally {
            this.isLoading = false;
        }
    },

    // Update summary cards with new data
    updateSummaryCards(summary) {
        // Total Revenue
        const revenueEl = document.getElementById('total-revenue');
        if (revenueEl) {
            revenueEl.innerHTML = `<span class="currency">₹</span>${this.formatNumber(summary.totalRevenue)}`;
        }

        // Total Orders
        const ordersEl = document.getElementById('total-orders-count');
        if (ordersEl) {
            ordersEl.textContent = this.formatNumber(summary.totalOrders);
        }

        // Average Rating
        const ratingEl = document.getElementById('avg-rating');
        if (ratingEl) {
            ratingEl.textContent = summary.averageRating.toFixed(1);
        }

        // Satisfaction Rate
        const satisfactionEl = document.getElementById('satisfaction-rate');
        if (satisfactionEl) {
            satisfactionEl.textContent = `${summary.satisfactionRate}%`;
        }
    },

    // Update all charts with new data
    updateCharts(chartData) {
        // Revenue Trend
        if (this.charts.revenueTrend && chartData.revenueTrend) {
            this.charts.revenueTrend.data.labels = chartData.revenueTrend.labels;
            this.charts.revenueTrend.data.datasets[0].data = chartData.revenueTrend.values;
            this.charts.revenueTrend.update();
        }

        // Orders Status
        if (this.charts.ordersStatus && chartData.ordersStatus) {
            this.charts.ordersStatus.data.labels = chartData.ordersStatus.labels;
            this.charts.ordersStatus.data.datasets[0].data = chartData.ordersStatus.values;
            this.charts.ordersStatus.update();
        }

        // Service Popularity
        if (this.charts.servicePopularity && chartData.servicePopularity) {
            this.charts.servicePopularity.data.labels = chartData.servicePopularity.labels;
            this.charts.servicePopularity.data.datasets[0].data = chartData.servicePopularity.values;
            this.charts.servicePopularity.update();
        }

        // Ratings
        if (this.charts.ratings && chartData.ratings) {
            this.charts.ratings.data.datasets[0].data = chartData.ratings;
            this.charts.ratings.update();
        }
    },

    // Update recent bookings table
    updateRecentBookings(bookings) {
        const tbody = document.getElementById('recent-bookings-body');
        if (!tbody) return;

        if (!bookings || bookings.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No recent bookings found</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = bookings.map(booking => `
            <tr>
                <td>#${booking.id}</td>
                <td>${booking.customer_name}</td>
                <td>${booking.service_name}</td>
                <td>${this.formatDate(booking.date)}</td>
                <td>₹${this.formatNumber(booking.amount)}</td>
                <td><span class="status-badge status-${booking.status.toLowerCase()}">${booking.status}</span></td>
            </tr>
        `).join('');
    },

    // Helper: Format numbers with commas
    formatNumber(number) {
        return new Intl.NumberFormat('en-IN').format(number);
    },

    // Helper: Format date
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    // Show loading state
    showLoading() {
        const sections = document.querySelectorAll('.analytics-section > div');
        sections.forEach(section => {
            section.classList.add('analytics-loading');
        });
    },

    // Hide loading state
    hideLoading() {
        const sections = document.querySelectorAll('.analytics-section > div');
        sections.forEach(section => {
            section.classList.remove('analytics-loading');
        });
    },

    // Show error message
    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-state';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <p>${message}</p>
            <button onclick="Analytics.loadData()" class="refresh-analytics">
                <i class="fas fa-sync-alt"></i> Try Again
            </button>
        `;

        const analyticsSection = document.querySelector('.analytics-section');
        if (analyticsSection) {
            analyticsSection.prepend(errorDiv);
            setTimeout(() => errorDiv.remove(), 5000);
        }
    }
};

// Initialize analytics when the page loads
document.addEventListener('DOMContentLoaded', () => Analytics.init()); 