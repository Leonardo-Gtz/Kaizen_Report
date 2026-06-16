<?php
session_start();
require_once __DIR__ . '/../../includes/SesionInactividad.php';
kaizen_verificar_sesion_inactiva('../login.php');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
    header('Location: ../login.php');
    exit();
}

$usuario = $_SESSION['usuario'];
$inicialesUsuario = '';
$partesNombre = preg_split('/\s+/', trim($usuario['nombre'] ?? ''));
if (count($partesNombre) >= 2) {
    $inicialesUsuario = strtoupper(substr($partesNombre[0], 0, 1) . substr($partesNombre[1], 0, 1));
} elseif (!empty($partesNombre[0])) {
    $inicialesUsuario = strtoupper(substr($partesNombre[0], 0, 2));
} else {
    $inicialesUsuario = 'RH';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard RH - Kaizen Reports</title>
    <?php include __DIR__ . '/../assets/pwa-head.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo kaizen_asset_href('../assets/logout-animation.css', __DIR__ . '/../assets/logout-animation.css'); ?>">
    <link rel="stylesheet" href="<?php echo kaizen_asset_href('../assets/plazo-revision.css', __DIR__ . '/../assets/plazo-revision.css'); ?>">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        .ntn-blue {
            color: #0066CC;
        }
        :root {
            --header-h: 4rem;
        }
        .top-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: linear-gradient(90deg, #1e293b 0%, #0f172a 55%, #0c1222 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.18);
        }
        .top-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #0066CC 0%, #38bdf8 50%, #0066CC 100%);
        }
        .shell-modal-header {
            position: relative;
            background: linear-gradient(90deg, #1e293b 0%, #0f172a 55%, #0c1222 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .shell-modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #0066CC 0%, #38bdf8 50%, #0066CC 100%);
        }
        .top-header-inner {
            height: var(--header-h);
            max-width: 100%;
            padding: 0 0.875rem 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        @media (min-width: 1024px) {
            .top-header-inner {
                padding: 0 1.25rem;
                gap: 1rem;
            }
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
            text-decoration: none;
            padding: 0.35rem 0.5rem 0.35rem 0;
            border-radius: 0.75rem;
            transition: background 0.15s ease;
        }
        .header-brand:hover {
            background: rgba(255, 255, 255, 0.04);
        }
        @media (min-width: 1024px) {
            .header-brand {
                padding-right: 1rem;
                margin-right: 0.25rem;
                border-right: 1px solid rgba(255, 255, 255, 0.08);
            }
        }
        .header-brand-logo {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            background: linear-gradient(145deg, #ffffff 0%, #f1f5f9 100%);
            border: 1px solid rgba(255, 255, 255, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.8);
            flex-shrink: 0;
        }
        .header-brand-logo img {
            width: 1.65rem;
            height: 1.65rem;
            object-fit: contain;
        }
        .header-brand-text {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
            min-width: 0;
        }
        .header-brand-name {
            display: flex;
            align-items: baseline;
            gap: 0.3rem;
            line-height: 1.1;
            white-space: nowrap;
        }
        .header-brand-kaizen {
            color: #fff;
            font-size: 0.9375rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .header-brand-reports {
            color: #7dd3fc;
            font-size: 0.8125rem;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        .header-brand-meta {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            min-width: 0;
        }
        .header-brand-ntn {
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.45);
        }
        .header-brand-meta-sep {
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            flex-shrink: 0;
        }
        .header-brand-role {
            display: inline-flex;
            align-items: center;
            padding: 0.1rem 0.45rem;
            font-size: 0.5625rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #bae6fd;
            background: rgba(0, 102, 204, 0.22);
            border: 1px solid rgba(56, 189, 248, 0.28);
            border-radius: 9999px;
            white-space: nowrap;
        }
        @media (max-width: 420px) {
            .header-brand-reports {
                display: none;
            }
            .header-brand-meta {
                display: none;
            }
            .header-brand-kaizen {
                font-size: 0.875rem;
            }
        }
        .header-nav {
            flex: 1;
            min-width: 0;
            display: none;
            justify-content: center;
        }
        @media (min-width: 1024px) {
            .header-nav {
                display: flex;
            }
        }
        .header-nav-track {
            display: flex;
            align-items: center;
            gap: 0.125rem;
            padding: 0.25rem;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.75rem;
            backdrop-filter: blur(8px);
        }
        .header-nav-sep {
            width: 1px;
            height: 1.25rem;
            background: rgba(255, 255, 255, 0.12);
            margin: 0 0.25rem;
            flex-shrink: 0;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.4375rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.72);
            text-decoration: none;
            border-radius: 0.5rem;
            white-space: nowrap;
            transition: color 0.15s, background 0.15s, box-shadow 0.15s;
            position: relative;
        }
        .nav-item svg {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
            opacity: 0.85;
        }
        .nav-item:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }
        .nav-item.active {
            color: #fff;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.14);
            box-shadow: inset 0 -2px 0 #0066CC, 0 1px 3px rgba(0, 0, 0, 0.12);
        }
        .nav-item.active svg {
            opacity: 1;
            color: #7dd3fc;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
            margin-left: auto;
        }
        .header-user {
            display: none;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.625rem 0.25rem 0.25rem;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 9999px;
        }
        @media (min-width: 768px) {
            .header-user {
                display: flex;
            }
        }
        .header-user-avatar {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #0066CC, #0284c7);
            color: #fff;
            font-size: 0.625rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .header-user-name {
            font-size: 0.75rem;
            font-weight: 600;
            color: #fff;
            max-width: 9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .header-user-role {
            font-size: 0.625rem;
            color: #94a3b8;
        }
        .header-logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            color: #94a3b8;
            border-radius: 0.5rem;
            border: 1px solid transparent;
            transition: color 0.15s, background 0.15s, border-color 0.15s;
        }
        .header-logout-btn:hover {
            color: #fff;
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
        }
        .header-menu-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            color: #cbd5e1;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 0.5rem;
            transition: background 0.15s, color 0.15s;
        }
        .header-menu-btn:hover,
        .header-menu-btn.open {
            color: #fff;
            background: rgba(255, 255, 255, 0.14);
        }
        @media (min-width: 1024px) {
            .header-menu-btn {
                display: none;
            }
        }
        .header-mobile-nav {
            display: none;
            padding: 0.75rem 1rem 1rem;
            background: #0f172a;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
        }
        .header-mobile-nav.open {
            display: block;
        }
        .header-mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }
        .header-mobile-nav .nav-item {
            flex-direction: column;
            gap: 0.25rem;
            padding: 0.75rem 0.5rem;
            text-align: center;
            font-size: 0.75rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .header-mobile-nav .nav-item.active {
            background: rgba(0, 102, 204, 0.2);
            border-color: rgba(56, 189, 248, 0.35);
            box-shadow: none;
        }
        .header-mobile-nav .nav-item svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        .app-shell {
            display: flex;
            min-height: calc(100vh - var(--header-h));
        }
        .filtros-panel {
            width: 17.5rem;
            flex-shrink: 0;
            background: linear-gradient(180deg, #f1f5f9 0%, #f8fafc 40%, #fff 100%);
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: var(--header-h);
            height: calc(100vh - var(--header-h));
            height: calc(100dvh - var(--header-h));
            overflow: hidden;
        }
        .app-shell.sin-filtros .filtros-panel {
            display: none;
        }
        .app-shell:not(.sin-filtros) {
            flex-direction: row;
        }
        .main-content {
            flex: 1;
            min-width: 0;
        }
        .filtros-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #fff;
            flex-shrink: 0;
            cursor: default;
        }
        .filtros-head-main {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
            flex: 1;
        }
        .filtros-head-brand {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            min-width: 0;
        }
        .filtros-head-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(96, 165, 250, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .filtros-head-icon svg {
            width: 1rem;
            height: 1rem;
            color: #93c5fd;
        }
        .filtros-head-text h3 {
            font-size: 0.875rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .filtros-head-text p {
            font-size: 0.625rem;
            color: #94a3b8;
            margin-top: 0.1rem;
        }
        .filtros-head-actions {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            flex-shrink: 0;
        }
        .filtros-btn-reset,
        .filtros-btn-pin,
        .filtros-btn-export,
        .filtros-btn-close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            padding: 0.4rem 0.65rem;
            font-size: 0.6875rem;
            font-weight: 600;
            border-radius: 0.5rem;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .filtros-btn-reset {
            color: #fecaca;
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.25);
        }
        .filtros-btn-reset:hover {
            background: rgba(239, 68, 68, 0.22);
            color: #fff;
        }
        .filtros-btn-pin {
            color: #cbd5e1;
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.12);
        }
        .filtros-btn-pin:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.12);
        }
        .filtros-btn-pin--active {
            color: #93c5fd;
            background: rgba(0, 102, 204, 0.25);
            border-color: rgba(56, 189, 248, 0.4);
        }
        .filtros-btn-pin svg,
        .filtros-btn-export svg {
            width: 0.9rem;
            height: 0.9rem;
        }
        .filtros-btn-export {
            color: #cbd5e1;
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.12);
            padding: 0.4rem;
        }
        .filtros-btn-export:hover {
            color: #fff;
            background: rgba(16, 185, 129, 0.22);
            border-color: rgba(52, 211, 153, 0.35);
        }
        .filtros-btn-export:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Flujo de aprobación (Sup → Ger → RH) — patrón Supervisor */
        .rh-table-flujo {
            vertical-align: middle;
            text-align: center;
            padding-left: 0.35rem;
            padding-right: 0.35rem;
        }
        .rev-flujo-pipe {
            display: inline-flex;
            align-items: flex-start;
            justify-content: center;
            max-width: 100%;
        }
        .rev-flujo-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            flex-shrink: 0;
            width: 2rem;
        }
        .rev-flujo-step-dot {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }
        .rev-flujo-step-icon {
            width: 0.625rem;
            height: 0.625rem;
        }
        .rev-flujo-step-pending {
            width: 0.375rem;
            height: 0.375rem;
            border-radius: 50%;
            background: currentColor;
        }
        .rev-flujo-step-lbl {
            font-size: 0.5625rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #94a3b8;
            line-height: 1;
        }
        .rev-flujo-step--ok .rev-flujo-step-dot {
            background: #ecfdf5;
            border-color: #34d399;
            color: #059669;
        }
        .rev-flujo-step--ok .rev-flujo-step-lbl { color: #047857; }
        .rev-flujo-step--pend .rev-flujo-step-dot {
            background: #fffbeb;
            border-color: #fbbf24;
            color: #d97706;
        }
        .rev-flujo-step--pend .rev-flujo-step-lbl { color: #b45309; }
        .rev-flujo-step--rech .rev-flujo-step-dot {
            background: #fef2f2;
            border-color: #f87171;
            color: #dc2626;
        }
        .rev-flujo-step--rech .rev-flujo-step-lbl { color: #b91c1c; }
        .rev-flujo-step--na .rev-flujo-step-dot {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #94a3b8;
        }
        .rev-flujo-step-connector {
            flex: 0 0 0.625rem;
            height: 2px;
            background: #e2e8f0;
            margin-top: 0.625rem;
            border-radius: 1px;
        }
        .rev-flujo-step-connector--done { background: #6ee7b7; }

        /* Tabla listado reportes RH */
        .rh-rep-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .rh-rep-table {
            width: 100%;
            min-width: 56rem;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .rh-rep-table thead tr {
            background: linear-gradient(to right, #f9fafb, #f3f4f6);
            border-bottom: 2px solid #e5e7eb;
        }
        .rh-rep-table th {
            padding: 0.65rem 0.75rem;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #374151;
            white-space: nowrap;
            vertical-align: middle;
        }
        .rh-rep-table td {
            padding: 0.65rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
        }
        .rh-rep-table tbody tr {
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .rh-rep-table tbody tr:hover {
            background: #f8fafc;
        }
        .rh-col-id { width: 3.5rem; }
        .rh-col-estado { width: 7rem; }
        .rh-col-tema { width: 22%; min-width: 9rem; }
        .rh-col-fecha { width: 5.75rem; }
        .rh-col-clf { width: 3.5rem; }
        .rh-col-part { width: 3.75rem; }
        .rh-col-flujo { width: 8.25rem; }
        .rh-col-exp { width: 4rem; }
        .rh-col-acc { width: 3.25rem; min-width: 3.25rem; }
        .rh-th-acc {
            text-align: center !important;
            padding-left: 0.35rem !important;
            padding-right: 0.35rem !important;
        }
        .rh-cell-acc {
            text-align: center;
            padding: 0.4rem 0.35rem;
            vertical-align: middle;
            position: relative;
            z-index: 2;
        }
        .rh-rep-table tbody tr.rh-rep-row {
            cursor: pointer;
        }
        .rh-rep-table tbody tr.rh-rep-row .rh-cell-acc {
            cursor: default;
        }
        .rh-btn-icon-only {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            padding: 0;
            border-radius: 0.5rem;
            border: 1px solid transparent;
            background: transparent;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }
        .rh-btn-icon-only svg {
            width: 1.125rem;
            height: 1.125rem;
        }
        .rh-btn-icon-only--danger {
            color: #dc2626;
            border-color: #fecaca;
            background: #fff;
        }
        .rh-btn-icon-only--danger:hover:not(:disabled) {
            background: #fef2f2;
            border-color: #f87171;
            color: #b91c1c;
        }
        .rh-btn-icon-only:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .rh-th-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
        }
        .rh-th-icon svg {
            width: 0.875rem;
            height: 0.875rem;
        }
        .rh-cell-id {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            font-variant-numeric: tabular-nums;
        }
        .rh-cell-estado {
            text-align: left;
        }
        .rh-cell-estado .rh-estado-badge {
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            padding: 0.2rem 0.45rem;
            font-size: 0.625rem;
            font-weight: 600;
            line-height: 1.2;
            border-radius: 9999px;
            white-space: nowrap;
        }
        .rh-cell-tema {
            overflow: hidden;
        }
        .rh-cell-tema p {
            margin: 0;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #111827;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .rh-cell-fecha {
            font-size: 0.75rem;
            color: #6b7280;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }
        .rh-cell-clf,
        .rh-cell-part,
        .rh-cell-exp {
            text-align: center;
        }
        .rh-cell-part .rh-part-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.2rem;
            min-width: 1.75rem;
            padding: 0.15rem 0.4rem;
            font-size: 0.6875rem;
            font-weight: 600;
            color: #4b5563;
            background: #f3f4f6;
            border-radius: 9999px;
        }
        .rh-cell-part .rh-part-badge svg {
            width: 0.75rem;
            height: 0.75rem;
            flex-shrink: 0;
        }
        .rh-rep-table .rh-table-flujo {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }
        .rh-rep-table .rev-flujo-step {
            width: 1.75rem;
        }
        .rh-rep-table .rev-flujo-step-dot {
            width: 1.125rem;
            height: 1.125rem;
        }
        .rh-rep-table .rev-flujo-step-icon {
            width: 0.5625rem;
            height: 0.5625rem;
        }
        .rh-rep-table .rev-flujo-step-lbl {
            font-size: 0.5rem;
        }
        .rh-rep-table .rev-flujo-step-connector {
            flex: 0 0 0.45rem;
            margin-top: 0.5rem;
        }
        .rh-cell-exp .rh-exp-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #059669;
        }
        .rh-cell-exp .rh-exp-icon svg {
            width: 1.125rem;
            height: 1.125rem;
        }
        .rh-cell-exp .rh-exp-pending {
            color: #d1d5db;
            font-size: 0.75rem;
        }

        .filtros-btn-close {
            color: #94a3b8;
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
            padding: 0.4rem;
        }
        .filtros-btn-close:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.12);
        }
        .filtros-panel-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            padding: 0.875rem 0.875rem 0;
        }
        .filtros-grid {
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
            padding-bottom: 0.5rem;
        }
        .filtros-reset-wrap {
            flex-shrink: 0;
            padding: 0.75rem 0.875rem;
            padding-bottom: calc(0.875rem + env(safe-area-inset-bottom, 0));
            border-top: 1px solid #e2e8f0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.92) 0%, #fff 100%);
            box-shadow: 0 -6px 16px rgba(15, 23, 42, 0.05);
        }
        .filtros-card {
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 0.75rem;
            padding: 0.75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            min-width: 0;
        }
        .filtros-card-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.625rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
        }
        .filtros-card-icon {
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .filtros-card-icon svg {
            width: 0.875rem;
            height: 0.875rem;
        }
        .filtros-card-body {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .filtros-inline-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        .filtros-aprob-grid {
            display: grid;
            gap: 0.5rem;
            grid-template-columns: 1fr;
        }
        .filtro-chip {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            min-width: 0;
        }
        .filtro-chip-label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.625rem;
            font-weight: 600;
            color: #64748b;
        }
        .filtro-chip-dot {
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .filtro-field {
            width: 100%;
            padding: 0.5rem 0.625rem;
            font-size: 0.8125rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #f8fafc;
            color: #334155;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .filtro-field:focus {
            outline: none;
            border-color: #0066CC;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }
        .filtro-input-wrap {
            position: relative;
        }
        .filtro-input-wrap .filtro-field {
            padding-left: 2rem;
        }
        .filtro-input-icon {
            position: absolute;
            left: 0.625rem;
            top: 50%;
            transform: translateY(-50%);
            width: 0.875rem;
            height: 0.875rem;
            color: #94a3b8;
            pointer-events: none;
        }
        .filtros-count-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.55rem;
            font-size: 0.625rem;
            font-weight: 600;
            color: #93c5fd;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 9999px;
            white-space: nowrap;
            max-width: 11rem;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .btn-limpiar-filtros {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.7rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #b91c1c;
            background: #fff;
            border: 1px solid #fecaca;
            border-radius: 0.625rem;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
            box-shadow: 0 1px 2px rgba(220, 38, 38, 0.06);
        }
        .btn-limpiar-filtros svg {
            width: 0.9rem;
            height: 0.9rem;
            flex-shrink: 0;
            opacity: 0.85;
        }
        .btn-limpiar-filtros:hover {
            background: #fef2f2;
            border-color: #f87171;
            color: #991b1b;
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.1);
        }
        .btn-limpiar-filtros:active {
            transform: translateY(1px);
        }
        @media (min-width: 768px) {
            .filtros-btn-pin,
            .filtros-btn-close,
            #btnAbrirFiltros {
                display: none !important;
            }
            .filtros-head-main {
                flex-direction: column;
                align-items: stretch;
            }
            .filtros-count-badge {
                max-width: none;
                align-self: flex-start;
                margin-top: 0.35rem;
            }
        }
        @media (min-width: 768px) and (max-width: 1023px) {
            .filtros-panel {
                width: 14.5rem;
            }
            .filtros-panel-head {
                padding: 0.65rem 0.75rem;
            }
            .filtros-head-text h3 {
                font-size: 0.8125rem;
            }
            .filtros-panel-body {
                padding: 0.65rem 0.65rem 0;
            }
            .filtros-reset-wrap {
                padding: 0.65rem 0.65rem 0.75rem;
            }
            .filtros-card {
                padding: 0.625rem;
            }
            .filtros-card-title {
                font-size: 0.625rem;
                margin-bottom: 0.5rem;
            }
            .filtro-field {
                font-size: 0.75rem;
                padding: 0.45rem 0.5rem;
            }
            .filtro-input-wrap .filtro-field {
                padding-left: 1.75rem;
            }
            .filtros-aprob-grid {
                gap: 0.4rem;
            }
            .btn-limpiar-filtros {
                font-size: 0.6875rem;
                padding: 0.5rem;
            }
        }
        @media (min-width: 1024px) and (max-width: 1366px) {
            .filtros-panel {
                width: 15.5rem;
            }
            .filtros-panel-head {
                padding: 0.7rem 0.875rem;
            }
            .filtros-panel-body {
                padding: 0.75rem 0.75rem 0;
            }
            .filtros-reset-wrap {
                padding: 0.7rem 0.75rem 0.8rem;
            }
            .filtros-card {
                padding: 0.65rem;
            }
            .filtro-field {
                font-size: 0.78125rem;
            }
        }
        .filtros-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            z-index: 44;
        }
        section {
            animation: fadeSlideIn 0.25s ease both;
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .section-hero {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem 1.125rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            position: relative;
            overflow: hidden;
        }
        .section-hero::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #0066CC;
            border-radius: 0.75rem 0 0 0.75rem;
        }
        .section-hero-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.625rem;
            background: #eff6ff;
            color: #0066CC;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .section-hero-icon svg {
            width: 1.35rem;
            height: 1.35rem;
        }
        .section-hero-body {
            flex: 1;
            min-width: 0;
            padding-left: 0.125rem;
        }
        .section-hero-eyebrow {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #0066CC;
            line-height: 1.2;
            margin-bottom: 0.2rem;
        }
        .section-hero-title {
            font-size: 1.375rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.025em;
            line-height: 1.2;
        }
        .section-hero-sub {
            font-size: 0.8125rem;
            color: #64748b;
            margin-top: 0.35rem;
            line-height: 1.4;
            max-width: 42rem;
        }
        .section-hero-meta {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.45rem;
            line-height: 1.35;
        }
        .section-hero-meta strong,
        .section-hero-meta .hero-meta-em {
            font-weight: 600;
            color: #475569;
        }
        .block-card-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            background: #fff;
        }
        .block-card-toolbar--compact {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
        .block-card-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.25;
        }
        .block-card-sub {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.15rem;
            line-height: 1.35;
        }
        .kpi-board {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.5rem;
            margin-bottom: 0.875rem;
        }
        @media (min-width: 768px) {
            .kpi-board {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        .kpi-tile {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.75rem 0.875rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            border-top-width: 3px;
            border-top-style: solid;
            cursor: pointer;
            font-family: inherit;
            text-align: left;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }
        .kpi-tile:hover {
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.07);
            transform: translateY(-1px);
        }
        .kpi-tile-head {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            width: 100%;
        }
        .kpi-tile-icon {
            width: 1.625rem;
            height: 1.625rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .kpi-tile-icon svg {
            width: 1rem;
            height: 1rem;
        }
        .kpi-tile-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            line-height: 1.2;
        }
        .kpi-tile-val {
            font-size: 1.375rem;
            font-weight: 700;
            line-height: 1;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }
        .kpi-tile--pend {
            border-top-color: #f59e0b;
        }
        .kpi-tile--pend .kpi-tile-icon {
            background: #fff7ed;
            color: #d97706;
        }
        .kpi-tile--pend .kpi-tile-val {
            color: #b45309;
        }
        .kpi-tile--acept {
            border-top-color: #22c55e;
        }
        .kpi-tile--acept .kpi-tile-icon {
            background: #f0fdf4;
            color: #16a34a;
        }
        .kpi-tile--acept .kpi-tile-val {
            color: #15803d;
        }
        .kpi-tile--rech {
            border-top-color: #ef4444;
        }
        .kpi-tile--rech .kpi-tile-icon {
            background: #fef2f2;
            color: #dc2626;
        }
        .kpi-tile--rech .kpi-tile-val {
            color: #b91c1c;
        }
        .kpi-tile--mes {
            border-top-color: #3b82f6;
        }
        .kpi-tile--mes .kpi-tile-icon {
            background: #eff6ff;
            color: #2563eb;
        }
        .kpi-tile--mes .kpi-tile-val {
            color: #1d4ed8;
        }
        .kpi-tile--active {
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25), 0 4px 10px rgba(15, 23, 42, 0.07);
        }
        .kpi-tile--active .kpi-tile-icon {
            background: #ffedd5;
        }
        .kpi-inicio-context {
            font-size: 0.75rem;
            color: #64748b;
            margin: -0.25rem 0 0.75rem;
            line-height: 1.35;
        }
        .filtros-activos-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.375rem;
            margin-bottom: 0.875rem;
        }
        .filtros-activos-strip.hidden {
            display: none;
        }
        .filtro-activo-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.45rem 0.2rem 0.6rem;
            font-size: 0.6875rem;
            font-weight: 600;
            color: #475569;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .filtro-activo-chip-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1rem;
            height: 1rem;
            padding: 0;
            border: none;
            border-radius: 9999px;
            background: #f1f5f9;
            color: #94a3b8;
            cursor: pointer;
            line-height: 1;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .filtro-activo-chip-remove:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        .filtro-activo-chip-remove svg {
            width: 0.625rem;
            height: 0.625rem;
        }
        .btn-limpiar-chips {
            font-size: 0.6875rem;
            font-weight: 600;
            color: #94a3b8;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.2rem 0.35rem;
            margin-left: 0.15rem;
        }
        .btn-limpiar-chips:hover {
            color: #dc2626;
        }
        .kpi-tile--total {
            border-top-color: #0066cc;
        }
        .kpi-tile--total .kpi-tile-icon {
            background: #eff6ff;
            color: #0066cc;
        }
        .kpi-tile--total .kpi-tile-val {
            color: #004c99;
        }
        .kpi-tile--pct {
            border-top-color: #8b5cf6;
        }
        .kpi-tile--pct .kpi-tile-icon {
            background: #f5f3ff;
            color: #7c3aed;
        }
        .kpi-tile--pct .kpi-tile-val {
            color: #6d28d9;
        }
        .kpi-tile--static {
            cursor: default;
        }
        .kpi-tile--static:hover {
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transform: none;
        }
        .stats-kpi-board {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            background: linear-gradient(to bottom, #f8fafc, #fff);
        }
        @media (min-width: 640px) {
            .stats-kpi-board {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (min-width: 1024px) {
            .stats-kpi-board {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }
        .stats-metas-block {
            padding: 1rem 1.5rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            background: #fff;
        }
        .stats-metas-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.875rem;
        }
        .stats-metas-title {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }
        .stats-metas-sub {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.2rem;
        }
        .stats-metas-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }
        .stats-metas-add {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        .stats-metas-add input {
            width: 9rem;
            padding: 0.375rem 0.625rem;
            font-size: 0.8125rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
        }
        .stats-metas-table-wrap {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
        }
        .stats-metas-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }
        .stats-metas-table th,
        .stats-metas-table td {
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
            white-space: nowrap;
        }
        .stats-metas-table th {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
            background: #f8fafc;
        }
        .stats-metas-table tr:last-child td {
            border-bottom: none;
        }
        .stats-metas-table tbody tr:hover {
            background: #fafbfc;
        }
        .stats-metas-input {
            width: 4.5rem;
            padding: 0.3rem 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            border-radius: 0.375rem;
            text-align: center;
        }
        .stats-metas-input:focus {
            outline: none;
            border-color: #0066CC;
            box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.15);
        }
        .stats-metas-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            font-size: 0.6875rem;
            font-weight: 700;
        }
        .stats-metas-badge--ok {
            background: #dcfce7;
            color: #15803d;
        }
        .stats-metas-badge--no {
            background: #fef3c7;
            color: #b45309;
        }
        .stats-metas-badge--na {
            background: #f1f5f9;
            color: #64748b;
        }
        .stats-metas-empty {
            padding: 1.25rem;
            text-align: center;
            font-size: 0.8125rem;
            color: #94a3b8;
        }
        .stats-metas-hint {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.625rem;
        }
        .meta-mensual-modal {
            max-width: min(96vw, 76rem);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .meta-mensual-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 45%, #fff 100%);
            border-bottom: 1px solid #e2e8f0;
        }
        .meta-mensual-header-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            background: #0066CC;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.25);
        }
        .meta-mensual-header-icon svg { width: 1.25rem; height: 1.25rem; }
        .meta-mensual-header-title {
            font-size: 1.125rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .meta-mensual-header-sub {
            font-size: 0.8125rem;
            color: #64748b;
            margin-top: 0.2rem;
            line-height: 1.4;
        }
        .meta-mensual-depto-badge {
            display: inline-flex;
            align-items: center;
            margin-top: 0.5rem;
            padding: 0.2rem 0.55rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #1d4ed8;
            background: #dbeafe;
            border-radius: 999px;
        }
        .meta-mensual-body {
            padding: 1.25rem 1.5rem;
            overflow-y: auto;
            flex: 1;
            background: #f8fafc;
        }
        .meta-mensual-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 0.75rem 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .meta-mensual-toolbar-field label {
            display: block;
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.3rem;
        }
        .meta-mensual-toolbar-field select {
            min-width: 11rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            background: #fff;
            color: #0f172a;
        }
        .meta-mensual-toolbar-field select:focus {
            outline: none;
            border-color: #0066CC;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.12);
        }
        .meta-mensual-leyenda {
            flex: 1 1 100%;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.15rem;
        }
        .meta-mensual-leyenda-chip {
            font-size: 0.6875rem;
            font-weight: 600;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 0.25rem 0.6rem;
        }
        .meta-mensual-leyenda-chip strong { color: #0f172a; }
        .meta-mensual-nota-consolidada {
            flex: 1 1 100%;
            margin: 0;
            padding: 0.55rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #1e40af;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 0.625rem;
            line-height: 1.4;
        }
        .meta-mensual-scroll {
            overflow: auto;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            background: #fff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }
        .meta-mensual-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.8125rem;
            min-width: 940px;
        }
        .meta-mensual-grid th,
        .meta-mensual-grid td {
            border-bottom: 1px solid #eef2f7;
            border-right: 1px solid #eef2f7;
            padding: 0.45rem 0.35rem;
            text-align: center;
            vertical-align: middle;
        }
        .meta-mensual-grid th:last-child,
        .meta-mensual-grid td:last-child { border-right: none; }
        .meta-mensual-grid thead th {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            color: #475569;
            position: sticky;
            top: 0;
            z-index: 3;
            padding: 0.55rem 0.35rem;
        }
        .meta-mensual-grid thead th.meta-col-total {
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
            color: #1d4ed8;
        }
        .meta-mensual-grid .meta-row-label {
            text-align: left;
            font-weight: 600;
            color: #334155;
            background: #fff;
            min-width: 10.5rem;
            position: sticky;
            left: 0;
            z-index: 2;
            padding-left: 0.75rem;
            box-shadow: 2px 0 4px rgba(15, 23, 42, 0.04);
        }
        .meta-mensual-grid .meta-row-section td {
            background: linear-gradient(90deg, #eff6ff 0%, #f8fafc 100%);
            color: #1e40af;
            font-weight: 800;
            text-align: left;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-size: 0.625rem;
            padding: 0.4rem 0.75rem;
            position: sticky;
            left: 0;
        }
        .meta-mensual-grid .meta-row-sub .meta-row-label {
            padding-left: 1.25rem;
            font-weight: 500;
            color: #64748b;
            font-size: 0.75rem;
        }
        .meta-mensual-grid .meta-row-sub.meta-row-input .meta-row-label {
            color: #334155;
            font-weight: 600;
        }
        .meta-mensual-grid .meta-row-total .meta-row-label {
            background: #f8fafc;
            font-weight: 700;
            color: #0f172a;
        }
        .meta-mensual-grid .meta-row-fixed .meta-row-label {
            color: #94a3b8;
            font-style: italic;
        }
        .meta-mensual-grid .meta-row-calc td {
            background: #fafbfc;
        }
        .meta-mensual-grid .meta-row-calc .meta-row-label {
            background: #fafbfc;
        }
        .meta-mensual-input-wrap {
            display: flex;
            justify-content: center;
            padding: 0.1rem;
        }
        .meta-mensual-input {
            width: 100%;
            max-width: 4.5rem;
            min-width: 2.75rem;
            min-height: 2rem;
            padding: 0.35rem 0.4rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-align: center;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #fff;
            color: #0f172a;
            -moz-appearance: textfield;
            appearance: textfield;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .meta-mensual-input::placeholder {
            color: #cbd5e1;
            font-weight: 500;
        }
        .meta-mensual-input:hover {
            border-color: #cbd5e1;
            background: #fafbfc;
        }
        .meta-mensual-input:focus {
            outline: none;
            border-color: #0066CC;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.14);
        }
        .meta-mensual-input::-webkit-outer-spin-button,
        .meta-mensual-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .meta-mensual-pct {
            display: inline-block;
            min-width: 2.85rem;
            padding: 0.2rem 0.45rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.6875rem;
        }
        .meta-mensual-pct--ok { background: #dcfce7; color: #166534; }
        .meta-mensual-pct--mid { background: #fef9c3; color: #854d0e; }
        .meta-mensual-pct--low { background: #fee2e2; color: #991b1b; }
        .meta-mensual-pct--na { background: #f1f5f9; color: #94a3b8; }
        .meta-mensual-calc {
            font-weight: 800;
            color: #0f172a;
            font-variant-numeric: tabular-nums;
        }
        .meta-mensual-col-total {
            background: #f8fbff !important;
            font-weight: 800;
        }
        .meta-mensual-empty {
            text-align: center;
            color: #94a3b8;
            padding: 2.5rem 1rem;
            font-size: 0.875rem;
            background: #fff;
            border: 1px dashed #e2e8f0;
            border-radius: 0.875rem;
        }
        .meta-mensual-estado-wrap {
            flex-shrink: 0;
        }
        .meta-mensual-footer .meta-mensual-estado-wrap {
            margin-left: auto;
        }
        .meta-mensual-estado {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8125rem;
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            border: 1px solid transparent;
            min-width: 8.5rem;
            justify-content: center;
        }
        .meta-mensual-footer .meta-mensual-estado {
            min-width: 9.5rem;
            padding: 0.55rem 1.15rem;
            font-size: 0.875rem;
        }
        .meta-mensual-estado-icon {
            position: relative;
            width: 1.25rem;
            height: 1.25rem;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .meta-mensual-estado-icon svg {
            width: 100%;
            height: 100%;
            display: block;
            overflow: visible;
        }
        .meta-mensual-estado--idle { color: #64748b; background: #f8fafc; border-color: #e2e8f0; }
        .meta-mensual-estado--pending { color: #92400e; background: #fef3c7; border-color: #fde68a; }
        .meta-mensual-estado--saving { color: #1d4ed8; background: #dbeafe; border-color: #93c5fd; }
        .meta-mensual-estado--saved { color: #15803d; background: #dcfce7; border-color: #86efac; }
        .meta-mensual-estado--error { color: #991b1b; background: #fee2e2; border-color: #fca5a5; }
        @keyframes metaEstadoPop {
            0% { transform: scale(0.92); }
            45% { transform: scale(1.04); }
            100% { transform: scale(1); }
        }
        @keyframes metaIconSpin {
            to { transform: rotate(360deg); }
        }
        @keyframes metaSavedRing {
            0% { transform: scale(0.75); opacity: 0.55; stroke-width: 2.5; }
            100% { transform: scale(1.65); opacity: 0; stroke-width: 0.5; }
        }
        @keyframes metaSavedCircleDraw {
            to { stroke-dashoffset: 0; }
        }
        @keyframes metaSavedCheckDraw {
            to { stroke-dashoffset: 0; }
        }
        @keyframes metaSavedBounce {
            0% { transform: scale(0.6); opacity: 0; }
            55% { transform: scale(1.12); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes metaPendingPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.08); opacity: 0.85; }
        }
        @keyframes metaEstadoTextSlide {
            0% { opacity: 0; transform: translateX(-6px); }
            100% { opacity: 1; transform: translateX(0); }
        }
        .meta-estado-icon-spin {
            transform-origin: center;
            animation: metaIconSpin 0.75s linear infinite;
        }
        .meta-mensual-estado--anim {
            animation: metaEstadoPop 0.5s cubic-bezier(0.34, 1.45, 0.64, 1);
        }
        .meta-mensual-estado--anim #metaMensualEstadoText {
            display: inline-block;
            animation: metaEstadoTextSlide 0.32s ease-out both;
        }
        .meta-mensual-estado--anim.meta-mensual-estado--saved #metaMensualEstadoIcon svg {
            animation: metaSavedBounce 0.55s cubic-bezier(0.34, 1.45, 0.64, 1) both;
        }
        .meta-mensual-estado--anim.meta-mensual-estado--saved .meta-saved-ring {
            transform-origin: 12px 12px;
            animation: metaSavedRing 0.65s ease-out 0.15s both;
        }
        .meta-mensual-estado--anim.meta-mensual-estado--saved .meta-saved-circle {
            stroke-dasharray: 63;
            stroke-dashoffset: 63;
            animation: metaSavedCircleDraw 0.42s cubic-bezier(0.4, 0, 0.2, 1) 0.08s forwards;
        }
        .meta-mensual-estado--anim.meta-mensual-estado--saved .meta-saved-check {
            stroke-dasharray: 12;
            stroke-dashoffset: 12;
            animation: metaSavedCheckDraw 0.32s cubic-bezier(0.4, 0, 0.2, 1) 0.38s forwards;
        }
        .meta-mensual-estado--anim.meta-mensual-estado--pending .meta-pending-dot {
            transform-origin: center;
            animation: metaPendingPulse 1.1s ease-in-out infinite;
        }
        .meta-mensual-estado--anim.meta-mensual-estado--saving #metaMensualEstadoText {
            animation: metaEstadoTextSlide 0.28s ease-out both;
        }
        .meta-mensual-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            background: #fff;
        }
        @media (max-width: 640px) {
            .meta-mensual-footer .meta-mensual-estado-wrap {
                margin-left: 0;
                width: 100%;
                display: flex;
                justify-content: center;
            }
        }
        .meta-mensual-footer-note {
            font-size: 0.75rem;
            color: #64748b;
            line-height: 1.4;
            flex: 1;
            min-width: 0;
        }
        .meta-resumen-modal {
            max-width: min(1180px, 100%);
        }
        .meta-resumen-preview-scroll {
            overflow: auto;
            max-height: min(58vh, 480px);
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            background: #fff;
            padding: 0.75rem;
        }
        .meta-resumen-bloque {
            margin-bottom: 1.25rem;
        }
        .meta-resumen-bloque:last-child {
            margin-bottom: 0;
        }
        .meta-resumen-grid-wrap {
            overflow: auto;
            border: 1px solid #e2e8f0;
            border-radius: 0.625rem;
        }
        .meta-resumen-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.8125rem;
            min-width: 980px;
        }
        .meta-resumen-grid th,
        .meta-resumen-grid td {
            border-bottom: 1px solid #eef2f7;
            border-right: 1px solid #eef2f7;
            padding: 0.4rem 0.35rem;
            text-align: center;
            vertical-align: middle;
        }
        .meta-resumen-grid th:last-child,
        .meta-resumen-grid td:last-child { border-right: none; }
        .meta-resumen-grid thead th {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            color: #475569;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .meta-resumen-grid thead th.meta-col-total {
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
            color: #1d4ed8;
        }
        .meta-resumen-dept {
            font-weight: 800;
            background: #fff;
            vertical-align: middle;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: #1e40af;
            min-width: 4.25rem;
            border-right: 2px solid #dbeafe !important;
        }
        .meta-resumen-cat {
            font-weight: 700;
            background: #f8fafc;
            font-size: 0.6875rem;
            text-transform: uppercase;
            color: #475569;
            min-width: 4.5rem;
        }
        .meta-resumen-cat--total {
            background: #eff6ff;
            color: #1d4ed8;
        }
        .meta-resumen-concept {
            text-align: left;
            font-weight: 600;
            color: #334155;
            font-size: 0.75rem;
            padding-left: 0.65rem;
            min-width: 7.25rem;
            background: #fff;
        }
        .meta-resumen-concept--total {
            background: #f8fafc;
            font-weight: 700;
        }
        .meta-resumen-target {
            font-weight: 700;
            color: #64748b;
            background: #fafbfc;
            min-width: 3.25rem;
        }
        .meta-resumen-grid .meta-row-total td {
            background: #f8fbff;
        }
        .meta-resumen-loading {
            text-align: center;
            padding: 2.5rem 1rem;
            color: #64748b;
            font-size: 0.875rem;
        }
        .stats-body {
            padding: 1.25rem 1.5rem 1.5rem;
        }
        .stats-chart-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 768px) {
            .stats-chart-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .stats-chart-card--wide {
                grid-column: span 2;
            }
        }
        .stats-chart-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .stats-chart-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            background: #f8fafc;
        }
        .stats-chart-card-title {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }
        .stats-chart-card-sub {
            font-size: 0.6875rem;
            font-weight: 500;
            color: #94a3b8;
            letter-spacing: normal;
            text-transform: none;
        }
        .stats-chart-wrap--funnel {
            min-height: 220px;
            height: auto;
        }
        .stats-chart-wrap {
            position: relative;
            height: 220px;
            padding: 0.75rem 1rem 1rem;
        }
        .stats-chart-wrap canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }
        .stats-chart-wrap--ranking {
            height: auto;
            min-height: 240px;
        }
        .stats-chart-wrap--compact {
            height: 200px;
        }
        .stats-chart-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 120px;
            padding: 1.5rem 1rem;
            text-align: center;
            font-size: 0.8125rem;
            color: #94a3b8;
        }
        .stats-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 3rem 1.5rem;
            text-align: center;
            color: #94a3b8;
        }
        .stats-empty-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 9999px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
        }
        .stats-empty-icon svg {
            width: 1.5rem;
            height: 1.5rem;
        }
        @media (max-width: 767px) {
            .app-shell:not(.filtros-fijados):not(.sin-filtros) {
                flex-direction: column;
            }
            .app-shell:not(.filtros-fijados) .filtros-panel {
                position: fixed;
                left: 0;
                top: var(--header-h);
                z-index: 45;
                width: min(100vw, 20rem);
                height: calc(100vh - var(--header-h));
                height: calc(100dvh - var(--header-h));
                padding-bottom: env(safe-area-inset-bottom, 0);
                transform: translateX(-100%);
                transition: transform 0.25s ease;
                box-shadow: 4px 0 24px rgba(15, 23, 42, 0.2);
            }
            .app-shell:not(.filtros-fijados) .filtros-panel.open {
                transform: translateX(0);
            }
            .app-shell.filtros-fijados:not(.sin-filtros) {
                flex-direction: row;
            }
            .app-shell.filtros-fijados .filtros-panel {
                position: sticky;
                transform: none;
                width: min(14.5rem, 46vw);
                box-shadow: none;
            }
            .app-shell.filtros-fijados .filtros-btn-close {
                display: none;
            }
            .filtros-overlay.open {
                display: block;
            }
        }
        .tabla-empleados {
            table-layout: fixed;
            width: 100%;
            font-size: 0.8125rem;
        }
        .tabla-empleados th,
        .tabla-empleados td {
            vertical-align: middle;
            padding: 0.5rem 0.375rem;
            overflow: hidden;
        }
        .tabla-empleados thead th {
            font-size: 0.625rem;
            letter-spacing: 0.04em;
            line-height: 1.2;
        }
        .tabla-empleados .emp-cell-id {
            width: 3.25rem;
            white-space: nowrap;
        }
        .tabla-empleados .emp-cell-nombre {
            width: 24%;
            overflow: visible;
            white-space: normal;
            word-break: break-word;
        }
        .tabla-empleados .emp-cell-nombre .emp-nombre-link {
            white-space: normal;
            word-break: break-word;
            overflow: visible;
            text-overflow: unset;
            line-height: 1.35;
        }
        .tabla-empleados .emp-cell-nombre .emp-baja-nota {
            white-space: normal;
            word-break: break-word;
            line-height: 1.3;
        }
        .tabla-empleados .emp-cell-depto {
            width: 9%;
        }
        .tabla-empleados .emp-cell-puesto {
            width: 10%;
        }
        .tabla-empleados .emp-cell-clasificacion {
            width: 11%;
        }
        .tabla-empleados .emp-cell-supervisor,
        .tabla-empleados .emp-cell-gerente {
            width: 14%;
        }
        .tabla-empleados .emp-cell-acciones {
            width: 7.5rem;
        }
        .tabla-empleados .emp-cell-puesto,
        .tabla-empleados .emp-cell-clasificacion {
            white-space: nowrap;
            text-align: center;
        }
        .emp-clasificacion-select {
            width: 100%;
            max-width: 6.75rem;
            padding: 0.2rem 0.35rem;
            font-size: 0.6875rem;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            border-radius: 0.375rem;
            background: #fff;
            color: #475569;
            cursor: pointer;
        }
        .emp-clasificacion-select:focus {
            outline: none;
            border-color: #0066CC;
            box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.12);
        }
        .emp-clasificacion-select:disabled {
            opacity: 0.6;
            cursor: wait;
        }
        .emp-jerarquia-compact {
            display: block;
            font-size: 0.75rem;
            color: #475569;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .emp-jerarquia-compact--empty {
            color: #cbd5e1;
            font-size: 0.75rem;
        }
        .emp-badge-compact {
            display: inline-flex;
            padding: 0.125rem 0.5rem;
            font-size: 0.6875rem;
            font-weight: 700;
            border-radius: 999px;
            line-height: 1.2;
        }
        .emp-acciones {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.15rem;
            flex-wrap: nowrap;
        }
        .emp-accion-btn {
            width: 1.75rem;
            height: 1.75rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            border: 1px solid transparent;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }
        .emp-accion-btn svg {
            width: 1rem;
            height: 1rem;
        }
        .emp-accion-btn--info {
            color: #475569;
            border-color: #cbd5e1;
            background: #fff;
        }
        .emp-accion-btn--info:hover {
            color: #fff;
            background: #475569;
            border-color: #475569;
        }
        .emp-accion-btn--pass {
            color: #2563eb;
            border-color: #93c5fd;
            background: #fff;
        }
        .emp-accion-btn--pass:hover {
            color: #fff;
            background: #2563eb;
            border-color: #2563eb;
        }
        .emp-accion-btn--baja,
        .emp-accion-btn--eliminar {
            color: #dc2626;
            border-color: #fca5a5;
            background: #fff;
        }
        .emp-accion-btn--baja:hover,
        .emp-accion-btn--eliminar:hover {
            color: #fff;
            background: #dc2626;
            border-color: #dc2626;
        }
        .emp-accion-btn--reactivar {
            color: #16a34a;
            border-color: #86efac;
            background: #fff;
        }
        .emp-accion-btn--reactivar:hover {
            color: #fff;
            background: #16a34a;
            border-color: #16a34a;
        }
        .emp-accion-btn--asignar {
            color: #7c3aed;
            border-color: #c4b5fd;
            background: #fff;
        }
        .emp-accion-btn--asignar:hover {
            color: #fff;
            background: #7c3aed;
            border-color: #7c3aed;
        }
        .emp-text-truncate {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .emp-detalle-modal {
            max-width: 42rem;
        }
        .emp-detalle-header {
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 55%, #fff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            padding: 1.25rem;
        }
        .emp-detalle-avatar {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .emp-detalle-seccion {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            overflow: hidden;
            background: #fff;
        }
        .emp-detalle-seccion-titulo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
        }
        .emp-detalle-seccion-titulo svg {
            width: 0.95rem;
            height: 0.95rem;
            color: #94a3b8;
        }
        .emp-detalle-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0;
        }
        .emp-detalle-campo {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .emp-detalle-campo:nth-child(odd) {
            border-right: 1px solid #f1f5f9;
        }
        .emp-detalle-campo--full {
            grid-column: 1 / -1;
            border-right: none !important;
        }
        .emp-detalle-label {
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 0.25rem;
        }
        .emp-detalle-valor {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1e293b;
            word-break: break-word;
        }
        .emp-detalle-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .emp-detalle-editar-nota {
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
            padding: 0.875rem 1rem;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 0.75rem;
            font-size: 0.8rem;
            color: #1d4ed8;
        }
        @media (max-width: 480px) {
            .emp-detalle-grid {
                grid-template-columns: 1fr;
            }
            .emp-detalle-campo:nth-child(odd) {
                border-right: none;
            }
        }
        .org-card-acciones {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            width: 100%;
        }
        .org-card-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.5rem;
            border: 1px solid transparent;
            background: #f8fafc;
            color: #64748b;
            transition: all 0.15s ease;
        }
        .org-card-btn svg {
            width: 0.9rem;
            height: 0.9rem;
        }
        .org-card-btn--info:hover {
            color: #2563eb;
            background: #eff6ff;
            border-color: #bfdbfe;
        }
        .org-card-btn--asignar:hover {
            color: #7c3aed;
            background: #f5f3ff;
            border-color: #ddd6fe;
        }
        .org-chart-viewport {
            max-height: min(68vh, 42rem);
            overflow: auto;
            overscroll-behavior: contain;
        }
        .org-chart-scroll {
            overflow-x: auto;
            padding: 0.25rem;
            min-width: min-content;
        }
        .org-chart-list {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            min-width: min-content;
        }
        .org-chart-block {
            background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 0.75rem 1rem 1.25rem;
        }
        .org-chart-block-toolbar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .org-block-toggle,
        .org-unassigned-toggle {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: all 0.15s ease;
        }
        .org-block-toggle svg,
        .org-unassigned-toggle svg {
            width: 0.9rem;
            height: 0.9rem;
            transition: transform 0.2s ease;
        }
        .org-chart-block--collapsed .org-block-toggle svg,
        .org-unassigned-panel--collapsed .org-unassigned-toggle svg {
            transform: rotate(-90deg);
        }
        .org-block-toggle:hover,
        .org-unassigned-toggle:hover {
            border-color: #93c5fd;
            color: #2563eb;
            background: #eff6ff;
        }
        .org-block-summary {
            font-size: 0.72rem;
            color: #64748b;
            flex: 1;
            min-width: 0;
        }
        .org-block-link {
            font-size: 0.68rem;
            font-weight: 600;
            color: #2563eb;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 9999px;
            padding: 0.25rem 0.6rem;
            cursor: pointer;
            flex-shrink: 0;
        }
        .org-block-link:hover {
            background: #dbeafe;
        }
        .org-chart-block--collapsed .org-chart-rama {
            display: none;
        }
        .org-chart-block--collapsed {
            padding-bottom: 0.75rem;
        }
        .org-chart-block--collapsed .org-chart {
            padding-bottom: 0;
        }
        .org-chart {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .org-vline {
            width: 2px;
            height: 1.5rem;
            background: #cbd5e1;
            flex-shrink: 0;
        }
        .org-branches {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 2rem;
            position: relative;
            padding-top: 1.5rem;
            width: 100%;
        }
        .org-branches--multi::before {
            content: '';
            position: absolute;
            top: 0;
            left: 8%;
            right: 8%;
            height: 2px;
            background: #cbd5e1;
        }
        .org-branches--multi {
            gap: 2.5rem;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
        .org-branch {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 9.5rem;
            flex-shrink: 0;
        }
        .org-team-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            max-width: 10.5rem;
        }
        .org-team-more {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.15rem;
            padding-top: 0.45rem;
            border-top: 1px dashed #e2e8f0;
            width: 100%;
        }
        .org-team-more-count {
            font-size: 0.68rem;
            font-weight: 600;
            color: #64748b;
            text-align: center;
            line-height: 1.3;
        }
        .org-team-col--grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.35rem;
            width: 11rem;
            align-items: stretch;
        }
        .org-team-col--scroll {
            max-height: 13.5rem;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 0.35rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #f8fafc;
        }
        .org-team-summary {
            width: 10.5rem;
            padding: 0.65rem 0.5rem;
            border: 1px dashed #cbd5e1;
            border-radius: 0.625rem;
            background: #f8fafc;
            text-align: center;
        }
        .org-team-count {
            font-size: 0.72rem;
            font-weight: 700;
            color: #475569;
        }
        .org-team-actions {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            margin-top: 0.45rem;
        }
        .org-btn-equipo {
            font-size: 0.62rem;
            font-weight: 600;
            padding: 0.3rem 0.55rem;
            border-radius: 9999px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .org-btn-equipo:hover {
            border-color: #3b82f6;
            color: #2563eb;
            background: #eff6ff;
        }
        .org-btn-equipo--primary {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #2563eb;
        }
        .org-btn-collapse {
            grid-column: 1 / -1;
            font-size: 0.62rem;
            font-weight: 600;
            color: #64748b;
            padding: 0.25rem;
            cursor: pointer;
            border: none;
            background: transparent;
            text-decoration: underline;
        }
        .org-node--compact {
            width: 100%;
            padding: 0.4rem 0.3rem;
        }
        .org-node--compact .org-node-avatar {
            width: 1.5rem;
            height: 1.5rem;
            font-size: 0.62rem;
            margin-bottom: 0.2rem;
        }
        .org-node--compact .org-node-rol,
        .org-node--compact .org-node-depto {
            display: none;
        }
        .org-node--compact .org-node-nombre {
            font-size: 0.62rem;
            line-height: 1.2;
        }
        .org-branches--dense {
            gap: 1.75rem;
        }
        .org-node {
            width: 10.5rem;
            padding: 0.75rem 0.625rem;
            border-radius: 0.75rem;
            border: 2px solid;
            background: #fff;
            text-align: center;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }
        .org-node:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.1);
        }
        .org-node-avatar {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 9999px;
            margin: 0 auto 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .org-node-rol {
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 0.2rem;
        }
        .org-node-nombre {
            font-size: 0.78rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.25;
            word-break: break-word;
        }
        .org-node-depto {
            font-size: 0.65rem;
            color: #64748b;
            margin-top: 0.2rem;
        }
        .org-node-meta {
            font-size: 0.6rem;
            margin-top: 0.25rem;
            opacity: 0.85;
        }
        .org-node--gerente {
            border-color: #3b82f6;
            background: linear-gradient(180deg, #eff6ff 0%, #fff 100%);
        }
        .org-node--gerente .org-node-avatar {
            background: #2563eb;
            color: #fff;
        }
        .org-node--gerente .org-node-rol { color: #2563eb; }
        .org-node--supervisor {
            border-color: #10b981;
            background: linear-gradient(180deg, #ecfdf5 0%, #fff 100%);
        }
        .org-node--supervisor .org-node-avatar {
            background: #059669;
            color: #fff;
        }
        .org-node--supervisor .org-node-rol { color: #059669; }
        .org-node--trabajador {
            border-color: #cbd5e1;
            width: 9.5rem;
            padding: 0.55rem 0.5rem;
        }
        .org-node--trabajador .org-node-avatar {
            width: 1.75rem;
            height: 1.75rem;
            font-size: 0.7rem;
            background: #e2e8f0;
            color: #475569;
            margin-bottom: 0.35rem;
        }
        .org-node--trabajador .org-node-rol { color: #64748b; }
        .org-node--trabajador:hover {
            border-color: #14b8a6;
        }
        .org-empty-level,
        .org-empty-team {
            font-size: 0.75rem;
            color: #94a3b8;
            font-style: italic;
            text-align: center;
            padding: 0.75rem 1rem;
            border: 1px dashed #e2e8f0;
            border-radius: 0.5rem;
            background: #f8fafc;
            max-width: 10rem;
        }
        .org-unassigned-panel {
            position: sticky;
            top: 0;
            z-index: 5;
            background: linear-gradient(180deg, #fffbeb 0%, #fff 100%);
            border: 2px dashed #fcd34d;
            border-radius: 0.875rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.08);
        }
        .org-unassigned-header {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.75rem 0.875rem;
        }
        .org-unassigned-header-icon {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.625rem;
            background: #fef3c7;
            color: #b45309;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .org-unassigned-body {
            padding: 0 0.875rem 0.75rem;
        }
        .org-unassigned-panel--collapsed .org-unassigned-body {
            display: none;
        }
        .org-unassigned-buscador {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.65rem;
            border: 1px solid #fde68a;
            border-radius: 0.5rem;
            background: #fff;
            margin-bottom: 0.5rem;
        }
        .org-unassigned-buscador svg {
            width: 0.85rem;
            height: 0.85rem;
            color: #d97706;
            flex-shrink: 0;
        }
        .org-unassigned-buscador input {
            width: 100%;
            border: none;
            outline: none;
            font-size: 0.75rem;
            color: #78350f;
            background: transparent;
        }
        .org-unassigned-lista {
            max-height: 11rem;
            overflow-y: auto;
            overscroll-behavior: contain;
            border: 1px solid #fde68a;
            border-radius: 0.5rem;
            background: #fff;
        }
        .org-unassigned-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem 0.65rem;
            border-bottom: 1px solid #fff7ed;
        }
        .org-unassigned-item:last-child {
            border-bottom: none;
        }
        .org-unassigned-item.hidden {
            display: none;
        }
        .org-unassigned-item-avatar {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.45rem;
            background: #fef3c7;
            color: #b45309;
            font-size: 0.7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .org-unassigned-item-info {
            flex: 1;
            min-width: 0;
            cursor: pointer;
        }
        .org-unassigned-item-nombre {
            font-size: 0.78rem;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .org-unassigned-item-meta {
            font-size: 0.65rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .org-unassigned-item-actions {
            display: flex;
            gap: 0.25rem;
            flex-shrink: 0;
        }
        .org-unassigned-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .org-modal-panel {
            width: 100%;
            max-width: 40rem;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 25px 50px rgba(15, 23, 42, 0.25);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }
        .org-modal-header {
            padding: 1.25rem 1.5rem;
            color: #fff;
            flex-shrink: 0;
        }
        .org-modal-header--supervisor {
            background: linear-gradient(135deg, #059669 0%, #0d9488 55%, #14b8a6 100%);
        }
        .org-modal-header--gerente {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 55%, #6366f1 100%);
        }
        .org-modal-panel--wide {
            max-width: 44rem;
        }
        .org-modal-header-inner {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        .org-modal-avatar {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 0.875rem;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            flex-shrink: 0;
            border: 2px solid rgba(255, 255, 255, 0.35);
        }
        .org-modal-header-actions {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex-shrink: 0;
        }
        .org-modal-icon-btn {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .org-modal-icon-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        .org-modal-icon-btn svg {
            width: 1.1rem;
            height: 1.1rem;
        }
        .org-modal-body {
            padding: 1.25rem 1.5rem 1.5rem;
            overflow-y: auto;
            flex: 1;
        }
        .org-modal-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.65rem;
            margin-bottom: 1.25rem;
        }
        .org-modal-stat {
            padding: 0.75rem 0.65rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.625rem;
            background: #f8fafc;
            text-align: center;
        }
        .org-modal-stat-val {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }
        .org-modal-stat-lbl {
            font-size: 0.62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-top: 0.15rem;
        }
        .org-modal-seccion {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .org-modal-seccion-titulo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.65rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
        }
        .org-modal-buscador {
            position: relative;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .org-modal-buscador input {
            width: 100%;
            padding: 0.5rem 0.65rem 0.5rem 2rem;
            font-size: 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #fff;
        }
        .org-modal-buscador input:focus {
            outline: none;
            border-color: #14b8a6;
            box-shadow: 0 0 0 2px rgba(20, 184, 166, 0.2);
        }
        .org-modal-buscador svg {
            position: absolute;
            left: 1.65rem;
            top: 50%;
            transform: translateY(-50%);
            width: 0.9rem;
            height: 0.9rem;
            color: #94a3b8;
            pointer-events: none;
        }
        .org-equipo-lista {
            max-height: 22rem;
            overflow-y: auto;
        }
        .org-equipo-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background 0.12s ease;
        }
        .org-equipo-item:last-child {
            border-bottom: none;
        }
        .org-equipo-item:hover {
            background: #f0fdfa;
        }
        .org-equipo-item.hidden {
            display: none;
        }
        .org-equipo-item-avatar {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .org-equipo-item-info {
            flex: 1;
            min-width: 0;
        }
        .org-equipo-item-nombre {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .org-equipo-item-depto {
            font-size: 0.7rem;
            color: #64748b;
            margin-top: 0.1rem;
        }
        .org-equipo-item-id {
            font-size: 0.65rem;
            font-weight: 600;
            color: #94a3b8;
            flex-shrink: 0;
        }
        .org-modal-vacio {
            padding: 2rem 1rem;
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
        }
        .org-supervisor-lista {
            max-height: 24rem;
            overflow-y: auto;
        }
        .org-supervisor-card {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .org-supervisor-card:last-child {
            border-bottom: none;
        }
        .org-supervisor-card.hidden {
            display: none;
        }
        .org-supervisor-card-top {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        .org-supervisor-card-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            background: #d1fae5;
            color: #047857;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .org-supervisor-card-info {
            flex: 1;
            min-width: 0;
        }
        .org-supervisor-card-nombre {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1e293b;
        }
        .org-supervisor-card-meta {
            font-size: 0.68rem;
            color: #64748b;
            margin-top: 0.15rem;
        }
        .org-supervisor-card-actions {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            flex-shrink: 0;
        }
        .org-supervisor-card-btn {
            font-size: 0.62rem;
            font-weight: 600;
            padding: 0.3rem 0.55rem;
            border-radius: 9999px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s ease;
        }
        .org-supervisor-card-btn:hover {
            border-color: #3b82f6;
            color: #2563eb;
            background: #eff6ff;
        }
        .org-supervisor-card-btn--primary {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #047857;
        }
        .org-supervisor-card-btn--primary:hover {
            border-color: #059669;
            background: #d1fae5;
        }
        .org-supervisor-card-stats {
            font-size: 0.68rem;
            color: #64748b;
            margin-top: 0.55rem;
            min-height: 1rem;
        }
        .org-supervisor-card-bar {
            width: 100%;
            height: 0.35rem;
            background: #f1f5f9;
            border-radius: 9999px;
            margin-top: 0.35rem;
            overflow: hidden;
        }
        .org-supervisor-card-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #34d399);
            border-radius: 9999px;
            width: 0;
            transition: width 0.4s ease;
        }
        @media (max-width: 520px) {
            .org-modal-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-50">

    <header class="top-header">
        <div class="top-header-inner">
            <a href="#" onclick="mostrarSeccion('inicio'); return false;" class="header-brand" title="Ir al inicio">
                <span class="header-brand-logo" aria-hidden="true">
                    <img src="../assets/logo.png" alt="">
                </span>
                <span class="header-brand-text">
                    <span class="header-brand-name">
                        <span class="header-brand-kaizen">Kaizen</span>
                        <span class="header-brand-reports">Reports</span>
                    </span>
                    <span class="header-brand-meta">
                        <span class="header-brand-ntn">NTN</span>
                        <span class="header-brand-meta-sep" aria-hidden="true"></span>
                        <span class="header-brand-role">Recursos Humanos</span>
                    </span>
                </span>
            </a>

            <nav class="header-nav" aria-label="Navegación principal">
                <div class="header-nav-track">
                    <a href="#" id="nav-inicio" onclick="mostrarSeccion('inicio'); return false;" class="nav-item active">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                        <span>Inicio</span>
                    </a>
                    <span class="header-nav-sep" aria-hidden="true"></span>
                    <a href="#" id="nav-empleados" onclick="mostrarSeccion('empleados'); return false;" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                        <span>Empleados</span>
                    </a>
                    <a href="#" id="nav-estadisticas" onclick="mostrarSeccion('estadisticas'); return false;" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                        <span>Estadísticas</span>
                    </a>
                    <span class="header-nav-sep" aria-hidden="true"></span>
                    <a href="#" id="nav-organigrama" onclick="mostrarSeccion('organigrama'); return false;" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 9a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H6a1 1 0 01-1-1v-4zm8-9a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V4z" clip-rule="evenodd"/></svg>
                        <span>Organigrama</span>
                    </a>
                </div>
            </nav>

            <div class="header-actions">
                <div class="header-user">
                    <span class="header-user-avatar"><?php echo htmlspecialchars($inicialesUsuario); ?></span>
                    <span>
                        <span class="header-user-name block"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                        <span class="header-user-role block">Recursos Humanos</span>
                    </span>
                </div>
                <button type="button" id="headerMenuToggle" class="header-menu-btn" onclick="toggleHeaderMenu()" aria-label="Abrir menú" aria-expanded="false">
                    <svg id="headerMenuIconOpen" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                    <svg id="headerMenuIconClose" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div class="header-notif-wrap">
                    <button type="button" id="btnNotifPlazo" class="header-notif-btn" onclick="PlazoRevisionUi.togglePanelNotificaciones()" aria-label="Avisos de plazo">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span id="notifPlazoCount" class="header-notif-count hidden">0</span>
                    </button>
                    <div id="notifPlazoPanel" class="notif-plazo-panel hidden">
                        <div class="notif-plazo-panel-head"><h4>Avisos de plazo</h4></div>
                        <div id="notifPlazoList"></div>
                    </div>
                </div>
                <a href="#" onclick="cerrarSesionConAnimacion(event); return false;" class="header-logout-btn" title="Cerrar sesión">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>

        <nav id="headerNavMobile" class="header-mobile-nav lg:hidden" aria-label="Menú móvil">
            <div class="header-mobile-grid">
                <a href="#" data-nav="inicio" onclick="mostrarSeccion('inicio'); return false;" class="nav-item active">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    <span>Inicio</span>
                </a>
                <a href="#" data-nav="empleados" onclick="mostrarSeccion('empleados'); return false;" class="nav-item">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                    <span>Empleados</span>
                </a>
                <a href="#" data-nav="estadisticas" onclick="mostrarSeccion('estadisticas'); return false;" class="nav-item">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                    <span>Estadísticas</span>
                </a>
                <a href="#" data-nav="organigrama" onclick="mostrarSeccion('organigrama'); return false;" class="nav-item">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 9a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H6a1 1 0 01-1-1v-4zm8-9a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V4z" clip-rule="evenodd"/></svg>
                    <span>Organigrama</span>
                </a>
            </div>
        </nav>
    </header>

    <div id="filtrosOverlay" class="filtros-overlay" onclick="cerrarFiltros()"></div>

    <div class="app-shell" id="appShell">
        <aside id="panelFiltrosLateral" class="filtros-panel">
            <div class="filtros-panel-head">
                <div class="filtros-head-main">
                    <div class="filtros-head-brand">
                        <span class="filtros-head-icon" aria-hidden="true">
                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                        </span>
                        <div class="filtros-head-text min-w-0">
                            <h3>Refinar búsqueda</h3>
                            <p>Reportes Kaizen</p>
                        </div>
                    </div>
                    <span class="filtros-count-badge" id="contadorFiltros">Todos los reportes</span>
                </div>
                <div class="filtros-head-actions">
                    <button type="button" id="btnExportarReportes" onclick="exportarReportesFiltrados()" class="filtros-btn-export" title="Exportar reportes del mes seleccionado (requiere año y mes)">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    </button>
                    <button type="button" id="btnFijarFiltros" onclick="toggleFijarFiltros()" class="filtros-btn-pin" title="Fijar panel de filtros">
                        <svg id="iconFijarFiltros" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                        <svg id="iconDesfijarFiltros" class="hidden" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14.382a1 1 0 01-1.447.894L10 17.618l-5.553 2.776A1 1 0 013 19.382V4z"/></svg>
                    </button>
                    <button type="button" onclick="cerrarFiltros()" class="filtros-btn-close" aria-label="Cerrar filtros">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </div>

            <div class="filtros-panel-body">
                <div class="filtros-grid">
                    <div class="filtros-card">
                        <div class="filtros-card-title">
                            <span class="filtros-card-icon bg-blue-50 text-blue-600">
                                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                            </span>
                            Período
                        </div>
                        <div class="filtros-card-body">
                            <div class="filtros-inline-2">
                                <select id="filtroAnioRapido" class="filtro-field" onchange="aplicarFiltros()"><option value="">Año</option></select>
                                <select id="filtroMesRapido" class="filtro-field" onchange="aplicarFiltros()">
                                    <option value="">Mes</option>
                                    <option value="01">Ene</option><option value="02">Feb</option><option value="03">Mar</option>
                                    <option value="04">Abr</option><option value="05">May</option><option value="06">Jun</option>
                                    <option value="07">Jul</option><option value="08">Ago</option><option value="09">Sep</option>
                                    <option value="10">Oct</option><option value="11">Nov</option><option value="12">Dic</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="filtros-card filtros-card--busqueda">
                        <div class="filtros-card-title">
                            <span class="filtros-card-icon bg-violet-50 text-violet-600">
                                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                            </span>
                            Búsqueda
                        </div>
                        <div class="filtros-card-body">
                            <div class="filtro-input-wrap">
                                <svg class="filtro-input-icon" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                <input type="text" id="filtroTexto" placeholder="Tema, ID o participante..." class="filtro-field" oninput="aplicarFiltros()">
                            </div>
                        </div>
                    </div>

                    <div class="filtros-card filtros-card--aprobaciones">
                        <div class="filtros-card-title">
                            <span class="filtros-card-icon bg-emerald-50 text-emerald-600">
                                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </span>
                            Aprobaciones
                        </div>
                        <div class="filtros-card-body filtros-aprob-grid">
                            <label class="filtro-chip">
                                <span class="filtro-chip-label"><span class="filtro-chip-dot bg-amber-400"></span> Supervisor</span>
                                <select id="filtroEstadoSupervisor" class="filtro-field" onchange="aplicarFiltros()">
                                    <option value="">Todos</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="aprobado">Aprobado</option>
                                    <option value="rechazado">Rechazado</option>
                                </select>
                            </label>
                            <label class="filtro-chip">
                                <span class="filtro-chip-label"><span class="filtro-chip-dot bg-blue-500"></span> Gerente</span>
                                <select id="filtroEstadoGerente" class="filtro-field" onchange="aplicarFiltros()">
                                    <option value="">Todos</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="autorizado">Autorizado</option>
                                    <option value="rechazado">Rechazado</option>
                                </select>
                            </label>
                            <label class="filtro-chip">
                                <span class="filtro-chip-label"><span class="filtro-chip-dot bg-purple-500"></span> RH</span>
                                <select id="filtroEstadoRH" class="filtro-field" onchange="aplicarFiltros()">
                                    <option value="">Todos</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="aceptado">Aceptado</option>
                                    <option value="rechazado">Rechazado</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="filtros-card">
                        <div class="filtros-card-title">
                            <span class="filtros-card-icon bg-orange-50 text-orange-600">
                                <svg fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                            </span>
                            Área y calidad
                        </div>
                        <div class="filtros-card-body">
                            <select id="filtroClasificacion" class="filtro-field" onchange="aplicarFiltros()">
                                <option value="">Clasificación</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                            <select id="filtroDepartamento" class="filtro-field" onchange="aplicarFiltros()">
                                <option value="">Departamento</option>
                            </select>
                            <select id="filtroAspecto" class="filtro-field" onchange="aplicarFiltros()">
                                <option value="">Aspecto evaluado</option>
                            </select>
                            <select id="filtroEstadoFlujo" class="filtro-field" onchange="aplicarFiltros()">
                                <option value="">Estado del reporte</option>
                                <option value="borrador">Borrador</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="en_curso">En curso</option>
                                <option value="completado">Completado</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                            <select id="filtroExportado" class="filtro-field" onchange="aplicarFiltros()">
                                <option value="">Exportado</option>
                                <option value="1">Sí — ya exportado</option>
                                <option value="0">No — pendiente</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="filtros-reset-wrap">
                <button type="button" onclick="limpiarFiltros(); limpiarFiltrosRapidos();" class="btn-limpiar-filtros">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    Restablecer filtros
                </button>
            </div>
        </aside>

        <main class="main-content p-6 lg:p-8" id="mainContent">
        
        <header class="section-hero" id="pageHeader">
            <div class="section-hero-icon" id="pageHeaderIcon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="section-hero-body">
                <p class="section-hero-eyebrow" id="pageHeaderEyebrow">Inicio</p>
                <h1 class="section-hero-title" id="pageHeaderTitle">Bandeja de revisión RH</h1>
                <p class="section-hero-sub" id="pageHeaderSub">Califica, acepta o rechaza reportes que ya pasaron supervisor y gerente.</p>
                <p class="section-hero-meta" id="pageHeaderMeta"></p>
            </div>
        </header>
        
        <!-- Sección Inicio -->
        <section id="seccion-inicio">
            <div class="kpi-board" id="kpiStripInicio" role="group" aria-label="Resumen de reportes">
                <button type="button" class="kpi-tile kpi-tile--pend" id="kpiCellPend" onclick="irAKpiFiltroRH('pendiente')" title="Filtrar reportes listos para revisión de RH">
                    <span class="kpi-tile-head">
                        <span class="kpi-tile-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <span class="kpi-tile-label">Por revisar</span>
                    </span>
                    <span class="kpi-tile-val" id="kpiInicioPendRH">0</span>
                </button>
                <button type="button" class="kpi-tile kpi-tile--acept" id="kpiCellAcept" onclick="irAKpiFiltroRH('aceptado')" title="Filtrar aceptados">
                    <span class="kpi-tile-head">
                        <span class="kpi-tile-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <span class="kpi-tile-label">Aceptados</span>
                    </span>
                    <span class="kpi-tile-val" id="kpiInicioAceptados">0</span>
                </button>
                <button type="button" class="kpi-tile kpi-tile--rech" id="kpiCellRech" onclick="irAKpiFiltroRH('rechazado')" title="Filtrar rechazados">
                    <span class="kpi-tile-head">
                        <span class="kpi-tile-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <span class="kpi-tile-label">Rechazados</span>
                    </span>
                    <span class="kpi-tile-val" id="kpiInicioRechazados">0</span>
                </button>
                <button type="button" class="kpi-tile kpi-tile--mes" id="kpiCellMes" onclick="irAKpiFiltroMes()" title="Filtrar mes actual">
                    <span class="kpi-tile-head">
                        <span class="kpi-tile-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        </span>
                        <span class="kpi-tile-label" id="kpiInicioMesLabel">Este mes</span>
                    </span>
                    <span class="kpi-tile-val" id="kpiInicioMes">0</span>
                </button>
            </div>
            <div id="filtrosActivosStrip" class="filtros-activos-strip hidden" aria-live="polite">
                <div id="filtrosActivosChips" class="flex flex-wrap items-center gap-1.5"></div>
            </div>

            <!-- Reportes Kaizen -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                <div class="block-card-toolbar">
                    <div>
                        <h2 class="block-card-title">Listado de reportes</h2>
                        <p class="block-card-sub" id="infoReportes">Cargando...</p>
                    </div>
                    <button type="button" id="btnAbrirFiltros" onclick="toggleFiltros()" class="flex items-center gap-2 px-3 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition shadow-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                        Filtros
                    </button>
                </div>

                <!-- Tabla -->
                <div class="rh-rep-table-wrap">
                    <table class="rh-rep-table">
                        <colgroup>
                            <col class="rh-col-id">
                            <col class="rh-col-estado">
                            <col class="rh-col-tema">
                            <col class="rh-col-fecha">
                            <col class="rh-col-clf">
                            <col class="rh-col-part">
                            <col class="rh-col-flujo">
                            <col class="rh-col-exp">
                            <col class="rh-col-acc">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-left">ID</th>
                                <th class="text-left">Estado</th>
                                <th class="text-left">Tema</th>
                                <th class="text-left">Fecha</th>
                                <th class="text-center">Clasif.</th>
                                <th class="text-center">Part.</th>
                                <th class="text-center">Flujo</th>
                                <th class="text-center">Export.</th>
                                <th class="rh-th-acc" scope="col" aria-label="Eliminar reporte">
                                    <span class="rh-th-icon" title="Eliminar">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tablaReportesInicio">
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="font-medium">Cargando reportes...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-600">
                        Mostrando <span class="font-semibold" id="rangoInicio">0</span> - <span class="font-semibold" id="rangoFin">0</span> de <span class="font-semibold" id="totalRegistros">0</span> reportes
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="cambiarPagina('primera')" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed" id="btnPrimera">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M15.707 15.707a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 010 1.414zm-6 0a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 011.414 1.414L5.414 10l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
                        </button>
                        <button onclick="cambiarPagina('anterior')" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed" id="btnAnterior">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </button>
                        <span class="px-4 py-1 text-sm font-semibold text-gray-700">Página <span id="paginaActual">1</span> de <span id="totalPaginas">1</span></span>
                        <button onclick="cambiarPagina('siguiente')" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed" id="btnSiguiente">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        </button>
                        <button onclick="cambiarPagina('ultima')" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed" id="btnUltima">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L14.586 10l-4.293-4.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M4.293 15.707a1 1 0 010-1.414L8.586 10 4.293 5.707a1 1 0 011.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Sección Empleados -->
        <section id="seccion-empleados" class="hidden">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                <div class="block-card-toolbar">
                    <div>
                        <h2 class="block-card-title">Equipo registrado</h2>
                        <p class="block-card-sub" id="infoEmpleados">Cargando...</p>
                    </div>
                    <button onclick="abrirModalNuevoEmpleado()" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition shadow-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                        <span>Nuevo empleado</span>
                    </button>
                </div>

                <!-- Buscador y filtros -->
                <div class="px-6 py-3 border-b border-gray-100 bg-white">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative flex-1 min-w-48">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                            <input type="text" id="buscarEmpleado" placeholder="Buscar por nombre o ID..."
                                class="w-full pl-9 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
                                onkeyup="filtrarEmpleados()">
                        </div>
                        <select id="filtroDepartamentoEmp" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700 cursor-pointer" onchange="filtrarEmpleados()">
                            <option value="">Departamentos</option>
                        </select>
                        <select id="filtroPuestoEmp" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700 cursor-pointer" onchange="filtrarEmpleados()">
                            <option value="">Puestos</option>
                            <option value="gerente">Gerente</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="trabajador">Trabajador</option>
                        </select>
                        <select id="filtroClasificacionEmp" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700 cursor-pointer" onchange="filtrarEmpleados()">
                            <option value="">Clasificación</option>
                            <option value="staff">Staff</option>
                            <option value="operativo">Operativo</option>
                            <option value="inspector">Inspector</option>
                            <option value="sin_asignar">Sin asignar</option>
                        </select>
                        <select id="filtroEstadoEmp" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700 cursor-pointer" onchange="filtrarEmpleados()">
                            <option value="activo">Activos</option>
                            <option value="inactivo">Inactivos</option>
                            <option value="">Todos</option>
                        </select>
                        <button onclick="limpiarFiltrosEmp()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Limpiar
                        </button>
                        <div class="ml-auto flex items-center gap-2">
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                                <input type="checkbox" id="mostrarSinAsignarEmp" onchange="filtrarEmpleados()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>Mostrar sin asignar</span>
                                <span id="empSinAsignarCount" class="text-xs font-semibold text-amber-600"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="px-2 sm:px-4">
                    <table class="w-full tabla-empleados">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                <th class="emp-cell-id text-left font-bold text-gray-700 uppercase">ID</th>
                                <th class="emp-cell-nombre text-left font-bold text-gray-700 uppercase">Nombre</th>
                                <th class="emp-cell-depto text-left font-bold text-gray-700 uppercase" title="Departamento">Depto</th>
                                <th class="emp-cell-puesto font-bold text-gray-700 uppercase">Puesto</th>
                                <th class="emp-cell-clasificacion font-bold text-gray-700 uppercase" title="Clasificación">Clasif.</th>
                                <th class="emp-cell-supervisor text-left font-bold text-gray-700 uppercase" title="Supervisor">Superv.</th>
                                <th class="emp-cell-gerente text-left font-bold text-gray-700 uppercase" title="Gerente">Gerente</th>
                                <th class="emp-cell-acciones font-bold text-gray-700 uppercase">Acc.</th>
                            </tr>
                        </thead>
                        <tbody id="tablaEmpleados" class="divide-y divide-gray-100">
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                                    <p class="font-medium">Cargando empleados...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-600">
                        Mostrando <span class="font-semibold" id="empRangoInicio">0</span> - <span class="font-semibold" id="empRangoFin">0</span> de <span class="font-semibold" id="empTotal">0</span> empleados
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="cambiarPaginaEmp('anterior')" id="empBtnAnterior" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </button>
                        <span class="px-3 py-1 text-sm font-semibold text-gray-700">Página <span id="empPaginaActual">1</span> de <span id="empTotalPaginas">1</span></span>
                        <button onclick="cambiarPaginaEmp('siguiente')" id="empBtnSiguiente" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Sección Estadísticas -->
        <section id="seccion-estadisticas" class="hidden">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                <div class="block-card-toolbar block-card-toolbar--compact">
                    <div>
                        <h2 class="block-card-title">Panel de indicadores</h2>
                        <p class="block-card-sub" id="infoEstadisticas">Cargando resumen...</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="abrirModalMetasDepartamento()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Metas por departamento
                    </button>
                    <button type="button" onclick="abrirModalResumenMetas()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-violet-700 bg-violet-50 hover:bg-violet-100 border border-violet-200 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Ver resumen
                    </button>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="px-6 py-3 border-b border-gray-100 bg-white">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mr-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                            Filtrar por
                        </div>
                        <select id="statsAnio" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700 cursor-pointer" onchange="actualizarEstadisticas()">
                            <option value="">Todos los años</option>
                        </select>
                        <select id="statsMes" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700 cursor-pointer" onchange="actualizarEstadisticas()">
                            <option value="">Todos los meses</option>
                            <option value="01">Enero</option>
                            <option value="02">Febrero</option>
                            <option value="03">Marzo</option>
                            <option value="04">Abril</option>
                            <option value="05">Mayo</option>
                            <option value="06">Junio</option>
                            <option value="07">Julio</option>
                            <option value="08">Agosto</option>
                            <option value="09">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </select>
                        <button onclick="limpiarFiltrosStats()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Limpiar
                        </button>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="stats-kpi-board" id="statsKpiBoard" role="group" aria-label="Resumen estadístico">
                    <div class="kpi-tile kpi-tile--total kpi-tile--static">
                        <span class="kpi-tile-head">
                            <span class="kpi-tile-icon" aria-hidden="true">
                                <svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="kpi-tile-label">Total reportes</span>
                        </span>
                        <span class="kpi-tile-val" id="statsKpiTotal">0</span>
                    </div>
                    <div class="kpi-tile kpi-tile--pend kpi-tile--static">
                        <span class="kpi-tile-head">
                            <span class="kpi-tile-icon" aria-hidden="true">
                                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="kpi-tile-label">Por revisar</span>
                        </span>
                        <span class="kpi-tile-val" id="statsKpiPend">0</span>
                    </div>
                    <div class="kpi-tile kpi-tile--acept kpi-tile--static">
                        <span class="kpi-tile-head">
                            <span class="kpi-tile-icon" aria-hidden="true">
                                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="kpi-tile-label">Aceptados</span>
                        </span>
                        <span class="kpi-tile-val" id="statsKpiAcept">0</span>
                    </div>
                    <div class="kpi-tile kpi-tile--rech kpi-tile--static">
                        <span class="kpi-tile-head">
                            <span class="kpi-tile-icon" aria-hidden="true">
                                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="kpi-tile-label">Rechazados</span>
                        </span>
                        <span class="kpi-tile-val" id="statsKpiRech">0</span>
                    </div>
                    <div class="kpi-tile kpi-tile--pct kpi-tile--static">
                        <span class="kpi-tile-head">
                            <span class="kpi-tile-icon" aria-hidden="true">
                                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="kpi-tile-label">% Aceptación</span>
                        </span>
                        <span class="kpi-tile-val" id="statsKpiPct">0%</span>
                    </div>
                </div>

                <!-- Gráficas -->
                <div class="stats-body">
                    <div id="statsEmpty" class="stats-empty hidden">
                        <div class="stats-empty-icon" aria-hidden="true">
                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Sin reportes para el periodo seleccionado</p>
                        <p class="text-xs text-gray-400">Ajusta el año o mes, o pulsa Limpiar para ver todos los registros.</p>
                    </div>
                    <div id="statsChartsWrap" class="stats-chart-grid">
                        <div class="stats-chart-card stats-chart-card--wide">
                            <div class="stats-chart-card-head">
                                <span class="stats-chart-card-title">Reportes por mes</span>
                            </div>
                            <div class="stats-chart-wrap">
                                <canvas id="chartReportesMes"></canvas>
                            </div>
                        </div>
                        <div class="stats-chart-card stats-chart-card--wide">
                            <div class="stats-chart-card-head">
                                <span class="stats-chart-card-title">Top 10 departamentos</span>
                            </div>
                            <div class="stats-chart-wrap stats-chart-wrap--ranking" id="chartDepartamentosWrap">
                                <div id="chartDepartamentosEmpty" class="stats-chart-empty hidden">Sin departamentos en los reportes del periodo</div>
                                <canvas id="chartDepartamentos"></canvas>
                            </div>
                        </div>
                        <div class="stats-chart-card">
                            <div class="stats-chart-card-head">
                                <span class="stats-chart-card-title">Estado RH</span>
                            </div>
                            <div class="stats-chart-wrap stats-chart-wrap--compact">
                                <canvas id="chartEstadoRH"></canvas>
                            </div>
                        </div>
                        <div class="stats-chart-card">
                            <div class="stats-chart-card-head">
                                <span class="stats-chart-card-title">Clasificación</span>
                            </div>
                            <div class="stats-chart-wrap stats-chart-wrap--compact">
                                <canvas id="chartEvaluaciones"></canvas>
                            </div>
                        </div>
                        <div class="stats-chart-card">
                            <div class="stats-chart-card-head">
                                <span class="stats-chart-card-title">Embudo de aprobación</span>
                            </div>
                            <div class="stats-chart-wrap stats-chart-wrap--funnel" id="chartEmbudoWrap">
                                <canvas id="chartEmbudo"></canvas>
                            </div>
                        </div>
                        <div class="stats-chart-card">
                            <div class="stats-chart-card-head">
                                <div>
                                    <span class="stats-chart-card-title">Comparativa</span>
                                    <p class="stats-chart-card-sub mt-0.5" id="chartComparativaSub">Mes actual vs anterior</p>
                                </div>
                            </div>
                            <div class="stats-chart-wrap">
                                <canvas id="chartComparativa"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Sección Organigrama -->
        <section id="seccion-organigrama" class="hidden">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                <div class="block-card-toolbar block-card-toolbar--compact">
                    <div>
                        <h2 class="block-card-title">Vista jerárquica</h2>
                        <p class="block-card-sub" id="infoOrganigrama">Cargando estructura...</p>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="px-6 py-3 border-b border-gray-100 bg-white">
                    <div class="flex flex-wrap items-center gap-2">
                        <select id="filtroGerenteOrg" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700 cursor-pointer" onchange="cargarOrganigrama()">
                            <option value="">Gerentes</option>
                        </select>
                        <select id="filtroDepartamentoOrg" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700 cursor-pointer" onchange="cargarOrganigrama()">
                            <option value="">Departamentos</option>
                        </select>
                        <button type="button" onclick="limpiarFiltrosOrganigrama()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Limpiar
                        </button>
                        <div class="ml-auto flex items-center gap-2">
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                                <input type="checkbox" id="mostrarSinAsignar" onchange="cargarOrganigrama()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>Mostrar sin asignar</span>
                                <span id="orgSinAsignarCount" class="text-xs font-semibold text-amber-600"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Árbol jerárquico -->
                <div class="px-6 pb-6 pt-4">
                    <div id="organigramaViewport" class="org-chart-viewport">
                        <div id="organigramaContainer">
                            <div class="flex items-center justify-center py-12 text-gray-400">
                                <svg class="w-12 h-12 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span class="ml-3">Cargando organigrama...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>



    </main>
    </div>

    <!-- Visor de imagen inline -->
    <div id="visorImagen" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4" style="background:rgba(15,23,42,0.9)" onclick="cerrarVisorImagen()">
        <div class="relative max-w-4xl w-full flex flex-col items-center" onclick="event.stopPropagation()">
            <button onclick="cerrarVisorImagen()" class="absolute -top-10 right-0 text-white hover:text-gray-300 transition">
                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
            <img id="visorImg" src="" alt="" class="max-h-[80vh] max-w-full rounded-xl shadow-2xl object-contain">
        </div>
    </div>

    <!-- Modal Metas por departamento (mensual) -->
    <div id="modalMetasDepartamento" class="hidden fixed inset-0 bg-black/50 backdrop-blur-[2px] z-50 flex items-center justify-center p-3 sm:p-4" onclick="if(event.target===this) cerrarModalMetasDepartamento()">
        <div class="bg-white rounded-2xl shadow-2xl meta-mensual-modal w-full max-h-[94vh] flex flex-col" onclick="event.stopPropagation()">
            <div class="meta-mensual-header">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="meta-mensual-header-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="meta-mensual-header-title">Metas mensuales</h3>
                        <p id="metaMensualHeaderSub" class="meta-mensual-header-sub">Captura personas y Kaizen por mes. Meta = Staff×1 + Operativo×0.5</p>
                        <span id="metaMensualDeptoBadge" class="meta-mensual-depto-badge">—</span>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalMetasDepartamento()" class="text-gray-400 hover:text-gray-600 hover:bg-white/80 p-2 rounded-lg transition flex-shrink-0" aria-label="Cerrar">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </div>
            <div class="meta-mensual-body">
                <div class="meta-mensual-toolbar">
                    <div class="meta-mensual-toolbar-field">
                        <label for="metaMensualDepto">Departamento</label>
                        <select id="metaMensualDepto" onchange="onMetaMensualContextChange()"></select>
                    </div>
                    <div class="meta-mensual-toolbar-field">
                        <label for="metaMensualAnio">Año</label>
                        <select id="metaMensualAnio" onchange="onMetaMensualContextChange()"></select>
                    </div>
                    <div class="meta-mensual-leyenda">
                        <span class="meta-mensual-leyenda-chip"><strong>Staff</strong> × 1.0</span>
                        <span id="metaMensualLeyendaOperativo" class="meta-mensual-leyenda-chip"><strong>Operativo</strong> × 0.5</span>
                        <span class="meta-mensual-leyenda-chip">% logro = Kaizen ÷ meta</span>
                    </div>
                    <p id="metaMensualNotaConsolidada" class="meta-mensual-nota-consolidada hidden"></p>
                </div>
                <div id="modalMetasEmpty" class="meta-mensual-empty">Selecciona departamento y año…</div>
                <div id="modalMetasScroll" class="meta-mensual-scroll hidden">
                    <table class="meta-mensual-grid" id="modalMetasGrid">
                        <thead id="modalMetasHead"></thead>
                        <tbody id="modalMetasBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="meta-mensual-footer">
                <p class="meta-mensual-footer-note">Los cambios se guardan solos al editar. El filtro de año muestra el actual y los anteriores conforme avanza el calendario; cada año nuevo inicia con plantilla vacía.</p>
                <button type="button" onclick="abrirModalResumenMetas(metaMensualState.anio)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-violet-700 bg-violet-50 hover:bg-violet-100 border border-violet-200 rounded-lg transition flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Ver resumen
                </button>
                <div class="meta-mensual-estado-wrap">
                    <span id="metaMensualEstado" class="meta-mensual-estado meta-mensual-estado--idle" aria-live="polite">
                        <span id="metaMensualEstadoIcon" class="meta-mensual-estado-icon" aria-hidden="true"></span>
                        <span id="metaMensualEstadoText">Listo</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal resumen metas (solo lectura, todos los departamentos) -->
    <div id="modalResumenMetas" class="hidden fixed inset-0 bg-black/50 backdrop-blur-[2px] z-[60] flex items-center justify-center p-3 sm:p-4" onclick="if(event.target===this) cerrarModalResumenMetas()">
        <div class="bg-white rounded-2xl shadow-2xl meta-mensual-modal meta-resumen-modal w-full max-h-[94vh] flex flex-col" onclick="event.stopPropagation()">
            <div class="meta-mensual-header">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="meta-mensual-header-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="meta-mensual-header-title">Resumen de metas</h3>
                        <p id="metaResumenSub" class="meta-mensual-header-sub">Todos los departamentos en una sola vista.</p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalResumenMetas()" class="text-gray-400 hover:text-gray-600 hover:bg-white/80 p-2 rounded-lg transition flex-shrink-0" aria-label="Cerrar">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </div>
            <div class="meta-mensual-body">
                <div class="meta-mensual-toolbar">
                    <div class="meta-mensual-toolbar-field">
                        <label for="metaResumenAnio">Año</label>
                        <select id="metaResumenAnio" onchange="cargarResumenMetas()"></select>
                    </div>
                </div>
                <div id="metaResumenLoading" class="meta-resumen-loading hidden">Cargando resumen…</div>
                <div id="metaResumenError" class="meta-mensual-empty hidden"></div>
                <div id="metaResumenPreviewWrap" class="meta-resumen-preview-scroll hidden">
                    <div id="metaResumenPreviewContent"></div>
                </div>
            </div>
            <div class="meta-mensual-footer">
                <p class="meta-mensual-footer-note">Vista de solo lectura. Para editar, usa Metas por departamento.</p>
            </div>
        </div>
    </div>

    <!-- Modal Empleado -->
    <div id="modalEmpleado" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold ntn-blue">Nuevo Empleado</h3>
                    <button onclick="cerrarModalEmpleado()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
                
                <form id="formEmpleado" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ID Empleado *</label>
                            <input type="number" id="empId" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                            <input type="text" id="firstName" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Apellido Paterno *</label>
                            <input type="text" id="lastName" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Apellido Materno</label>
                            <input type="text" id="surName" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Departamento *</label>
                            <select id="department" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">Seleccionar departamento</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Puesto *</label>
                            <select id="empRolNuevo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white" onchange="actualizarCamposClasificacionSegunPuesto('nuevo')">
                                <option value="trabajador">Trabajador</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="gerente">Gerente</option>
                            </select>
                        </div>

                        <div id="empClasificacionNuevoWrap">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Clasificación</label>
                            <select id="empClasificacionNuevo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">Sin asignar</option>
                                <option value="staff">Staff</option>
                                <option value="operativo">Operativo</option>
                                <option value="inspector">Inspector</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Solo aplica a trabajadores (Staff, Operativo, Inspector).</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña *</label>
                            <input type="password" id="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div id="mensajeModal" class="hidden p-3 rounded-lg text-sm"></div>
                    
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" onclick="cerrarModalEmpleado()" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detalle / Editar Empleado -->
    <div id="modalDetalleEmpleado" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl emp-detalle-modal w-full max-h-[92vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start gap-3 mb-5">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Ficha del empleado</h3>
                        <p class="text-sm text-gray-500 mt-1" id="detalleEmpSubtitulo">Información completa del colaborador</p>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <button type="button" id="btnToggleEditarEmp" onclick="activarEdicionEmpleado()" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition" title="Editar datos personales">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            Editar
                        </button>
                        <button type="button" onclick="cerrarModalDetalleEmpleado()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition" title="Cerrar">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                </div>

                <div id="empDetalleVista" class="space-y-4">
                    <div class="emp-detalle-header flex items-start gap-4">
                        <div class="emp-detalle-avatar" id="vistaEmpIniciales">--</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-bold text-slate-900 truncate" id="vistaEmpNombre">—</p>
                            <p class="text-sm text-gray-500 mt-0.5" id="vistaEmpIdLinea">ID —</p>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span id="vistaEmpPuestoBadge" class="emp-detalle-badge bg-blue-100 text-blue-700 ring-1 ring-blue-200">—</span>
                                <span id="vistaEmpEstadoBadge" class="emp-detalle-badge">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="emp-detalle-seccion">
                        <div class="emp-detalle-seccion-titulo">
                            <svg fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                            Información personal
                        </div>
                        <div class="emp-detalle-grid">
                            <div class="emp-detalle-campo">
                                <p class="emp-detalle-label">Nombre(s)</p>
                                <p class="emp-detalle-valor" id="vistaEmpFirstName">—</p>
                            </div>
                            <div class="emp-detalle-campo">
                                <p class="emp-detalle-label">Apellido paterno</p>
                                <p class="emp-detalle-valor" id="vistaEmpLastName">—</p>
                            </div>
                            <div class="emp-detalle-campo">
                                <p class="emp-detalle-label">Apellido materno</p>
                                <p class="emp-detalle-valor" id="vistaEmpSurName">—</p>
                            </div>
                            <div class="emp-detalle-campo">
                                <p class="emp-detalle-label">Nombre completo</p>
                                <p class="emp-detalle-valor" id="vistaEmpNombreCompleto">—</p>
                            </div>
                        </div>
                    </div>

                    <div class="emp-detalle-seccion">
                        <div class="emp-detalle-seccion-titulo">
                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/><path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/></svg>
                            Información laboral
                        </div>
                        <div class="emp-detalle-grid">
                            <div class="emp-detalle-campo">
                                <p class="emp-detalle-label">Departamento</p>
                                <p class="emp-detalle-valor" id="vistaEmpDepartamento">—</p>
                            </div>
                            <div class="emp-detalle-campo">
                                <p class="emp-detalle-label">Puesto</p>
                                <p class="emp-detalle-valor" id="vistaEmpPuesto">—</p>
                            </div>
                            <div class="emp-detalle-campo" id="vistaEmpClasificacionCampo">
                                <p class="emp-detalle-label">Clasificación</p>
                                <p class="emp-detalle-valor" id="vistaEmpClasificacion">—</p>
                            </div>
                            <div class="emp-detalle-campo emp-detalle-campo--full">
                                <p class="emp-detalle-label">Estado en el sistema</p>
                                <p class="emp-detalle-valor" id="vistaEmpEstado">—</p>
                            </div>
                        </div>
                    </div>

                    <div class="emp-detalle-seccion" id="vistaEmpJerarquiaSeccion">
                        <div class="emp-detalle-seccion-titulo">
                            <svg fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg>
                            Jerarquía y asignación
                        </div>
                        <div class="emp-detalle-grid" id="vistaEmpJerarquiaContenido"></div>
                    </div>

                    <div id="vistaEmpBajaBox" class="hidden emp-detalle-seccion">
                        <div class="emp-detalle-seccion-titulo" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">
                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            Registro de baja
                        </div>
                        <div class="emp-detalle-grid">
                            <div class="emp-detalle-campo">
                                <p class="emp-detalle-label">Fecha de baja</p>
                                <p class="emp-detalle-valor text-red-700" id="vistaEmpFechaBaja">—</p>
                            </div>
                            <div class="emp-detalle-campo">
                                <p class="emp-detalle-label">Motivo</p>
                                <p class="emp-detalle-valor text-red-700" id="vistaEmpMotivoBaja">—</p>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="formEditarEmpleado" class="hidden space-y-4">
                    <input type="hidden" id="editEmpIdOriginal">
                    <div class="emp-detalle-editar-nota">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <p>Actualiza los datos del colaborador. La <strong>jerarquía</strong> (supervisor o gerente) se asigna con el botón de enlace en la tabla de empleados.</p>
                    </div>
                    <div class="emp-detalle-seccion">
                        <div class="emp-detalle-seccion-titulo">
                            <svg fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                            Datos personales
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Nombre(s) *</label>
                                <input type="text" id="editEmpFirstName" required class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Apellido paterno *</label>
                                <input type="text" id="editEmpLastName" required class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Apellido materno</label>
                                <input type="text" id="editEmpSurName" class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Opcional">
                            </div>
                        </div>
                    </div>
                    <div class="emp-detalle-seccion">
                        <div class="emp-detalle-seccion-titulo">
                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/><path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/></svg>
                            Información laboral
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">ID empleado *</label>
                                <input type="number" id="editEmpNuevoId" required min="1" class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <p id="editEmpIdNota" class="hidden text-xs text-amber-600 mt-1">El ID del usuario RH (0) no se puede modificar.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Departamento *</label>
                                <select id="editEmpDepartment" required class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                    <option value="">Seleccionar departamento</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Puesto *</label>
                                <select id="editEmpRol" required class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" onchange="actualizarCamposClasificacionSegunPuesto('editar')">
                                    <option value="trabajador">Trabajador</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="gerente">Gerente</option>
                                </select>
                            </div>
                            <div id="editEmpClasificacionWrap">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Clasificación</label>
                                <select id="editEmpClasificacion" class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                    <option value="">Sin asignar</option>
                                    <option value="staff">Staff</option>
                                    <option value="operativo">Operativo</option>
                                    <option value="inspector">Inspector</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Solo aplica a trabajadores.</p>
                            </div>
                        </div>
                    </div>
                </form>

                <div id="mensajeDetalleEmp" class="hidden mt-4 p-3 rounded-lg text-sm"></div>

                <div class="flex justify-end gap-2 pt-4 mt-4 border-t border-gray-100">
                    <button type="button" id="btnCancelarEditarEmp" onclick="cancelarEdicionEmpleado()" class="hidden px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="button" id="btnGuardarEditarEmp" onclick="guardarEdicionEmpleado()" class="hidden px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                        Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cambiar Contraseña -->
    <div id="modalCambiarPassword" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold ntn-blue">Cambiar Contraseña</h3>
                    <button onclick="cerrarModalPassword()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
                
                <input type="hidden" id="passwordEmpleadoId">
                <p class="text-sm text-gray-600 mb-4">Empleado: <strong id="passwordEmpleadoNombre"></strong></p>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña *</label>
                    <input type="password" id="nuevaPassword" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Mínimo 4 caracteres">
                </div>
                
                <div id="mensajeModalPassword" class="hidden mb-4 p-3 rounded-lg text-sm"></div>
                
                <div class="flex justify-end gap-3">
                    <button onclick="cerrarModalPassword()" class="px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button onclick="confirmarCambioPassword()" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                        Cambiar Contraseña
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Permanentemente -->
    <div id="modalEliminarEmpleado" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Eliminar Permanentemente</h3>
                        <p class="text-sm text-red-600 font-semibold">¡ADVERTENCIA! Esta acción NO se puede deshacer</p>
                    </div>
                </div>
                
                <input type="hidden" id="eliminarEmpleadoId">
                <div class="bg-red-50 border-l-4 border-red-500 p-3 mb-4">
                    <p class="text-sm text-red-800">¿Estás seguro de eliminar permanentemente a <strong id="eliminarNombreEmpleado"></strong>?</p>
                    <p class="text-xs text-red-700 mt-2">Se eliminarán todos sus datos, reportes y jerarquías del sistema.</p>
                </div>
                
                <div id="mensajeModalEliminar" class="hidden mb-4 p-3 rounded-lg text-sm"></div>
                
                <div class="flex justify-end gap-3">
                    <button onclick="cerrarModalEliminar()" class="px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button onclick="confirmarEliminarEmpleado()" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                        Sí, Eliminar Permanentemente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Baja -->
    <div id="modalConfirmarBaja" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Confirmar Baja</h3>
                        <p class="text-sm text-gray-500">Esta acción marcará al empleado como inactivo</p>
                    </div>
                </div>
                
                <input type="hidden" id="bajaEmpleadoId">
                <p class="text-sm text-gray-700 mb-4">¿Estás seguro de dar de baja a <strong id="bajaNombreEmpleado"></strong>?</p>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de baja (opcional)</label>
                    <textarea id="bajaMotivo" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="Ej: Renuncia, despido, fin de contrato, etc."></textarea>
                </div>
                
                <div id="mensajeModalBaja" class="hidden mb-4 p-3 rounded-lg text-sm"></div>
                
                <div class="flex justify-end gap-3">
                    <button onclick="cerrarModalBaja()" class="px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button onclick="confirmarBajaEmpleado()" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                        Dar de Baja
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Reporte -->
    <div id="modalDetalleReporte" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,0.7)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[92vh] overflow-hidden flex flex-col">

            <!-- Header alineado con top-header -->
            <div class="shell-modal-header flex items-center justify-between px-8 py-5">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:rgba(255,255,255,0.1)">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest" style="color:rgba(255,255,255,0.5)">Reporte Kaizen</p>
                        <h3 class="text-lg font-bold text-white leading-tight mt-0.5" id="reporteIdHeader">—</h3>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div id="estadoBadgeHeader"></div>
                    <button onclick="cerrarModalDetalle()" class="text-white rounded-xl p-2 transition" style="background:rgba(255,255,255,0.1)" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Contenido -->
            <div class="flex-1 overflow-y-auto bg-gray-50 p-8">
                <div id="contenidoDetalle">
                    <p class="text-center text-gray-400 py-12">Cargando...</p>
                </div>
            </div>

            <!-- Footer -->
            <div id="botonesAccion" class="hidden px-8 py-4 border-t border-gray-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-white">
                <div class="flex items-center gap-2 min-w-0">
                    <button type="button" id="btnEliminarReporte" onclick="eliminarReportePermanente()" class="rh-btn-icon-only rh-btn-icon-only--danger flex-shrink-0" title="Eliminar permanentemente" aria-label="Eliminar permanentemente">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <p class="text-xs text-red-500 leading-snug">Administración RH · Se borra en todos los perfiles · No se puede deshacer</p>
                </div>
                <div id="accionesRhWrap" class="hidden flex flex-col sm:flex-row sm:items-center gap-3 lg:ml-auto">
                    <p class="text-xs text-gray-500" id="accionRhMensaje"></p>
                    <div class="flex gap-3 sm:ml-auto">
                        <button type="button" id="btnRechazarReporte" onclick="rechazarReporte()" class="px-5 py-2.5 text-sm font-semibold text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent">
                            Rechazar
                        </button>
                        <button type="button" id="btnAceptarReporte" onclick="aceptarReporte()" class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl transition disabled:opacity-40 disabled:cursor-not-allowed" style="background:#0066CC" onmouseover="if(!this.disabled)this.style.background='#0052a3'" onmouseout="if(!this.disabled)this.style.background='#0066CC'">
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Asignar Jerarquía -->
    <div id="modalAsignarJerarquia" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold ntn-blue">Asignar Jerarquía</h3>
                    <button onclick="cerrarModalJerarquia()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
                
                <input type="hidden" id="modalEmpleadoId">
                <p class="text-sm text-gray-600 mb-4">Empleado: <strong id="modalEmpleadoNombre"></strong></p>
                
                <!-- Mensaje informativo del rol -->
                <div id="mensajeInfoRol" class="mb-4 hidden"></div>
                
                <div class="space-y-5">
                    <!-- Supervisor (Select único) -->
                    <div id="containerSupervisor">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Supervisor Directo</label>
                        <div class="relative mb-2">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                            <input type="text" id="buscarSupervisorJer" placeholder="Buscar supervisor por nombre, ID o departamento..."
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
                                onkeyup="filtrarListaAsignacionJerarquia('supervisor')">
                        </div>
                        <div id="listaSupervisores" class="space-y-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>
                    
                    <!-- Gerentes (Checkboxes múltiples) -->
                    <div id="containerGerente">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Gerentes</label>
                        <div class="relative mb-2">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                            <input type="text" id="buscarGerenteJer" placeholder="Buscar gerente por nombre, ID o departamento..."
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
                                onkeyup="filtrarListaAsignacionJerarquia('gerente')">
                        </div>
                        <div id="listaGerentes" class="space-y-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>
                </div>
                
                <div id="mensajeModalJerarquia" class="hidden mt-4 p-3 rounded-lg text-sm"></div>
                
                <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-gray-200">
                    <button onclick="cerrarModalJerarquia()" class="px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button onclick="guardarJerarquia()" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo kaizen_asset_src('../assets/puesto-empleado.js', __DIR__ . '/../assets/puesto-empleado.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/plazo-revision.js', __DIR__ . '/../assets/plazo-revision.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/evaluacion-reporte.js', __DIR__ . '/../assets/evaluacion-reporte.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('dashboard-fixed.js', __DIR__ . '/dashboard-fixed.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/logout-animation.js', __DIR__ . '/../assets/logout-animation.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/session-inactividad.js', __DIR__ . '/../assets/session-inactividad.js'); ?>"></script>
</body>
</html>

