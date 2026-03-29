<?php
/**
 * UI Enhancements - Standardized Components
 * EduTrackr - School Management System
 * 
 * This file provides consistent UI styling across all pages
 * Include this after header.php for enhanced table hover effects,
 * card animations, and consistent spacing
 */
?>
<style>
/* Enhanced Table Hover Effects - Consistent across all pages */
.modern-table tbody tr {
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border-bottom: 1px solid rgba(241, 245, 249, 0.6);
    background: rgba(255, 255, 255, 0.4);
    cursor: pointer;
}

.modern-table tbody tr:nth-child(even) {
    background: rgba(248, 250, 252, 0.5);
}

.modern-table tbody tr:hover {
    background: rgba(63, 81, 181, 0.08);
    transform: translateX(4px) scale(1.002);
    box-shadow: 0 4px 12px rgba(63, 81, 181, 0.12);
    border-left: 3px solid var(--primary-blue);
}

.modern-table tbody tr:active {
    transform: translateX(2px) scale(0.998);
}

/* Enhanced Card Hover Effects */
.card {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.card:hover {
    transform: translateY(-4px) scale(1.01);
    box-shadow: var(--shadow-xl), inset 0 1px 0 rgba(255, 255, 255, 0.8);
    border-color: rgba(255, 255, 255, 0.4);
}

/* Consistent Page Spacing */
.ml-64 {
    padding: 2rem;
    min-height: 100vh;
    animation: pageEnter 0.6s ease;
}

/* Enhanced Button Hover States */
.btn-primary:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 24px rgba(63, 81, 181, 0.35), 0 0 0 1px rgba(63, 81, 181, 0.15);
}

/* Consistent Badge Animations */
.badge {
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.badge:hover {
    transform: scale(1.05) translateY(-1px);
}

/* Enhanced Input Focus States */
.input-field:focus {
    transform: translateY(-1px);
    box-shadow: 0 0 0 4px rgba(63, 81, 181, 0.12), inset 0 2px 4px rgba(15, 23, 42, 0.06), 0 1px 0 rgba(255, 255, 255, 0.9);
}

/* Consistent Stat Card Animations */
.stat-card {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.stat-card:hover {
    transform: translateY(-6px) scale(1.02);
}

/* Smooth Link Transitions */
a {
    transition: all 0.3s ease;
}

/* Enhanced Modal Animations */
.modal-overlay {
    animation: fadeIn 0.3s ease;
}

.modal-content {
    animation: slideUpScale 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Consistent Form Group Spacing */
.form-group {
    margin-bottom: 1.5rem;
}

/* Enhanced Select Dropdowns */
select.input-field {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

select.input-field:focus {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%233f51b5' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
}

/* Consistent Loading States */
.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(63, 81, 181, 0.3);
    border-top-color: var(--primary-blue);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

/* Enhanced Empty States */
.empty-state {
    padding: 4rem 2rem;
    text-align: center;
    color: #94a3b8;
}

.empty-state-icon {
    font-size: 4rem;
    opacity: 0.5;
    margin-bottom: 1rem;
    display: block;
}

/* Consistent Alert/Message Styles */
.alert {
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    animation: slideDown 0.3s ease;
    border: 2px solid;
}

.alert-success {
    background: rgba(46, 204, 113, 0.1);
    border-color: #2ecc71;
    color: #27ae60;
}

.alert-error {
    background: rgba(231, 76, 60, 0.1);
    border-color: #e74c3c;
    color: #c0392b;
}

.alert-warning {
    background: rgba(241, 196, 15, 0.1);
    border-color: #f1c40f;
    color: #d68910;
}

.alert-info {
    background: rgba(63, 81, 181, 0.1);
    border-color: #3f51b5;
    color: #303f9f;
}

/* Enhanced Page Headers */
.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid rgba(226, 232, 240, 0.6);
}

.page-header h1 {
    margin-bottom: 0.5rem;
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
}

.page-header p {
    color: #64748b;
    font-size: 0.95rem;
}

/* Consistent Action Buttons */
.action-btn {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Enhanced Grid Layouts */
.grid-responsive {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

/* Consistent Section Spacing */
.section {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}

/* Smooth Scroll Behavior */
html {
    scroll-behavior: smooth;
}

/* Enhanced Focus Visible States */
*:focus-visible {
    outline: 2px solid var(--primary-blue);
    outline-offset: 2px;
    border-radius: 4px;
}

/* Consistent Animation Keyframes */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUpScale {
    from {
        opacity: 0;
        transform: translateY(40px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes pageEnter {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Responsive Enhancements */
@media (max-width: 768px) {
    .ml-64 {
        padding: 1rem;
        margin-left: 0 !important;
    }
    
    .modern-table {
        font-size: 0.875rem;
    }
    
    .card {
        padding: 1rem !important;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
}

/* Print Styles */
@media print {
    .sidebar-link,
    .btn-primary,
    .action-btn {
        display: none;
    }
    
    .ml-64 {
        margin-left: 0 !important;
        padding: 0;
    }
}
</style>

