<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>EduTrackr</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts - Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --bg-primary: #f5f7fb;
            --accent-primary: #334155;
            --accent-action: #3f51b5;
            --accent-secondary: #5c6bc0;
            --success: #2ecc71;
            --error: #e74c3c;
            --warning: #f1c40f;
            --card-bg: rgba(255, 255, 255, 0.7);
            --card-bg-solid: #FFFFFF;
            --text-color: #1e293b;
            --accent-color: #3f51b5;
            --primary-blue: #3f51b5;
            --primary-blue-light: #5c6bc0;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow-sm: 0 2px 8px rgba(15, 23, 42, 0.04);
            --shadow-md: 0 4px 16px rgba(15, 23, 42, 0.08);
            --shadow-lg: 0 12px 32px rgba(15, 23, 42, 0.12);
            --shadow-xl: 0 20px 48px rgba(15, 23, 42, 0.16);
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fb 0%, #e8ecf1 100%);
            background-attachment: fixed;
            color: var(--text-color);
            font-weight: 400;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Premium Button Styles with Glassmorphism */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-weight: 600;
            border: none;
            cursor: pointer;
            border-radius: 12px;
            padding: 14px 28px;
            box-shadow: 0 4px 16px rgba(63, 81, 181, 0.25), 0 0 0 1px rgba(63, 81, 181, 0.1);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-blue-light) 0%, var(--primary-blue) 100%);
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 12px 28px rgba(63, 81, 181, 0.35), 0 0 0 1px rgba(63, 81, 181, 0.15);
        }
        
        .btn-primary:active {
            transform: translateY(-1px) scale(0.98);
            box-shadow: 0 4px 12px rgba(63, 81, 181, 0.25);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 2px 8px rgba(63, 81, 181, 0.15);
        }
        
        /* Premium Glassmorphism Card Styles */
        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-radius: 20px;
            box-shadow: var(--shadow-md), inset 0 1px 0 rgba(255, 255, 255, 0.6);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid var(--glass-border);
            overflow: hidden;
            position: relative;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
        }
        
        .card:hover {
            box-shadow: var(--shadow-xl), inset 0 1px 0 rgba(255, 255, 255, 0.8);
            transform: translateY(-6px) scale(1.01);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        /* Solid card variant for better contrast */
        .card-solid {
            background: var(--card-bg-solid);
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
        }
        
        /* Premium Stat Card with Glassmorphism & Icon Bubble */
        .stat-card {
            position: relative;
            overflow: hidden;
            background: var(--glass-bg);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--glass-border);
        }
        
        .stat-card .icon-bubble {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(63, 81, 181, 0.15) 0%, rgba(92, 107, 192, 0.1) 100%);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(63, 81, 181, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .stat-card:hover .icon-bubble {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 8px 20px rgba(63, 81, 181, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.6s;
        }
        
        .stat-card:hover::after {
            left: 100%;
        }
        
        /* Premium Input Fields with Glassmorphism */
        .input-field {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid rgba(226, 232, 240, 0.6);
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: inset 0 2px 4px rgba(15, 23, 42, 0.04), 0 1px 0 rgba(255, 255, 255, 0.8);
        }
        
        .input-field:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 4px rgba(63, 81, 181, 0.12), inset 0 2px 4px rgba(15, 23, 42, 0.06), 0 1px 0 rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
        }
        
        .input-field::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }
        
        /* Floating Label Style */
        .floating-label {
            position: relative;
        }
        
        .floating-label input:focus ~ label,
        .floating-label input:not(:placeholder-shown) ~ label {
            transform: translateY(-24px) scale(0.85);
            color: var(--primary-blue);
        }
        
        .floating-label label {
            position: absolute;
            left: 16px;
            top: 12px;
            transition: all 0.3s ease;
            pointer-events: none;
            color: #94a3b8;
        }
        
        /* Premium Table Styles with Glassmorphism */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--glass-bg);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md), inset 0 1px 0 rgba(255, 255, 255, 0.6);
            border: 1px solid var(--glass-border);
        }
        
        .modern-table thead {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(63, 81, 181, 0.2);
        }
        
        .modern-table thead th {
            padding: 18px 24px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .modern-table tbody tr {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-bottom: 1px solid rgba(241, 245, 249, 0.6);
            background: rgba(255, 255, 255, 0.4);
        }
        
        .modern-table tbody tr:nth-child(even) {
            background: rgba(248, 250, 252, 0.5);
        }
        
        .modern-table tbody tr:hover {
            background: rgba(63, 81, 181, 0.1);
            transform: scale(1.005);
            box-shadow: 0 4px 12px rgba(63, 81, 181, 0.1);
        }
        
        .modern-table tbody td {
            padding: 18px 24px;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Premium Sidebar with Neon Glow */
        .sidebar-link {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-left: 3px solid transparent;
            position: relative;
            margin: 4px 12px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: linear-gradient(180deg, var(--primary-blue), var(--primary-blue-light));
            transform: scaleY(0);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 0 12px rgba(63, 81, 181, 0.6);
        }
        
        .sidebar-link::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(63, 81, 181, 0.08) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .sidebar-link:hover {
            background: linear-gradient(90deg, rgba(63, 81, 181, 0.12) 0%, rgba(63, 81, 181, 0.04) 100%);
            color: var(--accent-color);
            transform: translateX(6px);
            box-shadow: 0 4px 12px rgba(63, 81, 181, 0.1);
        }
        
        .sidebar-link:hover::before {
            transform: scaleY(1);
        }
        
        .sidebar-link:hover::after {
            opacity: 1;
        }
        
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(63, 81, 181, 0.18) 0%, rgba(63, 81, 181, 0.06) 100%);
            border-left: 3px solid var(--accent-color);
            color: var(--accent-color);
            font-weight: 600;
            box-shadow: 0 0 20px rgba(63, 81, 181, 0.15), inset 0 0 20px rgba(63, 81, 181, 0.05);
        }
        
        .sidebar-link.active::before {
            transform: scaleY(1);
            box-shadow: 0 0 16px rgba(63, 81, 181, 0.8);
        }
        
        .sidebar-link.active::after {
            opacity: 1;
        }
        
        .sidebar-link svg {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .sidebar-link:hover svg {
            transform: scale(1.15) rotate(5deg);
        }
        
        .sidebar-link.active svg {
            transform: scale(1.1);
            filter: drop-shadow(0 0 4px rgba(63, 81, 181, 0.4));
        }
        
        /* Custom Scrollbar Styles */
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 10px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #a5b4fc;
            border-radius: 10px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #818cf8;
        }
        
        * {
            scrollbar-width: thin;
            scrollbar-color: #a5b4fc #f3f4f6;
        }
        
        /* Animated Counter */
        .counter {
            font-variant-numeric: tabular-nums;
        }
        
        /* Premium Badge Styles with Glassmorphism */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.6);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .badge:hover {
            transform: scale(1.05) translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }
        
        .badge-success {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.2) 0%, rgba(46, 204, 113, 0.1) 100%);
            color: var(--success);
            border-color: rgba(46, 204, 113, 0.3);
        }
        
        .badge-error {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.2) 0%, rgba(231, 76, 60, 0.1) 100%);
            color: var(--error);
            border-color: rgba(231, 76, 60, 0.3);
        }
        
        .badge-warning {
            background: linear-gradient(135deg, rgba(241, 196, 15, 0.2) 0%, rgba(241, 196, 15, 0.1) 100%);
            color: var(--warning);
            border-color: rgba(241, 196, 15, 0.3);
        }
        
        .badge-info {
            background: linear-gradient(135deg, rgba(63, 81, 181, 0.2) 0%, rgba(63, 81, 181, 0.1) 100%);
            color: var(--primary-blue);
            border-color: rgba(63, 81, 181, 0.3);
        }
        
        /* Progress Bar */
        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-blue), var(--primary-blue-light));
            border-radius: 10px;
            transition: width 0.6s ease;
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* Premium Announcement Card with Glassmorphism */
        .announcement-card {
            background: var(--glass-bg);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-left: 4px solid var(--primary-blue);
            border-radius: 16px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: slideUpFade 0.6s ease;
            box-shadow: var(--shadow-sm), inset 0 1px 0 rgba(255, 255, 255, 0.6);
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
        }
        
        .announcement-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, var(--primary-blue), var(--primary-blue-light));
            transition: width 0.4s ease;
        }
        
        .announcement-card:hover {
            border-left-width: 6px;
            transform: translateX(6px) translateY(-2px);
            box-shadow: var(--shadow-md), inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }
        
        .announcement-card:hover::before {
            width: 6px;
            box-shadow: 0 0 12px rgba(63, 81, 181, 0.4);
        }
        
        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Status Indicators */
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-present { background: var(--success); }
        .status-absent { background: var(--error); }
        .status-late { background: var(--warning); }
        
        /* Mark Color Coding */
        .mark-excellent { color: var(--success); font-weight: 700; }
        .mark-good { color: var(--primary-blue); font-weight: 600; }
        .mark-average { color: var(--warning); font-weight: 600; }
        .mark-poor { color: var(--error); font-weight: 600; }
        
        /* Premium Modal Styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: var(--shadow-xl), inset 0 1px 0 rgba(255, 255, 255, 0.7);
            border: 1px solid var(--glass-border);
            max-width: 90vw;
            max-height: 90vh;
            overflow: auto;
            animation: slideUpScale 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
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
        
        /* Premium Form Group Styles */
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-color);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }
        
        /* Premium Search Bar */
        .search-bar {
            position: relative;
            width: 100%;
        }
        
        .search-bar input {
            padding-left: 48px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .search-bar::before {
            content: '🔍';
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            opacity: 0.5;
            z-index: 1;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .empty-state svg,
        .empty-state .text-6xl {
            opacity: 0.6;
            margin-bottom: 16px;
        }
        
        /* Premium Sidebar Background */
        aside {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border-right: 1px solid var(--glass-border) !important;
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.08) !important;
        }
        
        /* Page Entry Animations */
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
        
        .ml-64 {
            animation: pageEnter 0.6s ease;
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .card {
                border-radius: 16px;
                padding: 1rem !important;
            }
            
            .modern-table {
                font-size: 12px;
            }
            
            .modern-table thead th,
            .modern-table tbody td {
                padding: 12px 16px;
            }
            
            .stat-card .icon-bubble {
                width: 48px;
                height: 48px;
            }
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Better focus states */
        a:focus, button:focus, input:focus, select:focus, textarea:focus {
            outline: 2px solid var(--accent-color);
            outline-offset: 2px;
        }
        
        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
            position: relative;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 3px solid rgba(63, 81, 181, 0.3);
            border-top-color: var(--primary-blue);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 26px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: var(--primary-blue);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        
        /* Form Group */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
            font-size: 14px;
        }
        
        /* Search Bar */
        .search-bar {
            position: relative;
        }
        
        .search-bar input {
            padding-left: 40px;
        }
        
        .search-bar::before {
            content: '🔍';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            opacity: 0.5;
        }
    </style>
    
    <script>
        // Animated Counter Function
        function animateCounter(element, target, duration = 2000) {
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = Math.round(target);
                    clearInterval(timer);
                } else {
                    element.textContent = Math.round(current);
                }
            }, 16);
        }
        
        // Initialize counters on page load
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.counter[data-target]');
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                if (target) {
                    animateCounter(counter, target);
                }
            });
            
            // Add smooth page transitions
            document.querySelectorAll('a[href]').forEach(link => {
                if (link.href && !link.href.includes('#') && !link.href.includes('javascript:')) {
                    link.addEventListener('click', function(e) {
                        if (!this.target || this.target === '_self') {
                            const href = this.getAttribute('href');
                            if (href && !href.startsWith('http') && !href.startsWith('mailto:')) {
                                // Add loading state
                                document.body.style.opacity = '0.7';
                                document.body.style.pointerEvents = 'none';
                            }
                        }
                    });
                }
            });
        });
    </script>
</head>
<body>
    <?php
    if (!isset($hideHeader)) {
        // Include UI enhancements for consistent styling
        if (file_exists(__DIR__ . '/ui_enhancements.php')) {
            include __DIR__ . '/ui_enhancements.php';
        }
    }
    ?>

