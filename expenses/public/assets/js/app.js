/**
 * Expenses - Native JavaScript Utilities
 */

document.addEventListener('DOMContentLoaded', function () {
    // Mobile sidebar toggle
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    let backdrop = null;

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            if (sidebar.classList.contains('show')) {
                backdrop = document.createElement('div');
                backdrop.className = 'sidebar-backdrop';
                document.body.appendChild(backdrop);
                backdrop.addEventListener('click', function () {
                    sidebar.classList.remove('show');
                    if (backdrop) backdrop.remove();
                });
            } else if (backdrop) {
                backdrop.remove();
            }
        });
    }

    // Auto dismiss flash alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function () {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // File input validation (size <= 5MB, format check)
    const fileInputs = document.querySelectorAll('input[type="file"][data-max-size]');
    fileInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            const maxSize = parseInt(input.getAttribute('data-max-size'), 10) || 5242880;
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.size > maxSize) {
                    alert('Ukuran file melebihi batas maksimum (' + Math.round(maxSize / 1024 / 1024) + ' MB). Silakan pilih file lain.');
                    input.value = '';
                }
            }
        });
    });

    // Confirm dialogs for destructive actions
    const confirmForms = document.querySelectorAll('form[data-confirm]');
    confirmForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const message = form.getAttribute('data-confirm') || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});

// Chart.js Global Helpers for Expenses
window.GTCharts = {
    colors: ['#2563EB', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#EC4899', '#64748B'],

    formatRupiah: function (value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    },

    createTrendChart: function (element, labels, data, datasetLabel) {
        if (!element || typeof Chart === 'undefined') return null;
        const ctx = element.getContext ? element.getContext('2d') : element;
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: datasetLabel || 'Total Pengeluaran',
                    data: data,
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#2563EB',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
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
                        callbacks: {
                            label: function (context) {
                                return ' ' + (context.dataset.label || 'Total') + ': ' + window.GTCharts.formatRupiah(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 12 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (val) {
                                if (val >= 1000000) {
                                    return 'Rp ' + (val / 1000000) + ' jt';
                                } else if (val >= 1000) {
                                    return 'Rp ' + (val / 1000) + ' rb';
                                }
                                return 'Rp ' + val;
                            },
                            font: { size: 11 }
                        },
                        grid: {
                            color: '#F1F5F9'
                        }
                    }
                }
            }
        });
    },

    createBarChart: function (element, labels, data, datasetLabel) {
        if (!element || typeof Chart === 'undefined') return null;
        const ctx = element.getContext ? element.getContext('2d') : element;
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: datasetLabel || 'Total Biaya',
                    data: data,
                    backgroundColor: '#2563EB',
                    borderRadius: 6,
                    maxBarThickness: 45
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
                        callbacks: {
                            label: function (context) {
                                return ' ' + (context.dataset.label || 'Total') + ': ' + window.GTCharts.formatRupiah(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 12 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (val) {
                                if (val >= 1000000) {
                                    return 'Rp ' + (val / 1000000) + ' jt';
                                } else if (val >= 1000) {
                                    return 'Rp ' + (val / 1000) + ' rb';
                                }
                                return 'Rp ' + val;
                            },
                            font: { size: 11 }
                        },
                        grid: {
                            color: '#F1F5F9'
                        }
                    }
                }
            }
        });
    },

    createDoughnutChart: function (element, labels, data) {
        if (!element || typeof Chart === 'undefined') return null;
        const ctx = element.getContext ? element.getContext('2d') : element;
        const colors = window.GTCharts.colors.slice(0, labels.length);
        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 12,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const val = context.raw || 0;
                                return ' ' + context.label + ': ' + window.GTCharts.formatRupiah(val);
                            }
                        }
                    }
                }
            }
        });
    }
};

