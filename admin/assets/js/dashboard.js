document.addEventListener('DOMContentLoaded', function() {
    // Post Status Chart
    const postStatusCtx = document.getElementById('postStatusChart');
    if (postStatusCtx) {
        const postStatusChart = new Chart(postStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Published', 'Draft', 'Pending'],
                datasets: [{
                    data: [
                        postStatusCtx.dataset.published || 0,
                        postStatusCtx.dataset.draft || 0,
                        postStatusCtx.dataset.pending || 0
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(108, 117, 125, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(108, 117, 125, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Post Status Distribution',
                        font: {
                            size: 16
                        }
                    }
                }
            }
        });
    }


    // Monthly Posts Chart
    const monthlyPostsCtx = document.getElementById('monthlyPostsChart');
    if (monthlyPostsCtx) {
        const labels = JSON.parse(monthlyPostsCtx.dataset.labels || '[]');
        const data = JSON.parse(monthlyPostsCtx.dataset.data || '[]');
        
        const monthlyPostsChart = new Chart(monthlyPostsCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Posts',
                    data: data,
                    backgroundColor: 'rgba(13, 110, 253, 0.2)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Posts Last 6 Months',
                        font: {
                            size: 16
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }


    // User Activity Chart
    const userActivityCtx = document.getElementById('userActivityChart');
    if (userActivityCtx) {
        const userActivityChart = new Chart(userActivityCtx, {
            type: 'bar',
            data: {
                labels: ['Posts', 'Comments', 'Media'],
                datasets: [{
                    label: 'Your Activity',
                    data: [
                        userActivityCtx.dataset.posts || 0,
                        userActivityCtx.dataset.comments || 0,
                        userActivityCtx.dataset.media || 0
                    ],
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(111, 66, 193, 0.7)',
                        'rgba(25, 135, 84, 0.7)'
                    ],
                    borderColor: [
                        'rgba(13, 110, 253, 1)',
                        'rgba(111, 66, 193, 1)',
                        'rgba(25, 135, 84, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Your Activity',
                        font: {
                            size: 16
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

});
