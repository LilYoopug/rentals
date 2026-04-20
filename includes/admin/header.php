<?php
// Set the active page variable before including this
$admin_active_section = $admin_active_section ?? 'overview';
$admin_active_href = $admin_active_href ?? 'index.php';
$admin_active_section_selector = preg_replace('/[^a-z0-9_-]/i', '', $admin_active_section) ?: 'overview';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Dashboard Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap"
      rel="stylesheet"
    />
    <style>
      :root {
        --accent-brass: #c7a65a;
        --accent-brass-soft: rgba(199, 166, 90, 0.18);
      }
      body {
        font-family: "Inter", sans-serif;
      }
      .font-serif {
        font-family: "Playfair Display", serif;
      }
      .nav-blur {
        background: rgba(5, 5, 5, 0.86);
        backdrop-filter: blur(18px);
      }
      .sidebar-blur {
        background: rgba(5, 5, 5, 0.94);
        backdrop-filter: blur(18px);
      }
      .nav-item-active {
        background-color: var(--accent-brass-soft);
        border-left: 3px solid var(--accent-brass);
        color: #fff;
      }
      .content-section {
        display: none;
      }
      #<?= e($admin_active_section_selector) ?>.content-section {
        display: block;
      }
      .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        border: 1px solid;
      }
      .badge-success {
        background-color: rgba(34, 197, 94, 0.15);
        color: #4ade80;
        border-color: rgba(34, 197, 94, 0.3);
      }
      .badge-warning {
        background-color: rgba(251, 191, 36, 0.15);
        color: #fbbf24;
        border-color: rgba(251, 191, 36, 0.3);
      }
      .badge-danger {
        background-color: rgba(239, 68, 68, 0.15);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.3);
      }
      .badge-info {
        background-color: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.3);
      }
      .table-row-hover:hover {
        background-color: rgba(255, 255, 255, 0.03);
      }
      @media (max-width: 639px) {
        .mobile-name-ellipsis {
          display: block;
          max-width: 100%;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
        .mobile-card-title-ellipsis {
          max-width: min(13rem, 100%);
        }
      }
      .sidebar-scroll::-webkit-scrollbar {
        width: 6px;
      }
      .sidebar-scroll::-webkit-scrollbar-track {
        background: transparent;
      }
      .sidebar-scroll::-webkit-scrollbar-thumb {
        background-color: #333;
        border-radius: 3px;
      }
      .sidebar-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #444;
      }
    </style>
  </head>
  <body class="bg-neutral-950 text-neutral-100 min-h-screen">
